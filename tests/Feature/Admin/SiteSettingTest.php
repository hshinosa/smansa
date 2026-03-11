<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user
        $this->adminUser = Admin::factory()->create();
    }

    public function test_admin_can_view_site_settings_page()
    {
        $response = $this->actingAs($this->adminUser, 'admin')->get(route('admin.site-settings.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SiteSettings/Index')
            ->has('sections')
        );
    }

    public function test_admin_can_update_general_settings()
    {
        Storage::fake('public');

        $data = [
            'section' => 'general',
            'content' => [
                'site_name' => 'Updated School Name',
                'site_description' => 'New Description',
            ]
        ];

        $response = $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.site-settings.update'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('site_settings', [
            'section_key' => 'general',
        ]);

        $setting = SiteSetting::where('section_key', 'general')->first();
        $this->assertEquals('Updated School Name', $setting->content['site_name']);
    }

    public function test_admin_can_update_social_media_settings()
    {
        $data = [
            'section' => 'social_media',
            'content' => [
                'facebook' => 'https://fb.com/school',
                'instagram' => 'https://instagram.com/school',
            ]
        ];

        $response = $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.site-settings.update'), $data);

        $response->assertRedirect();
        
        $setting = SiteSetting::where('section_key', 'social_media')->first();
        $this->assertEquals('https://fb.com/school', $setting->content['facebook']);
    }

    public function test_admin_can_upload_hero_image_in_settings()
    {
        Storage::fake('public');
        
        // Ensure setting exists first
        SiteSetting::create(['section_key' => 'hero_home', 'content' => []]);

        $file = UploadedFile::fake()->image('hero.jpg');

        $data = [
            'section' => 'hero_home',
            'content' => [
                'title' => 'Hero Title',
                'image_file' => $file,
            ]
        ];

        $response = $this->actingAs($this->adminUser, 'admin')
            ->post(route('admin.site-settings.update'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $setting = SiteSetting::where('section_key', 'hero_home')->first();
        $this->assertTrue($setting->hasMedia('hero_home'));
    }
}
