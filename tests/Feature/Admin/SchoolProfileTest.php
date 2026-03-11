<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\SchoolProfileSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
    }

    public function test_can_view_profile_page()
    {
        $response = $this->get(route('admin.school-profile.index'));
        $response->assertStatus(200);
    }

    public function test_can_update_profile_section_with_image()
    {
        $image = \Illuminate\Http\UploadedFile::fake()->image('history.jpg');

        $data = [
            'section' => 'history',
            'content' => [
                'title' => 'Sejarah',
                'description_html' => '<p>Desc</p>',
                'image' => $image,
            ]
        ];

        $response = $this->post(route('admin.school-profile.update'), $data);

        $response->assertRedirect();
        
        $setting = SchoolProfileSetting::where('section_key', 'history')->first();
        $this->assertTrue($setting->hasMedia('history'));
    }
}
