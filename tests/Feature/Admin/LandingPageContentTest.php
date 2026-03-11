<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\LandingPageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPageContentTest extends TestCase
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

    public function test_can_view_landing_page_settings(): void
    {
        $response = $this->get(route('admin.landingpage.content.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/LandingPageContentPage')
            ->has('currentSettings')
        );
    }

    public function test_can_update_hero_section_text(): void
    {
        $data = [
            'section' => 'hero',
            'content' => [
                'title_line1' => 'Selamat Datang',
                'title_line2' => 'Di Sekolah Kami',
                'hero_text' => 'Deskripsi hero baru',
                'stats' => [
                    ['label' => 'Stat 1', 'value' => '100', 'icon_name' => 'Users']
                ]
            ]
        ];

        $response = $this->post(route('admin.landingpage.content.update_all'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('landing_page_settings', [
            'section_key' => 'hero',
        ]);

        $setting = LandingPageSetting::where('section_key' , 'hero')->first();
        $this->assertEquals('Selamat Datang', $setting->content['title_line1']);
    }

    public function test_can_update_hero_images(): void
    {
        Storage::fake('public');

        $bgFile = \Illuminate\Http\UploadedFile::fake()->image('hero-bg.jpg');
        $studentFile = \Illuminate\Http\UploadedFile::fake()->image('student.png');

        $data = [
            'section' => 'hero',
            'content' => [
                'title_line1' => 'Judul',
                'title_line2' => 'Subjudul',
                'hero_text' => 'Teks',
                'stats' => [],
            ],
            'hero' => [
                'background_image' => $bgFile,
                'student_image' => $studentFile,
            ]
        ];

        $response = $this->post(route('admin.landingpage.content.update_all'), $data);

        $response->assertRedirect();
        
        $setting = LandingPageSetting::where('section_key', 'hero')->first();
        
        $this->assertTrue($setting->hasMedia('hero_background'));
        $this->assertTrue($setting->hasMedia('hero_student'));
    }

    public function test_can_update_about_section_with_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('about.jpg');

        $data = [
            'section' => 'about_lp',
            'content' => [
                'title' => 'Tentang Kami',
                'description_html' => '<p>Deskripsi</p>',
            ],
            'about_lp' => [
                'image' => $file,
            ]
        ];

        $response = $this->post(route('admin.landingpage.content.update_all'), $data);

        $response->assertRedirect();
        
        $setting = LandingPageSetting::where('section_key', 'about_lp')->first();
        $this->assertTrue($setting->hasMedia('about_image'));
    }

    public function test_can_update_kepsek_welcome_with_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('kepsek.jpg');

        $data = [
            'section' => 'kepsek_welcome_lp',
            'content' => [
                'title' => 'Sambutan',
                'kepsek_name' => 'Nama Kepsek',
                'kepsek_title' => 'Kepala Sekolah',
                'welcome_text_html' => '<p>Halo</p>',
            ],
            'kepsek_welcome_lp' => [
                'kepsek_image' => $file,
            ]
        ];

        $response = $this->post(route('admin.landingpage.content.update_all'), $data);

        $response->assertRedirect();
        
        $setting = LandingPageSetting::where('section_key', 'kepsek_welcome_lp')->first();
        $this->assertTrue($setting->hasMedia('kepsek_image'));
    }
}
