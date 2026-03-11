// FILE: resources/js/Pages/Admin/RagDocuments/Create.jsx  
// Consistent design with accent color theme

import React, { useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { FileText, Upload, ArrowLeft, File } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        content: '',
        excerpt: '',
        category: '',
        file: null,
        is_active: true,
    });

    const [uploadMode, setUploadMode] = useState('manual'); // 'manual' or 'file'

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.rag-documents.store'), {
            forceFormData: true,
        });
    };

    return (
        <AdminLayout>
            <Head title="Tambah Dokumen RAG" />

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
                        <h2 className="text-2xl font-bold text-gray-900">Tambah Dokumen RAG</h2>
                        <p className="mt-1 text-sm text-gray-600">
                            Upload file atau tulis manual dokumen untuk knowledge base AI
                        </p>
                    </div>

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            {/* Upload Mode Toggle */}
                            <div className="p-6 border-b border-gray-200">
                                <InputLabel value="Mode Input" />
                                <div className="mt-2 flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setUploadMode('manual')}
                                        className={`flex-1 px-4 py-3 rounded-lg border-2 flex items-center justify-center gap-2 transition-all ${
                                            uploadMode === 'manual'
                                                ? 'border-accent-yellow bg-accent-yellow/10 text-gray-900'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        }`}
                                    >
                                        <FileText size={20} />
                                        <span className="font-medium">Tulis Manual</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setUploadMode('file')}
                                        className={`flex-1 px-4 py-3 rounded-lg border-2 flex items-center justify-center gap-2 transition-all ${
                                            uploadMode === 'file'
                                                ? 'border-accent-yellow bg-accent-yellow/10 text-gray-900'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        }`}
                                    >
                                        <Upload size={20} />
                                        <span className="font-medium">Upload File</span>
                                    </button>
                                </div>
                            </div>

                            <div className="p-6 space-y-4">
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

                                {/* File Upload Mode */}
                                {uploadMode === 'file' && (
                                    <div>
                                        <InputLabel htmlFor="file" value="Upload File" />
                                        <span className="text-red-500 ml-1">*</span>
                                        <div className="mt-2">
                                            <label 
                                                htmlFor="file"
                                                className="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors"
                                            >
                                                <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <Upload className="w-8 h-8 mb-2 text-gray-500" />
                                                    <p className="mb-1 text-sm text-gray-500">
                                                        <span className="font-semibold">Klik untuk upload</span> atau drag & drop
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        TXT, PDF, DOC, DOCX (Max. 10MB)
                                                    </p>
                                                    {data.file && (
                                                        <p className="mt-2 text-sm text-accent-yellow font-medium flex items-center gap-1">
                                                            <File size={14} />
                                                            {data.file.name}
                                                        </p>
                                                    )}
                                                </div>
                                                <input
                                                    id="file"
                                                    type="file"
                                                    className="hidden"
                                                    accept=".txt,.pdf,.doc,.docx"
                                                    onChange={(e) => setData('file', e.target.files[0])}
                                                />
                                            </label>
                                        </div>
                                        <InputError message={errors.file} className="mt-2" />
                                    </div>
                                )}

                                {/* Manual Content Mode */}
                                {uploadMode === 'manual' && (
                                    <div>
                                        <InputLabel htmlFor="content" value="Konten Dokumen" />
                                        <span className="text-red-500 ml-1">*</span>
                                        <textarea
                                            id="content"
                                            className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring focus:ring-accent-yellow focus:ring-opacity-50 font-mono text-sm"
                                            rows={12}
                                            value={data.content}
                                            onChange={(e) => setData('content', e.target.value)}
                                            required={uploadMode === 'manual'}
                                            placeholder="Tulis konten dokumen di sini..."
                                        />
                                        <InputError message={errors.content} className="mt-2" />
                                    </div>
                                )}

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
                            <div className="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => router.visit(route('admin.rag-documents.index'))}
                                    className="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Batal
                                </button>
                                <PrimaryButton 
                                    type="submit" 
                                    disabled={processing}
                                    className="w-full sm:w-auto !bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 px-5 py-2"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan Dokumen'}
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
