<?php

namespace Tests\Unit\Services;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    public function test_get_site_settings_stores_and_retrieves_value()
    {
        $key = 'test_setting_' . uniqid();
        $value = ['data' => 'test_value'];

        $result = CacheService::getSiteSettings($key, fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has("site_settings.{$key}"));
    }

    public function test_get_posts_caches_with_correct_key()
    {
        $limit = 10;
        $value = collect(['post1', 'post2']);

        $result = CacheService::getPosts($limit, fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has("posts.latest.{$limit}"));
    }

    public function test_get_popular_posts_caches_with_correct_key()
    {
        $limit = 5;
        $value = collect(['popular1', 'popular2']);

        $result = CacheService::getPopularPosts($limit, fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has("posts.popular.{$limit}"));
    }

    public function test_get_programs_caches_correctly()
    {
        $value = collect(['program1', 'program2']);

        $result = CacheService::getPrograms(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('programs.all'));
    }

    public function test_get_featured_programs_caches_correctly()
    {
        $value = collect(['featured1', 'featured2']);

        $result = CacheService::getFeaturedPrograms(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('programs.featured'));
    }

    public function test_get_galleries_caches_with_limit()
    {
        $limit = 12;
        $value = collect(['gallery1', 'gallery2']);

        $result = CacheService::getGalleries($limit, fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has("galleries.latest.{$limit}"));
    }

    public function test_get_teachers_caches_correctly()
    {
        $value = collect(['teacher1', 'teacher2']);

        $result = CacheService::getTeachers(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('teachers.all'));
    }

    public function test_get_extracurriculars_caches_correctly()
    {
        $value = collect(['ekskul1', 'ekskul2']);

        $result = CacheService::getExtracurriculars(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('extracurriculars.all'));
    }

    public function test_get_faqs_caches_correctly()
    {
        $value = collect(['faq1', 'faq2']);

        $result = CacheService::getFaqs(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('faqs.all'));
    }

    public function test_get_alumni_caches_correctly()
    {
        $value = collect(['alumni1', 'alumni2']);

        $result = CacheService::getAlumni(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('alumni.all'));
    }

    public function test_get_academic_calendars_caches_correctly()
    {
        $value = collect(['calendar1', 'calendar2']);

        $result = CacheService::getAcademicCalendars(fn() => $value);

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has('academic_calendars.all'));
    }

    public function test_invalidate_all_clears_cache()
    {
        Cache::put('test_key_1', 'value1', 3600);
        Cache::put('test_key_2', 'value2', 3600);

        CacheService::invalidateAll();

        $this->assertFalse(Cache::has('test_key_1'));
        $this->assertFalse(Cache::has('test_key_2'));
    }

    public function test_get_stats_returns_configuration()
    {
        $stats = CacheService::getStats();

        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('stores', $stats);
        $this->assertArrayHasKey('ttl_configurations', $stats);
        $this->assertIsArray($stats['ttl_configurations']);
    }

    public function test_ttl_configurations_have_correct_values()
    {
        $stats = CacheService::getStats();
        $ttl = $stats['ttl_configurations'];

        $this->assertEquals(3600, $ttl['site_settings']);
        $this->assertEquals(900, $ttl['posts']);
        $this->assertEquals(1800, $ttl['popular_posts']);
        $this->assertEquals(3600, $ttl['programs']);
        $this->assertEquals(1800, $ttl['galleries']);
        $this->assertEquals(3600, $ttl['teachers']);
        $this->assertEquals(3600, $ttl['extracurriculars']);
        $this->assertEquals(3600, $ttl['faqs']);
        $this->assertEquals(3600, $ttl['alumnis']);
        $this->assertEquals(3600, $ttl['academic_calendars']);
    }
}
