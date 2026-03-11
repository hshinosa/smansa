<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProgramStudiSetting;
use Illuminate\Support\Facades\Log;

class FixProgramStudiFacilitiesStructure extends Seeder
{
    /**
     * Convert old facilities items structure (array of strings) 
     * to new structure (array of objects with title and image)
     */
    public function run(): void
    {
        $programs = ['mipa', 'ips', 'bahasa'];
        
        foreach ($programs as $program) {
            $setting = ProgramStudiSetting::where('program_name', $program)
                ->where('section_key', 'facilities')
                ->first();
                
            if (!$setting) {
                Log::warning("No facilities setting found for program: {$program}");
                continue;
            }
            
            $content = $setting->content;
            
            // Check if items exists and is array of strings (old format)
            if (isset($content['items']) && is_array($content['items'])) {
                $needsUpdate = false;
                $newItems = [];
                
                foreach ($content['items'] as $item) {
                    // If item is string, convert to object
                    if (is_string($item)) {
                        $needsUpdate = true;
                        $newItems[] = [
                            'title' => $item,
                            'image' => null,
                        ];
                    } else {
                        // Already object format, keep as is
                        $newItems[] = $item;
                    }
                }
                
                if ($needsUpdate) {
                    $content['items'] = $newItems;
                    $setting->content = $content;
                    $setting->save();
                    
                    $this->command->info("✓ Updated facilities structure for program: {$program}");
                }
            }
        }
        
        $this->command->info("✓ Facilities structure migration completed");
    }
}
