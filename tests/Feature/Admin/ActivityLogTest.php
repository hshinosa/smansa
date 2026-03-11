<?php

namespace Tests\Feature\Admin;

use App\Helpers\ActivityLogger;
use App\Models\Admin;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
    }

    public function test_can_fetch_activity_logs()
    {
        // Generate some logs
        $this->actingAs($this->admin, 'admin');
        ActivityLogger::log('Test Action', 'Test Description');
        
        $response = $this->getJson(route('admin.api.activitylogs.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'description', 'causer', 'created_at']
                ],
                'current_page',
                'per_page'
            ]);
    }
}