<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use ProcessMaker\Models\User;
use Tests\TestCase;

class DeployProcessControllerTest extends TestCase
{
    use RefreshDatabase;

    private const DEPLOY_ENDPOINT = '/api/1.0/package-skeleton/deploy-process';

    private const GET_ENDPOINT = '/api/1.0/package-skeleton/process-bpmn';

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'process-sync-user',
            'is_administrator' => true,
        ]);
    }

    public function testDeployProcessResolvesNamedReferences(): void
    {
        $screen = Screen::factory()->create(['title' => 'sample-screen-ref']);
        $script = Script::factory()->create(['title' => 'sample-script-ref']);

        $this->actingAs($this->user, 'api')
            ->postJson(self::DEPLOY_ENDPOINT, [
                'name' => 'sample-process',
                'bpmn' => $this->sampleBpmn(
                    '${screen:sample-screen-ref}',
                    '${script:sample-script-ref}'
                ),
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'created',
                'name' => 'sample-process',
            ]);

        $process = Process::where('name', 'sample-process')->firstOrFail();
        $this->assertStringContainsString('pm:screenRef="' . $screen->id . '"', $process->bpmn);
        $this->assertStringContainsString('pm:scriptRef="' . $script->id . '"', $process->bpmn);
    }

    public function testGetProcessBpmnRestoresNamedReferences(): void
    {
        Screen::factory()->create(['title' => 'restore-screen']);
        Script::factory()->create(['title' => 'restore-script']);

        $this->actingAs($this->user, 'api')
            ->postJson(self::DEPLOY_ENDPOINT, [
                'name' => 'restore-process',
                'bpmn' => $this->sampleBpmn(
                    '${screen:restore-screen}',
                    '${script:restore-script}'
                ),
            ])
            ->assertOk();

        $response = $this->actingAs($this->user, 'api')
            ->getJson(self::GET_ENDPOINT . '?name=restore-process');

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertStringContainsString('${screen:restore-screen}', $response->json('bpmn'));
        $this->assertStringContainsString('${script:restore-script}', $response->json('bpmn'));
    }

    private function sampleBpmn(string $screenReference, string $scriptReference): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions
  xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1">
  <bpmn:process id="Process_1" name="Test Process">
    <bpmn:task id="Task_1" pm:screenRef="{$screenReference}" />
    <bpmn:scriptTask id="ScriptTask_1" pm:scriptRef="{$scriptReference}" />
  </bpmn:process>
</bpmn:definitions>
XML;
    }
}
