<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function serapanPtn(): RedirectResponse
    {
        return redirect()->route('prestasi.akademik', [], 301);
    }

    public function hasilTka(): RedirectResponse
    {
        return redirect()->route('prestasi.akademik', [], 301);
    }

    public function login(): RedirectResponse
    {
        return redirect()->route('admin.login.form');
    }
}
