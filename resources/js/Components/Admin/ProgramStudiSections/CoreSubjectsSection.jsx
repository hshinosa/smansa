import React from 'react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import FileUploadField from '@/Components/Admin/FileUploadField';
import { getImageUrl } from '@/Utils/imageUtils';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { Plus, Trash2 } from 'lucide-react';
import IconPicker from '@/Components/Admin/IconPicker';

export default function CoreSubjectsSection({ data, setData, errors }) {
    const addItem = () => {
        const currentItems = Array.isArray(data.items) ? data.items : [];
        setData('items', [...currentItems, { title: '', description: '', icon_name: 'BookOpen' }]);
    };

    const removeItem = (index) => {
        const currentItems = Array.isArray(data.items) ? data.items : [];
        setData('items', currentItems.filter((_, i) => i !== index));
    };

    const updateItem = (index, field, value) => {
        const currentItems = Array.isArray(data.items) ? data.items : [];
        const newItems = [...currentItems];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    return (
        <div className="space-y-6">
            {/* Section Header */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Informasi Section
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Header untuk section mata pelajaran</p>
                </div>
                <div className="space-y-4">
                    <div>
                        <InputLabel htmlFor="subjects_title" value="Judul Section" />
                        <TextInput
                            id="subjects_title"
                            type="text"
                            className="mt-1 block w-full"
                            value={data.title || ''}
                            onChange={(e) => setData('title', e.target.value)}
                        />
                        <p className="text-sm text-gray-500 mt-1">Judul untuk section mata pelajaran</p>
                    </div>

                    <div>
                        <InputLabel htmlFor="subjects_description" value="Deskripsi Section" />
                        <textarea
                            id="subjects_description"
                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="2"
                            value={data.description || ''}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        <p className="text-sm text-gray-500 mt-1">Deskripsi singkat untuk section mata pelajaran</p>
                    </div>
                </div>
            </div>

            {/* Subject List */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3 flex justify-between items-center">
                    <div>
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                            Daftar Mata Pelajaran
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">Mata pelajaran unggulan program studi</p>
                    </div>
                    <PrimaryButton 
                        type="button" 
                        onClick={addItem} 
                        className="px-4 py-2 flex items-center gap-2 shadow-md hover:shadow-lg transition-all !bg-accent-yellow hover:!bg-[#EAB308] border-0 text-sm !text-gray-900"
                    >
                        <Plus size={16} /> 
                        <span className="font-bold">Tambah Mapel</span>
                    </PrimaryButton>
                </div>

                <div className="space-y-4">
                    {Array.isArray(data.items) && data.items.map((item, index) => (
                        <div key={index} className="p-4 border-2 border-gray-200 rounded-lg bg-gray-50 relative">
                            <button
                                type="button"
                                onClick={() => removeItem(index)}
                                className="absolute top-2 right-2 text-red-500 hover:text-red-700"
                            >
                                <Trash2 size={18} />
                            </button>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel value="Nama Mata Pelajaran" />
                                    <TextInput
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={item.title || ''}
                                        onChange={(e) => updateItem(index, 'title', e.target.value)}
                                        placeholder="Contoh: Matematika Lanjut"
                                    />
                                </div>
                                
                                <div>
                                    <IconPicker
                                        value={item.icon_name || 'BookOpen'}
                                        onChange={(iconName) => updateItem(index, 'icon_name', iconName)}
                                        label="Ikon Mata Pelajaran"
                                    />
                                </div>

                                <div className="md:col-span-2">
                                    <InputLabel value="Deskripsi Singkat" />
                                    <TextInput
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={item.description || ''}
                                        onChange={(e) => updateItem(index, 'description', e.target.value)}
                                        placeholder="Deskripsi singkat mata pelajaran..."
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                    {(!data.items || data.items.length === 0) && (
                        <p className="text-gray-500 text-center py-4">Belum ada mata pelajaran ditambahkan.</p>
                    )}
                </div>
            </div>
        </div>
    );
}
