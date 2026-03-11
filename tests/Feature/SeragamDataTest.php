<?php

namespace Tests\Feature;

use App\Models\Seragam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeragamDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PopulateSeragamSeeder::class);
    }

    public function test_seragam_data_exists(): void
    {
        $count = Seragam::count();
        
        $this->assertGreaterThan(0, $count, 'Seharusnya ada data seragam');
        
        $seragams = Seragam::with('media')->get();
        
        $output = "=== DATA SERAGAM ===\n";
        foreach ($seragams as $s) {
            $imageUrl = $s->getFirstMediaUrl('images');
            $output .= "Name: {$s->name}\n";
            $output .= "  Category: {$s->category}\n";
            $output .= "  Usage Days: " . implode(', ', $s->usage_days ?? []) . "\n";
            $output .= "  Image: " . ($imageUrl ? 'ADA' : 'TIDAK ADA') . "\n";
            $output .= "---\n";
        }
        
        $this->assertTrue(true, $output);
    }
}
