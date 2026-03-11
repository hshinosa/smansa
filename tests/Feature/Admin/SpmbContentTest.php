<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\SpmbSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpmbContentTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = Admin::factory()->create();
    }

    public function test_can_view_spmb_content_page()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.spmb.index'));

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/SpmbContentPage')
                ->has('currentSettings')
            );
    }

    public function test_can_update_pengaturan_umum()
    {
        $data = [
            'pengaturan_umum' => [
                'title' => 'Pendaftaran 2025',
                'description_html' => '<p>Deskripsi</p>',
                'is_registration_open' => 'true',
                'registration_year' => '2025/2026',
                'banner_image_file' => UploadedFile::fake()->image('banner.jpg')
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.spmb.update_all'), $data);

        $response->assertRedirect();
        
        $setting = SpmbSetting::where('section_key', 'pengaturan_umum')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('Pendaftaran 2025', $setting->content['title']);
        $this->assertTrue($setting->getMedia('banner_image')->count() > 0);
    }

    public function test_can_update_jalur_pendaftaran()
    {
        $data = [
            'jalur_pendaftaran' => [
                'items' => [
                    [
                        'label' => 'Jalur Prestasi',
                        'description' => 'Untuk siswa berprestasi',
                        'quota' => '30%',
                        'requirements' => ['Sertifikat', 'Rapor']
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.spmb.update_all'), $data);

        $response->assertRedirect();
        
        $setting = SpmbSetting::where('section_key', 'jalur_pendaftaran')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('Jalur Prestasi', $setting->content['items'][0]['label']);
        $this->assertCount(2, $setting->content['items'][0]['requirements']);
    }

    public function test_validates_required_fields_in_sections()
    {
        // Missing required fields for pengaturan_umum
        $data = [
            'pengaturan_umum' => [
                'title' => '', // Required
                'description_html' => 'Desc',
                'registration_open' => 1,
                'registration_year' => '2025'
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.spmb.update_all'), $data);

        $response->assertSessionHasErrors('pengaturan_umum.title');
    }

    public function test_can_update_jadwal_penting()
    {
        $data = [
            'jadwal_penting' => [
                'items' => [
                    [
                        'title' => 'Pendaftaran Ulang',
                        'date' => '2025-07-10',
                        'description' => 'Wajib hadir'
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.spmb.update_all'), $data);

        $response->assertRedirect();
        $setting = SpmbSetting::where('section_key', 'jadwal_penting')->first();
        $this->assertEquals('Pendaftaran Ulang', $setting->content['items'][0]['title']);
    }
}