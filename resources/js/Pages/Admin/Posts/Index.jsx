// FILE: resources/js/Pages/Admin\Posts/Index.jsx
// Fully responsive posts management page with accent color theme

import React, { useState } from 'react';
import { Head, useForm, usePage, Link, router } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FileUploadField from '@/Components/Admin/FileUploadField';
import MiniTextEditor from '@/Components/MiniTextEditor';
import { Plus, Edit2, Trash2, X, Eye, Newspaper, ExternalLink, ChevronLeft, ChevronRight, Upload, Image as ImageIcon } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import toast from 'react-hot-toast';

export default function Index({ posts }) {
    const { success } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);
    const [activeTab, setActiveTab] = useState('list');
    const [isDraggingImage, setIsDraggingImage] = useState(false);
    
    // Pagination state
    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 7;

    // Pagination logic
    const totalPages = Math.ceil(posts.length / itemsPerPage);
    const paginatedPosts = posts.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        title: '', 
        category: 'Berita', 
        content: '', 
        excerpt: '', 
        featured_image: null, 
        featured_image_url: '', 
        images: [], // For multiple images from Instagram
        status: 'published',
    });

    const tabs = [{ key: 'list', label: 'Berita & Pengumuman', description: 'Kelola konten berita.', icon: Newspaper }];

    const openModal = (postItem = null) => {
        if (postItem) { 
            setEditMode(true); 
            setCurrentId(postItem.id); 
            
            // Handle multiple images from Instagram or other sources
            let additionalImages = [];
            
            // 1. Existing Gallery
            if (postItem.gallery && Array.isArray(postItem.gallery)) {
                additionalImages = postItem.gallery.map(media => media.original_url);
            }
            
            // 2. Potentially missing scraped images
            if (postItem.scraped_images && Array.isArray(postItem.scraped_images)) {
                // Merge scraped images avoiding duplicates by filename
                const existingFilenames = additionalImages.map(url => url ? url.split('/').pop() : '');
                
                postItem.scraped_images.forEach(scrapedUrl => {
                    const filename = scrapedUrl.split('/').pop();
                    if (!existingFilenames.some(f => f.includes(filename) || filename.includes(f))) {
                        additionalImages.push(scrapedUrl);
                    }
                });
            } else if (additionalImages.length === 0 && postItem.images && Array.isArray(postItem.images)) {
                 // Fallback if no gallery or scraped_images (legacy or manual props)
                additionalImages = postItem.images;
            }

            setData({ 
                title: postItem.title || '', 
                category: postItem.category || 'Berita', 
                content: postItem.content || '', 
                excerpt: postItem.excerpt || '', 
                featured_image: null, 
                featured_image_url: postItem.featuredImage?.original_url || postItem.featured_image || '', 
                images: additionalImages,
                status: postItem.status || 'published' 
            }); 
        }
        else { setEditMode(false); setCurrentId(null); reset(); }
        setIsModalOpen(true);
    };

    const closeModal = () => { setIsModalOpen(false); reset(); };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // DEBUG: Log what's being sent
        console.log('[PostSubmit] Form data before submit:', {
            title: data.title,
            titleLength: data.title?.length,
            content: data.content?.substring(0, 100),
            contentLength: data.content?.length,
            category: data.category,
            status: data.status,
            excerpt: data.excerpt?.substring(0, 50),
            featured_image: data.featured_image,
            images: data.images?.map(img => img instanceof File ? `File: ${img.name}` : (typeof img === 'string' ? img.substring(0, 50) : img)),
            imagesCount: data.images?.length,
        });
        
        // BUG-3 Fix: Validate file before submit
        if (data.featured_image instanceof File) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (data.featured_image.size > maxSize) {
                toast.error('Ukuran file maksimal 10MB');
                return;
            }
            
            if (!allowedTypes.includes(data.featured_image.type)) {
                toast.error('Format file harus JPG, PNG, GIF, atau WebP');
                return;
            }
        }
        
        // Check if any images are File objects (for new uploads)
        const hasFileInImages = data.images && data.images.some(img => img instanceof File);
        const needsFormData = data.featured_image instanceof File || hasFileInImages;
        
        console.log('[PostSubmit] needsFormData:', needsFormData, 'hasFileInImages:', hasFileInImages);
        
        if (editMode) {
            // BUG FIX: When using FormData with files, we need to use router.post with _method spoofing
            // because PUT with FormData doesn't always serialize correctly in Inertia
            if (needsFormData) {
                // Prepare data with separate existing_images and new_images
                const existingImages = [];
                const newImages = [];
                
                if (data.images && data.images.length > 0) {
                    data.images.forEach((img) => {
                        if (img instanceof File) {
                            newImages.push(img);
                        } else if (typeof img === 'string') {
                            existingImages.push(img);
                        }
                    });
                }
                
                console.log('[PostSubmit] Using router.post with _method PUT for edit with files');
                console.log('[PostSubmit] existingImages:', existingImages.length, 'newImages:', newImages.length);
                
                router.post(route('admin.posts.update', currentId), {
                    _method: 'PUT',
                    title: data.title,
                    category: data.category,
                    content: data.content,
                    excerpt: data.excerpt,
                    status: data.status,
                    featured_image: data.featured_image instanceof File ? data.featured_image : null,
                    existing_images: JSON.stringify(existingImages),
                    new_images: newImages,
                }, {
                    forceFormData: true,
                    preserveState: true,
                    onSuccess: () => {
                        reset();
                        setIsModalOpen(false);
                        setEditMode(false);
                        setCurrentId(null);
                        toast.success('Berita berhasil diperbarui');
                    },
                });
            } else {
                // No files, use regular put()
                put(route('admin.posts.update', currentId), {
                    preserveState: true,
                    onSuccess: () => {
                        reset();
                        setIsModalOpen(false);
                        setEditMode(false);
                        setCurrentId(null);
                        toast.success('Berita berhasil diperbarui');
                    },
                });
            }
        } else {
            post(route('admin.posts.store'), {
                forceFormData: needsFormData,
                onSuccess: () => {
                    reset(); // Reset form first
                    setIsModalOpen(false); // Then close modal
                    toast.success('Berita baru berhasil dipublikasikan');
                }
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus berita ini? Tindakan ini tidak dapat dibatalkan.')) {
            destroy(route('admin.posts.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Berita berhasil dihapus')
            });
        }
    };

    const customAction = (
        <button
            onClick={() => openModal()}
            className="flex items-center gap-2 px-4 py-2 !bg-accent-yellow !text-gray-900 rounded-lg hover:!bg-yellow-500 transition-colors text-sm font-bold shadow-sm"
        >
            <Plus size={18} />
            Tulis Berita
        </button>
    );

    return (
        <ContentManagementPage
            headerTitle="Kelola Berita"
            headTitle="Berita & Pengumuman"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            noForm={true}
            customAction={customAction}
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {posts.length > 0 ? (
                    <>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50">
                                    <tr><th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Gambar</th><th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul</th><th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Kategori</th><th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th><th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th></tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {paginatedPosts.map((postItem) => (
                                        <tr key={postItem.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 whitespace-nowrap"><div className="h-12 w-16 rounded-lg overflow-hidden bg-gray-100">{(postItem.featuredImage?.original_url || postItem.featured_image) ? <img src={postItem.featuredImage?.original_url || postItem.featured_image} alt={postItem.title} className="w-full h-full object-cover" /> : <div className="w-full h-full flex items-center justify-center"><Newspaper className="text-gray-400 w-6 h-6" /></div>}</div></td>
                                            <td className="px-4 py-3"><p className="font-semibold text-gray-900 text-sm truncate max-w-[120px] sm:max-w-none">{postItem.title}</p><p className="text-xs text-gray-500 font-mono hidden sm:block">{postItem.slug}</p></td>
                                            <td className="px-4 py-3 hidden md:table-cell"><span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{postItem.category}</span></td>
                                            <td className="px-4 py-3"><span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${postItem.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>{postItem.status === 'published' ? 'Publis' : 'Draft'}</span></td>
                                            <td className="px-4 py-3 text-right"><div className="flex items-center justify-end gap-1"><Link href={`/berita/${postItem.slug}`} target="_blank" className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"><ExternalLink size={16} /></Link><button onClick={() => openModal(postItem)} className="p-2 !text-accent-yellow hover:bg-accent-yellow/10 rounded-lg"><Edit2 size={16} /></button><button onClick={() => handleDelete(postItem.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg"><Trash2 size={16} /></button></div></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Controls */}
                        {totalPages > 1 && (
                            <div className="flex items-center justify-between px-4 py-3 sm:px-6 border-t border-gray-200 bg-gray-50">
                                <div className="flex justify-between w-full sm:hidden">
                                    <button
                                        onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                                        disabled={currentPage === 1}
                                        className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                                        disabled={currentPage === totalPages}
                                        className="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Next
                                    </button>
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{(currentPage - 1) * itemsPerPage + 1}</span> to <span className="font-medium">{Math.min(currentPage * itemsPerPage, posts.length)}</span> of <span className="font-medium">{posts.length}</span> results
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <button
                                                onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                                                disabled={currentPage === 1}
                                                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <span className="sr-only">Previous</span>
                                                <ChevronLeft className="h-5 w-5" aria-hidden="true" />
                                            </button>
                                            
                                            {[...Array(totalPages)].map((_, i) => (
                                                <button
                                                    key={i}
                                                    onClick={() => setCurrentPage(i + 1)}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        currentPage === i + 1
                                                            ? 'z-10 bg-accent-yellow border-accent-yellow text-gray-900'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    {i + 1}
                                                </button>
                                            ))}

                                            <button
                                                onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                                                disabled={currentPage === totalPages}
                                                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <span className="sr-only">Next</span>
                                                <ChevronRight className="h-5 w-5" aria-hidden="true" />
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="p-8 sm:p-12 text-center">
                        <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <Newspaper size={24} className="sm:w-8 sm:h-8 text-gray-400" />
                        </div>
                        <p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada berita</p>
                        <button
                            onClick={() => openModal()}
                            className="flex items-center gap-2 px-4 py-2 !bg-accent-yellow !text-gray-900 rounded-lg hover:!bg-yellow-500 transition-colors text-sm font-medium mx-auto"
                        >
                            <Plus size={18} />
                            Tulis Berita
                        </button>
                    </div>
                )}
            </div>
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="6xl">
                <div className="bg-white rounded-xl sm:rounded-2xl shadow-xl max-w-6xl w-full mx-auto max-h-[95vh] overflow-y-auto">
                    <div className="p-4 sm:p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10"><div><h3 className="text-lg sm:text-xl font-bold text-gray-900">{editMode ? 'Edit Berita' : 'Berita Baru'}</h3><p className="text-xs sm:text-sm text-gray-500 mt-0.5">{editMode ? 'Perbarui' : 'Buat baru'}</p></div><button onClick={closeModal} className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"><X size={20} /></button></div>
                    <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-6">
                        {/* Section 1: Informasi Berita */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                                    Informasi Berita
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Detail utama berita atau pengumuman</p>
                            </div>
                            <div>
                                <InputLabel htmlFor="title" value="Judul" />
                                <TextInput id="title" type="text" className="mt-1 block w-full" value={data.title} onChange={(e) => setData('title', e.target.value)} required placeholder="Judul..." />
                                <p className="text-sm text-gray-500 mt-1">Buat judul yang menarik dan informatif</p>
                                <InputError message={errors.title} className="mt-2" />
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel htmlFor="category" value="Kategori" />
                                    <select id="category" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" value={data.category} onChange={(e) => setData('category', e.target.value)}>
                                        <option value="Berita">Berita</option>
                                        <option value="Pengumuman">Pengumuman</option>
                                        <option value="Prestasi">Prestasi</option>
                                        <option value="Akademik">Akademik</option>
                                        <option value="Kegiatan">Kegiatan</option>
                                    </select>
                                    <p className="text-sm text-gray-500 mt-1">Kategori konten</p>
                                </div>
                                <div>
                                    <InputLabel htmlFor="status" value="Status" />
                                    <select id="status" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" value={data.status} onChange={(e) => setData('status', e.target.value)}>
                                        <option value="published">Publis</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                    <p className="text-sm text-gray-500 mt-1">Status publikasi</p>
                                </div>
                            </div>
                            <div>
                                <InputLabel htmlFor="excerpt" value="Ringkasan" />
                                <textarea id="excerpt" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" rows="3" value={data.excerpt} onChange={(e) => setData('excerpt', e.target.value)} required></textarea>
                                <p className="text-sm text-gray-500 mt-1">Ringkasan singkat yang muncul di daftar berita</p>
                                <InputError message={errors.excerpt} className="mt-2" />
                            </div>
                        </div>

                        {/* Section 2: Gambar Berita */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                    Media Berita
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Upload atau tinjau gambar untuk berita ini</p>
                            </div>
                            
                            <div>
                                <InputLabel value="Gambar Utama (Thumbnail)" className="mb-2" />
                                <FileUploadField 
                                    id="featured_image" 
                                    onChange={(file) => setData('featured_image', file)} 
                                    previewUrl={data.featured_image_url} 
                                    label="Upload Thumbnail" 
                                />
                            </div>

                            {data.images && data.images.length > 0 && (
                                <div className="mt-4 pt-4 border-t border-gray-100">
                                    <InputLabel value={`Gambar Tambahan (${data.images.length})`} className="mb-2" />
                                    <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3">
                                        {data.images.map((img, idx) => (
                                            <div key={idx} className="relative group aspect-square rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                                                <img
                                                    src={typeof img === 'string' ? img : URL.createObjectURL(img)}
                                                    alt={`Gallery ${idx}`}
                                                    className="w-full h-full object-cover"
                                                />
                                                <div className="absolute top-1 right-1 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            const newImages = [...data.images];
                                                            newImages.splice(idx, 1);
                                                            setData('images', newImages);
                                                        }}
                                                        className="p-1 bg-white/90 text-red-600 rounded-full hover:bg-white shadow-sm border border-gray-200"
                                                    >
                                                        <X size={14} />
                                                    </button>
                                                </div>
                                                {/* Status Label (Existing vs New) */}
                                                <div className="absolute bottom-1 right-1 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded-full font-mono backdrop-blur-sm">
                                                    {typeof img === 'string' && img.includes('scraped-images') ? 'IG' : (typeof img === 'string' ? 'Saved' : 'New')}
                                                </div>
                                            </div>
                                        ))}
                                        {/* Compact Add Image Card */}
                                        <label
                                            htmlFor="additional_images_grid"
                                            className={`relative aspect-square rounded-lg border-2 border-dashed cursor-pointer flex flex-col items-center justify-center transition-all ${
                                                isDraggingImage 
                                                    ? 'border-primary bg-primary/5 scale-105' 
                                                    : 'border-gray-300 hover:border-primary hover:bg-gray-50'
                                            }`}
                                            onDragOver={(e) => { e.preventDefault(); setIsDraggingImage(true); }}
                                            onDragLeave={() => setIsDraggingImage(false)}
                                            onDrop={(e) => {
                                                e.preventDefault();
                                                setIsDraggingImage(false);
                                                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                                    const newFiles = Array.from(e.dataTransfer.files);
                                                    setData('images', [...(data.images || []), ...newFiles]);
                                                }
                                            }}
                                        >
                                            <Plus size={24} className="text-gray-400 mb-1" />
                                            <span className="text-[10px] text-gray-500 font-medium text-center px-1">Tambah Gambar</span>
                                            <input
                                                id="additional_images_grid"
                                                type="file"
                                                accept="image/*"
                                                multiple
                                                className="hidden"
                                                onChange={(e) => {
                                                    if (e.target.files && e.target.files.length > 0) {
                                                        const newFiles = Array.from(e.target.files);
                                                        setData('images', [...(data.images || []), ...newFiles]);
                                                    }
                                                }}
                                            />
                                        </label>
                                    </div>
                                    <p className="text-[10px] text-gray-500 mt-2 italic">* Media yang ditarik otomatis dari Instagram atau upload manual.</p>
                                </div>
                            )}

                            {(!data.images || data.images.length === 0) && (
                                <div className="mt-4 pt-4 border-t border-gray-100">
                                    <InputLabel value="Gambar Tambahan" className="mb-2" />
                                    <label
                                        htmlFor="additional_images_empty"
                                        className={`relative flex flex-col items-center justify-center w-full h-32 rounded-xl border-2 border-dashed cursor-pointer transition-all ${
                                            isDraggingImage 
                                                ? 'border-primary bg-primary/5' 
                                                : 'border-gray-300 hover:border-primary hover:bg-gray-50'
                                        }`}
                                        onDragOver={(e) => { e.preventDefault(); setIsDraggingImage(true); }}
                                        onDragLeave={() => setIsDraggingImage(false)}
                                        onDrop={(e) => {
                                            e.preventDefault();
                                            setIsDraggingImage(false);
                                            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                                                const newFiles = Array.from(e.dataTransfer.files);
                                                setData('images', [...(data.images || []), ...newFiles]);
                                            }
                                        }}
                                    >
                                        <ImageIcon size={32} className="text-gray-400 mb-2" />
                                        <span className="text-sm text-gray-500 font-medium">Klik atau drag gambar ke sini</span>
                                        <span className="text-xs text-gray-400 mt-1">Format: JPEG, PNG, GIF, WebP</span>
                                        <input
                                            id="additional_images_empty"
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            className="hidden"
                                            onChange={(e) => {
                                                if (e.target.files && e.target.files.length > 0) {
                                                    const newFiles = Array.from(e.target.files);
                                                    setData('images', [...(data.images || []), ...newFiles]);
                                                }
                                            }}
                                        />
                                    </label>
                                </div>
                            )}
                        </div>

                        {/* Section 3: Konten Berita */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span>
                                    Konten Berita
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Isi lengkap berita atau pengumuman</p>
                            </div>
                            <MiniTextEditor value={data.content} onChange={(val) => setData('content', val)} />
                            <p className="text-sm text-gray-500 mt-1">Gunakan editor untuk format teks, gambar, dan link</p>
                            <InputError message={errors.content} className="mt-2" />
                        </div>


                        <div className="flex flex-col sm:flex-row justify-end gap-3 mt-6 pt-4 border-t border-gray-200"><button type="button" onClick={closeModal} className="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">Batal</button><PrimaryButton type="submit" disabled={processing} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 px-5 py-2">{processing ? '...' : (editMode ? 'Simpan' : 'Publikasikan')}</PrimaryButton></div>
                    </form>
                </div>
            </Modal>
        </ContentManagementPage>
    );
}
