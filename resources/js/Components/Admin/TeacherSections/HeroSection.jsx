import React from 'react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import FileUploadField from '@/Components/Admin/FileUploadField';

export default function HeroSection({ data, errors, setData, handleFileChange, previewUrls }) {
    return (
        <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
            <div>
                <InputLabel htmlFor="title" value="Judul Hero" />
                <TextInput
                    id="title"
                    type="text"
                    className="mt-1 block w-full"
                    value={data.title || ''}
                    onChange={(e) => setData('title', e.target.value)}
                />
                <InputError message={errors.title} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="subtitle" value="Sub-judul Hero" />
                <textarea
                    id="subtitle"
                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="3"
                    value={data.subtitle || ''}
                    onChange={(e) => setData('subtitle', e.target.value)}
                />
                <InputError message={errors.subtitle} className="mt-2" />
            </div>

            <div>
                <InputLabel value="Background Hero" />
                <FileUploadField
                    id="image_file"
                    onChange={(file) => handleFileChange('image_file', file)}
                    previewUrl={previewUrls.image_file || data.image}
                    label="Pilih Foto Latar"
                    description="Gunakan gambar berkualitas tinggi (rekomendasi: 1920x1080px)"
                />
                <InputError message={errors.image_file} className="mt-2" />
            </div>
        </div>
    );
}
