import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Edit2, Trash2, Search, EyeOff, CheckCircle, CalendarDays } from 'lucide-react';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import FileUploadField from '@/Components/Admin/FileUploadField';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import toast from 'react-hot-toast';

export default function AcademicCalendarContentPage({ contents, filters }) {
    const [showModal, setShowModal] = useState(false);
    const [editingContent, setEditingContent] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [selectedFile, setSelectedFile] = useState(null);
    const [previewUrl, setPreviewUrl] = useState('');
    const [activeTab, setActiveTab] = useState('list');

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        calendar_image: null,
        semester: 1, // Default to odd semester (Ganjil)
        academic_year_start: new Date().getFullYear(),
        sort_order: 0,
    });

    const tabs = [
        { key: 'list', label: 'Kalender Akademik', description: 'Kelola jadwal kegiatan akademik sekolah.', icon: CalendarDays },
    ];

    const openCreateModal = () => {
        reset();
        setEditingContent(null);
        setSelectedFile(null);
        setPreviewUrl('');
        setShowModal(true);
    };    const openEditModal = (content) => {
        setData({
            title: content.title,
            calendar_image: null,
            semester: content.semester,
            academic_year_start: content.academic_year_start,
            sort_order: content.sort_order,
        });
        setEditingContent(content);
        setSelectedFile(null);
        setPreviewUrl(content.calendar_imagesImage?.original_url || content.calendar_image_url || '');
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setEditingContent(null);
        setSelectedFile(null);
        setPreviewUrl('');
        reset();
    };

    const handleSubmit = (e) => {
        if (e) e.preventDefault();
        
        // BUG-3 Fix: Validate file before submit
        if (selectedFile instanceof File) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (selectedFile.size > maxSize) {
                toast.error('Ukuran gambar kalender maksimal 10MB');
                return;
            }
            
            if (!allowedTypes.includes(selectedFile.type)) {
                toast.error('Format gambar harus JPG, PNG, GIF, atau WebP');
                return;
            }
        }
        
        const submitData = {
            title: data.title,
            semester: data.semester,
            academic_year_start: data.academic_year_start,
            sort_order: data.sort_order,
        };
        
        // Add file if selected
        if (selectedFile) {
            submitData.calendar_image = selectedFile;
        }
        
        // Use router.post with forceFormData for both create and update
        if (editingContent) {
            submitData._method = 'PUT';
            router.post(route('admin.academic-calendar.update', editingContent.id), submitData, {
                forceFormData: true,
                onSuccess: () => {
                    closeModal();
                },
                onError: (errors) => {
                    console.error('Update errors:', errors);
                }
            });
        } else {
            router.post(route('admin.academic-calendar.store'), submitData, {
                forceFormData: true,
                onSuccess: () => {
                    closeModal();
                },
                onError: (errors) => {
                    console.error('Store errors:', errors);
                }
            });
        }
    };

    const handleDelete = (content) => {
        if (confirm(`Apakah Anda yakin ingin menghapus kalender "${content.title}"? Tindakan ini tidak dapat dibatalkan.`)) {
            router.delete(route('admin.academic-calendar.destroy', content.id));
        }
    };

    const handleSetActive = (content) => {
        router.patch(route('admin.academic-calendar.set-active', content.id));
    };

    const handleSearch = (e) => {
        if (e) e.preventDefault();
        router.get(route('admin.academic-calendar.index'), { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <ContentManagementPage
            headerTitle="Kelola Kalender Akademik"
            headTitle="Kelola Kalender"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            extraHeader={
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                    <form onSubmit={handleSearch} className="flex-1 flex gap-2 w-full">
                        <TextInput
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari berdasarkan judul, deskripsi, atau tahun akademik..."
                            className="flex-1"
                        />
                        <PrimaryButton 
                            type="submit" 
                            className="flex items-center gap-2 !bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 shadow-sm"
                        >
                            <Search size={16} />
                            Cari
                        </PrimaryButton>
                    </form>
                    <PrimaryButton 
                        onClick={openCreateModal} 
                        className="flex items-center gap-2 !bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 shadow-sm whitespace-nowrap"
                    >
                        <Plus size={16} />
                        Tambah Kalender
                    </PrimaryButton>
                </div>
            }
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">
                                    Kalender Akademik
                                </th>
                                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                                    Semester & Tahun
                                </th>
                                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12 text-center">
                                    Status
                                </th>
                                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">
                                    Urutan
                                </th>
                                <th className="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {contents.data.length > 0 ? (
                                contents.data.map((content) => (
                                    <tr key={content.id} className="hover:bg-gray-50">
                                        <td className="px-3 py-4">
                                            <div className="flex items-center">
                                                <div className="flex-shrink-0 h-12 w-12">
                                                    <img
                                                        className="h-12 w-12 rounded object-cover border"
                                                        src={content.calendar_imagesImage?.original_url || content.calendar_image_url}
                                                        alt={content.title}
                                                        onError={(e) => {
                                                            e.target.src = 'https://placehold.co/48x48/E5E7EB/9CA3AF?text=IMG';
                                                        }}
                                                    />
                                                </div>
                                                <div className="ml-3 min-w-0 flex-1">                                                            <div className="text-sm font-medium text-gray-900 truncate">
                                                        {content.title}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-3 py-4 text-sm text-gray-900">
                                            <div className="flex flex-col">                                                        <span className="font-medium">{content.semester === 1 ? 'Semester Ganjil' : 'Semester Genap'}</span>
                                                <span className="text-xs text-gray-500">{content.academic_year_start}/{content.academic_year_start + 1}</span>
                                            </div>
                                        </td>
                                        <td className="px-3 py-4 text-sm text-center">
                                            <button
                                                onClick={() => handleSetActive(content)}
                                                className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold transition-colors ${
                                                    content.is_active 
                                                    ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                                    : 'bg-red-100 text-red-700 hover:bg-red-200'
                                                }`}
                                                title={content.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'}
                                            >
                                                {content.is_active ? (
                                                    <><CheckCircle size={12} /> Aktif</>
                                                ) : (
                                                    <><EyeOff size={12} /> Nonaktif</>
                                                )}
                                            </button>
                                        </td>
                                        <td className="px-3 py-4 text-sm text-gray-900 text-center">
                                            {content.sort_order}
                                        </td>
                                        <td className="px-3 py-4 text-sm font-medium">
                                            <div className="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    onClick={() => openEditModal(content)}
                                                    className="text-blue-600 hover:text-blue-900 p-1 rounded"
                                                    title="Edit"
                                                >
                                                    <Edit2 size={14} />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => handleDelete(content)}
                                                    className="text-red-600 hover:text-red-900 p-1 rounded"
                                                    title="Hapus"
                                                >
                                                    <Trash2 size={14} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="5" className="px-3 py-12 text-center text-gray-500">
                                        <div className="flex flex-col items-center gap-2">
                                            <p>Belum ada kalender akademik yang ditambahkan.</p>
                                            <PrimaryButton onClick={openCreateModal} className="mt-2">
                                                Tambah Kalender Pertama
                                            </PrimaryButton>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {contents.links && contents.links.length > 3 && (
                    <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div className="flex justify-between items-center">
                            <div className="text-sm text-gray-700">
                                Menampilkan {contents.from} - {contents.to} dari {contents.total} hasil
                            </div>
                            <div className="flex gap-1">
                                {contents.links.map((link, index) => (
                                    <button
                                        key={index}
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        className={`px-3 py-1 text-sm rounded ${
                                            link.active
                                                ? 'bg-primary text-white'
                                                : link.url
                                                ? 'bg-white text-gray-700 border hover:bg-gray-50'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Create/Edit Modal */}
            <Modal show={showModal} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={handleSubmit} className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900 mb-6">
                        {editingContent ? 'Edit Kalender Akademik' : 'Tambah Kalender Akademik'}
                    </h2>

                    <div className="space-y-6">
                        {/* Informasi Kalender */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                                    Informasi Kalender
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Detail kalender akademik</p>
                            </div>
                            <div>
                                <InputLabel htmlFor="title" value="Judul" />
                                <TextInput
                                    id="title"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    required
                                    placeholder="Contoh: Kalender Akademik SMA Negeri 1 Baleendah"
                                />
                                <p className="text-sm text-gray-500 mt-1">Judul untuk kalender akademik</p>
                                <InputError message={errors.title} className="mt-2" />
                            </div>
                        </div>

                        {/* Gambar Kalender */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                    Gambar Kalender
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Upload gambar kalender akademik</p>
                            </div>
                            <div>
                                <InputLabel htmlFor="calendar_image" value="Gambar Kalender" />
                                <FileUploadField
                                    id="calendar_image"
                                    label="Gambar Kalender"
                                    previewUrl={previewUrl}
                                    onChange={(file) => {
                                        setSelectedFile(file);
                                        setData('calendar_image', file);
                                        
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (event) => {
                                                setPreviewUrl(event.target.result);
                                            };
                                            reader.readAsDataURL(file);
                                        } else {
                                            setPreviewUrl('');
                                        }
                                    }}
                                    error={errors.calendar_image}
                                    description="Pilih file gambar kalender akademik (JPG, PNG, GIF, SVG - Maksimal 2MB)"
                                />
                            </div>
                        </div>

                        {/* Periode Akademik */}
                        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                            <div className="border-b pb-3">
                                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span>
                                    Periode Akademik
                                </h3>
                                <p className="text-sm text-gray-600 mt-1">Tahun dan semester akademik</p>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="academic_year_start" value="Tahun Awal Akademik" />
                                    <TextInput
                                        id="academic_year_start"
                                        type="number"
                                        className="mt-1 block w-full"
                                        value={data.academic_year_start}
                                        onChange={(e) => setData('academic_year_start', parseInt(e.target.value) || new Date().getFullYear())}
                                        required
                                        placeholder="2024"
                                        min="2000"
                                        max="2100"
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Contoh: 2024 untuk tahun akademik 2024/2025
                                    </p>
                                    <InputError message={errors.academic_year_start} className="mt-2" />
                                </div>
                                
                                <div>
                                    <InputLabel htmlFor="semester" value="Semester" />
                                    <select
                                        id="semester"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.semester}
                                        onChange={(e) => setData('semester', parseInt(e.target.value))}
                                        required
                                    >
                                        <option value={1}>Semester Ganjil</option>
                                        <option value={2}>Semester Genap</option>
                                    </select>
                                    <p className="text-sm text-gray-500 mt-1">Pilih semester akademik</p>
                                    <InputError message={errors.semester} className="mt-2" />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <div>
                                    <InputLabel htmlFor="sort_order" value="Urutan" />
                                    <TextInput
                                        id="sort_order"
                                        type="number"
                                        className="mt-1 block w-24"
                                        value={data.sort_order}
                                        onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                                        min="0"
                                    />
                                    <p className="text-sm text-gray-500 mt-1">Urutan tampilan</p>
                                    <InputError message={errors.sort_order} className="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 mt-6">
                        <SecondaryButton type="button" onClick={closeModal}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton 
                            type="submit" 
                            disabled={processing}
                            className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 shadow-sm"
                        >
                            {processing ? 'Menyimpan...' : (editingContent ? 'Perbarui' : 'Simpan')}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </ContentManagementPage>
    );
}
