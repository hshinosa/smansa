<?php

namespace App\Console\Commands;

use App\Models\LandingPageSetting;
use Illuminate\Console\Command;

class FixKepsekImagePath extends Command
{
    protected $signature = 'fix:kepsek-image-path';
    protected $description = 'Fix kepsek_image_url to use Media Library path';

    public function handle()
    {
        $this->info('Fixing kepsek_image_url path...');

        $setting = LandingPageSetting::where('section_key', 'kepsek_welcome_lp')->first();

        if (!$setting) {
            $this->error('kepsek_welcome_lp setting not found!');
            return 1;
        }

        $content = $setting->content ?? [];
        $this->info('Current kepsek_image_url: ' . ($content['kepsek_image_url'] ?? 'NOT SET'));

        $media = $setting->getFirstMedia('kepsek_image');

        if ($media) {
            $correctUrl = $media->getUrl();
            $this->info('Media Library URL: ' . $correctUrl);
            
            $content['kepsek_image_url'] = $correctUrl;
            $setting->content = $content;
            $setting->save();
            
            $this->info('✅ FIXED! Updated to: ' . $correctUrl);
        } else {
            $this->warn('No media found in Media Library for kepsek_image collection.');
            $this->warn('User needs to re-upload the image through admin panel.');
        }

        return 0;
    }
}
