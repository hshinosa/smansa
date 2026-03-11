import React from 'react';
import InputLabel from '@/Components/InputLabel';
import FileUploadField from '@/Components/Admin/FileUploadField';
import { ImageIcon } from 'lucide-react';

export default function ThumbnailCardSection({ data, setData, errors, programName }) {
    const programLabels = {
        mipa: 'MIPA (Matematika & Ilmu Pengetahuan Alam)',
        ips: 'IPS (Ilmu Pengetahuan Sosial)',
        bahasa: 'Bahasa'
    };

    return (
        <div className="space-y-6">
            {/* Upload Section */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Upload Foto Thumbnail
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Pilih dan upload foto thumbnail card</p>
                </div>

                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p className="text-sm text-blue-700 leading-relaxed">
                        Upload foto siswa/siswi yang akan ditampilkan di card program {programLabels[programName]} 
                        pada Landing Page dan halaman Program Akademik. Foto akan ditampilkan melayang di atas card.
                    </p>
                    <p className="text-xs text-blue-600 mt-2 font-medium">
                        💡 <strong>Tips:</strong> Gunakan foto dengan latar belakang transparan (PNG) atau background putih. 
                        Ukuran optimal: 800x1200px. Foto akan otomatis di-crop dan di-optimize.
                    </p>
                </div>

                <div>
                    <InputLabel htmlFor="thumbnail_card_image" value="Foto Thumbnail Card" />
                    <FileUploadField
                        id="thumbnail_card_image"
                        value={data.image}
                        previewUrl={data._preview}
                        onChange={(file) => setData('image', file)}
                        error={errors?.image}
                        accept="image/*"
                        preview={true}
                        required={false}
                        helpText="Format: JPG, PNG, WebP. Maksimal 10MB. Resolusi optimal: 800x1200px"
                    />
                </div>

                <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <h4 className="text-sm font-semibold text-gray-900 mb-3">📍 Lokasi Tampilan:</h4>
                    <ul className="space-y-2 text-sm text-gray-600">
                        <li className="flex items-center gap-2">
                            <span className="w-2 h-2 bg-primary rounded-full"></span>
                            <span><strong>Landing Page</strong> → Section "Program Akademik"</span>
                        </li>
                        <li className="flex items-center gap-2">
                            <span className="w-2 h-2 bg-primary rounded-full"></span>
                            <span><strong>Halaman Kurikulum</strong> → Card Program Studi</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
