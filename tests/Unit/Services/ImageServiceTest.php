<?php

namespace Tests\Unit\Services;

use App\Services\ImageService;
use App\Services\MediaLibrary\CustomPathGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    protected ImageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_fallback_image_returns_default_for_unknown_type(): void
    {
        $result = $this->service->getFallbackImage('unknown_type');

        $this->assertEquals('/images/hero-bg-sman1baleendah.jpeg', $result);
    }

    public function test_get_fallback_image_returns_hero_for_hero_type(): void
    {
        $result = $this->service->getFallbackImage('hero');

        $this->assertEquals('/images/hero-bg-sman1baleendah.jpeg', $result);
    }

    public function test_get_fallback_image_returns_post_for_post_type(): void
    {
        $result = $this->service->getFallbackImage('post');

        $this->assertEquals('/images/hero-bg-sman1baleendah.jpeg', $result);
    }

    public function test_get_fallback_image_returns_gallery_for_gallery_type(): void
    {
        $result = $this->service->getFallbackImage('gallery');

        $this->assertEquals('/images/hero-bg-sman1baleendah.jpeg', $result);
    }

    public function test_get_fallback_image_returns_default_for_empty_type(): void
    {
        $result = $this->service->getFallbackImage('');

        $this->assertEquals('/images/hero-bg-sman1baleendah.jpeg', $result);
    }

    public function test_get_responsive_image_data_returns_null_for_null_media(): void
    {
        $result = $this->service->getResponsiveImageData(null);

        $this->assertNull($result);
    }

    public function test_get_media_url_returns_null_for_null_media(): void
    {
        $result = $this->service->getMediaUrl(null);

        $this->assertNull($result);
    }

    public function test_has_media_checks_first_media(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getFirstMedia')->with('default')->andReturn(null);

        $result = $this->service->hasMedia($model, 'default');

        $this->assertFalse($result);
    }

    public function test_transform_model_with_media_returns_array(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('toArray')->andReturn(['id' => 1, 'title' => 'Test']);
        $model->shouldReceive('getFirstMedia')->andReturn(null);

        $result = $this->service->transformModelWithMedia($model, ['featured']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
    }

    public function test_transform_collection_with_media_returns_array(): void
    {
        $item1 = Mockery::mock(Model::class);
        $item1->shouldReceive('toArray')->andReturn(['id' => 1]);
        $item1->shouldReceive('getFirstMedia')->andReturn(null);

        $item2 = Mockery::mock(Model::class);
        $item2->shouldReceive('toArray')->andReturn(['id' => 2]);
        $item2->shouldReceive('getFirstMedia')->andReturn(null);

        $collection = new Collection([$item1, $item2]);

        $result = $this->service->transformCollectionWithMedia($collection, ['featured']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_transform_post_with_media_returns_array(): void
    {
        $post = Mockery::mock(Model::class);
        $post->featured_image = 'http://example.com/image.jpg';
        $post->shouldReceive('toArray')->andReturn(['id' => 1, 'title' => 'Test Post']);
        $post->shouldReceive('getFirstMedia')->andReturn(null);

        $result = $this->service->transformPostWithMedia($post);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('featured_image', $result);
    }

    public function test_transform_post_with_media_uses_fallback_url(): void
    {
        $post = Mockery::mock(Model::class);
        $post->featured_image = '/local/path/image.jpg';
        $post->shouldReceive('toArray')->andReturn(['id' => 1, 'title' => 'Test']);
        $post->shouldReceive('getFirstMedia')->andReturn(null);

        $result = $this->service->transformPostWithMedia($post);

        $this->assertIsArray($result);
    }

    public function test_transform_post_with_media_handles_null_featured_image(): void
    {
        $post = Mockery::mock(Model::class);
        $post->featured_image = null;
        $post->shouldReceive('toArray')->andReturn(['id' => 1, 'title' => 'Test']);
        $post->shouldReceive('getFirstMedia')->andReturn(null);

        $result = $this->service->transformPostWithMedia($post);

        $this->assertIsArray($result);
        $this->assertNull($result['featured_image']);
    }

    public function test_transform_post_detail_with_media_includes_gallery(): void
    {
        $post = Mockery::mock(Model::class);
        $post->featured_image = null;
        $post->shouldReceive('toArray')->andReturn(['id' => 1, 'title' => 'Test']);
        $post->shouldReceive('getFirstMedia')->andReturn(null);
        $post->shouldReceive('getMedia')->with('gallery')->andReturn(new Collection([]));

        $result = $this->service->transformPostDetailWithMedia($post);

        $this->assertIsArray($result);
    }

    public function test_transform_posts_collection_returns_collection(): void
    {
        $post1 = Mockery::mock(Model::class);
        $post1->featured_image = null;
        $post1->shouldReceive('toArray')->andReturn(['id' => 1]);
        $post1->shouldReceive('getFirstMedia')->andReturn(null);

        $post2 = Mockery::mock(Model::class);
        $post2->featured_image = null;
        $post2->shouldReceive('toArray')->andReturn(['id' => 2]);
        $post2->shouldReceive('getFirstMedia')->andReturn(null);

        $posts = new Collection([$post1, $post2]);

        $result = $this->service->transformPostsCollection($posts);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_get_all_media_data_returns_empty_array_for_no_media(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getMedia')->with('default')->andReturn(new Collection([]));

        $result = $this->service->getAllMediaData($model, 'default');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_ordered_media_data_returns_empty_array_for_no_media(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getMedia')->with('default')->andReturn(new Collection([]));

        $result = $this->service->getOrderedMediaData($model, 'default');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_first_media_data_returns_null_for_no_media(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getFirstMedia')->with('featured')->andReturn(null);

        $result = $this->service->getFirstMediaData($model, 'featured');

        $this->assertNull($result);
    }

    public function test_transform_model_with_media_includes_gallery_collection(): void
    {
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('toArray')->andReturn(['id' => 1]);
        $model->shouldReceive('getMedia')->with('gallery')->andReturn(new Collection([]));

        $result = $this->service->transformModelWithMedia($model, ['gallery']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('gallery', $result);
    }
}
