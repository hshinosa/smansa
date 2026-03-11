// FILE: resources/js/Pages/Admin/Extracurriculars/Index.jsx
// Fully responsive management page for Organisasi & Ekstrakurikuler

import React, { useState, useMemo } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ImageUploadCard from '@/Components/Admin/ImageUploadCard';
import { Plus, Edit2, Trash2, X, Star, Clock, Users, Activity, Award, MapPin, User, Calendar } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import toast from 'react-hot-toast';
import { getImageUrl } from '@/Utils/imageUtils';

export default function Index({ extracurriculars }) {
    const { success } = usePage().props;
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const { activeTab: initialTab } = usePage().props;
    const [currentId, setCurrentId] = useState(null);
    const [activeTab, setActiveTab] = useState(() => {
        // Prefer tab from prop (server redirect), otherwise use 'organisasi' default
        // Valid tabs are 'organisasi' and 'ekstrakurikuler'
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');
        return urlTab || initialTab || 'organisasi';
    });

    const { data, setData, post, delete: destroy, processing, errors, reset } = useForm({
        name: '',
        type: 'ekstrakurikuler',
        category: 'Olahraga',
        description: '',
        activity_description: '',
        image: null,
        image_url: '',
        bg_image: null,
        bg_image_url: '',
        profile_image: null,
        profile_image_url: '',
        achievements_data: [],
        training_info: { days: [], start_time: '', end_time: '', location: '', coach: '' },
        is_active: true,
    });

    const tabs = [
        { key: 'organisasi', label: 'Organisasi', description: 'Kelola organisasi siswa (OSIS, MPK).', icon: Users },
        { key: 'ekstrakurikuler', label: 'Ekstrakurikuler', description: 'Kelola kegiatan ekstrakurikuler.', icon: Activity }
    ];
    
    // Category for Organisasi (Student Organizations only)
    const organisasiCategories = [
        'Organisasi Siswa'
    ];
    
    // Categories for Ekstrakurikuler
    const ekstrakurikulerCategories = [
        'Olahraga', 
        'Seni & Budaya', 
        'Akademik & Sains', 
        'Keagamaan & Sosial', 
        'Kepemimpinan & Bela Negara',
        'Teknologi & Inovasi'
    ];

    // Get categories based on selected type
    const getCategories = (type) => {
        return type === 'organisasi' ? organisasiCategories : ekstrakurikulerCategories;
    };

    // Filter extracurriculars by active tab (type)
    const filteredItems = useMemo(() => {
        return extracurriculars.filter(item => item.type === activeTab);
    }, [extracurriculars, activeTab]);

    const openModal = (item = null) => {
        if (item) {
            console.log('📝 Opening edit modal for:', {
                id: item.id,
                name: item.name,
                image_url: item.image_url,
                bg_image_url: item.bg_image_url,
                profile_image_url: item.profile_image_url,
                full_item: item
            });
            
            setEditMode(true);
            setCurrentId(item.id);
            setData({
                name: item.name || '',
                type: item.type || 'ekstrakurikuler',
                category: item.category || 'Olahraga',
                description: item.description || '',
                activity_description: item.activity_description || '',
                image: null,
                image_url: item.image_url || '',
                bg_image: null,
                bg_image_url: item.bg_image_url || '',
                profile_image: null,
                profile_image_url: item.profile_image_url || '',
                achievements_data: item.achievements_data || [],
                training_info: item.training_info || { days: [], start_time: '', end_time: '', location: '', coach: '' },
                is_active: !!item.is_active
            });
            
            console.log('✅ Form data set:', {
                image_url: item.image_url || '',
                bg_image_url: item.bg_image_url || '',
                profile_image_url: item.profile_image_url || ''
            });
        } else {
            setEditMode(false);
            setCurrentId(null);
            // Set default type based on active tab
            const defaultCategory = activeTab === 'organisasi' ? 'Organisasi Siswa' : 'Olahraga';
            setData({
                name: '',
                type: activeTab,
                category: defaultCategory,
                description: '',
                activity_description: '',
                image: null,
                image_url: '',
                bg_image: null,
                bg_image_url: '',
                profile_image: null,
                profile_image_url: '',
                achievements_data: [],
                training_info: { days: [], start_time: '', end_time: '', location: '', coach: '' },
                is_active: true,
            });
        }
        setIsModalOpen(true);
    };

    const closeModal = () => { setIsModalOpen(false); reset(); };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Define cleanup option that preserves scroll and ensures tab consistency
        const requestOptions = {
            forceFormData: true,
            preserveScroll: true,
            preserveState: false, // We want full state reload to get updated table
            onSuccess: (page) => {
                closeModal();
                toast.success(data.type === 'organisasi' ? 'Organisasi berhasil disimpan' : 'Ekskul berhasil disimpan');
                
                // If the submitted type is different from current active tab, switch to it or let the server redirect handle it
                // Since we use preserveState: false, the component will re-mount with new props from server
                // The server redirect logic should now send ?tab=... which initializes the state correctly
            },
            onError: (errors) => {
                console.error('Submission errors:', errors);
                toast.error('Gagal menyimpan data. Periksa input Anda.');
            }
        };

        if (editMode) {
             // For update, Laravel requires _method: 'PUT' with FormData
            requestOptions._method = 'PUT';
            post(route('admin.extracurriculars.update', currentId), requestOptions);
        } else {
            post(route('admin.extracurriculars.store'), requestOptions);
        }
    };

    const handleDelete = (id) => {
        const itemType = activeTab === 'organisasi' ? 'organisasi' : 'ekstrakurikuler';
        if (confirm(`Apakah Anda yakin ingin menghapus ${itemType} ini? Tindakan ini tidak dapat dibatalkan.`)) {
            destroy(route('admin.extracurriculars.destroy', id), {
                preserveScroll: true,
                preserveState: false, // Full reload to refresh table
                onSuccess: () => toast.success(`${itemType === 'organisasi' ? 'Organisasi' : 'Ekskul'} berhasil dihapus`)
            });
        }
    };

    const getCategoryColor = (cat) => {
        const colors = {
            'Olahraga': 'bg-green-50 text-green-600 border border-green-200',
            'Seni & Budaya': 'bg-purple-50 text-purple-600 border border-purple-200',
            'Akademik & Sains': 'bg-blue-50 text-blue-600 border border-blue-200',
            'Keagamaan & Sosial': 'bg-amber-50 text-amber-600 border border-amber-200',
            'Organisasi Siswa': 'bg-rose-50 text-rose-600 border border-rose-200',
            'Kepemimpinan & Bela Negara': 'bg-orange-50 text-orange-600 border border-orange-200',
            'Teknologi & Inovasi': 'bg-cyan-50 text-cyan-600 border border-cyan-200'
        };
        return colors[cat] || 'bg-gray-50 text-gray-600 border border-gray-200';
    };

    const getTypeLabel = () => activeTab === 'organisasi' ? 'Organisasi' : 'Ekstrakurikuler';
    const getTypeIcon = () => activeTab === 'organisasi' ? Users : Activity;

    return (
        <ContentManagementPage 
            headerTitle="Kelola Organisasi & Ekskul" 
            headTitle="Organisasi & Ekstrakurikuler" 
            tabs={tabs} 
            activeTab={activeTab} 
            setActiveTab={setActiveTab} 
            noForm={true}
        >
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                {/* Header with Add Button */}
                <div className="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <div>
                        <h3 className="font-bold text-gray-900 text-lg">Daftar {getTypeLabel()}</h3>
                        <p className="text-sm text-gray-500 mt-0.5">
                            {activeTab === 'organisasi' 
                                ? 'OSIS, MPK, dan organisasi siswa lainnya' 
                                : 'PMR, Pramuka, Basket, Paskibra, dan kegiatan lainnya'}
                        </p>
                    </div>
                    <PrimaryButton type="button" onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2 px-4 py-2 text-sm font-medium w-full sm:w-auto justify-center">
                        <Plus size={18} />Tambah {getTypeLabel()}
                    </PrimaryButton>
                </div>
                {filteredItems.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Gambar</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Kategori</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {filteredItems.map((item) => {
                                    const IconComponent = getTypeIcon();
                                    return (
                                        <tr key={item.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <div className="h-12 w-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                                    {item.image_url ? (
                                                        <img src={getImageUrl(item.image_url)} alt={item.name} className="w-full h-full object-cover" />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center">
                                                            <IconComponent className="text-gray-400 w-6 h-6" />
                                                        </div>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <p className="font-semibold text-gray-900 text-sm">{item.name}</p>
                                                {item.description && <p className="text-xs text-gray-500 line-clamp-1 mt-0.5 hidden sm:block">{item.description}</p>}
                                            </td>
                                            <td className="px-4 py-3 hidden sm:table-cell">
                                                <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${getCategoryColor(item.category)}`}>
                                                    {item.category}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${item.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                                                    {item.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button onClick={() => openModal(item)} className="p-2 !text-accent-yellow hover:bg-accent-yellow/10 rounded-lg">
                                                        <Edit2 size={16} />
                                                    </button>
                                                    <button onClick={() => handleDelete(item.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                                                        <Trash2 size={16} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="p-8 sm:p-12 text-center">
                        <div className="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            {activeTab === 'organisasi' ? <Users size={24} className="sm:w-8 sm:h-8 text-gray-400" /> : <Activity size={24} className="sm:w-8 sm:h-8 text-gray-400" />}
                        </div>
                        <p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada {getTypeLabel().toLowerCase()}</p>
                        <PrimaryButton onClick={() => openModal()} className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 text-sm sm:text-base px-4 py-2">
                            Tambah {getTypeLabel()}
                        </PrimaryButton>
                    </div>
                )}
            </div>
            
            {/* Modal Form */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="4xl">
                <div className="bg-white rounded-2xl shadow-xl w-full mx-auto max-h-[90vh] overflow-y-auto">
                    <div className="p-4 sm:p-6 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                        <h3 className="text-lg sm:text-xl font-bold text-gray-900">
                            {editMode ? `Edit ${data.type === 'organisasi' ? 'Organisasi' : 'Ekskul'}` : `Tambah ${data.type === 'organisasi' ? 'Organisasi' : 'Ekskul'}`}
                        </h3>
                        <button onClick={closeModal} className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                            <X size={20} />
                        </button>
                    </div>
                    <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="name" value="Nama" />
                                <TextInput 
                                    id="name" 
                                    type="text" 
                                    className="mt-1 block w-full" 
                                    value={data.name} 
                                    onChange={(e) => setData('name', e.target.value)} 
                                    required 
                                    placeholder={data.type === 'organisasi' ? 'Contoh: OSIS' : 'Contoh: Basket'} 
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="category" value="Kategori" />
                                <select 
                                    id="category" 
                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm" 
                                    value={data.category} 
                                    onChange={(e) => setData('category', e.target.value)}
                                >
                                    {getCategories(data.type).map(c => <option key={c} value={c}>{c}</option>)}
                                </select>
                            </div>
                        </div>
                        
                        
                        
                        <div>
                            <InputLabel htmlFor="description" value="Deskripsi Singkat" />
                            <textarea
                                id="description"
                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm"
                                rows="3"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                required
                            ></textarea>
                            <InputError message={errors.description} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="activity_description" value="Deskripsi Kegiatan (Detail)" />
                            <textarea
                                id="activity_description"
                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm"
                                rows="4"
                                value={data.activity_description}
                                onChange={(e) => setData('activity_description', e.target.value)}
                                placeholder="Deskripsi detail tentang kegiatan..."
                            ></textarea>
                        </div>
                        
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <ImageUploadCard
                                    id="image"
                                    label="Gambar Utama"
                                    onChange={(file) => setData('image', file)}
                                    previewUrl={getImageUrl(data.image_url)}
                                    error={errors.image}
                                />
                            </div>
                            <div>
                                <ImageUploadCard
                                    id="bg_image"
                                    label="Background Image"
                                    onChange={(file) => setData('bg_image', file)}
                                    previewUrl={getImageUrl(data.bg_image_url)}
                                    error={errors.bg_image}
                                />
                            </div>
                            <div>
                                <ImageUploadCard
                                    id="profile_image"
                                    label="Profile Image (Tampil di Modal Edit/Halaman Publik)"
                                    onChange={(file) => setData('profile_image', file)}
                                    previewUrl={getImageUrl(data.profile_image_url)}
                                    error={errors.profile_image}
                                />
                            </div>
                        </div>

                        {/* Achievements Section */}
                        <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div className="flex items-center justify-between mb-3">
                                <InputLabel value="Jejak Prestasi" className="!mb-0" />
                                <button
                                    type="button"
                                    onClick={() => setData('achievements_data', [...data.achievements_data, { title: '', level: '', year: '' }])}
                                    className="text-sm flex items-center gap-1 text-accent-yellow hover:text-yellow-600 font-medium"
                                >
                                    <Plus size={16} /> Tambah Prestasi
                                </button>
                            </div>
                            {data.achievements_data.length > 0 ? (
                                <div className="space-y-3">
                                    {data.achievements_data.map((achievement, index) => (
                                        <div key={index} className="bg-white p-3 rounded-lg border border-gray-200">
                                            <div className="flex items-start gap-2">
                                                <Award className="w-4 h-4 text-yellow-500 mt-1 flex-shrink-0" />
                                                <div className="flex-1 space-y-2">
                                                    <TextInput
                                                        type="text"
                                                        className="block w-full text-sm"
                                                        placeholder="Judul Prestasi (contoh: Juara 1 Basket Tingkat Kota)"
                                                        value={achievement.title}
                                                        onChange={(e) => {
                                                            const newAchievements = [...data.achievements_data];
                                                            newAchievements[index].title = e.target.value;
                                                            setData('achievements_data', newAchievements);
                                                        }}
                                                    />
                                                    <div className="grid grid-cols-2 gap-2">
                                                        <TextInput
                                                            type="text"
                                                            className="block w-full text-sm"
                                                            placeholder="Tingkat (Kota/Provinsi/Nasional)"
                                                            value={achievement.level}
                                                            onChange={(e) => {
                                                                const newAchievements = [...data.achievements_data];
                                                                newAchievements[index].level = e.target.value;
                                                                setData('achievements_data', newAchievements);
                                                            }}
                                                        />
                                                        <TextInput
                                                            type="text"
                                                            className="block w-full text-sm"
                                                            placeholder="Tahun (2024)"
                                                            value={achievement.year}
                                                            onChange={(e) => {
                                                                const newAchievements = [...data.achievements_data];
                                                                newAchievements[index].year = e.target.value;
                                                                setData('achievements_data', newAchievements);
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        const newAchievements = data.achievements_data.filter((_, i) => i !== index);
                                                        setData('achievements_data', newAchievements);
                                                    }}
                                                    className="text-red-500 hover:text-red-700 p-1"
                                                >
                                                    <X size={16} />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 italic">Belum ada prestasi ditambahkan</p>
                            )}
                        </div>

                        {/* Training Info Section */}
                        <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <InputLabel value="Informasi Latihan" className="mb-3" />
                            <div className="space-y-3">
                                <div className="flex items-start gap-2">
                                    <Calendar className="w-4 h-4 text-gray-400 mt-2.5" />
                                    <div className="flex-1">
                                        <InputLabel value="Hari Latihan" className="text-xs !mb-1" />
                                        <div className="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            {['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'].map((day) => (
                                                <label key={day} className="flex items-center gap-2 p-2 rounded-lg border border-gray-200 hover:border-accent-yellow cursor-pointer transition-colors bg-white">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.training_info.days.includes(day)}
                                                        onChange={(e) => {
                                                            const newDays = e.target.checked
                                                                ? [...data.training_info.days, day]
                                                                : data.training_info.days.filter(d => d !== day);
                                                            setData('training_info', { ...data.training_info, days: newDays });
                                                        }}
                                                        className="w-4 h-4 rounded border-gray-300 text-accent-yellow focus:ring-accent-yellow"
                                                    />
                                                    <span className="text-sm text-gray-700">{day}</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-start gap-2">
                                    <Clock className="w-4 h-4 text-gray-400 mt-2.5" />
                                    <div className="flex-1 grid grid-cols-2 gap-2">
                                        <div>
                                            <InputLabel htmlFor="training_start_time" value="Jam Mulai" className="text-xs !mb-1" />
                                            <select
                                                id="training_start_time"
                                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm"
                                                value={data.training_info.start_time}
                                                onChange={(e) => setData('training_info', { ...data.training_info, start_time: e.target.value })}
                                            >
                                                <option value="">Pilih Jam</option>
                                                {Array.from({ length: 13 }, (_, i) => {
                                                    const hour = 6 + i;
                                                    const timeStr = `${hour.toString().padStart(2, '0')}:00`;
                                                    return <option key={timeStr} value={timeStr}>{timeStr}</option>;
                                                })}
                                            </select>
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="training_end_time" value="Jam Selesai" className="text-xs !mb-1" />
                                            <select
                                                id="training_end_time"
                                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow text-sm"
                                                value={data.training_info.end_time}
                                                onChange={(e) => setData('training_info', { ...data.training_info, end_time: e.target.value })}
                                            >
                                                <option value="">Pilih Jam</option>
                                                {Array.from({ length: 13 }, (_, i) => {
                                                    const hour = 6 + i;
                                                    const timeStr = `${hour.toString().padStart(2, '0')}:00`;
                                                    return <option key={timeStr} value={timeStr}>{timeStr}</option>;
                                                })}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-start gap-2">
                                    <MapPin className="w-4 h-4 text-gray-400 mt-2.5" />
                                    <div className="flex-1">
                                        <InputLabel htmlFor="training_location" value="Lokasi Latihan" className="text-xs !mb-1" />
                                        <TextInput
                                            id="training_location"
                                            type="text"
                                            className="block w-full text-sm"
                                            placeholder="Lapangan Basket / Ruang OSIS"
                                            value={data.training_info.location}
                                            onChange={(e) => setData('training_info', { ...data.training_info, location: e.target.value })}
                                        />
                                    </div>
                                </div>
                                <div className="flex items-start gap-2">
                                    <User className="w-4 h-4 text-gray-400 mt-2.5" />
                                    <div className="flex-1">
                                        <InputLabel htmlFor="training_coach" value="Pembina/Pelatih" className="text-xs !mb-1" />
                                        <TextInput
                                            id="training_coach"
                                            type="text"
                                            className="block w-full text-sm"
                                            placeholder="Nama pembina atau pelatih"
                                            value={data.training_info.coach}
                                            onChange={(e) => setData('training_info', { ...data.training_info, coach: e.target.value })}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <label className="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                id="is_active" 
                                checked={data.is_active} 
                                onChange={(e) => setData('is_active', e.target.checked)} 
                                className="w-4 h-4 rounded border-gray-300 text-accent-yellow focus:ring-accent-yellow" 
                            />
                            <span className="text-sm text-gray-700">Aktif</span>
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
