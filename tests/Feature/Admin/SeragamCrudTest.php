<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Seragam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeragamCrudTest extends TestCase
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

    public function test_can_view_seragams_index_page(): void
    {
        Seragam::factory()->count(3)->create();

        $response = $this->get(route('admin.seragam.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/SeragamPage')
            ->has('seragams', 3)
        );
    }

    public function test_can_create_seragam_without_image(): void
    {
        $data = [
            'name' => 'Seragam Putih-Abu',
            'slug' => 'seragam-putih-abu',
            'category' => 'Harian',
            'description' => 'Seragam harian putih abu-abu untuk siswa',
            'usage_days' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'rules' => 'Seragam harus bersih dan rapih. Tidak boleh robek.',
            'sort_order' => 1,
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Seragam berhasil ditambahkan');
        $this->assertDatabaseHas('seragams', [
            'name' => 'Seragam Putih-Abu',
            'category' => 'Harian',
        ]);
    }

    public function test_can_create_seragam_with_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('seragam.jpg');
        $data = [
            'name' => 'Seragam Batik',
            'slug' => 'seragam-batik',
            'category' => 'Khusus',
            'description' => 'Seragam batik sekolah',
            'usage_days' => ['Sabtu'],
            'image' => $file,
            'sort_order' => 2,
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('seragams', ['name' => 'Seragam Batik']);
        
        $seragam = Seragam::where('name', 'Seragam Batik')->first();
        $this->assertTrue($seragam->hasMedia('images'));
        $media = $seragam->getFirstMedia('images');
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_validation_requires_name_and_slug(): void
    {
        $data = [
            'name' => '',
            'slug' => '',
            'category' => '',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertSessionHasErrors(['name', 'slug', 'category']);
    }

    public function test_slug_must_be_unique(): void
    {
        Seragam::factory()->create(['slug' => 'seragam-putih-abu']);

        $data = [
            'name' => 'Seragam Putih-Abu Duplicate',
            'slug' => 'seragam-putih-abu',
            'category' => 'Harian',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_can_update_seragam(): void
    {
        $seragam = Seragam::factory()->create([
            'name' => 'Old Seragam',
            'slug' => 'old-seragam',
            'category' => 'Harian',
        ]);

        $data = [
            'name' => 'Updated Seragam',
            'slug' => 'updated-seragam',
            'category' => 'Khusus',
            'description' => 'Updated description',
            'usage_days' => ['Senin'],
            'rules' => 'Updated rules',
            'sort_order' => 5,
            'is_active' => false,
        ];

        $response = $this->put(route('admin.seragam.update', $seragam), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Seragam berhasil diperbarui');
        $this->assertDatabaseHas('seragams', [
            'id' => $seragam->id,
            'name' => 'Updated Seragam',
            'category' => 'Khusus',
        ]);
    }

    public function test_can_update_seragam_image(): void
    {
        Storage::fake('public');

        // Create with initial image
        $seragam = Seragam::factory()->create();
        $oldFile = \Illuminate\Http\UploadedFile::fake()->image('old.jpg');
        $seragam->addMedia($oldFile)->toMediaCollection('images');
        $oldMedia = $seragam->getFirstMedia('images');

        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new-seragam.jpg');

        $data = [
            'name' => 'Updated Seragam',
            'slug' => 'updated-seragam',
            'category' => 'Harian',
            'description' => 'Deskripsi',
            'usage_days' => ['Senin'],
            'image' => $newFile,
            'is_active' => true,
            '_method' => 'PUT'
        ];

        $response = $this->post(route('admin.seragam.update', $seragam), $data);

        $response->assertRedirect();
        
        $seragam->refresh();
        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
        
        $newMedia = $seragam->getFirstMedia('images');
        $this->assertEquals('new-seragam.jpg', $newMedia->file_name);
        Storage::disk('public')->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_can_delete_seragam(): void
    {
        $seragam = Seragam::factory()->create();

        $response = $this->delete(route('admin.seragam.destroy', $seragam));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Seragam berhasil dihapus');
        $this->assertDatabaseMissing('seragams', ['id' => $seragam->id]);
    }

    public function test_deleting_seragam_removes_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('seragam.jpg');
        $seragam = Seragam::factory()->create();
        $seragam->addMedia($file)->toMediaCollection('images');
        $media = $seragam->getFirstMedia('images');
        $path = $media->getPathRelativeToRoot();

        $this->delete(route('admin.seragam.destroy', $seragam));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_name_max_length_validation(): void
    {
        $data = [
            'name' => str_repeat('A', 256),
            'slug' => 'seragam-test',
            'category' => 'Harian',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_slug_max_length_validation(): void
    {
        $data = [
            'name' => 'Test Seragam',
            'slug' => str_repeat('a', 256),
            'category' => 'Harian',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_image_must_be_valid_image(): void
    {
        $data = [
            'name' => 'Test Seragam',
            'slug' => 'test-seragam',
            'category' => 'Harian',
            'image' => 'not-an-image',
            'is_active' => true,
        ];

        $response = $this->post(route('admin.seragam.store'), $data);

        $response->assertSessionHasErrors(['image']);
    }

    public function test_can_create_inactive_seragam(): void
    {
        $data = [
            'name' => 'Inactive Seragam',
            'slug' => 'inactive-seragam',
            'category' => 'Khusus',
            'description' => 'Deskripsi',
            'is_active' => false,
        ];

        $this->post(route('admin.seragam.store'), $data);

        $this->assertDatabaseHas('seragams', [
            'name' => 'Inactive Seragam',
            'is_active' => false,
        ]);
    }

    public function test_can_set_sort_order(): void
    {
        $data = [
            'name' => 'First Seragam',
            'slug' => 'first-seragam',
            'category' => 'Harian',
            'description' => 'Deskripsi',
            'sort_order' => 10,
            'is_active' => true,
        ];

        $this->post(route('admin.seragam.store'), $data);

        $this->assertDatabaseHas('seragams', [
            'name' => 'First Seragam',
            'sort_order' => 10,
        ]);
    }

    public function test_usage_days_stored_as_json(): void
    {
        $data = [
            'name' => 'Seragam Test',
            'slug' => 'seragam-test',
            'category' => 'Harian',
            'usage_days' => ['Senin', 'Selasa', 'Rabu'],
            'is_active' => true,
        ];

        $this->post(route('admin.seragam.store'), $data);

        $seragam = Seragam::where('slug', 'seragam-test')->first();
        $this->assertEquals(['Senin', 'Selasa', 'Rabu'], $seragam->usage_days);
    }
}
