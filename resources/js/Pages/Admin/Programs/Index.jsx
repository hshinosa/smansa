// FILE: resources/js/Pages/Admin/Programs/Index.jsx
// Fully responsive programs management page with accent color theme

import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FileUploadField from '@/Components/Admin/FileUploadField';
import { Plus, Edit2, Trash2, X, Grid, Star } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import toast from 'react-hot-toast';
import { getImageUrl } from '@/Utils/imageUtils';

export default function Index({ programs }) {
    const { success } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);
    const [activeTab, setActiveTab] = useState('list');
    const [previewUrl, setPreviewUrl] = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        title: '', category: '', description: '', link: '', image: null, image_url: '', sort_order: 0, is_featured: true,
    });

    // BUG-8 Fix: Cleanup URL.createObjectURL to prevent memory leak
    useEffect(() => {
        return () => {
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    // Update preview URL when image changes
    useEffect(() => {
        if (data.image instanceof File) {
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
            const newPreviewUrl = URL.createObjectURL(data.image);
            setPreviewUrl(newPreviewUrl);
        } else if (data.image_url) {
            setPreviewUrl(data.image_url);
        } else {
            setPreviewUrl(null);
        }
    }, [data.image, data.image_url]);

    // BUG-8 Fix: Cleanup URL.createObjectURL to prevent memory leak
    useEffect(() => {
        return () => {
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    // Update preview URL when image changes
    useEffect(() => {
        if (data.image instanceof File) {
            // Revoke old preview URL
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
            // Create new preview URL
            const newPreviewUrl = URL.createObjectURL(data.image);
            setPreviewUrl(newPreviewUrl);
        } else if (data.image_url) {
            setPreviewUrl(data.image_url);
        } else {
            setPreviewUrl(null);
        }
    }, [data.image, data.image_url]);

    const tabs = [{ key: 'list', label: 'Program Sekolah', description: 'Kelola program unggulan sekolah.', icon: Grid }];

    const openModal = (program = null) => {
        if (program) { 
            setEditMode(true); 
            setCurrentId(program.id); 
            setData({ 
                title: program.title || '', 
                category: program.category || '', 
                description: program.description || '', 
                link: program.link || '', 
                image: null, 
                image_url: program.image_url || '', 
                sort_order: program.sort_order || 0, 
                is_featured: !!program.is_featured 
            }); 
        } else { 
            setEditMode(false); 
            setCurrentId(null); 
            reset();
        }
        setIsModalOpen(true);
    };

    const closeModal = () => { setIsModalOpen(false); reset(); };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // BUG-3 Fix: Validate file before submit
        if (data.image instanceof File) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (data.image.size > maxSize) {
                toast.error('Ukuran gambar maksimal 10MB');
                return;
            }
            
            if (!allowedTypes.includes(data.image.type)) {
                toast.error('Format gambar harus JPG, PNG, GIF, atau WebP');
                return;
            }
        }
        
        const needsFormData = data.image instanceof File;
        if (editMode) {
            put(route('admin.programs.update', currentId), {
                forceFormData: needsFormData,
                preserveState: true,
                onSuccess: () => {
                    reset(); // BUG-2 Fix: Reset form first
                    setIsModalOpen(false); // Then close modal
                    setEditMode(false);
                    setCurrentId(null);
                    toast.success('Program berhasil diperbarui');
                },
            });
        } else {
            post(route('admin.programs.store'), {
                onSuccess: () => {
                    reset(); // BUG-2 Fix: Reset form first
                    setIsModalOpen(false); // Then close modal
                    toast.success('Program baru berhasil ditambahkan');
                }
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus program ini? Tindakan ini tidak dapat dibatalkan.')) {
            destroy(route('admin.programs.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Program berhasil dihapus')
            });
        }
    };

    return (
        <ContentManagementPage 
            headerTitle="Kelola Program Sekolah" 
            headTitle="Program Sekolah" 
            tabs={tabs} 
            activeTab={activeTab} 
            setActiveTab={setActiveTab} 
            noForm={true}
            extraHeader={<div className="flex justify-end"><PrimaryButton type="button" onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2 px-4 py-2 text-sm sm:text-base"><Plus size={18} /><span className="hidden xs:inline">Tambah</span> Program</PrimaryButton></div>}
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {programs.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Program</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Kategori</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Featured</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {programs.map((program) => (
                                    <tr key={program.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 sm:py-4">
                                            <div className="flex items-center gap-3">
                                                <div className={`w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center flex-shrink-0 ${program.color_class || 'bg-gray-100 text-gray-600'}`}>
                                                    {program.image_url ? <img src={getImageUrl(program.image_url)} alt={program.title} className="w-full h-full object-cover rounded-lg" /> : <Star size={20} className="sm:w-6 sm:h-6" />}
                                                </div>
                                                <div className="min-w-0 flex-1"><p className="font-semibold text-gray-900 text-sm sm:text-base truncate">{program.title}</p>{program.description && <p className="text-xs text-gray-500 line-clamp-1 mt-0.5 hidden sm:block">{program.description}</p>}</div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 hidden sm:table-cell"><span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{program.category}</span></td>
                                        <td className="px-4 py-3 text-center">
                                            {program.is_featured ? <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"><Star size={12} className="mr-1" /> Ya</span> : <span className="text-gray-300">-</span>}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <button onClick={() => openModal(program)} className="p-2 !text-accent-yellow hover:bg-accent-yellow/10 rounded-lg transition-colors"><Edit2 size={16} /></button>
                                                <button onClick={() => handleDelete(program.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"><Trash2 size={16} /></button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="p-8 sm:p-12 text-center">
                        <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4"><Grid size={24} className="sm:w-8 sm:h-8 text-gray-400" /></div>
                        <p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada program</p>
                        <PrimaryButton onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 text-sm sm:text-base px-4 py-2">Tambah Program</PrimaryButton>
                    </div>
                )}
            </div>
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-y-auto">
                    <div className="p-4 sm:p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h3 className="text-lg sm:text-xl font-bold text-gray-900">{editMode ? 'Edit Program' : 'Tambah Program'}</h3>
                        <button onClick={closeModal} className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"><X size={20} /></button>
                    </div>
                    <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><InputLabel htmlFor="title" value="Judul" /><TextInput id="title" type="text" className="mt-1 block w-full" value={data.title} onChange={(e) => setData('title', e.target.value)} required placeholder="Nama program..." /><InputError message={errors.title} className="mt-2" /></div>
                            <div><InputLabel htmlFor="category" value="Kategori" /><TextInput id="category" type="text" className="mt-1 block w-full" value={data.category} onChange={(e) => setData('category', e.target.value)} required placeholder="Kategori..." /><InputError message={errors.category} className="mt-2" /></div>
                        </div>
                        <div><InputLabel htmlFor="description" value="Deskripsi" /><textarea id="description" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" rows="3" value={data.description} onChange={(e) => setData('description', e.target.value)} required></textarea><InputError message={errors.description} className="mt-2" /></div>
                        <div><InputLabel htmlFor="link" value="Link Halaman (Opsional)" /><TextInput id="link" type="text" className="mt-1 block w-full" value={data.link} onChange={(e) => setData('link', e.target.value)} placeholder="/akademik/..." /></div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><InputLabel value="Gambar (Opsional)" /><FileUploadField id="program_image" label="Gambar" previewUrl={previewUrl || ''} onChange={(file) => setData('image', file)} error={errors.image} /></div>
                            <div className="space-y-4"><div><InputLabel htmlFor="sort_order" value="Urutan" /><TextInput id="sort_order" type="number" className="mt-1 block w-full max-w-[100px]" value={data.sort_order} onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)} min="0" /></div><label className="flex items-center gap-2"><input type="checkbox" id="is_featured" checked={data.is_featured} onChange={(e) => setData('is_featured', e.target.checked)} className="w-4 h-4 rounded border-gray-300 text-accent-yellow focus:ring-accent-yellow" /><span className="text-sm text-gray-700">Tampilkan di Landing Page</span></label></div>
                        </div>
                        <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200"><button type="button" onClick={closeModal} className="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">Batal</button><PrimaryButton type="submit" disabled={processing} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 px-5 py-2">{processing ? '...' : (editMode ? 'Simpan' : 'Tambah')}</PrimaryButton></div>
                    </form>
                </div>
            </Modal>
        </ContentManagementPage>
    );
}
