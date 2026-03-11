import React, { useState, useEffect, useRef } from 'react';
import { Upload, X, Image as ImageIcon } from 'lucide-react';
import InputLabel from '@/Components/InputLabel';
import { getImageUrl } from '@/Utils/imageUtils';

export default function ImageUploadCard({ id, label, onChange, previewUrl, error, accept = 'image/*' }) {
    const fileInputRef = useRef(null);
    const [preview, setPreview] = useState(previewUrl ? getImageUrl(previewUrl) : null);
    const [isDragging, setIsDragging] = useState(false);

    useEffect(() => {
        const processedUrl = previewUrl ? getImageUrl(previewUrl) : null;
        console.log(`🖼️ ImageUploadCard [${id}]: Processing previewUrl`, {
            original: previewUrl,
            processed: processedUrl,
            label: label
        });
        setPreview(processedUrl);
    }, [previewUrl, id, label]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const objectUrl = URL.createObjectURL(file);
            setPreview(objectUrl);
            onChange(file);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        const file = e.dataTransfer.files[0];
        if (file) {
            const objectUrl = URL.createObjectURL(file);
            setPreview(objectUrl);
            onChange(file);
        }
    };

    const removeImage = (e) => {
        e.stopPropagation();
        setPreview(null);
        onChange(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    return (
        <div className="w-full">
            <InputLabel htmlFor={id} value={label} className="mb-2 block text-sm font-medium text-gray-700" />
            
            <div
                onClick={() => fileInputRef.current?.click()}
                onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                onDragLeave={() => setIsDragging(false)}
                onDrop={handleDrop}
                className={`
                    relative w-full h-40 min-h-[160px] rounded-xl border-2 border-dashed transition-all cursor-pointer overflow-hidden group bg-white
                    ${isDragging ? 'border-accent-yellow bg-yellow-50' : 'border-gray-300 hover:border-accent-yellow hover:bg-gray-50'}
                    ${error ? 'border-red-500' : ''}
                `}
            >
                <input
                    type="file"
                    id={id}
                    ref={fileInputRef}
                    className="hidden"
                    accept={accept}
                    onChange={handleFileChange}
                />

                {preview ? (
                    <>
                        <img 
                            src={preview} 
                            alt="Preview" 
                            className="w-full h-full object-cover" 
                        />
                        <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center text-white">
                            <Upload className="w-8 h-8 mb-2" />
                            <span className="text-xs font-medium">Ganti Gambar</span>
                        </div>
                        <button 
                            onClick={removeImage}
                            className="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors z-10 shadow-sm"
                            title="Hapus gambar"
                        >
                            <X size={14} />
                        </button>
                    </>
                ) : (
                    <div className="absolute inset-0 flex flex-col items-center justify-center text-gray-400 p-4 text-center">
                        <ImageIcon className="w-8 h-8 mb-2 text-gray-300" />
                        <span className="text-sm font-semibold text-gray-600">Upload</span>
                        <span className="text-[10px] mt-1 text-gray-400">Klik atau Drag</span>
                    </div>
                )}
            </div>
            {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
        </div>
    );
}
