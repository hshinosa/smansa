// FILE: resources/js/Pages/Admin/Alumni/Index.jsx
// Fully responsive alumni management page with accent color theme and video support

import React, { useEffect, useMemo, useState, useCallback } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FileUploadField from '@/Components/Admin/FileUploadField';
import { Plus, Edit2, Trash2, X, User, GraduationCap, Star, Video, FileText, Image as ImageIcon, GripVertical } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import toast from 'react-hot-toast';

export default function Index({ alumnis }) {
    const { success } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);
    const [activeTab, setActiveTab] = useState('list');

    const { data, setData, post, delete: destroy, processing, errors, reset, transform } = useForm({
        name: '',
        graduation_year: new Date().getFullYear(),
        testimonial: '',
        content_type: 'text',
        image_url: '',
        testimonial_images: [],
        existing_testimonial_images: [],
        video_source: 'youtube',
        video_url: '',
        video_file: null,
        is_featured: false,
        is_published: true,
        sort_order: 0,
    });

    const tabs = [{ key: 'list', label: 'Daftar Alumni', description: 'Kelola data alumni.', icon: User }];

    const openModal = (alumni = null) => {
        if (alumni) {
            setEditMode(true);
            setCurrentId(alumni.id);
            setData({
                name: alumni.name || '',
                graduation_year: alumni.graduation_year || new Date().getFullYear(),
                testimonial: alumni.testimonial || '',
                content_type: alumni.content_type || 'text',
                image_url: alumni.image_url || '',
                testimonial_images: [],
                existing_testimonial_images: Array.isArray(alumni.testimonialImages)
                    ? alumni.testimonialImages.map((image) => image.original_url).filter(Boolean)
                    : [],
                video_source: alumni.video_source || 'youtube',
                video_url: alumni.video_url || '',
                video_file: null,
                is_featured: !!alumni.is_featured,
                is_published: !!alumni.is_published,
                sort_order: alumni.sort_order || 0
            });
        }
        else {
            setEditMode(false);
            setCurrentId(null);
            reset();
            setData((d) => ({
                ...d,
                testimonial_images: [],
                existing_testimonial_images: [],
            }));
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
        setData((d) => ({
            ...d,
            testimonial_images: [],
            existing_testimonial_images: [],
        }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            transform((data) => ({
                ...data,
                _method: 'PUT',
            }));
            post(route('admin.alumni.update', currentId), {
                forceFormData: true,
                onSuccess: () => {
                    closeModal();
                    toast.success('Data alumni berhasil diperbarui');
                },
                onError: (formErrors) => {
                    const firstError = Object.values(formErrors || {})[0];
                    toast.error(firstError || 'Gagal memperbarui data alumni');
                },
            });
        } else {
            transform((data) => data); // Reset transform or ensure no _method for create
            post(route('admin.alumni.store'), {
                forceFormData: true,
                onSuccess: () => {
                    closeModal();
                    toast.success('Alumni baru berhasil ditambahkan');
                },
                onError: (formErrors) => {
                    const firstError = Object.values(formErrors || {})[0];
                    toast.error(firstError || 'Gagal menambahkan alumni');
                },
            });
        }
    };

    const appendTestimonialImages = (files) => {
        if (!Array.isArray(files) || files.length === 0) return;
        setData((prev) => ({
            ...prev,
            testimonial_images: [...(prev.testimonial_images || []), ...files],
        }));
    };

    const newTestimonialImagePreviews = useMemo(
        () => (data.testimonial_images || []).map((file) => ({
            name: file.name,
            url: URL.createObjectURL(file),
        })),
        [data.testimonial_images]
    );

    useEffect(() => {
        return () => {
            newTestimonialImagePreviews.forEach((preview) => {
                URL.revokeObjectURL(preview.url);
            });
        };
    }, [newTestimonialImagePreviews]);

    // Drag and drop reordering state
    const [draggedIndex, setDraggedIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);

    const handleDragStart = useCallback((index, type) => {
        setDraggedIndex({ index, type });
    }, []);

    const handleDragOver = useCallback((e, index) => {
        e.preventDefault();
        setDragOverIndex(index);
    }, []);

    const handleDrop = useCallback((targetIndex, targetType) => {
        if (!draggedIndex) return;

        const sourceArray = draggedIndex.type === 'existing' ? 'existing_testimonial_images' : 'testimonial_images';
        const targetArray = targetType === 'existing' ? 'existing_testimonial_images' : 'testimonial_images';

        if (sourceArray === targetArray) {
            // Reorder within same array
            const items = [...(data[sourceArray] || [])];
            const [removed] = items.splice(draggedIndex.index, 1);
            items.splice(targetIndex, 0, removed);
            setData(sourceArray, items);
        } else {
            // Move between arrays - remove from source, add to target
            const sourceItems = [...(data[sourceArray] || [])];
            const targetItems = [...(data[targetArray] || [])];
            const [removed] = sourceItems.splice(draggedIndex.index, 1);
            targetItems.splice(targetIndex, 0, removed);
            setData(sourceArray, sourceItems);
            setData(targetArray, targetItems);
        }

        setDraggedIndex(null);
        setDragOverIndex(null);
    }, [draggedIndex, data, setData]);

    const handleDragEnd = useCallback(() => {
        setDraggedIndex(null);
        setDragOverIndex(null);
    }, []);

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus data alumni ini? Tindakan ini tidak dapat dibatalkan.')) {
            destroy(route('admin.alumni.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Data alumni berhasil dihapus')
            });
        }
    };

    return (
        <ContentManagementPage 
            headerTitle="Kelola Alumni" 
            headTitle="Jejak Alumni" 
            tabs={tabs} 
            activeTab={activeTab} 
            setActiveTab={setActiveTab} 
            noForm={true}
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {/* Header with Add Button */}
                <div className="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <h3 className="font-bold text-gray-900 text-lg">Daftar Alumni</h3>
                    <PrimaryButton type="button" onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2 px-4 py-2 text-sm font-medium w-full sm:w-auto justify-center">
                        <Plus size={18} />Tambah Alumni
                    </PrimaryButton>
                </div>
                {alumnis.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alumni</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Angkatan</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Featured</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {alumnis.map((alumni) => (
                                    <tr key={alumni.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            {(() => {
                                                const primaryImage = Array.isArray(alumni.testimonialImages) && alumni.testimonialImages.length > 0
                                                    ? alumni.testimonialImages[0]?.original_url
                                                    : alumni.image_url;
                                                return (
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex-shrink-0 border-2 border-gray-200">
                                                    {primaryImage ? (
                                                        <img
                                                            src={primaryImage}
                                                            alt={alumni.name}
                                                            className="w-full h-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center text-gray-400"><User size={18} /></div>
                                                    )}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-semibold text-gray-900 text-sm">{alumni.name}</p>
                                                        {alumni.content_type === 'video' && (
                                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                                                <Video size={10} /> {alumni.video_source === 'youtube' ? 'YouTube' : 'Upload'}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                                );
                                            })()}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap"><span className="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-accent-yellow/10 text-accent-yellow">{alumni.graduation_year}</span></td>
                                        <td className="px-4 py-3 text-center">{alumni.is_featured ? <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700"><Star size={12} fill="currentColor" /> Ya</span> : <span className="text-gray-300">-</span>}</td>
                                        <td className="px-4 py-3 text-right"><div className="flex items-center justify-end gap-1"><button onClick={() => openModal(alumni)} className="p-2 !text-accent-yellow hover:bg-accent-yellow/10 rounded-lg"><Edit2 size={16} /></button><button onClick={() => handleDelete(alumni.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg"><Trash2 size={16} /></button></div></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="p-8 sm:p-12 text-center"><div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4"><User size={24} className="sm:w-8 sm:h-8 text-gray-400" /></div><p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada alumni</p><PrimaryButton onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 text-sm sm:text-base px-4 py-2">Tambah Alumni</PrimaryButton></div>
                )}
            </div>
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-y-auto">
                    <div className="p-4 sm:p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10"><h3 className="text-lg sm:text-xl font-bold text-gray-900">{editMode ? 'Edit Alumni' : 'Tambah Alumni'}</h3><button onClick={closeModal} className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"><X size={20} /></button></div>
                    <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-6">
                        {/* Section 1: Tipe Konten */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                                    Tipe Konten
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Pilih format testimoni alumni</p>
                            </div>
                            <div className="flex gap-4">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="content_type"
                                        value="text"
                                        checked={data.content_type === 'text'}
                                        onChange={(e) => setData(d => ({ ...d, content_type: 'text', is_featured: false }))}
                                        className="w-4 h-4 text-accent-yellow focus:ring-accent-yellow"
                                    />
                                    <FileText size={16} className="text-gray-500" />
                                    <span className="text-sm text-gray-700">Testimoni (Teks)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="content_type"
                                        value="video"
                                        checked={data.content_type === 'video'}
                                        onChange={(e) => setData(d => ({ ...d, content_type: 'video', is_featured: true }))}
                                        className="w-4 h-4 text-accent-yellow focus:ring-accent-yellow"
                                    />
                                    <Video size={16} className="text-red-500" />
                                    <span className="text-sm text-gray-700">Video (Featured)</span>
                                </label>
                            </div>
                            <p className="text-sm text-gray-500">Video otomatis ditampilkan sebagai featured</p>
                        </div>

                        {/* Section 2: Data Alumni */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                    Data Alumni
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Informasi identitas alumni</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="name" value="Nama" />
                                    <TextInput id="name" type="text" className="mt-1 block w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} required placeholder="Nama..." />
                                    <p className="text-sm text-gray-500 mt-1">Nama lengkap alumni</p>
                                    <InputError message={errors.name} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="graduation_year" value="Tahun Lulus" />
                                    <TextInput id="graduation_year" type="number" className="mt-1 block w-full" value={data.graduation_year} onChange={(e) => setData('graduation_year', e.target.value)} />
                                    <p className="text-sm text-gray-500 mt-1">Angkatan kelulusan</p>
                                </div>
                            </div>
                        </div>

                        {/* Section 3: Konten Testimoni */}
                        {data.content_type === 'text' ? (
                            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                                <div className="border-b pb-3">
                                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span>
                                        Testimoni
                                    </h3>
                                    <p className="text-sm text-gray-600 mt-1">Cerita dan pengalaman alumni</p>
                                </div>
                                <div>
                                    <InputLabel htmlFor="testimonial" value="Testimoni" />
                                    <textarea id="testimonial" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" rows="3" value={data.testimonial} onChange={(e) => setData('testimonial', e.target.value)} required placeholder="Cerita alumni..."></textarea>
                                    <p className="text-sm text-gray-500 mt-1">Cerita singkat dari alumni</p>
                                    <InputError message={errors.testimonial} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="testimonial_images" value="Galeri Foto Testimoni" />
                                    <p className="text-sm text-gray-500 mt-1">Upload beberapa foto untuk ditampilkan sebagai carousel di modal testimoni (maksimal 10 foto).</p>

                                    <label
                                        htmlFor="testimonial_images"
                                        className="mt-3 flex h-32 w-full cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-accent-yellow hover:bg-yellow-50/40"
                                    >
                                        <ImageIcon size={28} className="text-gray-400 mb-2" />
                                        <span className="text-sm font-medium text-gray-600">Klik atau drag untuk upload foto</span>
                                        <span className="text-xs text-gray-400 mt-1">JPEG, PNG, WebP · maks 10MB/foto</span>
                                        <input
                                            id="testimonial_images"
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            className="hidden"
                                            onChange={(e) => {
                                                if (e.target.files && e.target.files.length > 0) {
                                                    appendTestimonialImages(Array.from(e.target.files));
                                                    e.target.value = '';
                                                }
                                            }}
                                        />
                                    </label>

                                    {(data.existing_testimonial_images?.length > 0 || data.testimonial_images?.length > 0) && (
                                        <div className="mt-4 space-y-2">
                                            <p className="text-xs text-gray-500 italic">* Geser gambar untuk mengubah urutan tampil di carousel</p>
                                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                                {(data.existing_testimonial_images || []).map((img, idx) => (
                                                    <div
                                                        key={`existing-${idx}`}
                                                        draggable
                                                        onDragStart={(e) => handleDragStart(idx, 'existing')}
                                                        onDragOver={(e) => handleDragOver(e, idx)}
                                                        onDrop={() => handleDrop(idx, 'existing')}
                                                        onDragEnd={handleDragEnd}
                                                        className={`relative overflow-hidden rounded-lg border-2 transition-all cursor-move ${
                                                            dragOverIndex === idx && draggedIndex?.type === 'existing' ? 'border-primary border-dashed scale-105' : 'border-gray-200'
                                                        } ${draggedIndex?.index === idx && draggedIndex?.type === 'existing' ? 'opacity-50' : ''}`}
                                                    >
                                                        <div className="absolute top-0 left-0 z-10 p-1">
                                                            <GripVertical size={14} className="text-gray-400" />
                                                        </div>
                                                        <img src={img} alt={`Existing testimonial ${idx + 1}`} className="h-24 w-full object-cover" />
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setData('existing_testimonial_images', (data.existing_testimonial_images || []).filter((_, i) => i !== idx));
                                                            }}
                                                            className="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-red-600 shadow"
                                                            aria-label="Hapus gambar yang sudah ada"
                                                        >
                                                            <X size={12} />
                                                        </button>
                                                        <span className="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 py-0.5 text-[10px] text-white">{idx + 1}</span>
                                                    </div>
                                                ))}

                                                {newTestimonialImagePreviews.map((preview, idx) => (
                                                    <div
                                                        key={`new-${idx}`}
                                                        draggable
                                                        onDragStart={(e) => handleDragStart(idx, 'new')}
                                                        onDragOver={(e) => handleDragOver(e, idx)}
                                                        onDrop={() => handleDrop(idx, 'new')}
                                                        onDragEnd={handleDragEnd}
                                                        className={`relative overflow-hidden rounded-lg border-2 border-primary/20 transition-all cursor-move ${
                                                            dragOverIndex === idx && draggedIndex?.type === 'new' ? 'border-primary border-dashed scale-105' : ''
                                                        } ${draggedIndex?.index === idx && draggedIndex?.type === 'new' ? 'opacity-50' : ''}`}
                                                    >
                                                        <div className="absolute top-0 left-0 z-10 p-1">
                                                            <GripVertical size={14} className="text-primary" />
                                                        </div>
                                                        <img src={preview.url} alt={preview.name} className="h-24 w-full object-cover" />
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setData('testimonial_images', (data.testimonial_images || []).filter((_, i) => i !== idx));
                                                            }}
                                                            className="absolute right-1 top-1 rounded-full bg-white/90 p-1 text-red-600 shadow"
                                                            aria-label="Hapus gambar baru"
                                                        >
                                                            <X size={12} />
                                                        </button>
                                                        <span className="absolute bottom-1 left-1 rounded bg-primary/80 px-1.5 py-0.5 text-[10px] text-white">
                                                            Baru {idx + 1}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    <InputError message={errors.testimonial_images} className="mt-2" />
                                    <InputError message={errors['testimonial_images.0']} className="mt-2" />
                                </div>
                            </div>
                        ) : (
                            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                                <div className="border-b pb-3">
                                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span>
                                        Video Testimoni
                                    </h3>
                                    <p className="text-sm text-gray-600 mt-1">Upload video atau masukkan link YouTube</p>
                                </div>
                                <div>
                                    <InputLabel value="Sumber Video" />
                                    <div className="mt-2 flex gap-4">
                                        <label className="flex items-center gap-2 cursor-pointer bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-100">
                                            <input
                                                type="radio"
                                                name="video_source"
                                                value="youtube"
                                                checked={data.video_source === 'youtube'}
                                                onChange={(e) => setData('video_source', e.target.value)}
                                                className="w-4 h-4 text-accent-yellow focus:ring-accent-yellow"
                                            />
                                            <span className="text-sm text-gray-700">YouTube</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-100">
                                            <input
                                                type="radio"
                                                name="video_source"
                                                value="upload"
                                                checked={data.video_source === 'upload'}
                                                onChange={(e) => setData('video_source', e.target.value)}
                                                className="w-4 h-4 text-accent-yellow focus:ring-accent-yellow"
                                            />
                                            <span className="text-sm text-gray-700">Upload File</span>
                                        </label>
                                    </div>
                                </div>

                                {data.video_source === 'youtube' ? (
                                    <div>
                                        <InputLabel htmlFor="video_url" value="URL Video YouTube" />
                                        <TextInput
                                            id="video_url"
                                            type="url"
                                            className="mt-1 block w-full"
                                            value={data.video_url}
                                            onChange={(e) => setData('video_url', e.target.value)}
                                            placeholder="https://youtube.com/watch?v=..."
                                        />
                                        <p className="text-sm text-gray-500 mt-1">Salin link video dari YouTube</p>
                                        <InputError message={errors.video_url} className="mt-2" />
                                    </div>
                                ) : (
                                    <div>
                                        <InputLabel htmlFor="video_file" value="File Video" />
                                        <FileUploadField
                                            id="video_file"
                                            label="Upload Video"
                                            accept="video/*"
                                            fileType="video"
                                            onChange={(file) => setData('video_file', file)}
                                            previewUrl={getImageUrl(data.video_url)}
                                            error={errors.video_file}
                                            description="Format: MP4, WebM. Maksimal 50MB."
                                        />
                                    </div>
                                )}
                            </div>
                        )}
                        
                        {/* Section 4: Pengaturan */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">4</span>
                                    Pengaturan
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Status publikasi</p>
                            </div>
                            <div className="flex flex-wrap gap-4 sm:gap-6">
                                <label className="flex items-center gap-2">
                                    <input type="checkbox" id="is_published" checked={data.is_published} onChange={(e) => setData('is_published', e.target.checked)} className="w-4 h-4 rounded border-gray-300 text-accent-yellow focus:ring-accent-yellow" />
                                    <span className="text-sm text-gray-700">Publish</span>
                                </label>
                            </div>
                            <p className="text-sm text-gray-500">Centang untuk menampilkan di website</p>
                        </div>
                        
                        <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                            <button type="button" onClick={closeModal} className="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">Batal</button>
                            <PrimaryButton type="submit" disabled={processing} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 px-5 py-2">{processing ? '...' : (editMode ? 'Simpan' : 'Tambah')}</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </ContentManagementPage>
    );
}
