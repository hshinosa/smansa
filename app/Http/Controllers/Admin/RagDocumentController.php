<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RagDocument;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Requests\RagDocumentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RagDocumentController extends Controller
{
    protected RagService $ragService;

    public function __construct(RagService $ragService)
    {
        $this->ragService = $ragService;
    }

    public function index()
    {
        $documents = RagDocument::with('uploader')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/RagDocuments/Index', [
            'documents' => $documents,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/RagDocuments/Create');
    }

    public function store(RagDocumentRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            
            $document = new RagDocument();
            $document->title = strip_tags($validated['title']);
            $document->excerpt = isset($validated['excerpt']) ? strip_tags($validated['excerpt']) : null;
            $document->category = $validated['category'] ?? 'Umum';
            $document->is_active = $validated['is_active'] ?? true;
            $document->uploaded_by = Auth::guard('admin')->id();
            $document->source = $request->hasFile('file') ? 'upload' : 'manual';

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('rag_documents', 'public');
                
                $document->file_path = $path;
                $document->file_type = $file->getClientOriginalExtension();
                $document->file_size = $file->getSize();
                $document->content = $this->extractTextFromFile($file);
            } else {
                $document->content = $validated['content'];
            }

            $document->save();

            DB::commit();

            // Processing via queue to avoid timeouts on large documents
            \App\Jobs\ProcessRagDocument::dispatch($document);

            return redirect()->route('admin.rag-documents.index')
                ->with('success', 'Dokumen berhasil ditambahkan dan sedang diproses di latar belakang.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store RAG document: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal menambah dokumen RAG.']);
        }
    }

    public function edit(RagDocument $ragDocument)
    {
        $ragDocument->load('uploader');
        
        return Inertia::render('Admin/RagDocuments/Edit', [
            'document' => $ragDocument,
        ]);
    }

    public function update(RagDocumentRequest $request, RagDocument $ragDocument)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['title'] = strip_tags($validated['title']);
            if (isset($validated['excerpt'])) {
                $validated['excerpt'] = strip_tags($validated['excerpt']);
            }

            $oldContent = $ragDocument->content;
            $ragDocument->update($validated);

            DB::commit();

            if ($ragDocument->content !== $oldContent) {
                \App\Jobs\ProcessRagDocument::dispatch($ragDocument);
            }

            return redirect()->route('admin.rag-documents.index')
                ->with('success', 'Dokumen berhasil diperbarui dan sedang diproses di latar belakang.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update RAG document: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Gagal memperbarui dokumen RAG.']);
        }
    }

    public function destroy(RagDocument $ragDocument)
    {
        if ($ragDocument->file_path) {
            Storage::disk('public')->delete($ragDocument->file_path);
        }

        $ragDocument->delete();

        return redirect()->route('admin.rag-documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    public function reprocess(RagDocument $ragDocument)
    {
        \App\Jobs\ProcessRagDocument::dispatch($ragDocument);
        return back()->with('success', 'Dokumen sedang diproses ulang di latar belakang.');
    }

    protected function extractTextFromFile($file): string
    {
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'txt') {
            return file_get_contents($file->getRealPath());
        }

        if ($extension === 'pdf') {
            if (class_exists('\Smalot\PdfParser\Parser')) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($file->getRealPath());
                    $text = $pdf->getText();
                    return !empty($text) ? $text : "PDF content is empty or unreadable.";
                } catch (\Exception $e) {
                    Log::error('PDF extraction failed', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    return "PDF extraction error: " . $e->getMessage();
                }
            } else {
                return "PDF extraction requires 'smalot/pdfparser' package.";
            }
        }

        if (in_array($extension, ['doc', 'docx'])) {
            if (class_exists('\PhpOffice\PhpWord\IOFactory')) {
                try {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . "\n";
                            } elseif (method_exists($element, 'getElements')) {
                                foreach ($element->getElements() as $childElement) {
                                    if (method_exists($childElement, 'getText')) {
                                        $text .= $childElement->getText() . " ";
                                    }
                                }
                                $text .= "\n";
                            }
                        }
                    }
                    return !empty($text) ? trim($text) : "DOCX content is empty.";
                } catch (\Exception $e) {
                    Log::error('DOCX extraction failed', [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    return "DOCX extraction error: " . $e->getMessage();
                }
            } else {
                return "DOCX extraction requires 'phpoffice/phpword' package.";
            }
        }

        return "File type '{$extension}' is not supported.";
    }
}
