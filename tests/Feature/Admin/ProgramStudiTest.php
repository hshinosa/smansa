<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ProgramStudiSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgramStudiTest extends TestCase
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

    public function test_can_view_program_studi_page()
    {
        $response = $this->get(route('admin.program-studi.index'));
        $response->assertStatus(200);
    }

    public function test_can_update_program_studi_content()
    {
        $heroImage = \Illuminate\Http\UploadedFile::fake()->image('hero.jpg');

        $data = [
            'program_name' => 'mipa',
            'hero' => [
                'title' => 'MIPA Hero',
                'description' => 'Desc',
                'background_image' => $heroImage
            ]
        ];

        $response = $this->post(route('admin.program-studi.update_all'), $data);

        $response->assertRedirect();
        
        $setting = ProgramStudiSetting::where('program_name', 'mipa')
            ->where('section_key', 'hero')
            ->first();
            
        $this->assertNotNull($setting);
        $this->assertTrue($setting->hasMedia('hero_background_image'));
    }
}
