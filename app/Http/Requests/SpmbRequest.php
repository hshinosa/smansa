<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpmbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        $inputData = $this->all();
        $targetSection = 'pengaturan_umum';
        if (isset($inputData['pengaturan_umum'])) $targetSection = 'pengaturan_umum';
        elseif (isset($inputData['jalur_pendaftaran'])) $targetSection = 'jalur_pendaftaran';
        elseif (isset($inputData['jadwal_penting'])) $targetSection = 'jadwal_penting';
        elseif (isset($inputData['persyaratan'])) $targetSection = 'persyaratan';
        elseif (isset($inputData['prosedur'])) $targetSection = 'prosedur';
        elseif (isset($inputData['faq'])) $targetSection = 'faq';

        $rules = [];
        if ($targetSection === 'pengaturan_umum') {
            $rules["{$targetSection}.title"] = 'required|string|max:200';
            $rules["{$targetSection}.description_html"] = 'required|string|max:5000';
            $rules["{$targetSection}.banner_image_file"] = 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240';
            $rules["{$targetSection}.is_registration_open"] = 'required';
            $rules["{$targetSection}.registration_year"] = 'required|string|max:20';
        } elseif ($targetSection === 'jalur_pendaftaran') {
            $rules["{$targetSection}.items"] = 'nullable|array';
            $rules["{$targetSection}.items.*.label"] = 'required|string|max:200';
            $rules["{$targetSection}.items.*.description"] = 'required|string|max:2000';
        }
        
        return $rules;
    }
}
