import React from 'react';
import { Users, X, Plus, Trash2 } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import MiniTextEditor from '@/Components/MiniTextEditor';

export default function RegistrationPathsSection({ data, setData, localErrors, formErrors, updateRequirementsArray, addRequirement, removeRequirement }) {
    const paths = data.jalur_pendaftaran?.items || [];

    const addPath = () => {
        const newPath = {
            label: 'Jalur Baru',
            description: '',
            quota: '0%',
            requirements: []
        };
        setData('jalur_pendaftaran', {
            ...data.jalur_pendaftaran,
            items: [...paths, newPath]
        });
    };

    const removePath = (index) => {
        const newPaths = paths.filter((_, i) => i !== index);
        setData('jalur_pendaftaran', {
            ...data.jalur_pendaftaran,
            items: newPaths
        });
    };

    const updatePath = (index, key, value) => {
        const newPaths = [...paths];
        newPaths[index] = { ...newPaths[index], [key]: value };
        setData('jalur_pendaftaran', {
            ...data.jalur_pendaftaran,
            items: newPaths
        });
    };

    const updatePathRequirement = (pathIndex, reqIndex, value) => {
        const newPaths = [...paths];
        const newRequirements = [...newPaths[pathIndex].requirements];
        newRequirements[reqIndex] = value;
        newPaths[pathIndex] = { ...newPaths[pathIndex], requirements: newRequirements };
        setData('jalur_pendaftaran', {
            ...data.jalur_pendaftaran,
            items: newPaths
        });
    };

    const addPathRequirement = (pathIndex) => {
        const newPaths = [...paths];
        newPaths[pathIndex] = { 
            ...newPaths[pathIndex], 
            requirements: [...(newPaths[pathIndex].requirements || []), ''] 
        };
        setData('jalur_pendaftaran', {
            ...data.jalur_pendaftaran,
            items: newPaths
        });
    };

    const removePathRequirement = (pathIndex, reqIndex) => {
        const newPaths = [...paths];
        const newRequirements = newPaths[pathIndex].requirements.filter((_, i) => i !== reqIndex);
        newPaths[pathIndex] = { ...newPaths[pathIndex], requirements: newRequirements };
        setData('jalur_pendaftaran', {
            ...data.jalur_pendaftaran,
            items: newPaths
        });
    };

    return (
        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
            <div className="border-b pb-3 flex justify-between items-center">
                <div>
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Daftar Jalur Pendaftaran
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Kelola jalur pendaftaran yang tersedia</p>
                </div>
                <button
                    type="button"
                    onClick={addPath}
                    className="flex items-center px-4 py-2 bg-primary text-white rounded-xl hover:bg-blue-700 transition-colors font-bold text-sm"
                >
                    <Plus size={18} className="mr-2" />
                    Tambah Jalur
                </button>
            </div>

            <div className="space-y-4">
                {paths.map((path, index) => (
                <div key={index} className="bg-gray-50 border-2 border-gray-200 rounded-xl p-6 relative group">
                    <button
                        type="button"
                        onClick={() => removePath(index)}
                        className="absolute top-4 right-4 p-2 text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity"
                        title="Hapus Jalur"
                    >
                        <Trash2 size={20} />
                    </button>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <InputLabel value="Nama Jalur" />
                            <TextInput
                                type="text"
                                className="mt-1 block w-full"
                                value={path.label || ''}
                                onChange={(e) => updatePath(index, 'label', e.target.value)}
                                placeholder="Contoh: Jalur Zonasi"
                            />
                        </div>
                        <div>
                            <InputLabel value="Kuota (Teks)" />
                            <TextInput
                                type="text"
                                className="mt-1 block w-full"
                                value={path.quota || ''}
                                onChange={(e) => updatePath(index, 'quota', e.target.value)}
                                placeholder="Contoh: 50%"
                            />
                        </div>
                    </div>

                    <div className="mb-6">
                        <InputLabel value="Deskripsi Jalur" />
                        <div className="mt-1">
                            <MiniTextEditor
                                initialValue={path.description || ''}
                                onChange={(value) => updatePath(index, 'description', value)}
                                placeholder="Jelaskan tentang jalur ini..."
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Persyaratan Dokumen" />
                        <div className="space-y-3 mt-2">
                            {(path.requirements || []).map((req, reqIndex) => (
                                <div key={reqIndex} className="flex items-center gap-2">
                                    <TextInput
                                        type="text"
                                        className="flex-1"
                                        value={req}
                                        onChange={(e) => updatePathRequirement(index, reqIndex, e.target.value)}
                                        placeholder={`Persyaratan ${reqIndex + 1}`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => removePathRequirement(index, reqIndex)}
                                        className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    >
                                        <X size={16} />
                                    </button>
                                </div>
                            ))}
                            <button
                                type="button"
                                onClick={() => addPathRequirement(index)}
                                className="flex items-center text-primary hover:text-blue-700 font-bold text-sm mt-2"
                            >
                                <Plus size={16} className="mr-1" />
                                Tambah Persyaratan
                            </button>
                        </div>
                    </div>
                </div>
            ))}

            {paths.length === 0 && (
                <div className="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 text-gray-400">
                    <Users className="mx-auto h-12 w-12 mb-4" />
                    <h3 className="text-lg font-medium">Belum ada jalur pendaftaran</h3>
                    <p className="mb-6">Klik tombol di atas untuk menambah jalur baru.</p>
                </div>
            )}
            </div>
        </div>
    );
}
