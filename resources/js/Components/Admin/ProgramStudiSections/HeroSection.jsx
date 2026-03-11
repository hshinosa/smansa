import React from 'react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import FileUploadField from '@/Components/Admin/FileUploadField';
import { getImageUrl } from '@/Utils/imageUtils';

export default function HeroSection({ data, setData, errors }) {
    return (
        <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
            <div className="border-b pb-3">
                <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                    Hero Section
                </h3>
                <p className="text-sm text-gray-600 mt-1">Banner utama program studi</p>
            </div>

            <div>
                <InputLabel htmlFor="hero_description" value="Deskripsi Singkat" />
                <textarea
                    id="hero_description"
                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    rows="4"
                    value={data.description || ''}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Deskripsi singkat tentang program studi ini..."
                />
                <p className="text-sm text-gray-500 mt-1">Pengenalan singkat tentang program studi</p>
                {errors?.description && <p className="text-red-500 text-sm mt-1">{errors.description}</p>}
            </div>

            <div>
                <FileUploadField
                    id="hero_background_image"
                    previewUrl={getImageUrl(data.background_image)}
                    onChange={(file) => setData('background_image', file)}
                    error={errors?.background_image}
                    label="Background Image"
                    description="Gambar latar belakang untuk hero section program studi ini."
                />
                <p className="text-xs text-gray-500 mt-2 ml-1">Recommended size: 1920x1080px</p>
            </div>
        </div>
    );
}
