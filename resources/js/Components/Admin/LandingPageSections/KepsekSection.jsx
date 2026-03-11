import React from 'react';
import { User } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import MiniTextEditor from '@/Components/MiniTextEditor';
import FileUploadField from '@/Components/Admin/FileUploadField';
import FormSection from '@/Components/Admin/FormSection';

export default function KepsekSection({ data, localErrors, formErrors, previewUrls, handleSectionInputChange, handleFileChange }) {
    return (
        <div className="space-y-6">
            {/* Profil Kepala Sekolah Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Profil Kepala Sekolah
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Foto dan informasi kepala sekolah</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="md:col-span-2">
                        <InputLabel htmlFor="kepsek_title" value="Judul Bagian Sambutan" />
                        <TextInput id="kepsek_title" value={data.kepsek_welcome_lp?.title || ''}
                            onChange={e => handleSectionInputChange('kepsek_welcome_lp', 'title', e.target.value)}
                            className="mt-1 block w-full" />
                        <InputError message={localErrors['kepsek_welcome_lp.title'] || formErrors['kepsek_welcome_lp.title']} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="kepsek_name" value="Nama Lengkap" />
                        <TextInput id="kepsek_name" value={data.kepsek_welcome_lp?.kepsek_name || ''}
                            onChange={e => handleSectionInputChange('kepsek_welcome_lp', 'kepsek_name', e.target.value)}
                            className="mt-1 block w-full" placeholder="Contoh: Dr. H. Nama, M.Pd" />
                        <InputError message={localErrors['kepsek_welcome_lp.kepsek_name'] || formErrors['kepsek_welcome_lp.kepsek_name']} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="kepsek_title_role" value="Jabatan" />
                        <TextInput id="kepsek_title_role" value={data.kepsek_welcome_lp?.kepsek_title || ''}
                            onChange={e => handleSectionInputChange('kepsek_welcome_lp', 'kepsek_title', e.target.value)}
                            className="mt-1 block w-full" placeholder="Contoh: Kepala Sekolah" />
                        <InputError message={localErrors['kepsek_welcome_lp.kepsek_title'] || formErrors['kepsek_welcome_lp.kepsek_title']} className="mt-1" />
                    </div>
                    <div className="md:col-span-2">
                        <MiniTextEditor
                            label="Isi Sambutan"
                            initialValue={data.kepsek_welcome_lp?.welcome_text_html || ''}
                            onChange={html => handleSectionInputChange('kepsek_welcome_lp', 'welcome_text_html', html)}
                            error={localErrors['kepsek_welcome_lp.welcome_text_html'] || formErrors['kepsek_welcome_lp.welcome_text_html']}
                        />
                    </div>
                </div>
            </div>

            {/* Foto Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                        Foto Kepala Sekolah
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Foto resmi kepala sekolah</p>
                </div>
                <FileUploadField 
                    id="kepsek_image"
                    label=""
                    previewUrl={previewUrls.kepsekImage}
                    error={localErrors['kepsekImage'] || localErrors['kepsek_welcome_lp.kepsek_image'] || formErrors['kepsek_welcome_lp.kepsek_image']}
                    onChange={(file) => handleFileChange('kepsekImage', file)}
                />
            </div>
        </div>
    );
}
