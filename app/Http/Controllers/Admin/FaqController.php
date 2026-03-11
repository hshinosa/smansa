<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Requests\FaqRequest;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->get();
        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => $faqs
        ]);
    }

    public function store(FaqRequest $request)
    {
        $validated = $request->validated();
        $validated['question'] = strip_tags($validated['question']);
        $validated['answer'] = strip_tags($validated['answer'], '<b><i><u><p><br>');

        Faq::create($validated);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ berhasil ditambahkan.');
    }

    public function update(FaqRequest $request, Faq $faq)
    {
        $validated = $request->validated();
        $validated['question'] = strip_tags($validated['question']);
        $validated['answer'] = strip_tags($validated['answer'], '<b><i><u><p><br>');

        $faq->update($validated);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ berhasil diperbarui.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ berhasil dihapus.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['items' => 'required|array']);
        foreach ($request->input('items') as $index => $id) {
            Faq::where('id', $id)->update(['sort_order' => $index + 1]);
        }
        return back()->with('success', 'Urutan berhasil diperbarui.');
    }
}
