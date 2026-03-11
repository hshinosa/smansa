// FILE: resources/js/Pages/Admin/Galleries/Index.jsx
// Fully responsive galleries management page with accent color theme

import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FileUploadField from '@/Components/Admin/FileUploadField';
import { Plus, Edit2, Trash2, X, Image as ImageIcon, Video, Star } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import toast from 'react-hot-toast';
import { getImageUrl } from '@/Utils/imageUtils';

const GalleryMedia = ({ item }) => {
    const [imgSrc, setImgSrc] = useState('');
    const [hasError, setHasError] = useState(false);

    // Determine initial image source
    useEffect(() => {
        let source = '';
        const url = getImageUrl(item.url);

        if (item.type === 'photo') {
            source = url;
        } else if (item.type === 'video') {
            // YouTube
            const youtubeMatch = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
            if (youtubeMatch) {
                // Use maxresdefault for better quality
                source = `https://img.youtube.com/vi/${youtubeMatch[1]}/maxresdefault.jpg`;
            } else {
                source = url; // for video tag or fallback img
            }
        }
        setImgSrc(source);
    }, [item]);

    const handleError = (e) => {
        if (!hasError) {
            // For YouTube thumbnails, try hqdefault as fallback
            if (imgSrc && imgSrc.includes('maxresdefault.jpg')) {
                const hqdefaultSrc = imgSrc.replace('maxresdefault.jpg', 'hqdefault.jpg');
                setImgSrc(hqdefaultSrc);
                setHasError(true);
                return;
            }
            
            // Fallback to a placeholder
            setHasError(true);
        }
    };

    // For photos & YouTube thumbs (rendered as img)
    const isYoutubeThumb = item.type === 'video' && imgSrc && imgSrc.includes('img.youtube.com');

    if (item.type === 'photo' || isYoutubeThumb) {
        return (
            <img 
                src={imgSrc} 
                alt={item.title} 
                className="w-full h-full object-cover" 
                loading="lazy"
                onError={handleError}
                style={{ objectPosition: 'center' }}
            />
        );
    }

    // For uploaded videos
    if (item.type === 'video') {
        const url = getImageUrl(item.url);
        // Safety check: if the URL points to an image file (wrong data type in DB), render as image
        const isImageFile = url.match(/\.(jpeg|jpg|png|gif|webp)$/i);
        
        if (isImageFile) {
            return (
                <img 
                    src={url} 
                    alt={item.title} 
                    className="w-full h-full object-cover" 
                    loading="lazy"
                    style={{ objectPosition: 'center' }}
                />
            );
        }

        return (
            <div className="w-full h-full bg-slate-100 relative overflow-hidden flex items-center justify-center group-hover:bg-slate-200 transition-colors">
                 {/* Fallback Icon - always visible underneath */}
                 <div className="absolute inset-0 flex flex-col items-center justify-center text-slate-400 gap-2">
                    <Video size={32} className="opacity-50" />
                    <span className="text-[10px] font-medium opacity-50">Video</span>
                 </div>
                 
                 <video 
                     src={url} 
                     className="w-full h-full object-cover relative z-10"
                     muted
                     playsInline
                     loop
                     onMouseEnter={(e) => e.target.play().catch(() => {})}
                     onMouseLeave={(e) => { 
                         e.target.pause(); 
                         e.target.currentTime = 0.1; 
                     }}
                     style={{ objectPosition: 'center' }}
                 />
            </div>
        );
    }

    // Unknown type fallback
    return (
        <div className="w-full h-full bg-slate-100 flex items-center justify-center">
            <Video size={32} className="text-slate-400 opacity-50" />
        </div>
    );
};

export default function Index({ galleries: initialGalleries }) {
    const { success } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);
    const [activeTab, setActiveTab] = useState('list');
    const [formKey, setFormKey] = useState(0);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [galleries, setGalleries] = useState(initialGalleries);

    const categories = ['Kegiatan Sekolah', 'Prestasi', 'Fasilitas', 'Alumni', 'Lainnya'];

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm({
        title: '', description: '', type: 'photo', file: null, url: '', is_external: false, category: '', is_featured: true,
    });

    // Cleanup URL.createObjectURL to prevent memory leak
    useEffect(() => {
        return () => {
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    // Update preview URL when file changes
    useEffect(() => {
        if (data.file instanceof File) {
            if (previewUrl && previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewUrl);
            }
            const newPreviewUrl = URL.createObjectURL(data.file);
            setPreviewUrl(newPreviewUrl);
        } else if (data.url) {
            setPreviewUrl(getImageUrl(data.url));
        } else {
            setPreviewUrl(null);
        }
    }, [data.file, data.url]);

    const tabs = [{ key: 'list', label: 'Galeri Foto & Video', description: 'Kelola dokumentasi visual.', icon: ImageIcon }];

    const openModal = (gallery = null) => {
        setFormKey(prev => prev + 1);
        if (gallery) {
            setEditMode(true); 
            setCurrentId(gallery.id);
            setData({ 
                title: gallery.title || '', 
                description: gallery.description || '', 
                type: gallery.type || 'photo', 
                file: null, 
                url: gallery.url || '', 
                is_external: !!gallery.is_external, 
                category: gallery.category || '', 
                is_featured: !!gallery.is_featured 
            });
        } else { 
            setEditMode(false); 
            setCurrentId(null); 
            setData({
                title: '', 
                description: '', 
                type: 'photo', 
                file: null, 
                url: '', 
                is_external: false, 
                category: '', 
                is_featured: true
            });
        }
        setIsModalOpen(true);
    };

    const closeModal = () => { setIsModalOpen(false); reset(); };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        if (editMode) {
            // Manually construct FormData to ensure all fields are included
            const formData = new FormData();
            
            // Add all fields from data
            Object.keys(data).forEach(key => {
                if (key === 'file') {
                    // Only add file if it's a File object
                    if (data.file instanceof File) {
                        formData.append('file', data.file);
                    }
                } else if (key === 'is_featured' || key === 'is_external') {
                    // Convert booleans to strings for FormData
                    formData.append(key, data[key] ? '1' : '0');
                } else {
                    formData.append(key, data[key] || '');
                }
            });
            
            // Add method spoofing
            formData.append('_method', 'PUT');
            
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Use native fetch to send the request directly
            fetch(route('admin.galleries.update', currentId), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            })
            .then(response => {
                if (response.ok) {
                    // Success - update local state with the new data
                    setGalleries(prevGalleries => 
                        prevGalleries.map(gallery => 
                            gallery.id === currentId 
                                ? { 
                                    ...gallery, 
                                    title: data.title,
                                    description: data.description,
                                    type: data.type,
                                    url: data.url,
                                    category: data.category,
                                    is_featured: data.is_featured,
                                    is_external: data.is_external,
                                    // If file was uploaded, we'll need to refresh to get the new media URL
                                    ...(data.file instanceof File ? { url: data.url } : {})
                                } 
                                : gallery
                        )
                    );
                    closeModal();
                    toast.success('Item galeri berhasil diperbarui');
                } else {
                    return response.json().then(err => {
                        toast.error('Gagal memperbarui item galeri');
                        throw err;
                    });
                }
            })
            .catch(error => {
                toast.error('Gagal memperbarui item galeri');
            });
        } else {
            post(route('admin.galleries.store'), {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    closeModal();
                    // Update local state with new galleries data
                    if (page.props && page.props.galleries) {
                        setGalleries(page.props.galleries);
                    }
                    toast.success('Item galeri baru berhasil ditambahkan');
                }
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus item galeri ini?')) {
            destroy(route('admin.galleries.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Item galeri berhasil dihapus')
            });
        }
    };

    return (
        <ContentManagementPage 
            headerTitle="Kelola Galeri" 
            headTitle="Galeri Sekolah" 
            tabs={tabs} 
            activeTab={activeTab} 
            setActiveTab={setActiveTab} 
            noForm={true}
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div className="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <h3 className="font-bold text-gray-900 text-lg">Galeri Foto & Video</h3>
                    <PrimaryButton type="button" onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2 px-4 py-2 text-sm font-medium w-full sm:w-auto justify-center">
                        <Plus size={18} />Tambah Item
                    </PrimaryButton>
                </div>

                <div className="p-4 sm:p-6">
                    {galleries.length > 0 ? (
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                            {galleries.map((item) => (
                                <div key={item.id} className="group bg-white border border-gray-100 rounded-lg sm:rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                                    <div className="aspect-square bg-gray-100 relative">
                                        <GalleryMedia item={item} />
                                        <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 z-20">
                                            <button onClick={() => openModal(item)} className="p-2 bg-white/90 rounded-full text-accent-yellow hover:bg-white"><Edit2 size={16} /></button>
                                            <button onClick={() => handleDelete(item.id)} className="p-2 bg-white/90 rounded-full text-red-600 hover:bg-white"><Trash2 size={16} /></button>
                                        </div>
                                        {item.is_featured && <div className="absolute top-2 left-2 px-2 py-1 bg-yellow-500 text-white text-[10px] sm:text-xs font-bold rounded flex items-center gap-1"><Star size={10} className="sm:size-3" /> Featured</div>}
                                        {item.type === 'video' && <div className="absolute top-2 right-2 px-2 py-1 bg-red-600 text-white text-[10px] sm:text-xs font-bold rounded">Video</div>}
                                    </div>
                                    <div className="p-2 sm:p-3">
                                        <h4 className="font-semibold text-gray-800 text-xs sm:text-sm truncate">{item.title}</h4>
                                        <div className="flex justify-between items-center mt-1">
                                            <span className="text-[10px] sm:text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{item.category || '-'}</span>
                                            <span className="text-[10px] sm:text-xs text-gray-400">{item.date ? new Date(item.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'}</span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 sm:p-12 text-center">
                            <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <ImageIcon size={24} className="sm:w-8 sm:h-8 text-gray-400" />
                            </div>
                            <p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada item</p>
                            <PrimaryButton onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 text-sm sm:text-base px-4 py-2">Tambah Item</PrimaryButton>
                        </div>
                    )}
                </div>
            </div>

            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-y-auto">
                    <div className="p-4 sm:p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h3 className="text-lg sm:text-xl font-bold text-gray-900">{editMode ? 'Edit Item' : 'Tambah Item'}</h3>
                        <button onClick={closeModal} className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                            <X size={20} />
                        </button>
                    </div>
                    <form key={formKey} onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="title" value="Judul" />
                                <TextInput id="title" type="text" className="mt-1 block w-full" value={data.title} onChange={(e) => setData('title', e.target.value)} required placeholder="Judul..." />
                                <InputError message={errors.title} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="type" value="Tipe" />
                                <select id="type" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                    <option value="photo">Foto</option>
                                    <option value="video">Video</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <InputLabel htmlFor="description" value="Deskripsi (Opsional)" />
                            <textarea id="description" className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" rows="2" value={data.description} onChange={(e) => setData('description', e.target.value)}></textarea>
                        </div>
                        <div className="p-4 bg-gray-50 rounded-xl space-y-3">
                            <label className="flex items-center gap-2">
                                <input type="checkbox" id="is_external" checked={data.is_external} onChange={(e) => setData('is_external', e.target.checked)} className="w-4 h-4 rounded border-gray-300 text-accent-yellow focus:ring-accent-yellow" />
                                <span className="text-sm font-medium">{data.type === 'video' ? 'Gunakan URL Eksternal (YouTube dll)' : 'Gunakan URL Eksternal'}</span>
                            </label>

                            {data.is_external ? (
                                <div>
                                    <InputLabel htmlFor="url" value="URL" />
                                    <TextInput id="url" type="url" className="mt-1 block w-full" value={data.url} onChange={(e) => setData('url', e.target.value)} placeholder="https://..." />
                                    <InputError message={errors.url} className="mt-2" />
                                </div>
                            ) : (
                                <FileUploadField 
                                    id="gallery_file" 
                                    label={data.type === 'video' ? 'Upload Video' : 'Upload Foto'} 
                                    accept={data.type === 'video' ? 'video/mp4,video/webm,video/quicktime' : 'image/*'} 
                                    fileType={data.type === 'video' ? 'video' : 'image'} 
                                    previewUrl={previewUrl}
                                    onChange={(file) => setData('file', file)} 
                                    error={errors.file} 
                                />
                            )}
                        </div>
                        <div className="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel htmlFor="category" value="Kategori" />
                                <TextInput id="category" type="text" list="category-options" className="mt-1 block w-full" value={data.category} onChange={(e) => setData('category', e.target.value)} placeholder="Kategori..." />
                                <datalist id="category-options">{categories.map(c => <option key={c} value={c} />)}</datalist>
                            </div>
                        </div>
                        <label className="flex items-center gap-2">
                            <input type="checkbox" id="is_featured_gallery" checked={data.is_featured} onChange={(e) => setData('is_featured', e.target.checked)} className="w-4 h-4 rounded border-gray-300 text-accent-yellow focus:ring-accent-yellow" />
                            <span className="text-sm text-gray-700">Featured</span>
                        </label>
                        <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                            <button type="button" onClick={closeModal} className="px-4 py-2 text-gray-700 hover:text-gray-900 font-medium">Batal</button>
                            <PrimaryButton type="submit" disabled={processing} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500">
                                {processing ? '...' : (editMode ? 'Simpan' : 'Tambah')}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </ContentManagementPage>
    );
}
