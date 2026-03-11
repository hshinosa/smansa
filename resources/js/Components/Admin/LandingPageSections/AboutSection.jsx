import React from 'react';
import { Info } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import MiniTextEditor from '@/Components/MiniTextEditor';
import FileUploadField from '@/Components/Admin/FileUploadField';
import FormSection from '@/Components/Admin/FormSection';

export default function AboutSection({ data, localErrors, formErrors, previewUrls, handleSectionInputChange, handleFileChange }) {
    return (
        <div className="space-y-6">
            {/* Informasi Sekolah Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Informasi Sekolah
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Informasi singkat mengenai profil sekolah</p>
                </div>
                <div className="space-y-6">
                    <div>
                        <InputLabel htmlFor="about_lp_title" value="Judul Bagian Tentang" />
                        <TextInput id="about_lp_title" value={data.about_lp?.title || ''}
                            onChange={e => handleSectionInputChange('about_lp', 'title', e.target.value)}
                            className="mt-1 block w-full" />
                        <InputError message={localErrors['about_lp.title'] || formErrors['about_lp.title']} className="mt-1" />
                    </div>
                    <div>
                        <MiniTextEditor
                            label="Deskripsi Lengkap"
                            initialValue={data.about_lp?.description_html || ''}
                            onChange={html => handleSectionInputChange('about_lp', 'description_html', html)}
                            error={localErrors['about_lp.description_html'] || formErrors['about_lp.description_html']}
                        />
                    </div>
                </div>
            </div>

            {/* Gambar Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                        Gambar Ilustrasi
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Gambar untuk mengilustrasikan bagian tentang sekolah</p>
                </div>
                <FileUploadField 
                    id="about_lp_image"
                    label=""
                    previewUrl={previewUrls.aboutImage}
                    error={localErrors['aboutImage'] || localErrors['about_lp.image'] || formErrors['about_lp.image']}
                    onChange={(file) => handleFileChange('aboutImage', file)}
                />
            </div>
        </div>
    );
}
