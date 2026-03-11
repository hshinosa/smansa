<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\AiSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_can_view_ai_settings_page()
    {
        AiSetting::set('groq_api_keys', json_encode(['gsk-test-key']), 'json');
        AiSetting::set('groq_chat_model', 'llama-3.3-70b-versatile');

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ai-settings.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/AiSettings/Index')
                ->has('settings', 2)
            );
    }

    public function test_can_update_ai_settings()
    {
        AiSetting::set('groq_chat_model', 'llama-3.3-70b-versatile');

        $data = [
            'settings' => [
                [
                    'key' => 'groq_chat_model',
                    'value' => 'mixtral-8x7b-32768',
                ],
                [
                    'key' => 'groq_api_keys',
                    'value' => json_encode(['gsk-new-key-123']),
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ai-settings.update'), $data);

        $response->assertRedirect();
        $this->assertEquals('mixtral-8x7b-32768', AiSetting::get('groq_chat_model'));
    }

    public function test_validates_ai_settings_update()
    {
        $data = [
            'settings' => 'not-an-array'
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.ai-settings.update'), $data);

        $response->assertSessionHasErrors('settings');
    }
}
