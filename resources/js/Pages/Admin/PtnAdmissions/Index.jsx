import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import {
    GraduationCap,
    Building2,
    Plus,
    Edit2,
    Trash2,
    X,
    Eye,
    ChevronRight,
    BarChart3,
    Users,
    Calendar,
    Check,
    XCircle
} from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import toast from 'react-hot-toast';

export default function Index({ batches, universities, stats }) {
    const [activeTab, setActiveTab] = useState('batches');
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [modalType, setModalType] = useState('batch');
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);

    const batchForm = useForm({
        name: '',
        type: 'SNBP',
        year: new Date().getFullYear(),
        description: '',
        is_published: true,
    });

    const universityForm = useForm({
        name: '',
        short_name: '',
        color: '#1E40AF',
        sort_order: 0,
    });

    const tabs = [
        { key: 'batches', label: 'Batch Penerimaan', description: 'Kelola data penerimaan per periode.', icon: Calendar },
        { key: 'universities', label: 'Daftar PTN', description: 'Kelola master data PTN.', icon: Building2 },
    ];

    const openBatchModal = (batch = null) => {
        setModalType('batch');
        if (batch) {
            setEditMode(true);
            setCurrentId(batch.id);
            batchForm.setData({
                name: batch.name,
                type: batch.type,
                year: batch.year,
                description: batch.description || '',
                is_published: batch.is_published,
            });
        } else {
            setEditMode(false);
            setCurrentId(null);
            batchForm.reset();
        }
        setIsModalOpen(true);
    };

    const openUniversityModal = (university = null) => {
        setModalType('university');
        if (university) {
            setEditMode(true);
            setCurrentId(university.id);
            universityForm.setData({
                name: university.name,
                short_name: university.short_name || '',
                color: university.color || '#1E40AF',
                sort_order: university.sort_order || 0,
            });
        } else {
            setEditMode(false);
            setCurrentId(null);
            universityForm.reset();
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        batchForm.reset();
        universityForm.reset();
    };

    const handleBatchSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            batchForm.put(route('admin.ptn-admissions.batches.update', currentId), {
                onSuccess: () => {
                    closeModal();
                    toast.success('Batch berhasil diperbarui');
                },
            });
        } else {
            batchForm.post(route('admin.ptn-admissions.batches.store'), {
                onSuccess: () => {
                    closeModal();
                    toast.success('Batch berhasil ditambahkan');
                },
            });
        }
    };

    const handleUniversitySubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            universityForm.put(route('admin.ptn-admissions.universities.update', currentId), {
                onSuccess: () => {
                    closeModal();
                    toast.success('PTN berhasil diperbarui');
                },
            });
        } else {
            universityForm.post(route('admin.ptn-admissions.universities.store'), {
                onSuccess: () => {
                    closeModal();
                    toast.success('PTN berhasil ditambahkan');
                },
            });
        }
    };

    const handleDeleteBatch = (id) => {
        if (confirm('Hapus batch ini? Semua data penerimaan dalam batch akan ikut terhapus.')) {
            router.delete(route('admin.ptn-admissions.batches.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Batch berhasil dihapus'),
            });
        }
    };

    const handleDeleteUniversity = (id) => {
        if (confirm('Hapus PTN ini? PTN yang memiliki data penerimaan tidak bisa dihapus.')) {
            router.delete(route('admin.ptn-admissions.universities.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('PTN berhasil dihapus'),
                onError: () => toast.error('PTN tidak bisa dihapus karena masih memiliki data penerimaan'),
            });
        }
    };

    return (
        <ContentManagementPage
            headerTitle="Kelola Serapan PTN"
            headTitle="Data Serapan PTN"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            noForm={true}
        >
            {/* Stats Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                        <Calendar className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-gray-900">{stats.total_batches}</p>
                        <p className="text-sm text-gray-500">Total Batch</p>
                    </div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                        <Users className="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-gray-900">{stats.total_admissions}</p>
                        <p className="text-sm text-gray-500">Total Siswa Diterima</p>
                    </div>
                </div>
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                        <Building2 className="w-6 h-6 text-purple-600" />
                    </div>
                    <div>
                        <p className="text-2xl font-bold text-gray-900">{stats.total_universities}</p>
                        <p className="text-sm text-gray-500">PTN Tujuan</p>
                    </div>
                </div>
            </div>

            {activeTab === 'batches' && (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <h3 className="font-bold text-gray-900 text-lg">Batch Penerimaan</h3>
                        <PrimaryButton
                            type="button"
                            onClick={() => openBatchModal()}
                            className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2"
                        >
                            <Plus size={18} />Tambah Batch
                        </PrimaryButton>
                    </div>
                    {batches.length > 0 ? (
                        <div className="divide-y divide-gray-100">
                            {batches.map((batch) => (
                                <div key={batch.id} className="p-4 hover:bg-gray-50 transition-colors">
                                    <div className="flex flex-col sm:flex-row justify-between gap-4">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-2">
                                                <h4 className="font-semibold text-gray-900">{batch.name}</h4>
                                                <span className={`px-2.5 py-0.5 text-xs font-semibold rounded-full ${
                                                    batch.type === 'SNBP' ? 'bg-blue-100 text-blue-700' :
                                                    batch.type === 'SNBT' ? 'bg-green-100 text-green-700' :
                                                    'bg-purple-100 text-purple-700'
                                                }`}>
                                                    {batch.type}
                                                </span>
                                                {batch.is_published ? (
                                                    <span className="flex items-center gap-1 text-xs text-green-600">
                                                        <Check size={12} /> Publik
                                                    </span>
                                                ) : (
                                                    <span className="flex items-center gap-1 text-xs text-gray-400">
                                                        <XCircle size={12} /> Draft
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-4 text-sm text-gray-600">
                                                <span className="flex items-center gap-1">
                                                    <Users size={14} /> {batch.total_students} siswa
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Building2 size={14} /> {batch.summaries?.length || 0} PTN
                                                </span>
                                            </div>
                                            {batch.summaries?.length > 0 && (
                                                <div className="flex flex-wrap gap-2 mt-3">
                                                    {batch.summaries.slice(0, 5).map((summary, idx) => (
                                                        <span
                                                            key={idx}
                                                            className="px-2 py-1 text-xs font-medium rounded-lg"
                                                            style={{
                                                                backgroundColor: `${summary.university_color}15`,
                                                                color: summary.university_color
                                                            }}
                                                        >
                                                            {summary.university_name}: {summary.count}
                                                        </span>
                                                    ))}
                                                    {batch.summaries.length > 5 && (
                                                        <span className="px-2 py-1 text-xs text-gray-500">
                                                            +{batch.summaries.length - 5} lainnya
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => router.visit(route('admin.ptn-admissions.batches.show', batch.id))}
                                                className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
                                                title="Lihat Detail"
                                            >
                                                <Eye size={18} />
                                            </button>
                                            <button
                                                onClick={() => openBatchModal(batch)}
                                                className="p-2 text-accent-yellow hover:bg-accent-yellow/10 rounded-lg"
                                                title="Edit"
                                            >
                                                <Edit2 size={18} />
                                            </button>
                                            <button
                                                onClick={() => handleDeleteBatch(batch.id)}
                                                className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                                title="Hapus"
                                            >
                                                <Trash2 size={18} />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-12 text-center">
                            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <Calendar className="w-8 h-8 text-gray-400" />
                            </div>
                            <p className="text-gray-500 mb-4">Belum ada batch penerimaan</p>
                            <PrimaryButton
                                onClick={() => openBatchModal()}
                                className="!bg-accent-yellow !text-gray-900"
                            >
                                Tambah Batch Pertama
                            </PrimaryButton>
                        </div>
                    )}
                </div>
            )}

            {activeTab === 'universities' && (
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <h3 className="font-bold text-gray-900 text-lg">Daftar PTN</h3>
                        <PrimaryButton
                            type="button"
                            onClick={() => openUniversityModal()}
                            className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2"
                        >
                            <Plus size={18} />Tambah PTN
                        </PrimaryButton>
                    </div>
                    {universities.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PTN</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Warna</th>
                                        <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Urutan</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {universities.map((uni) => (
                                        <tr key={uni.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="font-semibold text-gray-900">{uni.name}</p>
                                                    {uni.short_name && (
                                                        <p className="text-sm text-gray-500">{uni.short_name}</p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <div
                                                        className="w-6 h-6 rounded-full border border-gray-200"
                                                        style={{ backgroundColor: uni.color }}
                                                    />
                                                    <span className="text-sm text-gray-600">{uni.color}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <span className="text-sm text-gray-600">{uni.sort_order}</span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => openUniversityModal(uni)}
                                                        className="p-2 text-accent-yellow hover:bg-accent-yellow/10 rounded-lg"
                                                    >
                                                        <Edit2 size={16} />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteUniversity(uni.id)}
                                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
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
                    ) : (
                        <div className="p-12 text-center">
                            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <Building2 className="w-8 h-8 text-gray-400" />
                            </div>
                            <p className="text-gray-500 mb-4">Belum ada PTN terdaftar</p>
                            <PrimaryButton
                                onClick={() => openUniversityModal()}
                                className="!bg-accent-yellow !text-gray-900"
                            >
                                Tambah PTN Pertama
                            </PrimaryButton>
                        </div>
                    )}
                </div>
            )}

            {/* Modal */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="lg">
                <div className="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-auto">
                    <div className="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 className="text-xl font-bold text-gray-900">
                            {modalType === 'batch'
                                ? (editMode ? 'Edit Batch' : 'Tambah Batch')
                                : (editMode ? 'Edit PTN' : 'Tambah PTN')
                            }
                        </h3>
                        <button onClick={closeModal} className="p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                            <X size={20} />
                        </button>
                    </div>

                    {modalType === 'batch' ? (
                        <form onSubmit={handleBatchSubmit} className="p-6 space-y-4">
                            <div>
                                <InputLabel value="Nama Batch" />
                                <TextInput
                                    value={batchForm.data.name}
                                    onChange={(e) => batchForm.setData('name', e.target.value)}
                                    placeholder="SNBP 2025"
                                    className="w-full mt-1"
                                />
                                <InputError message={batchForm.errors.name} className="mt-1" />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Tipe" />
                                    <select
                                        value={batchForm.data.type}
                                        onChange={(e) => batchForm.setData('type', e.target.value)}
                                        className="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring-accent-yellow"
                                    >
                                        <option value="SNBP">SNBP</option>
                                        <option value="SNBT">SNBT</option>
                                        <option value="Mandiri">Mandiri</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="Tahun" />
                                    <TextInput
                                        type="number"
                                        value={batchForm.data.year}
                                        onChange={(e) => batchForm.setData('year', parseInt(e.target.value))}
                                        min="2000"
                                        max="2100"
                                        className="w-full mt-1"
                                    />
                                    <InputError message={batchForm.errors.year} className="mt-1" />
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Deskripsi (Opsional)" />
                                <textarea
                                    value={batchForm.data.description}
                                    onChange={(e) => batchForm.setData('description', e.target.value)}
                                    rows={3}
                                    className="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring-accent-yellow"
                                    placeholder="Keterangan tambahan..."
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_published"
                                    checked={batchForm.data.is_published}
                                    onChange={(e) => batchForm.setData('is_published', e.target.checked)}
                                    className="w-4 h-4 text-accent-yellow rounded focus:ring-accent-yellow"
                                />
                                <label htmlFor="is_published" className="text-sm text-gray-700">
                                    Tampilkan di halaman publik
                                </label>
                            </div>
                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                                >
                                    Batal
                                </button>
                                <PrimaryButton
                                    type="submit"
                                    disabled={batchForm.processing}
                                    className="!bg-accent-yellow !text-gray-900"
                                >
                                    {batchForm.processing ? 'Menyimpan...' : 'Simpan'}
                                </PrimaryButton>
                            </div>
                        </form>
                    ) : (
                        <form onSubmit={handleUniversitySubmit} className="p-6 space-y-4">
                            <div>
                                <InputLabel value="Nama PTN" />
                                <TextInput
                                    value={universityForm.data.name}
                                    onChange={(e) => universityForm.setData('name', e.target.value)}
                                    placeholder="Universitas Padjadjaran"
                                    className="w-full mt-1"
                                />
                                <InputError message={universityForm.errors.name} className="mt-1" />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Nama Singkat" />
                                    <TextInput
                                        value={universityForm.data.short_name}
                                        onChange={(e) => universityForm.setData('short_name', e.target.value)}
                                        placeholder="UNPAD"
                                        className="w-full mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Warna" />
                                    <div className="flex items-center gap-2 mt-1">
                                        <input
                                            type="color"
                                            value={universityForm.data.color}
                                            onChange={(e) => universityForm.setData('color', e.target.value)}
                                            className="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer"
                                        />
                                        <TextInput
                                            value={universityForm.data.color}
                                            onChange={(e) => universityForm.setData('color', e.target.value)}
                                            placeholder="#1E40AF"
                                            className="flex-1"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Urutan" />
                                <TextInput
                                    type="number"
                                    value={universityForm.data.sort_order}
                                    onChange={(e) => universityForm.setData('sort_order', parseInt(e.target.value))}
                                    className="w-full mt-1"
                                />
                            </div>
                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <button
                                    type="button"
                                    onClick={closeModal}
                                    className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                                >
                                    Batal
                                </button>
                                <PrimaryButton
                                    type="submit"
                                    disabled={universityForm.processing}
                                    className="!bg-accent-yellow !text-gray-900"
                                >
                                    {universityForm.processing ? 'Menyimpan...' : 'Simpan'}
                                </PrimaryButton>
                            </div>
                        </form>
                    )}
                </div>
            </Modal>
        </ContentManagementPage>
    );
}
