<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Controller for public contact page
 * Refactored from routes/web.php closure
 */
class KontakController extends Controller
{
    /**
     * Display the contact page with FAQs
     */
    public function index()
    {
        $faqs = Faq::where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('KontakPage', [
            'faqs' => $faqs
        ]);
    }
}
