<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\CurriculumSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CurriculumTest extends TestCase
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

    public function test_can_view_curriculum_page()
    {
        $response = $this->get(route('admin.curriculum.index'));
        $response->assertStatus(200);
    }

    public function test_can_update_section_with_media()
    {
        $heroImage = \Illuminate\Http\UploadedFile::fake()->image('hero.jpg');

        $data = [
            'section' => 'hero',
            'content' => [
                'title' => 'Hero Title',
                'subtitle' => 'Hero Sub',
            ],
            'media' => $heroImage,
        ];

        $response = $this->post(route('admin.curriculum.update'), $data);

        $response->assertRedirect();
        
        $setting = CurriculumSetting::where('section_key', 'hero')->first();
        $this->assertTrue($setting->hasMedia('hero_bg'));
    }

    public function test_can_update_section_without_media()
    {
        $data = [
            'section' => 'problem',
            'content' => [
                'title' => 'PISA',
                'description' => 'Schooling But Not Learning',
                'stats' => [
                    ['label' => 'Membaca', 'lots' => '99,2%', 'hots' => '0,8%']
                ],
            ],
        ];

        $response = $this->post(route('admin.curriculum.update'), $data);

        $response->assertRedirect();

        $setting = CurriculumSetting::where('section_key', 'problem')->first();
        $this->assertEquals('PISA', $setting->content['title']);
    }
}
