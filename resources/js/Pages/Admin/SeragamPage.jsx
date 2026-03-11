import React, { useState } from 'react';
import { usePage, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { 
    Shirt, 
    Plus, 
    Edit, 
    Trash2, 
    Image as ImageIcon,
    X,
    ChevronDown,
    Search,
    AlertCircle
} from 'lucide-react';

// Days configuration
const DAYS = [
    { key: 'monday', label: 'Senin' },
    { key: 'tuesday', label: 'Selasa' },
    { key: 'wednesday', label: 'Rabu' },
    { key: 'thursday', label: 'Kamis' },
    { key: 'friday', label: 'Jumat' },
    { key: 'saturday', label: 'Sabtu' },
    { key: 'sunday', label: 'Minggu' },
];

const CATEGORIES = ['Seragam Harian', 'Seragam Dinas', 'Seragam Olahraga', 'Seragam Khusus'];

export default function SeragamPage({ seragams = [] }) {
    const { errors } = usePage().props;
    
    // State
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isEditMode, setIsEditMode] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    
    // Form state
    const [formData, setFormData] = useState({
        name: '',
        slug: '',
        category: 'Seragam Harian',
        description: '',
        usage_days: [],
        rules: '',
        sort_order: 0,
        is_active: true,
        image: null,
        image_preview: null,
    });

    // Filter seragams
    const filteredSeragams = seragams.filter(seragam => {
        const matchesSearch = seragam.name.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesCategory = !selectedCategory || seragam.category === selectedCategory;
        return matchesSearch && matchesCategory;
    });

    // Reset form
    const resetForm = () => {
        setFormData({
            name: '',
            slug: '',
            category: 'Seragam Harian',
            description: '',
            usage_days: [],
            rules: '',
            sort_order: seragams.length + 1,
            is_active: true,
            image: null,
            image_preview: null,
        });
        setEditingId(null);
        setIsEditMode(false);
    };

    // Open modal for new item
    const handleOpenModal = () => {
        resetForm();
        setIsModalOpen(true);
    };

    // Open modal for edit
    const handleEdit = (seragam) => {
        setFormData({
            name: seragam.name,
            slug: seragam.slug,
            category: seragam.category || 'Seragam Harian',
            description: seragam.description || '',
            usage_days: seragam.usage_days || [],
            rules: seragam.rules || '',
            sort_order: seragam.sort_order || 0,
            is_active: seragam.is_active,
            image: null,
            image_preview: seragam.image_url || null,
        });
        setEditingId(seragam.id);
        setIsEditMode(true);
        setIsModalOpen(true);
    };

    // Handle delete
    const handleDelete = (id) => {
        setDeletingId(id);
        setShowDeleteConfirm(true);
    };

    const confirmDelete = () => {
        router.delete(`/admin/seragam/${deletingId}`, {
            onSuccess: () => {
                setShowDeleteConfirm(false);
                setDeletingId(null);
            },
        });
    };

    // Handle form submission
    const handleSubmit = (e) => {
        e.preventDefault();
        
        const url = isEditMode ? `/admin/seragam/${editingId}` : '/admin/seragam';
        const method = isEditMode ? 'put' : 'post';

        router[method](url, formData, {
            onSuccess: () => {
                setIsModalOpen(false);
                resetForm();
            },
        });
    };

    // Handle image change
    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setFormData({
                ...formData,
                image: file,
                image_preview: URL.createObjectURL(file),
            });
        }
    };

    // Toggle day selection
    const toggleDay = (dayKey) => {
        const currentDays = formData.usage_days || [];
        const newDays = currentDays.includes(dayKey)
            ? currentDays.filter(d => d !== dayKey)
            : [...currentDays, dayKey];
        setFormData({ ...formData, usage_days: newDays });
    };

    // Auto-generate slug from name
    const handleNameChange = (e) => {
        const name = e.target.value;
        setFormData({
            ...formData,
            name,
            slug: formData.slug || name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
        });
    };

    return (
        <AdminLayout 
            title="Kelola Seragam"
            breadcrumbs={[
                { label: 'Dashboard', href: '/admin' },
                { label: 'Seragam' },
            ]}
        >
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Kelola Seragam</h1>
                        <p className="text-gray-600 mt-1">Kelola data seragam sekolah</p>
                    </div>
                    <button
                        onClick={handleOpenModal}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-darker transition-colors"
                    >
                        <Plus size={20} />
                        Tambah Seragam
                    </button>
                </div>

                {/* Filters */}
                <div className="flex flex-col sm:flex-row gap-4">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Cari seragam..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        />
                    </div>
                    <select
                        value={selectedCategory}
                        onChange={(e) => setSelectedCategory(e.target.value)}
                        className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                    >
                        <option value="">Semua Kategori</option>
                        {CATEGORIES.map(cat => (
                            <option key={cat} value={cat}>{cat}</option>
                        ))}
                    </select>
                </div>

                {/* Table */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Gambar</th>
                                    <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Nama</th>
                                    <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                                    <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Jadwal</th>
                                    <th className="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th className="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {filteredSeragams.length > 0 ? (
                                    filteredSeragams.map((seragam) => (
                                        <tr key={seragam.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <div className="w-16 h-16 rounded-lg overflow-hidden bg-gray-100">
                                                    {seragam.image_url ? (
                                                        <img 
                                                            src={seragam.image_url} 
                                                            alt={seragam.name}
                                                            className="w-full h-full object-cover"
                                                        />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center">
                                                            <ImageIcon className="w-6 h-6 text-gray-300" />
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-medium text-gray-900">{seragam.name}</div>
                                                <div className="text-sm text-gray-500">{seragam.slug}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="inline-block px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                                                    {seragam.category}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {seragam.usage_days?.length > 0 
                                                    ? seragam.usage_days.map(d => {
                                                        const day = DAYS.find(day => day.key === d);
                                                        return day ? day.label : d;
                                                    }).join(', ')
                                                    : '-'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`inline-block px-2 py-1 text-xs rounded-full ${
                                                    seragam.is_active 
                                                        ? 'bg-green-100 text-green-700' 
                                                        : 'bg-gray-100 text-gray-700'
                                                }`}>
                                                    {seragam.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        onClick={() => handleEdit(seragam)}
                                                        className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                        title="Edit"
                                                    >
                                                        <Edit size={18} />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(seragam.id)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                        title="Hapus"
                                                    >
                                                        <Trash2 size={18} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                                            <Shirt className="w-12 h-12 mx-auto mb-3 text-gray-300" />
                                            <p>Tidak ada data seragam</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Modal Form */}
            {isModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/50" onClick={() => setIsModalOpen(false)}></div>
                    <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        {/* Modal Header */}
                        <div className="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                            <h2 className="text-xl font-bold text-gray-900">
                                {isEditMode ? 'Edit Seragam' : 'Tambah Seragam'}
                            </h2>
                            <button
                                onClick={() => setIsModalOpen(false)}
                                className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                <X size={20} />
                            </button>
                        </div>

                        {/* Modal Body */}
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            {/* Image Upload */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Gambar Seragam
                                </label>
                                <div className="flex items-center gap-4">
                                    <div className="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 overflow-hidden bg-gray-50 flex items-center justify-center">
                                        {formData.image_preview ? (
                                            <img 
                                                src={formData.image_preview} 
                                                alt="Preview" 
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <ImageIcon className="w-8 h-8 text-gray-300" />
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-primary-darker"
                                        />
                                        <p className="text-xs text-gray-500 mt-1">PNG, JPG, atau WebP. Maks 2MB</p>
                                    </div>
                                </div>
                                {errors?.image && (
                                    <p className="mt-1 text-sm text-red-600 flex items-center gap-1">
                                        <AlertCircle size={14} /> {errors.image}
                                    </p>
                                )}
                            </div>

                            {/* Name & Slug */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Nama Seragam <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.name}
                                        onChange={handleNameChange}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                        placeholder="Contoh: Seragam Senin-Kamis"
                                        required
                                    />
                                    {errors?.name && (
                                        <p className="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle size={14} /> {errors.name}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Slug <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.slug}
                                        onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                        placeholder="seragam-senin-kamis"
                                        required
                                    />
                                    {errors?.slug && (
                                        <p className="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle size={14} /> {errors.slug}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Category & Sort Order */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Kategori
                                    </label>
                                    <select
                                        value={formData.category}
                                        onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    >
                                        {CATEGORIES.map(cat => (
                                            <option key={cat} value={cat}>{cat}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Urutan
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.sort_order}
                                        onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value) || 0 })}
                                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                        min="0"
                                    />
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Deskripsi
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    rows={3}
                                    placeholder="Deskripsi seragam..."
                                />
                            </div>

                            {/* Usage Days */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Jadwal Penggunaan
                                </label>
                                <div className="flex flex-wrap gap-2">
                                    {DAYS.map(day => (
                                        <button
                                            key={day.key}
                                            type="button"
                                            onClick={() => toggleDay(day.key)}
                                            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                                formData.usage_days?.includes(day.key)
                                                    ? 'bg-primary text-white'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            }`}
                                        >
                                            {day.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Rules */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Peraturan
                                </label>
                                <textarea
                                    value={formData.rules}
                                    onChange={(e) => setFormData({ ...formData, rules: e.target.value })}
                                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    rows={2}
                                    placeholder="Peraturan penggunaan seragam..."
                                />
                            </div>

                            {/* Active Status */}
                            <div className="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={formData.is_active}
                                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    className="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary"
                                />
                                <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                                    Aktif
                                </label>
                            </div>

                            {/* Submit Button */}
                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={() => setIsModalOpen(false)}
                                    className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-darker transition-colors"
                                >
                                    {isEditMode ? 'Simpan Perubahan' : 'Tambah Seragam'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {showDeleteConfirm && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div className="absolute inset-0 bg-black/50" onClick={() => setShowDeleteConfirm(false)}></div>
                    <div className="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                        <div className="text-center">
                            <div className="w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                                <Trash2 className="w-8 h-8 text-red-600" />
                            </div>
                            <h3 className="text-lg font-bold text-gray-900 mb-2">Hapus Seragam?</h3>
                            <p className="text-gray-600 mb-6">
                                Apakah Anda yakin ingin menghapus seragam ini? Tindakan ini tidak dapat dibatalkan.
                            </p>
                            <div className="flex gap-3 justify-center">
                                <button
                                    onClick={() => setShowDeleteConfirm(false)}
                                    className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    onClick={confirmDelete}
                                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
