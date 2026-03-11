// FILE: resources/js/Pages/Admin/RagDocuments/Index.jsx
// Consistent design with accent color theme

import React, { useState } from 'react';
import { Head, usePage, Link, router } from '@inertiajs/react';
import { Sparkles, Plus, Edit2, Trash2, RefreshCw, FileText } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import toast from 'react-hot-toast';

export default function Index({ documents }) {
    const { success } = usePage().props;
    const [activeTab, setActiveTab] = useState('list');
    const [processing, setProcessing] = useState(null);

    const tabs = [
        { key: 'list', label: 'RAG Documents', description: 'Kelola dokumen knowledge base untuk AI chatbot.', icon: Sparkles }
    ];

    const handleReprocess = (id) => {
        if (confirm('Apakah Anda yakin ingin memproses ulang embeddings untuk dokumen ini? Proses ini mungkin memakan waktu beberapa menit.')) {
            setProcessing(id);
            toast.loading('Memproses embeddings... Ini mungkin memakan waktu beberapa menit.', { duration: 5000 });
            
            router.post(route('admin.rag-documents.reprocess', id), {}, {
                preserveScroll: true,
                onFinish: () => setProcessing(null),
                onSuccess: () => toast.success('Dokumen berhasil diproses ulang! Embeddings sudah diperbarui.'),
                onError: () => {
                    toast.error('Gagal memproses dokumen. Periksa konfigurasi AI atau coba lagi.');
                }
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus dokumen ini? Semua chunks dan embeddings akan dihapus dan tidak dapat dipulihkan.')) {
            router.delete(route('admin.rag-documents.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Dokumen berhasil dihapus'),
            });
        }
    };

    return (
        <ContentManagementPage 
            headerTitle="RAG Documents" 
            headTitle="RAG Documents"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            extraHeader={
                <div className="flex justify-end">
                    <Link href={route('admin.rag-documents.create')}>
                        <PrimaryButton className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2 px-4 py-2 text-sm sm:text-base">
                            <Plus size={18} />
                            Tambah Dokumen
                        </PrimaryButton>
                    </Link>
                </div>
            }
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {documents.data && documents.data.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Kategori</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Source</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Chunks</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Progress</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {documents.data.map((doc) => (
                                    <tr key={doc.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <FileText className="w-5 h-5 text-blue-600" />
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-gray-900 text-sm truncate max-w-[200px]">{doc.title}</p>
                                                    <p className="text-xs text-gray-500">{new Date(doc.created_at).toLocaleDateString('id-ID')}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 hidden md:table-cell">
                                            <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                {doc.category || 'Umum'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 hidden lg:table-cell">
                                            <span className="text-xs text-gray-600">{doc.source || 'manual'}</span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                                {doc.chunks_count || 0}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {doc.is_processed ? (
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                    Selesai
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 animate-pulse">
                                                    Memproses...
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${
                                                doc.is_active 
                                                    ? 'bg-green-100 text-green-700' 
                                                    : 'bg-gray-100 text-gray-600'
                                            }`}>
                                                {doc.is_active ? 'Aktif' : 'Nonaktif'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <button
                                                    onClick={() => handleReprocess(doc.id)}
                                                    disabled={processing === doc.id}
                                                    className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg disabled:opacity-50"
                                                    title="Reprocess Embeddings"
                                                >
                                                    <RefreshCw size={16} className={processing === doc.id ? 'animate-spin' : ''} />
                                                </button>
                                                <Link
                                                    href={route('admin.rag-documents.edit', doc.id)}
                                                    className="p-2 !text-accent-yellow hover:bg-accent-yellow/10 rounded-lg"
                                                >
                                                    <Edit2 size={16} />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(doc.id)}
                                                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                                >
                                                    <Trash2 size={16} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="p-8 sm:p-12 text-center">
                        <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <FileText size={24} className="sm:w-8 sm:h-8 text-gray-400" />
                        </div>
                        <p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada dokumen RAG</p>
                        <Link href={route('admin.rag-documents.create')}>
                            <PrimaryButton className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 text-sm sm:text-base px-4 py-2">
                                Tambah Dokumen
                            </PrimaryButton>
                        </Link>
                    </div>
                )}
            </div>

            {/* Pagination */}
            {documents.links && documents.links.length > 3 && (
                <div className="mt-4 flex justify-center gap-2">
                    {documents.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url || '#'}
                            className={`px-3 py-1 rounded-lg text-sm ${
                                link.active
                                    ? 'bg-accent-yellow text-gray-900 font-bold'
                                    : link.url
                                    ? 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </ContentManagementPage>
    );
}
