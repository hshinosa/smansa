<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

use App\Http\Requests\ContactMessageRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function store(ContactMessageRequest $request)
    {
        // Rate limiting: max 3 messages per minute per session
        if ($request->session()->has('last_message_time')) {
            $lastTime = $request->session()->get('last_message_time');
            if (now()->diffInSeconds($lastTime) < 60) {
                return back()->with('error', 'Mohon tunggu sebentar sebelum mengirim pesan lagi.');
            }
        }

        try {
            DB::beginTransaction();

            $validated = $request->validated();

            $cleaned = Arr::map($validated, function ($value) {
                return is_string($value) ? trim(strip_tags($value)) : $value;
            });

            ContactMessage::create($cleaned);

            DB::commit();
            
            $request->session()->put('last_message_time', now());

            return back()->with('success', 'Pesan Anda telah terkirim. Terima kasih telah menghubungi kami!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store contact message: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengirim pesan. Silakan coba lagi nanti.');
        }
    }
}
