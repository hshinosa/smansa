<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_url' => $this->getUrl(),
            'conversions' => [
                'mobile' => $this->hasGeneratedConversion('mobile') ? $this->getUrl('mobile') : null,
                'tablet' => $this->hasGeneratedConversion('tablet') ? $this->getUrl('tablet') : null,
                'desktop' => $this->hasGeneratedConversion('desktop') ? $this->getUrl('desktop') : null,
                'large' => $this->hasGeneratedConversion('large') ? $this->getUrl('large') : null,
                'webp' => $this->hasGeneratedConversion('webp') ? $this->getUrl('webp') : null,
                'thumb' => $this->hasGeneratedConversion('thumb') ? $this->getUrl('thumb') : null,
            ],
            'name' => $this->name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
        ];
    }
}
