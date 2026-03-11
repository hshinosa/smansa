import React from 'react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import MiniTextEditor from '@/Components/MiniTextEditor';

export default function GeneralSettingsSection({ data, setData, localErrors, formErrors }) {
    const updateField = (field, value) => {
        setData('pengaturan_umum', {
            ...data.pengaturan_umum,
            [field]: value
        });
    };

    return (
        <div className="space-y-6">
            {/* Informasi Halaman */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Informasi Halaman
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Pengaturan umum halaman SPMB</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="title" value="Judul Halaman" />
                            <TextInput
                                id="title"
                                value={data.pengaturan_umum.title || ''}
                                onChange={(e) => updateField('title', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="Informasi Penerimaan Peserta Didik Baru"
                            />
                            <InputError message={localErrors['pengaturan_umum.title']} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="registration_year" value="Tahun Ajaran" />
                            <TextInput
                                id="registration_year"
                                value={data.pengaturan_umum.registration_year || ''}
                                onChange={(e) => updateField('registration_year', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="2025/2026"
                            />
                            <InputError message={localErrors['pengaturan_umum.registration_year']} className="mt-2" />
                        </div>
                    </div>

                    <div className="space-y-4">
                        <InputLabel value="Status Pendaftaran" />
                        <div className="p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-gray-700">Status Saat Ini:</span>
                                <span className={`px-3 py-1 rounded-full text-xs font-bold ${data.pengaturan_umum.is_registration_open ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                    {data.pengaturan_umum.is_registration_open ? 'DIBUKA' : 'DITUTUP'}
                                </span>
                            </div>
                            <div className="mt-4 flex gap-4">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <div className="relative">
                                        <input
                                            type="radio"
                                            name="is_registration_open"
                                            checked={data.pengaturan_umum.is_registration_open === true}
                                            onChange={() => updateField('is_registration_open', true)}
                                            className="peer sr-only"
                                        />
                                        <div className="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-green-500 peer-checked:bg-green-500 transition-all"></div>
                                    </div>
                                    <span className="text-sm text-gray-700">Buka Pendaftaran</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <div className="relative">
                                        <input
                                            type="radio"
                                            name="is_registration_open"
                                            checked={data.pengaturan_umum.is_registration_open === false}
                                            onChange={() => updateField('is_registration_open', false)}
                                            className="peer sr-only"
                                        />
                                        <div className="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-red-500 peer-checked:bg-red-500 transition-all"></div>
                                    </div>
                                    <span className="text-sm text-gray-700">Tutup Pendaftaran</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-6">
                    <InputLabel value="Deskripsi Utama" />
                    <div className="mt-2">
                        <MiniTextEditor
                            initialValue={data.pengaturan_umum.description_html || ''}
                            onChange={(value) => updateField('description_html', value)}
                            placeholder="Jelaskan secara singkat tentang proses penerimaan siswa baru..."
                        />
                        <InputError message={localErrors['pengaturan_umum.description_html']} className="mt-2" />
                    </div>
                </div>

                <div className="mt-6">
                    <InputLabel htmlFor="announcement_text" value="Pengumuman Penutupan (Opsional)" />
                    <div className="mt-2 bg-yellow-50 p-4 rounded-xl border border-yellow-100">
                        <MiniTextEditor
                            initialValue={data.pengaturan_umum.announcement_text || ''}
                            onChange={(value) => updateField('announcement_text', value)}
                            placeholder="Pesan yang akan muncul ketika pendaftaran ditutup..."
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
