import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Plus, Trash2, Edit, Save, X, ChevronRight, BarChart3 } from 'lucide-react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Index({ groups }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    
    // Form untuk tambah data
    const { data, setData, post, processing, errors, reset } = useForm({
        academic_year: new Date().getFullYear().toString(),
        exam_type: 'UTBK',
        subject_name: '',
        average_score: '',
    });

    const openModal = () => {
        reset();
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.tka-averages.store'), {
            onSuccess: () => closeModal(),
        });
    };

    const handleDeleteGroup = (year, type) => {
        if (confirm(`Yakin ingin menghapus seluruh data ${type} ${year}?`)) {
            router.delete(route('admin.tka-averages.group.destroy'), {
                data: { academic_year: year, exam_type: type }
            });
        }
    };

    return (
        <AdminLayout
            title="Hasil TKA"
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Manajemen Hasil TKA
                    </h2>
                    <PrimaryButton onClick={openModal} className="!bg-primary">
                        <Plus className="w-4 h-4 mr-2" />
                        Tambah Data
                    </PrimaryButton>
                </div>
            }
        >
            <Head title="Manajemen Hasil TKA" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* LIST GROUPS */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {groups.map((group, idx) => (
                            <div key={idx} className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 hover:shadow-md transition-shadow">
                                <div className="p-6">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <span className="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">
                                                {group.academic_year}
                                            </span>
                                            <h3 className="text-lg font-bold text-gray-900 mt-2">{group.exam_type}</h3>
                                        </div>
                                        <button 
                                            onClick={() => handleDeleteGroup(group.academic_year, group.exam_type)}
                                            className="text-gray-400 hover:text-red-500 transition-colors"
                                            title="Hapus Grup"
                                        >
                                            <Trash2 className="w-5 h-5" />
                                        </button>
                                    </div>
                                    
                                    <div className="flex justify-between items-center text-sm text-gray-500 mb-4">
                                        <span>{group.subject_count} Mata Pelajaran</span>
                                        <span className="flex items-center gap-1 font-medium text-gray-700">
                                            <BarChart3 className="w-4 h-4" />
                                            Avg: {parseFloat(group.overall_average).toFixed(2)}
                                        </span>
                                    </div>

                                    <div className="pt-4 border-t border-gray-100">
                                        <a 
                                            href={route('admin.tka-averages.show', [group.academic_year, group.exam_type])}
                                            className="text-primary hover:text-primary/80 text-sm font-medium flex items-center justify-center w-full"
                                        >
                                            Lihat Detail <ChevronRight className="w-4 h-4 ml-1" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        ))}
                        
                        {groups.length === 0 && (
                            <div className="col-span-full text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                                <p className="text-gray-500">Belum ada data Hasil TKA.</p>
                                <button onClick={openModal} className="text-primary font-medium mt-2 hover:underline">
                                    Tambah Data Pertama
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* MODAL CREATE */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="md">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-bold text-gray-900">Tambah Data Nilai</h3>
                        <button onClick={closeModal}><X className="w-5 h-5 text-gray-500" /></button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Tahun Akademik" />
                                <TextInput
                                    value={data.academic_year}
                                    onChange={(e) => setData('academic_year', e.target.value)}
                                    placeholder="2024"
                                    className="w-full mt-1"
                                />
                                <InputError message={errors.academic_year} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Jenis Ujian" />
                                <TextInput
                                    value={data.exam_type}
                                    onChange={(e) => setData('exam_type', e.target.value)}
                                    placeholder="UTBK / TO 1"
                                    className="w-full mt-1"
                                />
                                <InputError message={errors.exam_type} className="mt-1" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Nama Mata Pelajaran" />
                            <TextInput
                                value={data.subject_name}
                                onChange={(e) => setData('subject_name', e.target.value)}
                                placeholder="Contoh: Matematika Saintek"
                                className="w-full mt-1"
                            />
                            <InputError message={errors.subject_name} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel value="Nilai Rata-rata" />
                            <TextInput
                                type="number"
                                step="0.01"
                                value={data.average_score}
                                onChange={(e) => setData('average_score', e.target.value)}
                                placeholder="0.00"
                                className="w-full mt-1"
                            />
                            <InputError message={errors.average_score} className="mt-1" />
                        </div>

                        <div className="flex justify-end gap-3 pt-4">
                            <SecondaryButton onClick={closeModal}>Batal</SecondaryButton>
                            <PrimaryButton disabled={processing}>Simpan</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AdminLayout>
    );
}
