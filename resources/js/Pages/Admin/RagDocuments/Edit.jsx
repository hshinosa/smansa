// FILE: resources/js/Pages/Admin/RagDocuments/Edit.jsx
// Consistent design with accent color theme

import React from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { ArrowLeft, RefreshCw, AlertCircle } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function Edit({ document }) {
    const { data, setData, put, processing, errors } = useForm({
        title: document.title || '',
        content: document.content || '',
        excerpt: document.excerpt || '',
        category: document.category || '',
        is_active: document.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('admin.rag-documents.update', document.id));
    };

    return (
        <AdminLayout>
            <Head title={`Edit: ${document.title}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <button
                            onClick={() => router.visit(route('admin.rag-documents.index'))}
                            className="flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4 transition-colors"
                        >
                            <ArrowLeft size={16} className="mr-2" />
                            Kembali
                        </button>
                        <h2 className="text-2xl font-bold text-gray-900">Edit Dokumen RAG</h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Update dokumen untuk knowledge base AI
                        </p>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-4">
                                {/* Document Info */}
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div className="flex items-start gap-3">
                                        <RefreshCw className="w-5 h-5 text-blue-600 mt-0.5" />
                                        <div className="text-sm">
                                            <p className="font-medium text-blue-900">Info Dokumen</p>
                                            <p className="text-blue-700 mt-1">
                                                Source: <span className="font-medium">{document.source || 'manual'}</span>
                                                {document.file_type && (
                                                    <> • File: <span className="font-medium">.{document.file_type}</span></>
                                                )}
                                            </p>
                                            <p className="text-blue-700">
                                                Chunks: <span className="font-medium">{document.chunks_count || 0}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* Warning */}
                                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div className="flex items-start gap-3">
                                        <AlertCircle className="w-5 h-5 text-yellow-600 mt-0.5" />
                                        <div className="text-sm text-yellow-800">
                                            <p className="font-medium">Perhatian</p>
                                            <p className="mt-1">Perubahan konten akan otomatis memproses ulang embeddings</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Title */}
                                <div>
                                    <InputLabel htmlFor="title" value="Judul Dokumen" />
                                    <span className="text-red-500 ml-1">*</span>
                                    <TextInput
                                        id="title"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        required
                                        placeholder="Contoh: Informasi PPDB 2025"
                                    />
                                    <InputError message={errors.title} className="mt-2" />
                                </div>

                                {/* Category */}
                                <div>
                                    <InputLabel htmlFor="category" value="Kategori" />
                                    <TextInput
                                        id="category"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        placeholder="Contoh: PPDB, Akademik, Ekstrakurikuler"
                                    />
                                    <InputError message={errors.category} className="mt-2" />
                                </div>

                                {/* Excerpt */}
                                <div>
                                    <InputLabel htmlFor="excerpt" value="Ringkasan Singkat" />
                                    <textarea
                                        id="excerpt"
                                        className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring focus:ring-accent-yellow focus:ring-opacity-50 text-sm"
                                        rows={2}
                                        value={data.excerpt}
                                        onChange={(e) => setData('excerpt', e.target.value)}
                                        placeholder="Ringkasan singkat tentang dokumen ini..."
                                    />
                                    <InputError message={errors.excerpt} className="mt-2" />
                                </div>

                                {/* Content */}
                                <div>
                                    <InputLabel htmlFor="content" value="Konten Dokumen" />
                                    <span className="text-red-500 ml-1">*</span>
                                    <textarea
                                        id="content"
                                        className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring focus:ring-accent-yellow focus:ring-opacity-50 font-mono text-sm"
                                        rows={12}
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        required
                                        placeholder="Konten dokumen..."
                                    />
                                    <InputError message={errors.content} className="mt-2" />
                                </div>

                                {/* Active Status */}
                                <div className="flex items-center pt-2">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-4 w-4 text-accent-yellow focus:ring-accent-yellow border-gray-300 rounded"
                                    />
                                    <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
                                        Aktifkan dokumen ini untuk chatbot
                                    </label>
                                </div>
                            </div>

                            {/* Footer */}
                            <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <p className="text-sm text-gray-600">
                                    Terakhir diupdate: {new Date(document.updated_at).toLocaleDateString('id-ID')}
                                </p>
                                <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                                    <button
                                        type="button"
                                        onClick={() => router.visit(route('admin.rag-documents.index'))}
                                        className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <PrimaryButton 
                                        type="submit" 
                                        disabled={processing}
                                        className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 px-5 py-2"
                                    >
                                        {processing ? 'Menyimpan...' : 'Update Dokumen'}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
