import React, { useState, useEffect, useMemo } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { BookOpen, Activity, ListChecks, Repeat, Network, Users, Image as ImageIcon } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import FileUploadField from '@/Components/Admin/FileUploadField';
import toast from 'react-hot-toast';

export default function Index({ auth, settings = {}, sectionMeta = {}, activeSection = 'hero' }) {
    const [activeTab, setActiveTab] = useState(activeSection);

    const iconMap = {
        BookOpen,
        Activity,
        ListChecks,
        Repeat,
        Network,
        Users,
        ImageIcon
    };

    const initialContent = useMemo(() => {
        return settings?.[activeTab] || {};
    }, [activeTab, settings]);

    const form = useForm({
        section: activeTab,
        content: initialContent,
        media: null,
    });

    useEffect(() => {
        form.setData('section', activeTab);
        form.setData('content', settings?.[activeTab] || {});
        form.setData('media', null);
    }, [activeTab, settings]);

    const tabs = Object.keys(sectionMeta)
        .map((key) => ({ 
            key, 
            ...sectionMeta[key],
            icon: iconMap[sectionMeta[key].icon] || BookOpen
        }));

    const handleTabChange = (tab) => {
        setActiveTab(tab);
        form.setData('section', tab);
        form.setData('content', settings?.[tab] || {});
        form.setData('media', null);
    };

    const handleSave = (e) => {
        if (e) e.preventDefault();
        form.post(route('admin.curriculum.update'), {
            forceFormData: true,
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => toast.success('Konten kurikulum berhasil disimpan.'),
            onError: () => toast.error('Gagal menyimpan konten kurikulum.'),
        });
    };

    const updateContentField = (field, value) => {
        form.setData('content', { ...form.data.content, [field]: value });
    };

    const updateItemList = (field, index, value) => {
        const items = [...(form.data.content?.[field] || [])];
        items[index] = value;
        updateContentField(field, items);
    };

    const addItem = (field, value) => {
        const items = [...(form.data.content?.[field] || [])];
        items.push(value);
        updateContentField(field, items);
    };

    const removeItem = (field, index) => {
        const items = [...(form.data.content?.[field] || [])];
        items.splice(index, 1);
        updateContentField(field, items);
    };

    return (
        <ContentManagementPage
            headerTitle="Manajemen Kurikulum"
            headTitle="Manajemen Kurikulum"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={handleTabChange}
            processing={form.processing}
            onSave={handleSave}
            errors={form.errors}
        >
            <div className="space-y-6">
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                    <div className="border-b pb-3">
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                            {sectionMeta[activeTab]?.label}
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">{sectionMeta[activeTab]?.description}</p>
                    </div>

                    <div className="grid gap-4">
                        {'title' in (form.data.content || {}) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Judul</label>
                                <input
                                    type="text"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value={form.data.content?.title || ''}
                                    onChange={(e) => updateContentField('title', e.target.value)}
                                />
                            </div>
                        )}

                        {'subtitle' in (form.data.content || {}) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Subjudul</label>
                                <input
                                    type="text"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value={form.data.content?.subtitle || ''}
                                    onChange={(e) => updateContentField('subtitle', e.target.value)}
                                />
                            </div>
                        )}

                        {'description' in (form.data.content || {}) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Deskripsi</label>
                                <textarea
                                    rows="3"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value={form.data.content?.description || ''}
                                    onChange={(e) => updateContentField('description', e.target.value)}
                                ></textarea>
                            </div>
                        )}

                        {'source' in (form.data.content || {}) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sumber</label>
                                <input
                                    type="text"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    value={form.data.content?.source || ''}
                                    onChange={(e) => updateContentField('source', e.target.value)}
                                />
                            </div>
                        )}

                        {Array.isArray(form.data.content?.items) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Daftar Item</label>
                                <div className="space-y-3">
                                    {form.data.content.items.map((item, idx) => (
                                        <div key={idx} className="grid gap-2">
                                            {typeof item === 'string' ? (
                                                <div className="flex gap-2">
                                                    <input
                                                        type="text"
                                                        className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                        value={item}
                                                        onChange={(e) => updateItemList('items', idx, e.target.value)}
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItem('items', idx)}
                                                        className="p-2 text-red-500 hover:bg-red-50 rounded-md"
                                                    >
                                                        Hapus
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="grid md:grid-cols-2 gap-2">
                                                    <input
                                                        type="text"
                                                        className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="Judul"
                                                        value={item.title || ''}
                                                        onChange={(e) => updateItemList('items', idx, { ...item, title: e.target.value })}
                                                    />
                                                    <input
                                                        type="text"
                                                        className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                        placeholder="Deskripsi"
                                                        value={item.description || ''}
                                                        onChange={(e) => updateItemList('items', idx, { ...item, description: e.target.value })}
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => removeItem('items', idx)}
                                                        className="text-red-500 hover:text-red-700 text-sm"
                                                    >
                                                        Hapus
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => addItem('items', typeof form.data.content.items?.[0] === 'string' ? '' : { title: '', description: '' })}
                                        className="mt-2 flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Tambah Item
                                    </button>
                                </div>
                            </div>
                        )}

                        {Array.isArray(form.data.content?.steps) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Tahapan</label>
                                <div className="space-y-3">
                                    {form.data.content.steps.map((step, idx) => (
                                        <div key={idx} className="grid md:grid-cols-2 gap-2">
                                            <input
                                                type="text"
                                                className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Judul"
                                                value={step.title || ''}
                                                onChange={(e) => updateItemList('steps', idx, { ...step, title: e.target.value })}
                                            />
                                            <input
                                                type="text"
                                                className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Deskripsi"
                                                value={step.description || ''}
                                                onChange={(e) => updateItemList('steps', idx, { ...step, description: e.target.value })}
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeItem('steps', idx)}
                                                className="text-red-500 hover:text-red-700 text-sm"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => addItem('steps', { title: '', description: '' })}
                                        className="mt-2 flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Tambah Tahapan
                                    </button>
                                </div>
                            </div>
                        )}

                        {Array.isArray(form.data.content?.stats) && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Statistik (PISA)</label>
                                <div className="space-y-3">
                                    {form.data.content.stats.map((stat, idx) => (
                                        <div key={idx} className="grid md:grid-cols-3 gap-2">
                                            <input
                                                type="text"
                                                className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Bidang"
                                                value={stat.label || ''}
                                                onChange={(e) => updateItemList('stats', idx, { ...stat, label: e.target.value })}
                                            />
                                            <input
                                                type="text"
                                                className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="LOTS"
                                                value={stat.lots || ''}
                                                onChange={(e) => updateItemList('stats', idx, { ...stat, lots: e.target.value })}
                                            />
                                            <input
                                                type="text"
                                                className="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="HOTS"
                                                value={stat.hots || ''}
                                                onChange={(e) => updateItemList('stats', idx, { ...stat, hots: e.target.value })}
                                            />
                                            <button
                                                type="button"
                                                onClick={() => removeItem('stats', idx)}
                                                className="text-red-500 hover:text-red-700 text-sm"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => addItem('stats', { label: '', lots: '', hots: '' })}
                                        className="mt-2 flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Tambah Baris
                                    </button>
                                </div>
                            </div>
                        )}

                        {sectionMeta[activeTab]?.hasMedia && (
                            <FileUploadField
                                id={`${activeTab}-media`}
                                label="Media"
                                previewUrl={form.data.content?.image?.original_url}
                                onChange={(file) => form.setData('media', file)}
                            />
                        )}
                    </div>
                </div>
            </div>
        </ContentManagementPage>
    );
}
