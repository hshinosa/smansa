import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Plus,
    Edit2,
    Trash2,
    X,
    Upload,
    Users,
    Building2,
    Search,
    Filter,
    BarChart3
} from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import toast from 'react-hot-toast';

export default function Show({ batch, admissions, universities }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isImportModalOpen, setIsImportModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [currentId, setCurrentId] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [filterUniversity, setFilterUniversity] = useState('');

    const { data, setData, post, put, processing, errors, reset } = useForm({
        university_id: '',
        program_studi: '',
        count: '',
    });

    const importForm = useForm({
        file: null,
    });

    const openModal = (admission = null) => {
        if (admission) {
            setEditMode(true);
            setCurrentId(admission.id);
            setData({
                university_id: admission.university_id.toString(),
                program_studi: admission.program_studi,
                count: admission.count,
            });
        } else {
            setEditMode(false);
            setCurrentId(null);
            reset();
        }
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
    };

    const handleImportSubmit = (e) => {
        e.preventDefault();
        importForm.post(route('admin.ptn-admissions.batches.import-excel', batch.id), {
            forceFormData: true,
            onSuccess: () => {
                setIsImportModalOpen(false);
                importForm.reset();
                toast.success('Import berhasil!');
            },
            onError: () => {
                toast.error('Import gagal. Periksa format file.');
            }
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            put(route('admin.ptn-admissions.admissions.update', currentId), {
                onSuccess: () => {
                    closeModal();
                    toast.success('Data berhasil diperbarui');
                },
            });
        } else {
            post(route('admin.ptn-admissions.batches.admissions.store', batch.id), {
                onSuccess: () => {
                    closeModal();
                    toast.success('Data berhasil ditambahkan');
                },
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Hapus data ini?')) {
            router.delete(route('admin.ptn-admissions.admissions.destroy', id), {
                preserveScroll: true,
                onSuccess: () => toast.success('Data berhasil dihapus'),
            });
        }
    };

    const filteredAdmissions = admissions.filter((admission) => {
        const matchesSearch = !searchQuery ||
            admission.program_studi.toLowerCase().includes(searchQuery.toLowerCase()) ||
            admission.university_name.toLowerCase().includes(searchQuery.toLowerCase());
        const matchesUniversity = !filterUniversity || admission.university_id.toString() === filterUniversity;
        return matchesSearch && matchesUniversity;
    });

    const tabs = [{ key: 'list', label: 'Data Penerimaan', description: 'Kelola data jumlah penerimaan per jurusan.', icon: BarChart3 }];

    return (
        <ContentManagementPage
            headerTitle={`${batch.name}`}
            headTitle={`Data ${batch.name}`}
            tabs={tabs}
            activeTab="list"
            setActiveTab={() => {}}
            noForm={true}
        >
            {/* Back Link */}
            <div className="mb-4">
                <Link
                    href={route('admin.ptn-admissions.index')}
                    className="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <ArrowLeft size={18} />
                    <span>Kembali ke Daftar Batch</span>
                </Link>
            </div>

            {/* Batch Info */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div className="flex flex-col sm:flex-row justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900">{batch.name}</h2>
                        <div className="flex items-center gap-4 mt-2 text-sm text-gray-600">
                            <span className={`px-2.5 py-0.5 font-semibold rounded-full ${
                                batch.type === 'SNBP' ? 'bg-blue-100 text-blue-700' :
                                batch.type === 'SNBT' ? 'bg-green-100 text-green-700' :
                                'bg-purple-100 text-purple-700'
                            }`}>
                                {batch.type}
                            </span>
                            <span>Tahun {batch.year}</span>
                            <span className="flex items-center gap-1">
                                <Users size={14} /> {batch.total_students} siswa diterima
                            </span>
                        </div>
                        {batch.description && (
                            <p className="mt-3 text-gray-600">{batch.description}</p>
                        )}
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div className="p-5 border-b border-gray-200">
                    <div className="flex flex-col lg:flex-row justify-between gap-4">
                        <div className="flex flex-col sm:flex-row gap-3 flex-1">
                            {/* Search */}
                            <div className="relative flex-1 max-w-md">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="Cari PTN atau jurusan..."
                                    className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-accent-yellow focus:border-accent-yellow"
                                />
                            </div>
                            {/* Filter University */}
                            <select
                                value={filterUniversity}
                                onChange={(e) => setFilterUniversity(e.target.value)}
                                className="border border-gray-300 rounded-lg px-3 py-2 focus:ring-accent-yellow focus:border-accent-yellow"
                            >
                                <option value="">Semua PTN</option>
                                {universities.map((uni) => (
                                    <option key={uni.id} value={uni.id}>{uni.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex gap-2">
                            <PrimaryButton
                                type="button"
                                onClick={() => setIsImportModalOpen(true)}
                                className="!bg-green-600 !text-white hover:!bg-green-700 flex items-center gap-2"
                            >
                                <Upload size={18} /> Import Excel
                            </PrimaryButton>
                            <PrimaryButton
                                type="button"
                                onClick={() => openModal()}
                                className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 flex items-center gap-2"
                            >
                                <Plus size={18} />Tambah Data
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                {filteredAdmissions.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PTN</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jurusan / Program Studi</th>
                                    <th className="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {filteredAdmissions.map((admission) => (
                                    <tr key={admission.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <span className="font-medium text-gray-900">{admission.university_name}</span>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">{admission.program_studi}</td>
                                        <td className="px-4 py-3 text-center">
                                            <span className="px-3 py-1 text-sm font-bold rounded-lg bg-gray-100 text-gray-900">
                                                {admission.count}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center justify-end gap-1">
                                                <button
                                                    onClick={() => openModal(admission)}
                                                    className="p-2 text-accent-yellow hover:bg-accent-yellow/10 rounded-lg"
                                                >
                                                    <Edit2 size={16} />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(admission.id)}
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
                            <BarChart3 className="w-8 h-8 text-gray-400" />
                        </div>
                        <p className="text-gray-500 mb-4">
                            {searchQuery || filterUniversity
                                ? 'Tidak ada data yang sesuai filter'
                                : 'Belum ada data penerimaan'
                            }
                        </p>
                        {!searchQuery && !filterUniversity && (
                            <PrimaryButton
                                onClick={() => openModal()}
                                className="!bg-accent-yellow !text-gray-900"
                            >
                                Tambah Data Pertama
                            </PrimaryButton>
                        )}
                    </div>
                )}
            </div>

            {/* Modal Import */}
            <Modal show={isImportModalOpen} onClose={() => setIsImportModalOpen(false)} maxWidth="lg">
                <div className="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-auto">
                    <div className="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 className="text-xl font-bold text-gray-900">Import Data Excel</h3>
                        <button onClick={() => setIsImportModalOpen(false)} className="p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                            <X size={20} />
                        </button>
                    </div>
                    <form onSubmit={handleImportSubmit} className="p-6 space-y-4">
                        <div className="p-4 bg-blue-50 text-blue-700 rounded-lg text-sm mb-4">
                            <p className="font-semibold mb-1">Panduan Import:</p>
                            <ul className="list-disc pl-4 space-y-1">
                                <li>Format file: <strong>.xlsx, .csv, atau .pdf</strong></li>
                                <li>Kolom wajib Excel: <strong>PTN, Jurusan, Jumlah</strong></li>
                                <li>Format PDF: Teks baris per baris dengan format <strong>[Nama PTN] [Nama Jurusan] [Angka Jumlah]</strong></li>
                                <li>PTN baru akan otomatis dibuat</li>
                            </ul>
                            <a 
                                href={route('admin.ptn-admissions.download-template')} 
                                target="_blank"
                                className="inline-block mt-3 text-blue-600 underline hover:text-blue-800 font-medium"
                            >
                                Download Template Excel
                            </a>
                        </div>

                        <div>
                            <InputLabel value="File Import" />
                            <input
                                type="file"
                                accept=".xlsx, .xls, .csv, .pdf"
                                onChange={(e) => importForm.setData('file', e.target.files[0])}
                                className="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100"
                            />
                            <InputError message={importForm.errors.file} className="mt-1" />
                        </div>

                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <button
                                type="button"
                                onClick={() => setIsImportModalOpen(false)}
                                className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg"
                            >
                                Batal
                            </button>
                            <PrimaryButton
                                type="submit"
                                disabled={importForm.processing}
                                className="!bg-green-600 !text-white hover:!bg-green-700"
                            >
                                {importForm.processing ? 'Mengupload...' : 'Upload & Import'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            {/* Modal Create/Edit */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="lg">
                <div className="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-auto">
                    <div className="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 className="text-xl font-bold text-gray-900">
                            {editMode ? 'Edit Data' : 'Tambah Data'}
                        </h3>
                        <button onClick={closeModal} className="p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                            <X size={20} />
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="p-6 space-y-4">
                        <div>
                            <InputLabel value="PTN Tujuan *" />
                            <select
                                value={data.university_id}
                                onChange={(e) => setData('university_id', e.target.value)}
                                className="w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-accent-yellow focus:ring-accent-yellow"
                            >
                                <option value="">Pilih PTN</option>
                                {universities.map((uni) => (
                                    <option key={uni.id} value={uni.id}>{uni.name}</option>
                                ))}
                            </select>
                            <InputError message={errors.university_id} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel value="Jurusan / Program Studi *" />
                            <TextInput
                                value={data.program_studi}
                                onChange={(e) => setData('program_studi', e.target.value)}
                                placeholder="Contoh: Teknik Informatika"
                                className="w-full mt-1"
                            />
                            <InputError message={errors.program_studi} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel value="Jumlah Mahasiswa Diterima (Total per Jurusan) *" />
                            <TextInput
                                type="number"
                                value={data.count}
                                onChange={(e) => setData('count', e.target.value)}
                                placeholder="0"
                                min="1"
                                className="w-full mt-1"
                            />
                            <InputError message={errors.count} className="mt-1" />
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
                                disabled={processing}
                                className="!bg-accent-yellow !text-gray-900"
                            >
                                {processing ? 'Menyimpan...' : 'Simpan'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </ContentManagementPage>
    );
}
