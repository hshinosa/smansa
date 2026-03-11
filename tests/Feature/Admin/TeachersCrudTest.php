<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeachersCrudTest extends TestCase
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

    public function test_can_view_teachers_index_page(): void
    {
        Teacher::factory()->count(3)->create();

        $response = $this->get(route('admin.teachers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Teachers/Index')
            ->has('teachers', 3)
        );
    }

    public function test_can_create_teacher_without_image(): void
    {
        $data = [
            'name' => 'Budi Santoso',
            'type' => 'guru',
            'position' => 'Guru Matematika',
            'department' => 'MIPA',
            'nip' => '198501012010011001',
            'email' => 'budi@sekolah.sch.id',
            'phone' => '081234567890',
            'bio' => 'Guru berpengalaman 10 tahun',
            'sort_order' => 1,
            'is_active' => true,
        ];

        $response = $this->post(route('admin.teachers.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Data Guru/Staff berhasil ditambahkan');
        $this->assertDatabaseHas('teachers', [
            'name' => 'Budi Santoso',
            'type' => 'guru',
            'position' => 'Guru Matematika',
        ]);
    }

    public function test_can_create_teacher_with_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('teacher.jpg');
        $data = [
            'name' => 'Siti Rahayu',
            'type' => 'guru',
            'position' => 'Guru Bahasa Indonesia',
            'image' => $file,
            'sort_order' => 2,
            'is_active' => true,
        ];

        $response = $this->post(route('admin.teachers.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('teachers', ['name' => 'Siti Rahayu']);
        
        $teacher = Teacher::where('name', 'Siti Rahayu')->first();
        $this->assertTrue($teacher->hasMedia('photos'));
        $media = $teacher->getFirstMedia('photos');
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_validation_requires_name_and_position(): void
    {
        $data = [
            'name' => '',
            'type' => 'guru',
            'position' => '',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.teachers.store'), $data);

        $response->assertSessionHasErrors(['name', 'position']);
    }

    public function test_type_must_be_valid(): void
    {
        $data = [
            'name' => 'Test Teacher',
            'type' => 'invalid_type',
            'position' => 'Guru',
        ];

        $response = $this->post(route('admin.teachers.store'), $data);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_can_update_teacher(): void
    {
        $teacher = Teacher::factory()->create([
            'name' => 'Old Name',
            'position' => 'Old Position',
        ]);

        $data = [
            'name' => 'Updated Name',
            'type' => 'guru',
            'position' => 'Updated Position',
            'department' => 'MIPA',
            'is_active' => true,
        ];

        $response = $this->put(route('admin.teachers.update', $teacher), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Data Guru/Staff berhasil diperbarui');
        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'Updated Name',
            'position' => 'Updated Position',
        ]);
    }

    public function test_can_update_teacher_image(): void
    {
        Storage::fake('public');

        // Create teacher with initial image
        $teacher = Teacher::factory()->create();
        $oldFile = \Illuminate\Http\UploadedFile::fake()->image('old.jpg');
        $teacher->addMedia($oldFile)->toMediaCollection('photos');
        $oldMedia = $teacher->getFirstMedia('photos');

        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new_teacher.jpg');

        $data = [
            'name' => 'Teacher with new image',
            'type' => 'guru',
            'position' => 'Position',
            'image' => $newFile,
            'is_active' => true,
        ];

        $response = $this->put(route('admin.teachers.update', $teacher), $data);

        $response->assertRedirect();
        
        $teacher->refresh();
        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
        
        $newMedia = $teacher->getFirstMedia('photos');
        $this->assertEquals('new_teacher.jpg', $newMedia->file_name);
        Storage::disk('public')->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_can_delete_teacher(): void
    {
        $teacher = Teacher::factory()->create();

        $response = $this->delete(route('admin.teachers.destroy', $teacher));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Data Guru/Staff berhasil dihapus');
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }

    public function test_deleting_teacher_removes_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('teacher.jpg');
        $teacher = Teacher::factory()->create();
        $teacher->addMedia($file)->toMediaCollection('photos');
        $media = $teacher->getFirstMedia('photos');
        $path = $media->getPathRelativeToRoot();

        $this->delete(route('admin.teachers.destroy', $teacher));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_can_update_hero_settings(): void
    {
        $data = [
            'title' => 'Guru & Staff',
            'subtitle' => 'Tim pengajar profesional kami',
        ];

        $response = $this->post(route('admin.teachers.update_settings'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pengaturan hero berhasil diperbarui');
        $this->assertDatabaseHas('site_settings', ['section_key' => 'hero_teachers']);
    }
}
