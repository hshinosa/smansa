// FILE: resources/js/Pages/Admin/ContactMessages/Index.jsx
// Fully responsive contact messages page with accent color theme

import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Mail, Trash2, Eye, CheckCircle, Clock, Calendar } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';

export default function Index({ messages }) {
    const { delete: destroy } = useForm();
    const [activeTab, setActiveTab] = useState('list');

    const tabs = [{ key: 'list', label: 'Pesan Masuk', description: 'Kelola pesan kontak.', icon: Mail }];

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus pesan ini? Tindakan ini tidak dapat dibatalkan.')) {
            destroy(route('admin.contact-messages.destroy', id));
        }
    };

    return (
        <ContentManagementPage headerTitle="Pesan Kontak" headTitle="Pesan Masuk" tabs={tabs} activeTab={activeTab} setActiveTab={setActiveTab}>
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {messages.data.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Pengirim</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Subjek</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {messages.data.map((message) => (
                                    <tr key={message.id} className={`hover:bg-gray-50 ${!message.is_read ? 'bg-blue-50/30' : ''}`}>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {message.is_read ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                    <CheckCircle size={12} /> Dibaca
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-accent-yellow/20 text-accent-yellow">
                                                    <Clock size={12} /> Baru
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <p className="font-semibold text-gray-900 text-sm">{message.name}</p>
                                            <p className="text-xs text-gray-500">{message.email}</p>
                                        </td>
                                        <td className="px-4 py-3 hidden sm:table-cell">
                                            <p className="text-sm text-gray-700 line-clamp-1">{message.subject}</p>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <div className="flex items-center gap-1.5 text-xs sm:text-sm text-gray-500">
                                                <Calendar size={14} />
                                                {new Date(message.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Link href={route('admin.contact-messages.show', message.id)} className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                                                    <Eye size={16} />
                                                </Link>
                                                <button onClick={() => handleDelete(message.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg">
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
                            <Mail size={24} className="sm:w-8 sm:h-8 text-gray-400" />
                        </div>
                        <p className="text-gray-500 font-medium text-sm sm:text-base">Belum ada pesan</p>
                    </div>
                )}

                {messages.links && messages.links.length > 3 && (
                    <div className="px-4 py-3 bg-gray-50 border-t border-gray-200 flex justify-center gap-2 flex-wrap">
                        {messages.links.map((link, i) => (
                            <Link key={i} href={link.url || '#'}
                                className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                                    link.active ? 'bg-accent-yellow text-gray-900' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </ContentManagementPage>
    );
}
