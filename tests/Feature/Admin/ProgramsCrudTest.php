<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgramsCrudTest extends TestCase
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

    public function test_can_view_programs_index_page(): void
    {
        Program::factory()->count(3)->create();

        $response = $this->get(route('admin.programs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Programs/Index')
            ->has('programs', 3)
        );
    }

    public function test_can_create_program_without_image(): void
    {
        $data = [
            'title' => 'Program Unggulan',
            'category' => 'Akademik',
            'icon_name' => 'trophy',
            'description' => 'Program unggulan sekolah untuk meningkatkan kualitas pendidikan',
            'color_class' => 'bg-blue-500',
            'is_featured' => true,
            'sort_order' => 1,
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Program berhasil ditambahkan');
        $this->assertDatabaseHas('programs', [
            'title' => 'Program Unggulan',
            'category' => 'Akademik',
        ]);
    }

    public function test_can_create_program_with_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('program.jpg');
        $data = [
            'title' => 'Program Kreatif',
            'category' => 'Non Akademik',
            'description' => 'Program kreatif untuk mengembangkan bakat siswa',
            'image' => $file,
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('programs', ['title' => 'Program Kreatif']);
        
        $program = Program::where('title', 'Program Kreatif')->first();
        $this->assertTrue($program->hasMedia('program_images'));
        $media = $program->getFirstMedia('program_images');
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_can_create_program_with_link(): void
    {
        $data = [
            'title' => 'Program Online',
            'category' => 'Digital',
            'description' => 'Program pembelajaran secara online',
            'link' => 'https://example.com/program',
        ];

        $this->post(route('admin.programs.store'), $data);

        $this->assertDatabaseHas('programs', [
            'title' => 'Program Online',
            'link' => 'https://example.com/program',
        ]);
    }

    public function test_validation_requires_title_category_and_description(): void
    {
        $data = [
            'title' => '',
            'category' => '',
            'description' => '',
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertSessionHasErrors(['title', 'category', 'description']);
    }

    public function test_can_update_program(): void
    {
        $program = Program::factory()->create([
            'title' => 'Old Title',
            'category' => 'Old Category',
        ]);

        $data = [
            'title' => 'Updated Title',
            'category' => 'Updated Category',
            'icon_name' => 'book',
            'description' => 'Updated description with more details',
            'color_class' => 'bg-green-500',
            'is_featured' => false,
            'sort_order' => 5,
        ];

        $response = $this->put(route('admin.programs.update', $program), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Program berhasil diperbarui');
        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'title' => 'Updated Title',
            'category' => 'Updated Category',
        ]);
    }

    public function test_can_update_program_image(): void
    {
        Storage::fake('public');

        $program = Program::factory()->create();
        $oldFile = \Illuminate\Http\UploadedFile::fake()->image('old.jpg');
        $program->addMedia($oldFile)->toMediaCollection('program_images');
        $oldMedia = $program->getFirstMedia('program_images');
        $program->update(['image_url' => $oldMedia->getUrl()]);

        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new-program.jpg');

        $data = [
            'title' => 'Program',
            'category' => 'Category',
            'description' => 'Description',
            'image' => $newFile,
        ];

        $response = $this->put(route('admin.programs.update', $program), $data);

        $response->assertRedirect();
        
        $program->refresh();
        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
        
        $newMedia = $program->getFirstMedia('program_images');
        $this->assertEquals('new-program.jpg', $newMedia->file_name);
        Storage::disk('public')->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_can_delete_program(): void
    {
        $program = Program::factory()->create();

        $response = $this->delete(route('admin.programs.destroy', $program));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Program berhasil dihapus');
        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_deleting_program_removes_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('program.jpg');
        $program = Program::factory()->create();
        $program->addMedia($file)->toMediaCollection('program_images');
        $media = $program->getFirstMedia('program_images');
        $path = $media->getPathRelativeToRoot();

        $this->delete(route('admin.programs.destroy', $program));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_external_image_does_not_remove_file(): void
    {
        $program = Program::factory()->create([
            'image_url' => 'https://external.com/image.jpg',
        ]);

        $this->delete(route('admin.programs.destroy', $program));

        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
        // No file should be deleted as it's external
    }

    public function test_title_max_length_validation(): void
    {
        $data = [
            'title' => str_repeat('A', 256), // exceeds max of 255
            'category' => 'Category',
            'description' => 'Description',
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertSessionHasErrors(['title']);
    }

    public function test_category_max_length_validation(): void
    {
        $data = [
            'title' => 'Title',
            'category' => str_repeat('A', 256), // exceeds max of 255
            'description' => 'Description',
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertSessionHasErrors(['category']);
    }

    public function test_link_max_length_validation(): void
    {
        $data = [
            'title' => 'Title',
            'category' => 'Category',
            'description' => 'Description',
            'link' => str_repeat('A', 256), // exceeds max of 255
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertSessionHasErrors(['link']);
    }

    public function test_can_create_featured_program(): void
    {
        $data = [
            'title' => 'Featured Program',
            'category' => 'Unggulan',
            'description' => 'Program unggulan sekolah',
            'is_featured' => true,
        ];

        $this->post(route('admin.programs.store'), $data);

        $this->assertDatabaseHas('programs', [
            'title' => 'Featured Program',
            'is_featured' => true,
        ]);
    }

    public function test_can_set_sort_order(): void
    {
        $data = [
            'title' => 'Ordered Program',
            'category' => 'Category',
            'description' => 'Description',
            'sort_order' => 10,
        ];

        $this->post(route('admin.programs.store'), $data);

        $this->assertDatabaseHas('programs', [
            'title' => 'Ordered Program',
            'sort_order' => 10,
        ]);
    }

    public function test_image_max_size_validation(): void
    {
        $largeFile = \Illuminate\Http\UploadedFile::fake()->image('large.jpg')->size(12000); // 12MB, exceeds 10MB limit

        $data = [
            'title' => 'Program',
            'category' => 'Category',
            'description' => 'Description',
            'image' => $largeFile,
        ];

        $response = $this->post(route('admin.programs.store'), $data);

        $response->assertSessionHasErrors(['image']);
    }

    public function test_can_update_program_with_external_image_url(): void
    {
        $program = Program::factory()->create();

        $data = [
            'title' => 'Updated Program',
            'category' => 'Category',
            'description' => 'Description',
            'image_url' => 'https://external.com/image.jpg',
        ];

        $this->put(route('admin.programs.update', $program), $data);

        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'image_url' => 'https://external.com/image.jpg',
        ]);
    }
}
