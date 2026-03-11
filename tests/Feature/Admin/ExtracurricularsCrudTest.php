<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Extracurricular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtracurricularsCrudTest extends TestCase
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

    public function test_can_view_extracurriculars_index_page(): void
    {
        Extracurricular::factory()->count(3)->create();

        $response = $this->get(route('admin.extracurriculars.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Extracurriculars/Index')
            ->has('extracurriculars', 3)
        );
    }

    public function test_can_create_extracurricular_without_image(): void
    {
        $data = [
            'name' => 'Pramuka',
            'type' => 'organisasi',
            'category' => 'Kedisiplinan',
            'description' => 'Kegiatan pramuka untuk melatih kedisiplinan',
            'icon_name' => 'users',
            'schedule' => 'Jumat, 14:00 - 16:00',
            'coach_name' => 'Pak Budi',
            'sort_order' => 1,
            'is_active' => true,
        ];

        $response = $this->post(route('admin.extracurriculars.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Ekstrakurikuler berhasil ditambahkan');
        $this->assertDatabaseHas('extracurriculars', [
            'name' => 'Pramuka',
            'category' => 'Kedisiplinan',
        ]);
    }

    public function test_can_create_extracurricular_with_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('pramuka.jpg');
        $data = [
            'name' => 'Basket',
            'type' => 'ekstrakurikuler',
            'category' => 'Olahraga',
            'description' => 'Klub basket sekolah',
            'image' => $file,
            'sort_order' => 2,
            'is_active' => true,
        ];

        $response = $this->post(route('admin.extracurriculars.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('extracurriculars', ['name' => 'Basket']);
        
        $extracurricular = Extracurricular::where('name', 'Basket')->first();
        $this->assertTrue($extracurricular->hasMedia('images'));
        $media = $extracurricular->getFirstMedia('images');
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_validation_requires_name_category_and_description(): void
    {
        $data = [
            'name' => '',
            'category' => '',
            'description' => '',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.extracurriculars.store'), $data);

        $response->assertSessionHasErrors(['name', 'category', 'description']);
    }

    public function test_can_update_extracurricular(): void
    {
        $extracurricular = Extracurricular::factory()->create([
            'name' => 'Old Name',
            'type' => 'ekstrakurikuler',
            'category' => 'Old Category',
        ]);

        $data = [
            'name' => 'Updated Name',
            'type' => 'organisasi',
            'category' => 'Updated Category',
            'description' => 'Updated description',
            'icon_name' => 'trophy',
            'schedule' => 'Selasa, 15:00 - 17:00',
            'coach_name' => 'Pak Budi',
            'sort_order' => 5,
            'is_active' => false,
        ];

        // Use PUT directly as router.put doesn't send files but here we don't send files
        $response = $this->put(route('admin.extracurriculars.update', $extracurricular), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Ekstrakurikuler berhasil diperbarui');
        $this->assertDatabaseHas('extracurriculars', [
            'id' => $extracurricular->id,
            'name' => 'Updated Name',
            'category' => 'Updated Category',
        ]);
    }

    public function test_can_update_extracurricular_image(): void
    {
        Storage::fake('public');

        // Create with initial image
        $extracurricular = Extracurricular::factory()->create();
        $oldFile = \Illuminate\Http\UploadedFile::fake()->image('old.jpg');
        $extracurricular->addMedia($oldFile)->toMediaCollection('images');
        $oldMedia = $extracurricular->getFirstMedia('images');

        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new-activity.jpg');

        $data = [
            'name' => 'Activity',
            'type' => 'ekstrakurikuler',
            'category' => 'Kategori',
            'description' => 'Deskripsi',
            'image' => $newFile,
            'is_active' => true,
            '_method' => 'PUT' // FormData method spoofing
        ];

        // Use POST for file uploads with method spoofing
        $response = $this->post(route('admin.extracurriculars.update', $extracurricular), $data);

        $response->assertRedirect();
        
        $extracurricular->refresh();
        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
        
        $newMedia = $extracurricular->getFirstMedia('images');
        $this->assertEquals('new-activity.jpg', $newMedia->file_name);
        Storage::disk('public')->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_can_delete_extracurricular(): void
    {
        $extracurricular = Extracurricular::factory()->create();

        $response = $this->delete(route('admin.extracurriculars.destroy', $extracurricular));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Ekstrakurikuler berhasil dihapus');
        $this->assertDatabaseMissing('extracurriculars', ['id' => $extracurricular->id]);
    }

    public function test_deleting_extracurricular_removes_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('activity.jpg');
        $extracurricular = Extracurricular::factory()->create();
        $extracurricular->addMedia($file)->toMediaCollection('images');
        $media = $extracurricular->getFirstMedia('images');
        $path = $media->getPathRelativeToRoot();

        $this->delete(route('admin.extracurriculars.destroy', $extracurricular));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_name_max_length_validation(): void
    {
        $data = [
            'name' => str_repeat('A', 256), // exceeds max of 255
            'category' => 'Kategori',
            'description' => 'Deskripsi',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.extracurriculars.store'), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_category_max_length_validation(): void
    {
        $data = [
            'name' => 'Activity',
            'category' => str_repeat('A', 256), // exceeds max of 255
            'description' => 'Deskripsi',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.extracurriculars.store'), $data);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_can_create_inactive_extracurricular(): void
    {
        $data = [
            'name' => 'Inactive Activity',
            'type' => 'ekstrakurikuler',
            'category' => 'Non-aktif',
            'description' => 'Deskripsi',
            'is_active' => false,
        ];

        $this->post(route('admin.extracurriculars.store'), $data);

        $this->assertDatabaseHas('extracurriculars', [
            'name' => 'Inactive Activity',
            'is_active' => false,
        ]);
    }

    public function test_can_set_sort_order(): void
    {
        $data = [
            'name' => 'First Activity',
            'type' => 'organisasi',
            'category' => 'Kategori',
            'description' => 'Deskripsi',
            'sort_order' => 10,
            'is_active' => true,
        ];

        $this->post(route('admin.extracurriculars.store'), $data);

        $this->assertDatabaseHas('extracurriculars', [
            'name' => 'First Activity',
            'sort_order' => 10,
        ]);
    }
}
