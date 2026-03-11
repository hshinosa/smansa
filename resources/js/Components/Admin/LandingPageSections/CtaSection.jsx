import React from 'react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';

export default function CtaSection({ data, handleSectionInputChange }) {
    return (
        <div className="space-y-6">
            {/* Call to Action Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Call to Action (CTA)
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Ajakan untuk calon siswa baru mendaftar</p>
                </div>
                <div className="space-y-6">
                    <div>
                        <InputLabel htmlFor="cta_title" value="Judul Ajakan" />
                        <TextInput id="cta_title" value={data.cta_lp?.title || ''}
                            onChange={e => handleSectionInputChange('cta_lp', 'title', e.target.value)}
                            className="mt-1 block w-full" placeholder="Contoh: Siap Bergabung Bersama Kami?" />
                        <p className="mt-1 text-sm text-gray-500">Judul untuk mengajak calon siswa baru</p>
                    </div>
                    <div>
                        <InputLabel htmlFor="cta_description" value="Deskripsi Ajakan" />
                        <textarea id="cta_description" value={data.cta_lp?.description || ''}
                            onChange={e => handleSectionInputChange('cta_lp', 'description', e.target.value)}
                            className="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="3"></textarea>
                        <p className="mt-1 text-sm text-gray-500">Deskripsi pendukung untuk ajakan</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
