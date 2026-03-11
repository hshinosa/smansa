import React from 'react';
import { BookOpen, ChevronRight } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import FormSection from '@/Components/Admin/FormSection';

export default function ProgramsSection({ data, localErrors, formErrors, handleSectionInputChange }) {
    return (
        <div className="space-y-6">
            {/* Header Program Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Header Program Akademik
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Judul untuk bagian program studi/sekolah</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel htmlFor="programs_title" value="Judul Section" />
                        <TextInput id="programs_title" value={data.programs_lp?.title || ''}
                            onChange={e => handleSectionInputChange('programs_lp', 'title', e.target.value)}
                            className="mt-1 block w-full" />
                        <InputError message={localErrors['programs_lp.title'] || formErrors['programs_lp.title']} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="programs_description" value="Deskripsi Singkat" />
                        <TextInput id="programs_description" value={data.programs_lp?.description || ''}
                            onChange={e => handleSectionInputChange('programs_lp', 'description', e.target.value)}
                            className="mt-1 block w-full" />
                        <InputError message={localErrors['programs_lp.description'] || formErrors['programs_lp.description']} className="mt-1" />
                    </div>
                </div>
            </div>

            {/* Manajemen Program Studi Info */}
            <div className="p-8 bg-blue-50/50 rounded-2xl border border-blue-100 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
                <div>
                    <h4 className="text-xl font-bold text-blue-900 mb-2">Manajemen Program Studi</h4>
                    <p className="text-gray-900 max-w-xl">
                        Kelola detail konten untuk setiap program studi (MIPA, IPS, Bahasa) seperti kurikulum, fasilitas, dan prospek karir.
                    </p>
                </div>
                <a 
                    href={route('admin.program-studi.index')} 
                    className="px-6 py-3 bg-white text-gray-900 border border-gray-300 rounded-xl font-bold shadow-sm hover:shadow-md transition-all flex items-center"
                >
                    Buka Halaman Program Studi <ChevronRight size={18} className="ml-2" />
                </a>
            </div>
        </div>
    );
}
