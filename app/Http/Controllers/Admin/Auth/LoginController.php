<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia; // Untuk error login

class LoginController extends Controller
{
    /**
     * Menampilkan halaman login admin.
     */
    public function create()
    {
        // Pastikan guest admin, jika sudah login, redirect
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard'); // Buat rute ini nanti
        }

        return Inertia::render('Admin/Auth/LoginPage');
    }

    /**
     * Menangani upaya login admin.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Gunakan guard 'admin' untuk attempt
        if (Auth::guard('admin')->attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            // Redirect ke dashboard admin setelah login berhasil
            return redirect()->intended(route('admin.dashboard')); // Buat rute ini nanti
        }

        // Jika login gagal
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')], // Pesan error standar Laravel
        ]);
    }

    /**
     * Menangani proses logout admin.
     */
    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login.form'); // Redirect ke halaman login admin
    }
}
