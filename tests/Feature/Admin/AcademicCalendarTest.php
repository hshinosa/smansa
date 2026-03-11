<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicCalendarContent;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademicCalendarTest extends TestCase
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

    public function test_can_view_index_page()
    {
        AcademicCalendarContent::factory()->count(3)->create();

        $response = $this->get(route('admin.academic-calendar.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/AcademicCalendarContentPage')
            ->has('contents.data', 3)
        );
    }

    public function test_can_create_academic_calendar()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('calendar.jpg');

        $data = [
            'title' => 'Kalender Ganjil 2024',
            'calendar_image' => $file,
            'semester' => 1,
            'academic_year_start' => 2024,
            'sort_order' => 1,
        ];

        $response = $this->post(route('admin.academic-calendar.store'), $data);

        $response->assertRedirect(route('admin.academic-calendar.index'));
        $this->assertDatabaseHas('academic_calendar_contents', ['title' => 'Kalender Ganjil 2024']);
        
        $content = AcademicCalendarContent::where('title', 'Kalender Ganjil 2024')->first();
        $this->assertTrue($content->hasMedia('calendar_images'));
    }

    public function test_can_update_academic_calendar()
    {
        $content = AcademicCalendarContent::factory()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->image('new-calendar.jpg');

        $data = [
            'title' => 'Updated Calendar',
            'calendar_image' => $file,
            'semester' => 2,
            'academic_year_start' => 2025,
            'sort_order' => 2,
        ];

        $response = $this->put(route('admin.academic-calendar.update', $content), $data);

        $response->assertRedirect(route('admin.academic-calendar.index'));
        
        $content->refresh();
        $this->assertEquals('Updated Calendar', $content->title);
        $this->assertTrue($content->hasMedia('calendar_images'));
        
        $media = $content->getFirstMedia('calendar_images');
        $this->assertEquals('new-calendar.jpg', $media->file_name);
    }

    public function test_can_delete_academic_calendar()
    {
        $content = AcademicCalendarContent::factory()->create();

        $response = $this->delete(route('admin.academic-calendar.destroy', $content));

        $response->assertRedirect(route('admin.academic-calendar.index'));
        $this->assertDatabaseMissing('academic_calendar_contents', ['id' => $content->id]);
    }
}
