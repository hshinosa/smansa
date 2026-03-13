<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Display admin login page.
     */
    public function create()
    {
        // Ensure admin is guest, if already logged in, redirect
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard'); // Create this route later
        }

        return Inertia::render('Admin/Auth/LoginPage');
    }

    /**
     * Handle admin login attempt.
     */
    public function store(AdminLoginRequest $request)
    {
        if (Auth::guard('admin')->attempt($request->validated(), $request->filled('remember'))) {
            $request->session()->regenerate();
            
            // Redirect to dashboard after successful login
            return redirect()->intended(route('admin.dashboard')); // Create this route later
        }

        // If login fails
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')], // Standard Laravel error message
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
