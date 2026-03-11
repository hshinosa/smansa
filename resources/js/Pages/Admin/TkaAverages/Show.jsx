import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Plus, Trash2, Edit, Save, X, ArrowLeft } from 'lucide-react';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Show({ academic_year, exam_type, subjects }) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [editId, setEditId] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        academic_year: academic_year,
        exam_type: exam_type,
        subject_name: '',
        average_score: '',
    });

    const openCreateModal = () => {
        setEditMode(false);
        setEditId(null);
        setData({
            academic_year: academic_year,
            exam_type: exam_type,
            subject_name: '',
            average_score: '',
        });
        setIsModalOpen(true);
    };

    const openEditModal = (subject) => {
        setEditMode(true);
        setEditId(subject.id);
        setData({
            academic_year: subject.academic_year,
            exam_type: subject.exam_type,
            subject_name: subject.subject_name,
            average_score: subject.average_score,
        });
        setIsModalOpen(true);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            // Update logic (we reuse store since updateOrCreate is used in controller, but safer to use separate route usually)
            // But controller uses updateOrCreate based on keys.
            // If we change subject_name, it creates new row? Yes.
            // So edit should technically be update ID. But controller store uses updateOrCreate by keys.
            // Let's rely on store for simplicity or update controller later.
            // Actually, for edit name, we need ID update.
            // For now, I will use store and assume user doesn't change name often, or delete old one.
            // Wait, standard resource update uses ID.
            // I should use PUT /tka-averages/{id}.
            // But I didn't make update method in controller for ID, I made resource.
            // Resource controller has update(Request, TkaAverage).
            // Let's use that.
            router.put(route('admin.tka-averages.update', editId), data, {
                onSuccess: () => closeModal(),
            });
        } else {
            post(route('admin.tka-averages.store'), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = (id) => {
        if (confirm('Hapus mata pelajaran ini?')) {
            router.delete(route('admin.tka-averages.destroy', id));
        }
    };

    return (
        <AdminLayout
            title={`Detail ${exam_type} ${academic_year}`}
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-4">
                        <Link href={route('admin.tka-averages.index')} className="text-gray-500 hover:text-gray-700">
                            <ArrowLeft className="w-6 h-6" />
                        </Link>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {exam_type} - {academic_year}
                        </h2>
                    </div>
                    <PrimaryButton onClick={openCreateModal} className="!bg-primary">
                        <Plus className="w-4 h-4 mr-2" />
                        Tambah Mapel
                    </PrimaryButton>
                </div>
            }
        >
            <Head title={`Detail ${exam_type}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Rata-rata</th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {subjects.map((subject) => (
                                            <tr key={subject.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {subject.subject_name}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">
                                                    {subject.average_score}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button 
                                                        onClick={() => openEditModal(subject)}
                                                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                                                    >
                                                        <Edit className="w-5 h-5" />
                                                    </button>
                                                    <button 
                                                        onClick={() => handleDelete(subject.id)}
                                                        className="text-red-600 hover:text-red-900"
                                                    >
                                                        <Trash2 className="w-5 h-5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                        {subjects.length === 0 && (
                                            <tr>
                                                <td colSpan="3" className="px-6 py-12 text-center text-gray-500">
                                                    Belum ada mata pelajaran.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* MODAL */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="md">
                <div className="p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-bold text-gray-900">
                            {editMode ? 'Edit Nilai' : 'Tambah Mata Pelajaran'}
                        </h3>
                        <button onClick={closeModal}><X className="w-5 h-5 text-gray-500" /></button>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Hidden fields for Year/Type to keep consistency */}
                        
                        <div>
                            <InputLabel value="Nama Mata Pelajaran" />
                            <TextInput
                                value={data.subject_name}
                                onChange={(e) => setData('subject_name', e.target.value)}
                                placeholder="Contoh: Matematika"
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
