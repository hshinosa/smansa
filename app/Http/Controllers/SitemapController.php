<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Program;
use App\Models\Gallery;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $baseUrl = config('app.url');
        
        // Static pages
        $staticPages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/profil-sekolah', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/visi-misi', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/struktur-organisasi', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/guru-staff', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/kurikulum', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/program-sekolah', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/program-mipa', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/program-ips', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/program-bahasa', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/ekstrakurikuler', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/galeri', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => '/berita', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => '/informasi-spmb', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/alumni', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/kontak', 'priority' => '0.7', 'changefreq' => 'yearly'],
            ['loc' => '/kalender-akademik', 'priority' => '0.7', 'changefreq' => 'weekly'],
        ];
        
        foreach ($staticPages as $page) {
            $sitemap .= '<url>';
            $sitemap .= '<loc>' . $baseUrl . $page['loc'] . '</loc>';
            $sitemap .= '<changefreq>' . $page['changefreq'] . '</changefreq>';
            $sitemap .= '<priority>' . $page['priority'] . '</priority>';
            $sitemap .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
            $sitemap .= '</url>';
        }
        
        // Dynamic posts/berita
        $posts = Post::where('status', 'published')->latest('updated_at')->get();
        foreach ($posts as $post) {
            $sitemap .= '<url>';
            $sitemap .= '<loc>' . $baseUrl . '/berita/' . $post->slug . '</loc>';
            $sitemap .= '<lastmod>' . $post->updated_at->toAtomString() . '</lastmod>';
            $sitemap .= '<changefreq>monthly</changefreq>';
            $sitemap .= '<priority>0.6</priority>';
            $sitemap .= '</url>';
        }
        
        // Dynamic programs
        $programs = Program::where('is_featured', true)->get();
        foreach ($programs as $program) {
            if ($program->link) {
                $sitemap .= '<url>';
                $sitemap .= '<loc>' . $baseUrl . $program->link . '</loc>';
                $sitemap .= '<lastmod>' . $program->updated_at->toAtomString() . '</lastmod>';
                $sitemap .= '<changefreq>monthly</changefreq>';
                $sitemap .= '<priority>0.7</priority>';
                $sitemap .= '</url>';
            }
        }
        
        $sitemap .= '</urlset>';
        
        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }
    
    public function robots()
    {
        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /admin/*\n";
        $content .= "Disallow: /login\n";
        $content .= "Disallow: /api\n";
        $content .= "\n";
        $content .= "Sitemap: " . config('app.url') . "/sitemap.xml\n";
        
        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
