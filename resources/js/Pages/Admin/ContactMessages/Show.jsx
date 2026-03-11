import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Mail, Trash2, ArrowLeft, User, Calendar, Tag, MessageSquare } from 'lucide-react';

export default function Show({ message }) {
    const { delete: destroy } = useForm();

    const handleDelete = () => {
        if (confirm('Apakah Anda yakin ingin menghapus pesan ini?')) {
            destroy(route('admin.contact-messages.destroy', message.id));
        }
    };

    return (
        <AdminLayout headerTitle="Detail Pesan">
            <Head title={`Pesan dari ${message.name} - Admin`} />

            <div className="mb-6">
                <Link 
                    href={route('admin.contact-messages.index')}
                    className="inline-flex items-center gap-2 text-gray-600 hover:text-primary transition-colors"
                >
                    <ArrowLeft className="w-4 h-4" />
                    Kembali ke Daftar Pesan
                </Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {/* Left Column: Message Content */}
                <div className="lg:col-span-2 space-y-6">
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="p-6 border-b border-gray-200 bg-gray-50/50">
                            <h2 className="text-xl font-bold text-gray-900 flex items-center gap-3">
                                <Tag className="w-5 h-5 text-primary" />
                                {message.subject}
                            </h2>
                        </div>
                        <div className="p-8">
                            <div className="flex items-start gap-4 mb-8">
                                <div className="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                                    <MessageSquare className="w-6 h-6 text-primary" />
                                </div>
                                <div className="flex-1 bg-gray-50 rounded-2xl p-6 text-gray-800 leading-relaxed whitespace-pre-wrap">
                                    {message.message}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Right Column: Sender Info */}
                <div className="space-y-6">
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="p-6 border-b border-gray-200">
                            <h3 className="font-bold text-gray-900">Informasi Pengirim</h3>
                        </div>
                        <div className="p-6 space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                                    <User className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500 uppercase font-semibold tracking-wider">Nama</p>
                                    <p className="font-bold text-gray-900">{message.name}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                                    <Mail className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500 uppercase font-semibold tracking-wider">Email</p>
                                    <p className="font-medium text-gray-900">{message.email}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500">
                                    <Calendar className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500 uppercase font-semibold tracking-wider">Dikirim Pada</p>
                                    <p className="font-medium text-gray-900">
                                        {new Date(message.created_at).toLocaleDateString('id-ID', { 
                                            day: 'numeric', 
                                            month: 'long', 
                                            year: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        })}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div className="p-6 bg-gray-50 border-t border-gray-200">
                            <button 
                                onClick={handleDelete}
                                className="w-full flex items-center justify-center gap-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl transition-colors font-bold"
                            >
                                <Trash2 className="w-4 h-4" />
                                Hapus Pesan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}