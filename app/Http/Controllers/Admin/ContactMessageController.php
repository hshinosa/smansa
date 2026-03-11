<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Requests\ContactMessageRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(10);

        return Inertia::render('Admin/ContactMessages/Index', [
            'messages' => $messages
        ]);
    }

    public function show(ContactMessage $contactMessage)
    {
        try {
            DB::beginTransaction();
            if (!$contactMessage->is_read) {
                $contactMessage->update(['is_read' => true]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update message read status: ' . $e->getMessage());
        }

        return Inertia::render('Admin/ContactMessages/Show', [
            'message' => $contactMessage
        ]);
    }

    public function destroy(ContactMessage $contactMessage)
    {
        try {
            DB::beginTransaction();
            $contactMessage->delete();
            DB::commit();
            return redirect()->back()->with('success', 'Pesan berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete contact message: ' . $e->getMessage());
            return redirect()->back()->withErrors(['general' => 'Gagal menghapus pesan.']);
        }
    }
}
