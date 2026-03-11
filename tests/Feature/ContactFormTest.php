<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_applies_rate_limit(): void
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Pertanyaan Penting',
            'message' => 'Halo, saya ingin tahu lebih lanjut tentang PPDB.',
        ];

        $firstResponse = $this->post(route('kontak.store'), $payload);
        $firstResponse->assertSessionHas('success');
        $this->assertDatabaseCount('contact_messages', 1);

        $secondResponse = $this->post(route('kontak.store'), $payload);
        $secondResponse->assertSessionHas('error', 'Mohon tunggu sebentar sebelum mengirim pesan lagi.');
        $this->assertDatabaseCount('contact_messages', 1);
    }
}
