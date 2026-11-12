<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use Tests\TestCase;

class DeployScreenControllerTest extends TestCase
{
    use RefreshDatabase;

    private const API_ENDPOINT = '/api/1.0/package-skeleton/deploy-screen';

    private const GET_ENDPOINT = '/api/1.0/package-skeleton/screen-yaml';

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'screen-sync-user',
            'is_administrator' => true,
        ]);
    }

    public function testDeployScreenRequiresAuthentication(): void
    {
        $this->postJson(self::API_ENDPOINT, [
            'yml_content' => "config: []\n",
            'name' => 'sample-screen',
        ])->assertStatus(401);
    }

    public function testDeployScreenCreatesFromYaml(): void
    {
        $yaml = <<<'YAML'
config:
  - component: FormBlank
    items: []
computed: []
watchers: []
custom_css: ''
YAML;

        $this->actingAs($this->user, 'api')
            ->postJson(self::API_ENDPOINT, [
                'yml_content' => $yaml,
                'name' => 'sample-screen',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => 'sample-screen',
            ]);

        $this->assertNotNull(Screen::where('title', 'sample-screen')->first());
    }

    public function testGetScreenYamlReturnsYamlForPublishedScreen(): void
    {
        $yaml = "config: []\n";
        $this->actingAs($this->user, 'api')
            ->postJson(self::API_ENDPOINT, [
                'yml_content' => $yaml,
                'name' => 'yaml-pull-screen',
            ])
            ->assertOk();

        $response = $this->actingAs($this->user, 'api')
            ->get(self::GET_ENDPOINT . '?name=yaml-pull-screen');

        $response->assertOk();
        $this->assertStringContainsString('yaml', (string) $response->headers->get('Content-Type'));
    }
}
