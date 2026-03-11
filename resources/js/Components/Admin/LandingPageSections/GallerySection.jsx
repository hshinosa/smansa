import React from 'react';
import { ChevronRight } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';

export default function GallerySection({ data, handleSectionInputChange }) {
    return (
        <div className="space-y-6">
            {/* Header Galeri Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Header Galeri
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Judul untuk bagian galeri kegiatan</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel htmlFor="gallery_title" value="Judul Section" />
                        <TextInput id="gallery_title" value={data.gallery_lp?.title || ''}
                            onChange={e => handleSectionInputChange('gallery_lp', 'title', e.target.value)}
                            className="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel htmlFor="gallery_description" value="Deskripsi Singkat" />
                        <TextInput id="gallery_description" value={data.gallery_lp?.description || ''}
                            onChange={e => handleSectionInputChange('gallery_lp', 'description', e.target.value)}
                            className="mt-1 block w-full" />
                    </div>
                </div>
            </div>

            <div className="p-8 bg-blue-50/50 rounded-2xl border border-blue-100 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
                <div>
                    <h4 className="text-xl font-bold text-blue-900 mb-2">Manajemen Foto Galeri</h4>
                    <p className="text-gray-900 max-w-xl">
                        Upload, hapus, dan kelola foto-foto kegiatan sekolah untuk ditampilkan di galeri landing page.
                    </p>
                </div>
                <a 
                    href={route('admin.galleries.index')} 
                    className="px-6 py-3 bg-white text-gray-900 border border-gray-300 rounded-xl font-bold shadow-sm hover:shadow-md transition-all flex items-center"
                >
                    Buka Halaman Galeri <ChevronRight size={18} className="ml-2" />
                </a>
            </div>
        </div>
    );
}
