// FILE: resources/js/Pages/Admin/Teachers/Index.jsx
// Redesigned with consistent card-based UI and accessibility

import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import { useContentManagement } from '@/Hooks/useContentManagement';
import { Layout, Users, UserCog, X, User, Edit, Trash2, ChevronLeft, ChevronRight } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FileUploadField from '@/Components/Admin/FileUploadField';
import Modal from '@/Components/Modal';
import toast from 'react-hot-toast';
import { getImageUrl } from '@/Utils/imageUtils';

export default function Index({ teachers, currentSettings }) {
    const [activeTab, setActiveTab] = useState('pimpinan');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);

    // Form for teacher data
    const { data, setData, post, delete: destroy, processing, errors, reset, transform } = useForm({
        name: '',
        nip: '',
        type: 'guru',
        position: '',
        department: '',
        email: '',
        image: null,
        image_url: '',
    });

    const tabs = [
        // Pimpinan
        { key: 'pimpinan', label: 'Pimpinan', description: 'Kepala Sekolah & Wakasek', icon: Users, filter: (t) => ['Kepala Sekolah', 'Wakasek'].includes(t.position) },
        
        // MIPA
        { key: 'matematika', label: 'Matematika', description: 'MGMP Matematika', icon: Users, filter: (t) => t.department === 'Matematika' },
        { key: 'fisika', label: 'Fisika', description: 'MGMP Fisika', icon: Users, filter: (t) => t.department === 'Fisika' },
        { key: 'kimia', label: 'Kimia', description: 'MGMP Kimia', icon: Users, filter: (t) => t.department === 'Kimia' },
        { key: 'biologi', label: 'Biologi', description: 'MGMP Biologi', icon: Users, filter: (t) => t.department === 'Biologi' },
        
        // IPS
        { key: 'ekonomi', label: 'Ekonomi', description: 'MGMP Ekonomi', icon: Users, filter: (t) => t.department === 'Ekonomi' },
        { key: 'geografi', label: 'Geografi', description: 'MGMP Geografi', icon: Users, filter: (t) => t.department === 'Geografi' },
        { key: 'sejarah', label: 'Sejarah', description: 'MGMP Sejarah', icon: Users, filter: (t) => t.department === 'Sejarah' },
        { key: 'sosiologi', label: 'Sosiologi', description: 'MGMP Sosiologi', icon: Users, filter: (t) => t.department === 'Sosiologi' },
        
        // Bahasa
        { key: 'bahasa-indonesia', label: 'Bahasa Indonesia', description: 'MGMP Bahasa Indonesia', icon: Users, filter: (t) => t.department === 'Bahasa Indonesia' },
        { key: 'bahasa-inggris', label: 'Bahasa Inggris', description: 'MGMP Bahasa Inggris', icon: Users, filter: (t) => t.department === 'Bahasa Inggris' },
        { key: 'bahasa-sunda', label: 'Bahasa Sunda', description: 'MGMP Bahasa Sunda', icon: Users, filter: (t) => t.department === 'Bahasa Sunda' },
        { key: 'bahasa-jepang', label: 'Bahasa Jepang', description: 'MGMP Bahasa Jepang', icon: Users, filter: (t) => t.department === 'Bahasa Jepang' },
        
        // Agama & Kewarganegaraan
        { key: 'pai', label: 'Pendidikan Agama Islam', description: 'MGMP PAI', icon: Users, filter: (t) => ['Pendidikan Agama Islam', 'PAI'].includes(t.department) },
        { key: 'ppkn', label: 'Pendidikan Pancasila', description: 'MGMP PPKN', icon: Users, filter: (t) => ['Pendidikan Pancasila', 'PPKN'].includes(t.department) },
        { key: 'bk', label: 'Bimbingan Konseling', description: 'Guru BK', icon: Users, filter: (t) => t.department === 'Bimbingan Konseling' },
        
        // Vokasi & Seni
        { key: 'penjasorkes', label: 'Penjasorkes', description: 'MGMP PJOK', icon: Users, filter: (t) => ['Penjasorkes', 'PJOK', 'Pendidikan Jasmani'].includes(t.department) },
        { key: 'pkwu', label: 'PKWU', description: 'MGMP PKWU', icon: Users, filter: (t) => ['Prakarya dan Kewirausahaan', 'PKWU', 'Prakarya'].includes(t.department) },
        { key: 'seni-budaya', label: 'Seni Budaya', description: 'MGMP Seni Budaya', icon: Users, filter: (t) => ['Seni Budaya', 'Seni'].includes(t.department) },
        
        // Staff
        { key: 'staff', label: 'Staff & TU', description: 'Tenaga Kependidikan', icon: UserCog, filter: (t) => t.type === 'staff' },
    ];

    const openModal = (teacher = null, type = 'guru') => {
        if (teacher) {
            setEditMode(true);
            setCurrentId(teacher.id);
            setData({
                name: teacher.name || '',
                nip: teacher.nip || '',
                type: teacher.type || 'guru',
                position: teacher.position || '',
                department: teacher.department || '',
                email: teacher.email || '',
                image: null,
                image_url: teacher.image_url || '',
            });
        } else {
            setEditMode(false);
            setCurrentId(null);
            reset();
            setData('type', type);
            // Set default position based on type
            setData('position', type === 'guru' ? 'Guru' : 'Staff');
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
    };

    const handleTeacherSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            // Use transform to add _method for method spoofing with file uploads
            transform((formData) => ({
                ...formData,
                _method: 'PUT',
            }));
            post(route('admin.teachers.update', currentId), {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    reset(); // Reset form first
                    setIsModalOpen(false); // Then close modal
                    setEditMode(false);
                    setCurrentId(null);
                    toast.success('Data guru/staff berhasil diperbarui');
                },
            });
        } else {
            // Reset transform for create
            transform((formData) => formData);
            post(route('admin.teachers.store'), {
                onSuccess: () => {
                    reset(); // Reset form first
                    setIsModalOpen(false); // Then close modal
                    toast.success('Data guru/staff baru berhasil ditambahkan');
                },
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            destroy(route('admin.teachers.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Data guru/staff berhasil dihapus')
            });
        }
    };

    // Filter teachers by active tab
    const getFilteredTeachers = () => {
        const currentTab = tabs.find(t => t.key === activeTab);
        if (!currentTab || !currentTab.filter) return [];
        return teachers.filter(currentTab.filter);
    };

    const renderTabContent = () => {
        const currentTab = tabs.find(t => t.key === activeTab);
        if (!currentTab) return null;
        
        const filteredTeachers = getFilteredTeachers();
        const isStaffTab = activeTab === 'staff';
        
        return (
            <TeachersListSection
                key={activeTab}
                teachers={filteredTeachers}
                onEdit={(t) => openModal(t, isStaffTab ? 'staff' : 'guru')}
                onDelete={handleDelete}
            />
        );
    };

    const isStaffTab = activeTab === 'staff';
    const customAction = (
        <button
            onClick={() => openModal(null, isStaffTab ? 'staff' : 'guru')}
            className="flex items-center gap-2 px-4 py-2 !bg-accent-yellow !text-gray-900 rounded-lg hover:!bg-yellow-500 transition-colors text-sm font-bold shadow-sm"
        >
            <User size={18} />
            Tambah Data
        </button>
    );

    return (
        <ContentManagementPage
            headerTitle="Kelola Guru & Staff"
            headTitle="Guru & Staff"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            noForm={true}
            customAction={customAction}
        >
            <div className="space-y-6">
                {renderTabContent()}
            </div>

            {/* Modal for Add/Edit Teacher */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="7xl">
                <div className="relative bg-white rounded-2xl shadow-2xl w-full max-h-[90vh] flex flex-col">
                    {/* Header */}
                    <div className="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-2xl flex-shrink-0">
                        <div>
                            <h3 className="text-lg sm:text-xl font-bold text-gray-900">
                                {editMode ? 'Edit Data' : 'Tambah Data Baru'}
                            </h3>
                            <p className="text-sm text-gray-500 mt-0.5">
                                {editMode ? 'Perbarui data guru/staff' : 'Tambahkan data baru'}
                            </p>
                        </div>
                        <button
                            onClick={closeModal}
                            className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded-lg transition-colors flex-shrink-0"
                        >
                            <X size={20} />
                        </button>
                    </div>

                    {/* Form - Scrollable */}
                    <form onSubmit={handleTeacherSubmit} className="overflow-y-auto flex-1">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                                {/* Column 1: Media (3 cols) */}
                                <div className="md:col-span-3 space-y-4">
                                    <div className="w-full">
                                        <InputLabel htmlFor="image" value="Foto Profil" className="mb-2 block font-bold text-gray-700" />
                                        <FileUploadField
                                            id="image"
                                            onChange={(file) => setData('image', file)}
                                            previewUrl={data.image_url}
                                            label="Upload Foto"
                                        />
                                        <InputError message={errors.image} className="mt-2" />
                                    </div>
                                    
                                    <div className="bg-blue-50 border border-blue-100 rounded-xl p-4">
                                        <h4 className="text-xs font-bold text-blue-900 mb-2 flex items-center gap-1">
                                            <span className="text-lg">💡</span> Tips
                                        </h4>
                                        <ul className="text-xs text-blue-800 space-y-1 list-disc list-inside opacity-90">
                                            <li>Foto rasio 1:1 (persegi)</li>
                                            <li>Format JPG/PNG</li>
                                        </ul>
                                    </div>
                                </div>

                                {/* Column 2: Personal Data (5 cols) */}
                                <div className="md:col-span-5 space-y-5">
                                    <h4 className="text-sm font-bold text-gray-900 border-b pb-2 flex items-center gap-2">
                                        <User size={16} /> Data Pribadi
                                    </h4>
                                    
                                    <div className="space-y-4">
                                        <div>
                                            <InputLabel htmlFor="name" value="Nama Lengkap" />
                                            <TextInput
                                                id="name"
                                                type="text"
                                                className="mt-1 block w-full"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                required
                                                placeholder="Nama lengkap..."
                                            />
                                            <InputError message={errors.name} className="mt-2" />
                                        </div>

                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel htmlFor="nip" value="NIP (Opsional)" />
                                                <TextInput
                                                    id="nip"
                                                    type="text"
                                                    className="mt-1 block w-full"
                                                    value={data.nip}
                                                    onChange={(e) => setData('nip', e.target.value)}
                                                    placeholder="NIP..."
                                                />
                                            </div>
                                            <div>
                                                <InputLabel htmlFor="email" value="Email (Opsional)" />
                                                <TextInput
                                                    id="email"
                                                    type="email"
                                                    className="mt-1 block w-full"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    placeholder="Email..."
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Column 3: Employment Data (4 cols) */}
                                <div className="md:col-span-4 space-y-5">
                                    <h4 className="text-sm font-bold text-gray-900 border-b pb-2 flex items-center gap-2">
                                        <Users size={16} /> Data Kepegawaian
                                    </h4>
                                    
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <InputLabel htmlFor="type" value="Tipe" />
                                                <select
                                                    id="type"
                                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring-accent-yellow text-sm"
                                                    value={data.type}
                                                    onChange={(e) => setData('type', e.target.value)}
                                                >
                                                    <option value="guru">Guru</option>
                                                    <option value="staff">Staff</option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel htmlFor="position" value="Jabatan" />
                                                <select
                                                    id="position"
                                                    className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring-accent-yellow text-sm"
                                                    value={data.position}
                                                    onChange={(e) => setData('position', e.target.value)}
                                                    required
                                                >
                                                    <option value="">Pilih...</option>
                                                    <option value="Kepala Sekolah">Kepala Sekolah</option>
                                                    <option value="Wakasek">Wakasek</option>
                                                    <option value="Guru">Guru</option>
                                                    <option value="Staff">Staff</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <InputError message={errors.position} />

                                        <div>
                                            <InputLabel htmlFor="department" value="Unit / Bidang" />
                                            <TextInput
                                                id="department"
                                                type="text"
                                                className="mt-1 block w-full"
                                                value={data.department}
                                                onChange={(e) => setData('department', e.target.value)}
                                                placeholder={
                                                    data.position === 'Guru' ? 'Matematika' :
                                                    data.position === 'Staff' ? 'Tata Usaha' : 'Bidang/Unit'
                                                }
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                {data.position === 'Wakasek' ? 'Bidang Wakasek' :
                                                 data.position === 'Guru' ? 'Mata Pelajaran' : 'Unit Kerja'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Action buttons - Fixed at bottom */}
                        <div className="flex flex-col sm:flex-row justify-end gap-3 p-5 sm:p-6 border-t border-gray-200 bg-gray-50">
                            <button
                                type="button"
                                onClick={closeModal}
                                className="px-5 py-2.5 text-gray-700 hover:text-gray-900 font-medium transition-colors order-2 sm:order-1"
                            >
                                Batal
                            </button>
                            <PrimaryButton
                                type="submit"
                                disabled={processing}
                                className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 px-6 py-2.5 font-semibold order-1 sm:order-2"
                            >
                                {processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Tambah Data')}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </ContentManagementPage>
    );
}

// Teachers List Section Component
function TeachersListSection({ teachers, onEdit, onDelete }) {
    const [currentPage, setCurrentPage] = React.useState(1);
    const itemsPerPage = 7;
    
    // Pagination logic
    const totalPages = Math.ceil(teachers.length / itemsPerPage);
    const paginatedTeachers = teachers.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {teachers.length > 0 ? (
                <>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 sm:px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Foto</th>
                                    <th className="px-4 py-3 sm:px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th className="px-4 py-3 sm:px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Jabatan</th>
                                    <th className="px-4 py-3 sm:px-6 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {paginatedTeachers.map((teacher) => (
                                    <tr key={teacher.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-4 py-3 sm:px-6 whitespace-nowrap">
                                            <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                                {teacher.image_url ? (
                                                    <img src={getImageUrl(teacher.image_url)} alt={teacher.name} className="w-full h-full object-cover" />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-400">
                                                        <User size={20} className="sm:w-6 sm:h-6" />
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 sm:px-6">
                                            <div>
                                                <p className="font-semibold text-gray-900 text-sm">{teacher.name}</p>
                                                {teacher.nip && <p className="text-xs text-gray-500 font-mono mt-0.5">{teacher.nip}</p>}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 sm:px-6 text-sm text-gray-600 hidden sm:table-cell">{teacher.position}</td>
                                        <td className="px-4 py-3 sm:px-6 text-right">
                                            <div className="flex items-center justify-end gap-1 sm:gap-2">
                                                <button
                                                    onClick={() => onEdit(teacher)}
                                                    className="p-2 !text-accent-yellow hover:bg-accent-yellow/10 rounded-lg transition-colors"
                                                    title="Edit"
                                                >
                                                    <Edit size={16} />
                                                </button>
                                                <button
                                                    onClick={() => onDelete(teacher.id)}
                                                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Hapus"
                                                >
                                                    <Trash2 size={16} />
                                                </button>
                                            </div>
                                        </td>
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
                                        Showing <span className="font-medium">{(currentPage - 1) * itemsPerPage + 1}</span> to <span className="font-medium">{Math.min(currentPage * itemsPerPage, teachers.length)}</span> of <span className="font-medium">{teachers.length}</span> results
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
                    <div className="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                        <Users size={28} className="sm:w-8 sm:h-8 text-gray-400" />
                    </div>
                    <p className="text-gray-500 font-medium mb-4 text-sm sm:text-base">Belum ada data</p>
                </div>
            )}
        </div>
    );
}
