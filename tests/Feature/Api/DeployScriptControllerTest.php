<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\ScriptExecutor;
use ProcessMaker\Models\User;
use Tests\TestCase;

class DeployScriptControllerTest extends TestCase
{
    use RefreshDatabase;

    private const API_ENDPOINT = '/api/1.0/package-skeleton/deploy-script';

    private const GET_ENDPOINT = '/api/1.0/package-skeleton/script-php';

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'testuser',
            'is_administrator' => true,
        ]);
    }

    public function testDeployScriptRequiresAuthentication(): void
    {
        $this->postJson(self::API_ENDPOINT, [
            'code' => '<?php return [];',
            'name' => 'sample-sync-script',
        ])->assertStatus(401);
    }

    public function testDeployScriptValidatesCodeRequired(): void
    {
        $this->actingAs($this->user, 'api')
            ->postJson(self::API_ENDPOINT, [
                'name' => 'sample-sync-script',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['code']);
    }

    public function testDeployScriptCreatesWhenTitleDoesNotExist(): void
    {
        $this->ensureExecutor();
        $code = "<?php\nreturn ['ok' => true];\n";
        $name = 'sample-sync-script';

        $response = $this->actingAs($this->user, 'api')
            ->postJson(self::API_ENDPOINT, [
                'code' => $code,
                'name' => $name,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => $name,
            ]);

        $script = Script::where('title', $name)->first();
        $this->assertNotNull($script);
        $this->assertSame(rtrim($code), rtrim((string) $script->code));
        $this->assertSame($this->user->id, $script->run_as_user_id);
    }

    public function testDeployScriptUpdatesExistingScriptByTitle(): void
    {
        $existing = Script::factory()->create([
            'title' => 'sample-update-script',
            'code' => '<?php return ["old" => true];',
            'run_as_user_id' => $this->user->id,
        ]);

        $newCode = "<?php\nreturn ['mapped' => true];\n";

        $this->actingAs($this->user, 'api')
            ->postJson(self::API_ENDPOINT, [
                'code' => $newCode,
                'name' => 'sample-update-script',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'updated',
                'script_id' => $existing->id,
            ]);

        $existing->refresh();
        $this->assertSame(rtrim($newCode), rtrim((string) $existing->code));
    }

    public function testGetScriptPhpByNameReturnsPhpCode(): void
    {
        $code = "<?php\nreturn ['joined' => true];\n";
        $script = Script::factory()->create([
            'title' => 'sample-get-script',
            'code' => $code,
            'run_as_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->get(self::GET_ENDPOINT . '?name=sample-get-script');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertSame((string) $script->id, $response->headers->get('X-Script-Id'));
        $this->assertSame($code, $response->getContent());
    }

    public function testDeployScriptPrefersPhpNayraExecutorOnCreate(): void
    {
        ScriptExecutor::factory()->create(['language' => 'php']);
        $nayra = ScriptExecutor::factory()->create(['language' => 'php-nayra']);

        $this->actingAs($this->user, 'api')
            ->postJson(self::API_ENDPOINT, [
                'code' => '<?php return [];',
                'name' => 'new-nayra-script',
            ])
            ->assertStatus(200)
            ->assertJson(['action' => 'created']);

        $script = Script::where('title', 'new-nayra-script')->first();
        $this->assertSame($nayra->id, $script->script_executor_id);
    }

    private function ensureExecutor(): ScriptExecutor
    {
        return ScriptExecutor::query()->first() ?? ScriptExecutor::factory()->create();
    }
}
