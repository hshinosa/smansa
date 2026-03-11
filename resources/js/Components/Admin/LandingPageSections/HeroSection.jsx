import React from 'react';
import { Layout } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import FileUploadField from '@/Components/Admin/FileUploadField';
import FormSection from '@/Components/Admin/FormSection';
import IconPicker from '@/Components/Admin/IconPicker';

export default function HeroSection({ data, setData, localErrors, formErrors, previewUrls, handleSectionInputChange, handleFileChange }) {
    const updateStat = (index, key, value) => {
        const currentStats = [...(data.hero?.stats || [])];
        currentStats[index] = { ...currentStats[index], [key]: value };
        handleSectionInputChange('hero', 'stats', currentStats);
    };

    return (
        <div className="space-y-6">
            {/* Konten Utama Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Konten Utama Hero
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Teks utama yang ditampilkan di bagian hero/banner</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel htmlFor="hero_title_line1" value="Judul Baris 1" />
                        <TextInput id="hero_title_line1" value={data.hero?.title_line1 || ''}
                            onChange={e => handleSectionInputChange('hero', 'title_line1', e.target.value)}
                            className="mt-1 block w-full" placeholder="Contoh: SMAN 1" />
                        <InputError message={localErrors['hero.title_line1'] || formErrors['hero.title_line1']} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="hero_title_line2" value="Judul Baris 2 (Highlight)" />
                        <TextInput id="hero_title_line2" value={data.hero?.title_line2 || ''}
                            onChange={e => handleSectionInputChange('hero', 'title_line2', e.target.value)}
                            className="mt-1 block w-full" placeholder="Contoh: Baleendah" />
                        <InputError message={localErrors['hero.title_line2'] || formErrors['hero.title_line2']} className="mt-1" />
                    </div>
                    <div className="md:col-span-2">
                        <InputLabel htmlFor="hero_text" value="Teks Deskripsi Hero" />
                        <textarea
                            id="hero_text"
                            value={data.hero?.hero_text || ''}
                            onChange={e => handleSectionInputChange('hero', 'hero_text', e.target.value)}
                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm min-h-[100px]"
                            placeholder="Tuliskan deskripsi singkat yang menarik..."
                        ></textarea>
                        <InputError message={localErrors['hero.hero_text'] || formErrors['hero.hero_text']} className="mt-1" />
                    </div>
                </div>
            </div>

            {/* Gambar Hero Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                        Gambar Hero
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Gambar latar belakang dan foreground untuk hero section</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <FileUploadField 
                        id="hero_background_image"
                        label="Gambar Latar Belakang (Hero Image)"
                        previewUrl={previewUrls.heroBackgroundImage}
                        error={localErrors['heroBackgroundImage'] || localErrors['hero.background_image'] || formErrors['hero.background_image']}
                        onChange={(file) => handleFileChange('heroBackgroundImage', file)}
                    />

                    <FileUploadField 
                        id="hero_student_image"
                        label="Gambar Siswa (Foreground)"
                        previewUrl={previewUrls.heroStudentImage}
                        error={localErrors['heroStudentImage'] || localErrors['hero.student_image'] || formErrors['hero.student_image']}
                    onChange={(file) => handleFileChange('heroStudentImage', file)}
                />
                </div>
            </div>

            {/* Statistik Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span>
                        Statistik Melayang (Floating Cards)
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Card statistik yang melayang di hero section (maksimal 3 item)</p>
                </div>
                <div className="space-y-4">
                    {(data.hero?.stats || []).length === 0 ? (
                        <p className="text-gray-400 text-sm text-center py-4">Belum ada statistik. Klik tombol "Tambah Statistik" untuk menambahkan.</p>
                    ) : (
                        (data.hero?.stats || []).map((stat, index) => (
                            <div key={index} className="flex flex-wrap md:flex-nowrap items-end gap-4 p-4 bg-gray-50 rounded-xl border border-gray-200 relative group">
                                <div className="flex-1 min-w-[150px]">
                                    <InputLabel value="Label" />
                                    <TextInput 
                                        value={stat.label || ''} 
                                        onChange={e => updateStat(index, 'label', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="Contoh: Akreditasi"
                                    />
                                </div>
                                <div className="flex-1 min-w-[150px]">
                                    <InputLabel value="Nilai" />
                                    <TextInput 
                                        value={stat.value || ''} 
                                        onChange={e => updateStat(index, 'value', e.target.value)}
                                        className="mt-1 block w-full"
                                        placeholder="Contoh: A (Unggul)"
                                    />
                                </div>
                                <div className="w-full md:w-40">
                                    <IconPicker
                                        value={stat.icon_name || 'Trophy'}
                                        onChange={(iconName) => updateStat(index, 'icon_name', iconName)}
                                        label="Ikon (Lucide)"
                                    />
                                </div>
                            </div>
                    ))
                    )}
                </div>
            </div>
        </div>
    );
}
