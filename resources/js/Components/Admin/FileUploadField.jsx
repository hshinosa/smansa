import React, { useState, useEffect } from 'react';
import { Upload, Image as ImageIcon, Video as VideoIcon, X } from 'lucide-react';
import InputLabel from '@/Components/InputLabel';

export default function FileUploadField({ id, label, onChange, previewUrl, error, description, accept = 'image/*', fileType = 'image', multiple = false }) {
    const [isDragging, setIsDragging] = useState(false);
    const [localPreview, setLocalPreview] = useState(null);
    const [localFileType, setLocalFileType] = useState(null);
    const [hasError, setHasError] = useState(false);
    const [fileCount, setFileCount] = useState(0);

    // Cleanup local preview URL when component unmounts or localPreview changes
    useEffect(() => {
        setHasError(false); // Reset error state on change
        return () => {
            if (localPreview && localPreview.startsWith('blob:')) {
                URL.revokeObjectURL(localPreview);
            }
        };
    }, [localPreview, previewUrl]);

    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = () => {
        setIsDragging(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            if (multiple) {
                const files = Array.from(e.dataTransfer.files);
                setFileCount(files.length);
                setLocalPreview(null);
                onChange(files);
            } else {
                const file = e.dataTransfer.files[0];
                processFile(file);
            }
        }
    };

    const handleFileChange = (e) => {
        if (e.target.files && e.target.files.length > 0) {
            if (multiple) {
                const files = Array.from(e.target.files);
                setFileCount(files.length);
                setLocalPreview(null);
                onChange(files);
            } else {
                const file = e.target.files[0];
                processFile(file);
            }
        }
    };

    const processFile = (file) => {
        const objectUrl = URL.createObjectURL(file);
        setLocalPreview(objectUrl);
        setLocalFileType(file.type.startsWith('video/') ? 'video' : 'image');
        onChange(file);
    };

    const handleClear = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setLocalPreview(null);
        setLocalFileType(null);
        onChange(null);
        // Reset input file value
        const input = document.getElementById(id);
        if (input) input.value = '';
    };

    // Priority: localPreview (newly selected) > previewUrl (from parent/props)
    const displayUrl = localPreview || previewUrl;
    const currentType = localFileType || fileType;

    return (
        <div 
            className={`bg-slate-50 p-5 rounded-2xl border-2 border-dashed transition-all duration-200 ${
                isDragging ? 'border-primary bg-blue-50 scale-[1.01] shadow-md' : 'border-slate-200 hover:border-primary/50'
            }`}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
        >
            <InputLabel htmlFor={id} value={label} className="mb-3 text-slate-700 font-bold text-base" />
            <div className="flex flex-col sm:flex-row gap-6 items-start">
                {multiple ? (
                    fileCount > 0 ? (
                        <div className="w-40 h-40 bg-blue-50 rounded-xl flex flex-col items-center justify-center text-primary border-2 border-blue-200 shrink-0">
                            <ImageIcon size={32} className="mb-2" />
                            <span className="text-sm font-bold">{fileCount} File Dipilih</span>
                        </div>
                    ) : (
                        <div className="w-40 h-40 bg-slate-200 rounded-xl flex flex-col items-center justify-center text-slate-400 border-2 border-slate-300 border-dashed shrink-0">
                            <ImageIcon size={32} className="mb-2 opacity-50" />
                            <span className="text-[10px] uppercase tracking-wider font-bold">Multiple Files</span>
                        </div>
                    )
                ) : (
                    displayUrl && !hasError ? (
                        <div className="relative group shrink-0">
                            {currentType === 'video' ? (
                                <video
                                    src={displayUrl}
                                    className="w-40 h-40 object-cover rounded-xl shadow-md ring-4 ring-white bg-black"
                                    muted
                                    playsInline
                                    onError={() => setHasError(true)}
                                />
                            ) : (
                                <img
                                    src={displayUrl}
                                    alt={`Preview ${label}`}
                                    className="w-40 h-40 object-cover rounded-xl shadow-md ring-4 ring-white"
                                    onError={() => setHasError(true)}
                                />
                            )}
                            <div className="absolute inset-0 bg-black/40 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-medium">
                                {localPreview ? 'Preview Baru' : (currentType === 'video' ? 'Video Saat Ini' : 'Gambar Saat Ini')}
                            </div>
                            {localPreview && (
                                <button
                                    onClick={handleClear}
                                    className="absolute -top-2 -right-2 bg-red-500 text-white p-1 rounded-full shadow-lg hover:bg-red-600 transition-colors"
                                    title="Batalkan pilihan"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="w-40 h-40 bg-slate-200 rounded-xl flex flex-col items-center justify-center text-slate-400 border-2 border-slate-300 border-dashed shrink-0">
                            {accept.includes('video') ? <VideoIcon size={32} className="mb-2 opacity-50" /> : <ImageIcon size={32} className="mb-2 opacity-50" />}
                            <span className="text-[10px] uppercase tracking-wider font-bold">{accept.includes('video') ? 'No Video' : 'No Image'}</span>
                        </div>
                    )
                )}
                
                <div className="flex-1 pt-2">
                    <label
                        htmlFor={id}
                        className="inline-flex items-center px-5 py-2.5 bg-white border border-slate-300 rounded-xl font-bold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 hover:border-primary/50 focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                    >
                        <Upload size={16} className="mr-2 text-primary" />
                        {accept.includes('video') ? 'Pilih atau Drag Video' : 'Pilih atau Drag Gambar'}
                    </label>
                    <input
                        id={id}
                        type="file"
                        accept={accept}
                        multiple={multiple}
                        className="hidden"
                        onChange={handleFileChange}
                    />
                    <p className="mt-4 text-sm text-slate-500 leading-relaxed">
                        {description || (accept.includes('video') ? 'Format: MP4, WebM. Maksimal 50MB.' : 'Format: JPEG, PNG, JPG, GIF, SVG. Maksimal 10MB. Bisa drag & drop file ke sini.')}
                    </p>
                    {error && (
                        <div className="mt-3 p-2 bg-red-50 border border-red-100 rounded-lg">
                            <p className="text-xs text-red-600 font-medium">{error}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
