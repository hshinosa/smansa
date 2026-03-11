<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostsCrudTest extends TestCase
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

    public function test_can_view_posts_index_page(): void
    {
        Post::factory()->count(3)->create();

        $response = $this->get(route('admin.posts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Posts/Index')
            ->has('posts', 3)
        );
    }

    public function test_can_create_draft_post(): void
    {
        $data = [
            'title' => 'Berita Penting: PPDB 2025',
            'excerpt' => 'Informasi penting tentang pendaftaran',
            'content' => '<p>Konten berita lengkap di sini...</p>',
            'category' => 'Pengumuman',
            'status' => 'draft',
            'is_featured' => false,
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertRedirect(route('admin.posts.index'));
        $response->assertSessionHas('success', 'Berita berhasil dibuat');
        $this->assertDatabaseHas('posts', [
            'title' => 'Berita Penting: PPDB 2025',
            'status' => 'draft',
        ]);
    }

    public function test_can_create_published_post(): void
    {
        $data = [
            'title' => 'Prestasi Siswa',
            'excerpt' => 'Siswa kami meraih juara',
            'content' => '<p>Siswa dari kelas IPA...</p>',
            'category' => 'Prestasi',
            'status' => 'published',
            'is_featured' => true,
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'title' => 'Prestasi Siswa',
            'status' => 'published',
            'is_featured' => true,
        ]);
        $this->assertNotNull(Post::where('title', 'Prestasi Siswa')->first()->published_at);
    }

    public function test_can_create_post_with_featured_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('featured.jpg', 1200, 600);
        $data = [
            'title' => 'Post with Image',
            'content' => '<p>Content here</p>',
            'category' => 'Umum',
            'status' => 'published',
            'featured_image' => $file,
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertRedirect();
        $post = Post::where('title', 'Post with Image')->first();
        
        // Assert Media Library attached the file
        $this->assertTrue($post->hasMedia('featured'));
        
        // Assert the file exists in the storage (Media Library path structure)
        $media = $post->getFirstMedia('featured');
        Storage::disk('public')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_validation_requires_title_content_and_category(): void
    {
        $data = [
            'title' => '',
            'content' => '',
            'category' => '',
            'status' => 'draft',
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertSessionHasErrors(['title', 'content', 'category']);
    }

    public function test_status_must_be_valid(): void
    {
        $data = [
            'title' => 'Test Post',
            'content' => 'Content',
            'category' => 'Umum',
            'status' => 'invalid',
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_can_update_post(): void
    {
        $post = Post::factory()->create([
            'title' => 'Original Title',
            'status' => 'draft',
        ]);

        $data = [
            'title' => 'Updated Title',
            'excerpt' => 'Updated excerpt',
            'content' => '<p>Updated content</p>',
            'category' => 'Updated Category',
            'status' => 'published',
            'is_featured' => true,
            'published_at' => now()->toDateString(),
        ];

        $response = $this->put(route('admin.posts.update', $post), $data);

        $response->assertRedirect(route('admin.posts.index'));
        $response->assertSessionHas('success', 'Berita berhasil diperbarui');
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'status' => 'published',
        ]);
    }

    public function test_updating_title_generates_new_slug(): void
    {
        $post = Post::factory()->create(['title' => 'Old Title', 'slug' => 'old-title-slug']);

        $data = [
            'title' => 'New Title',
            'content' => 'Content',
            'category' => 'Umum',
            'status' => 'draft',
        ];

        $this->put(route('admin.posts.update', $post), $data);

        $post->refresh();
        $this->assertNotEquals('old-title-slug', $post->slug);
        $this->assertStringContainsString('new-title', $post->slug);
    }

    public function test_can_update_post_image(): void
    {
        Storage::fake('public');

        // Create post with initial image
        $post = Post::factory()->create();
        $oldFile = \Illuminate\Http\UploadedFile::fake()->image('old.jpg');
        $post->addMedia($oldFile)->toMediaCollection('featured');
        $oldMedia = $post->getFirstMedia('featured');

        $newFile = \Illuminate\Http\UploadedFile::fake()->image('new-featured.jpg');

        $data = [
            'title' => 'Post',
            'content' => 'Content',
            'category' => 'Umum',
            'status' => 'draft',
            'featured_image' => $newFile,
        ];

        $response = $this->put(route('admin.posts.update', $post), $data);

        $response->assertRedirect();
        
        // Assert old media is gone
        $post->refresh();
        $this->assertDatabaseMissing('media', ['id' => $oldMedia->id]);
        
        // Assert new media exists
        $newMedia = $post->getFirstMedia('featured');
        $this->assertEquals('new-featured.jpg', $newMedia->file_name);
        Storage::disk('public')->assertExists($newMedia->getPathRelativeToRoot());
    }

    public function test_can_delete_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->delete(route('admin.posts.destroy', $post));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Berita berhasil dihapus');
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_deleting_post_removes_featured_image(): void
    {
        Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->image('featured.jpg');
        $post = Post::factory()->create();
        $post->addMedia($file)->toMediaCollection('featured');
        $media = $post->getFirstMedia('featured');
        $path = $media->getPathRelativeToRoot();

        $this->delete(route('admin.posts.destroy', $post));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_content_is_sanitized_when_creating(): void
    {
        $maliciousContent = '<script>alert("xss")</script><p>Safe content</p>';

        $data = [
            'title' => 'Test Post',
            'content' => $maliciousContent,
            'category' => 'Umum',
            'status' => 'draft',
        ];

        $this->post(route('admin.posts.store'), $data);

        $post = Post::where('title', 'Test Post')->first();
        $this->assertStringNotContainsString('<script>', $post->content);
        $this->assertStringContainsString('<p>Safe content</p>', $post->content);
    }

    public function test_excerpt_max_length_validation(): void
    {
        $data = [
            'title' => 'Test Post',
            'content' => 'Content',
            'category' => 'Umum',
            'status' => 'draft',
            'excerpt' => str_repeat('A', 501), // 501 chars exceeds max of 500
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertSessionHasErrors(['excerpt']);
    }

    public function test_title_max_length_validation(): void
    {
        $data = [
            'title' => str_repeat('A', 256), // 256 chars exceeds max of 255
            'content' => 'Content',
            'category' => 'Umum',
            'status' => 'draft',
        ];

        $response = $this->post(route('admin.posts.store'), $data);

        $response->assertSessionHasErrors(['title']);
    }
}
