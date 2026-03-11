import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import {
    History,
    Target,
    Building2,
    Network,
    Plus,
    Trash2,
    MoveUp,
    MoveDown,
    Image as ImageIcon,
    GripVertical
} from 'lucide-react';
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import FileUploadField from '@/Components/Admin/FileUploadField';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import MiniTextEditor from '@/Components/MiniTextEditor';
import toast from 'react-hot-toast';
import { getImageUrl } from '@/Utils/imageUtils';

// Sortable Mission Item Component
const SortableMissionItem = ({ id, value, index, onUpdate, onRemove }) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div className="relative">
            {/* Numbering tetap tidak bergerak */}
            <div className="absolute -left-4 top-4 bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold shadow-lg z-10">
                {index + 1}
            </div>
            
            {/* Card yang bisa di-drag */}
            <div
                ref={setNodeRef}
                style={style}
                className="bg-gradient-to-r from-gray-50 to-white p-4 rounded-xl border-2 border-gray-200 relative group hover:shadow-md transition-all"
                data-dragging={isDragging}
            >
                <div className="flex items-center gap-3 pl-8" style={{ opacity: isDragging ? 0.5 : 1 }}>
                    <button
                        type="button"
                        {...attributes}
                        {...listeners}
                        className="cursor-grab active:cursor-grabbing p-1.5 text-gray-400 hover:text-primary hover:bg-primary/5 rounded-lg transition-colors flex-shrink-0"
                        title="Seret untuk mengubah urutan"
                    >
                        <GripVertical className="w-5 h-5" />
                    </button>
                    <input
                        type="text"
                        value={value}
                        onChange={(e) => onUpdate(index, e.target.value)}
                        className="flex-1 rounded-lg border-gray-300 focus:ring-primary focus:border-primary"
                        placeholder="Masukkan misi sekolah..."
                    />
                    <button
                        type="button"
                        onClick={() => onRemove(index)}
                        className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0"
                        title="Hapus misi"
                    >
                        <Trash2 className="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    );
};

export default function SchoolProfileIndex({ auth, sections, activeSection: initialSection }) {
    const [activeTab, setActiveTab] = useState(initialSection || 'history');
    
    const { data, setData, post, processing, errors } = useForm({
        section: activeTab,
        content: sections[activeTab] || {}
    });

    // Setup drag and drop sensors for mission list
    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    const tabs = [
        { key: 'history', label: 'Konten Sejarah', description: 'Kelola lini masa sejarah berdirinya sekolah.', icon: History },
        { key: 'vision_mission', label: 'Konten Visi & Misi', description: 'Visi, misi, dan tujuan strategis sekolah.', icon: Target },
        { key: 'facilities', label: 'Konten Fasilitas', description: 'Daftar fasilitas pendukung kegiatan belajar mengajar.', icon: Building2 },
        { key: 'organization', label: 'Bagan Organisasi', description: 'Bagan struktur kepengurusan sekolah.', icon: Network },
    ];

    const handleTabChange = (tabId) => {
        setActiveTab(tabId);
        setData({
            section: tabId,
            content: sections[tabId] || {}
        });
    };

    const handleSubmit = (e) => {
        if (e) e.preventDefault();
        
        // BUG-3 Fix: Validate files before submit
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        // Check organization/history image
        if ((activeTab === 'organization' || activeTab === 'history') && data.content?.image instanceof File) {
            if (data.content.image.size > maxSize) {
                toast.error('Ukuran gambar maksimal 10MB');
                return;
            }
            if (!allowedTypes.includes(data.content.image.type)) {
                toast.error('Format gambar harus JPG, PNG, GIF, atau WebP');
                return;
            }
        }
        
        // Check facilities images
        if (activeTab === 'facilities' && data.content?.items) {
            const invalidFile = data.content.items.find(item => {
                if (item.image_file instanceof File) {
                    return item.image_file.size > maxSize || !allowedTypes.includes(item.image_file.type);
                }
                return false;
            });
            
            if (invalidFile) {
                if (invalidFile.image_file.size > maxSize) {
                    toast.error('Ukuran gambar fasilitas maksimal 10MB');
                } else {
                    toast.error('Format gambar fasilitas harus JPG, PNG, GIF, atau WebP');
                }
                return;
            }
        }
        
        // Check if we need to use FormData (for file uploads)
        const hasFiles = (activeTab === 'facilities' && data.content?.items?.some(item => item.image_file instanceof File)) ||
                         (activeTab === 'organization' && data.content?.image instanceof File) ||
                         (activeTab === 'history' && data.content?.image instanceof File);
        
        if (hasFiles) {
            // Use manual FormData for file uploads
            const formData = new FormData();
            formData.append('section', data.section);
            
            // Add title and description
            if (data.content.title) formData.append('content[title]', data.content.title);
            if (data.content.description) formData.append('content[description]', data.content.description);
            
            // Handle organization/history image upload
            if ((activeTab === 'organization' || activeTab === 'history') && data.content.image instanceof File) {
                formData.append('content[image]', data.content.image);
            } else if (data.content.image_url) {
                formData.append('content[image_url]', data.content.image_url);
            }
            
            // Add items with files (for facilities)
            if (data.content.items && Array.isArray(data.content.items)) {
                data.content.items.forEach((item, index) => {
                    const title = item.title || item.name || '';
                    const imageUrl = item.image_url || item.image || '';
                    formData.append(`content[items][${index}][title]`, title);
                    formData.append(`content[items][${index}][image_url]`, imageUrl);
                    if (item.image_file instanceof File) {
                        formData.append(`content[items][${index}][image_file]`, item.image_file);
                    }
                });
            }
            
            // Use router.post for FormData
            router.post(route('admin.school-profile.update'), formData, {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success('Perubahan berhasil disimpan!');
                },
                onError: (errors) => {
                    console.error('Save errors:', errors);
                    toast.error('Gagal menyimpan perubahan.');
                }
            });
        } else {
            // Regular submit without files
            post(route('admin.school-profile.update'), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success('Perubahan berhasil disimpan!');
                },
                onError: (errors) => {
                    console.error('Save errors:', errors);
                    toast.error('Gagal menyimpan perubahan. Silakan periksa formulir.');
                }
            });
        }
    };

    // Helper to update nested content
    const updateContent = (key, value) => {
        setData('content', {
            ...data.content,
            [key]: value
        });
    };

    // --- Section Specific Renderers ---

    const renderHistoryEditor = () => {
        const historyData = data.content || {};
        const events = Array.isArray(historyData.timeline) ? historyData.timeline : [];
        
        const updateTimeline = (newTimeline) => {
            updateContent('timeline', newTimeline);
        };

        const addEvent = () => {
            updateTimeline([...events, { year: '', title: '', description: '' }]);
        };

        const removeEvent = (index) => {
            const newEvents = [...events];
            newEvents.splice(index, 1);
            updateTimeline(newEvents);
        };

        const updateEvent = (index, field, value) => {
            const newEvents = [...events];
            newEvents[index] = { ...newEvents[index], [field]: value };
            updateTimeline(newEvents);
        };

        const moveEvent = (index, direction) => {
            if ((direction === -1 && index === 0) || (direction === 1 && index === events.length - 1)) return;
            const newEvents = [...events];
            const temp = newEvents[index];
            newEvents[index] = newEvents[index + direction];
            newEvents[index + direction] = temp;
            updateTimeline(newEvents);
        };

        return (
            <div className="space-y-6">
                {/* Header Info Box */}
                <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                    <div className="flex items-start gap-3">
                        <div className="flex-shrink-0">
                            <History className="w-6 h-6 text-blue-600" />
                        </div>
                        <div>
                            <h4 className="font-semibold text-blue-900 mb-1">Panduan Bagian Sejarah</h4>
                            <p className="text-sm text-blue-800">
                                Bagian ini terdiri dari <strong>Header Sejarah</strong> (judul dan deskripsi umum) 
                                serta <strong>Timeline Peristiwa</strong> (kronologi peristiwa penting berdasarkan tahun).
                            </p>
                        </div>
                    </div>
                </div>

                {/* Header Section */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-6">
                    <div className="border-b pb-3">
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                            Header Halaman Sejarah
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">Bagian atas halaman sejarah yang akan ditampilkan kepada pengunjung</p>
                    </div>

                    <div className="space-y-6">
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-2">
                                Judul Halaman <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={historyData.title || ''}
                                onChange={(e) => updateContent('title', e.target.value)}
                                className="w-full rounded-xl border-gray-300 focus:ring-primary focus:border-primary"
                                placeholder="Contoh: Sejarah SMAN 1 Baleendah"
                            />
                            <p className="text-xs text-gray-500 mt-1">Judul besar yang muncul di bagian atas halaman sejarah</p>
                        </div>

                        <div>
                            <MiniTextEditor
                                label="Deskripsi / Pengantar Sejarah"
                                initialValue={historyData.description_html || ''}
                                onChange={(content) => updateContent('description_html', content)}
                            />
                            <p className="text-xs text-gray-500 mt-1">
                                Tuliskan pengantar atau ringkasan umum tentang sejarah sekolah (1-2 paragraf). 
                                Detail kronologis akan ditambahkan di Timeline di bawah.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Timeline Section */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                    <div className="border-b pb-3 flex justify-between items-start">
                        <div>
                            <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                Timeline Peristiwa Sejarah
                            </h3>
                            <p className="text-sm text-gray-600 mt-1">
                                Daftar peristiwa penting dalam perjalanan sekolah, diurutkan berdasarkan tahun
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={addEvent}
                            className="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
                        >
                            <Plus className="w-4 h-4 mr-2" /> Tambah Peristiwa
                        </button>
                    </div>

                    <div className="space-y-4">
                        {events.length === 0 ? (
                            <div className="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <History className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p className="text-gray-500 mb-4">Belum ada timeline peristiwa</p>
                                <button
                                    type="button"
                                    onClick={addEvent}
                                    className="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
                                >
                                    <Plus className="w-4 h-4 mr-2" /> Tambah Peristiwa Pertama
                                </button>
                            </div>
                        ) : (
                            events.map((event, index) => (
                                <div key={index} className="bg-gradient-to-r from-gray-50 to-white p-6 rounded-xl border-2 border-gray-200 relative group hover:shadow-md transition-all">
                                    <div className="absolute -left-4 top-8 bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold shadow-lg">
                                        {index + 1}
                                    </div>
                                    
                                    {/* Layout: Kiri (Tahun & Judul) | Kanan (Deskripsi) */}
                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 pl-8">
                                        {/* Kolom Kiri: Tahun dan Judul */}
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-xs font-bold text-gray-700 uppercase mb-2 flex items-center gap-1">
                                                    <span>📅</span> Tahun Peristiwa
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1900"
                                                    max={new Date().getFullYear() + 10}
                                                    value={event.year}
                                                    onChange={(e) => updateEvent(index, 'year', e.target.value)}
                                                    className="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary text-2xl font-bold text-primary"
                                                    placeholder="2024"
                                                />
                                                <p className="text-xs text-gray-500 mt-1">Atau tuliskan "Sekarang" untuk tahun berjalan</p>
                                            </div>
                                            
                                            <div>
                                                <label className="block text-xs font-bold text-gray-700 uppercase mb-2">Judul Peristiwa</label>
                                                <input
                                                    type="text"
                                                    value={event.title}
                                                    onChange={(e) => updateEvent(index, 'title', e.target.value)}
                                                    className="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary font-semibold text-lg"
                                                    placeholder="Contoh: Awal Pendirian Sekolah"
                                                />
                                            </div>
                                        </div>
                                        
                                        {/* Kolom Kanan: Deskripsi */}
                                        <div className="space-y-2">
                                            <label className="block text-xs font-bold text-gray-700 uppercase mb-2">Deskripsi Peristiwa</label>
                                            <textarea
                                                value={event.description}
                                                onChange={(e) => updateEvent(index, 'description', e.target.value)}
                                                className="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary"
                                                rows="5"
                                                placeholder="Jelaskan detail peristiwa yang terjadi pada tahun ini..."
                                            />
                                        </div>
                                    </div>
                                    
                                    {/* Action Buttons */}
                                    <div className="flex justify-end items-center gap-2 mt-4 pt-4 border-t">
                                        <button 
                                            type="button" 
                                            onClick={() => moveEvent(index, -1)} 
                                            disabled={index === 0}
                                            className="px-3 py-2 text-sm text-gray-600 hover:text-primary hover:bg-gray-100 rounded-lg disabled:opacity-30 disabled:cursor-not-allowed transition-colors flex items-center gap-1"
                                            title="Pindah ke atas"
                                        >
                                            <MoveUp className="w-4 h-4" />
                                            <span>Naik</span>
                                        </button>
                                        <button 
                                            type="button" 
                                            onClick={() => moveEvent(index, 1)} 
                                            disabled={index === events.length - 1}
                                            className="px-3 py-2 text-sm text-gray-600 hover:text-primary hover:bg-gray-100 rounded-lg disabled:opacity-30 disabled:cursor-not-allowed transition-colors flex items-center gap-1"
                                            title="Pindah ke bawah"
                                        >
                                            <span>Turun</span>
                                            <MoveDown className="w-4 h-4" />
                                        </button>
                                        <div className="flex-1"></div>
                                        <button 
                                            type="button" 
                                            onClick={() => removeEvent(index)} 
                                            className="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-2"
                                            title="Hapus peristiwa"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        );
    };

    const renderVisionMissionEditor = () => {
        const visionMissionData = data.content || {};
        const vision = visionMissionData.vision || '';
        const mission = Array.isArray(visionMissionData.mission) ? visionMissionData.mission : [];

        const updateMission = (index, value) => {
            const newMission = [...mission];
            newMission[index] = value;
            updateContent('mission', newMission);
        };

        const updateMissionArray = (newMissionArray) => {
            updateContent('mission', newMissionArray);
        };

        const addMission = () => {
            updateContent('mission', [...mission, '']);
        };

        const removeMission = (index) => {
            const newMission = [...mission];
            newMission.splice(index, 1);
            updateContent('mission', newMission);
        };

        const handleDragEnd = (event) => {
            const { active, over } = event;

            if (over && active.id !== over.id) {
                const oldIndex = mission.findIndex((_, idx) => `mission-${idx}` === active.id);
                const newIndex = mission.findIndex((_, idx) => `mission-${idx}` === over.id);
                
                if (oldIndex !== -1 && newIndex !== -1) {
                    updateMissionArray(arrayMove(mission, oldIndex, newIndex));
                }
            }
        };

        return (
            <div className="space-y-6">
                {/* Visi Section */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                    <div className="border-b pb-3">
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                            Visi Sekolah
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">Pernyataan visi atau cita-cita sekolah di masa depan</p>
                    </div>
                    <div>
                        <label className="block text-sm font-bold text-gray-700 mb-2">
                            Visi Sekolah <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            value={vision}
                            onChange={(e) => updateContent('vision', e.target.value)}
                            className="w-full rounded-xl border-gray-300 focus:ring-primary focus:border-primary"
                            rows="3"
                            placeholder="Masukkan visi sekolah..."
                        />
                        <p className="text-sm text-gray-500 mt-2">Tuliskan visi sekolah dengan jelas (1-2 paragraf)</p>
                    </div>
                </div>

                {/* Misi Section */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                    <div className="border-b pb-3 flex justify-between items-start">
                        <div>
                            <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                Misi Sekolah
                            </h3>
                            <p className="text-sm text-gray-600 mt-1">Daftar misi atau langkah untuk mencapai visi</p>
                        </div>
                        <button type="button" onClick={addMission} className="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                            <Plus className="w-4 h-4 mr-2" /> Tambah Misi
                        </button>
                    </div>
                    <div className="space-y-3">
                        {mission.length > 0 ? (
                            <DndContext
                                sensors={sensors}
                                collisionDetection={closestCenter}
                                onDragEnd={handleDragEnd}
                            >
                                <SortableContext
                                    items={mission.map((_, idx) => `mission-${idx}`)}
                                    strategy={verticalListSortingStrategy}
                                >
                                    {mission.map((item, idx) => (
                                        <SortableMissionItem
                                            key={`mission-${idx}`}
                                            id={`mission-${idx}`}
                                            value={item}
                                            index={idx}
                                            onUpdate={updateMission}
                                            onRemove={removeMission}
                                        />
                                    ))}
                                </SortableContext>
                            </DndContext>
                        ) : (
                            <p className="text-gray-400 text-sm text-center py-4">Belum ada misi. Klik tombol "Tambah Misi" untuk menambahkan.</p>
                        )}
                    </div>
                    <p className="text-sm text-gray-500 mt-2">Seret untuk mengubah urutan misi</p>
                </div>
            </div>
        );
    };

    const renderFacilitiesEditor = () => {
        const facilityData = data.content || {};
        // Get items directly from data.content.items (don't create new array each render)
        const facilities = Array.isArray(facilityData.items) ? facilityData.items : [];

        const updateFacilities = (newItems) => {
            updateContent('items', newItems);
        };

        const addFacility = () => {
            updateFacilities([...facilities, { title: '', image_url: '' }]);
        };

        const removeFacility = (index) => {
            const newList = [...facilities];
            newList.splice(index, 1);
            updateFacilities(newList);
        };

        const updateFacility = (index, field, value) => {
            const newList = facilities.map((item, i) => {
                if (i !== index) return item;
                // Normalize the item if it has old field names
                const normalized = {
                    title: item.title || item.name || '',
                    image_url: item.image_url || item.image || '',
                    image_file: item.image_file,
                };
                // Update the specific field
                normalized[field] = value;
                return normalized;
            });
            updateFacilities(newList);
        };

        return (
            <div className="space-y-6">
                <div className="md:col-span-2 space-y-4 bg-white border-2 border-gray-200 rounded-xl p-6">
                    <div className="border-b pb-3 mb-4">
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                            Informasi Umum Fasilitas
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">Informasi umum tentang fasilitas sekolah</p>
                    </div>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-2">Judul Fasilitas</label>
                            <input
                                type="text"
                                value={facilityData.title || ''}
                                onChange={(e) => updateContent('title', e.target.value)}
                                className="w-full rounded-xl border-gray-300 focus:ring-primary focus:border-primary"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-bold text-gray-700 mb-2">Deskripsi Fasilitas</label>
                            <textarea
                                value={facilityData.description || ''}
                                onChange={(e) => updateContent('description', e.target.value)}
                                className="w-full rounded-xl border-gray-300 focus:ring-primary focus:border-primary"
                                rows="2"
                            />
                        </div>
                    </div>
                </div>

                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                    <div className="border-b pb-3 flex justify-between items-start">
                        <div>
                            <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                                Daftar Fasilitas
                            </h3>
                            <p className="text-sm text-gray-600 mt-1">Kelola fasilitas-fasilitas yang tersedia di sekolah</p>
                        </div>
                        <button
                            type="button"
                            onClick={addFacility}
                            className="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
                        >
                            <Plus className="w-4 h-4 mr-2" /> Tambah Fasilitas
                        </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {facilities.length === 0 ? (
                            <div className="md:col-span-2 text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <Building2 className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p className="text-gray-500 mb-4">Belum ada fasilitas</p>
                                <button
                                    type="button"
                                    onClick={addFacility}
                                    className="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
                                >
                                    <Plus className="w-4 h-4 mr-2" /> Tambah Fasilitas Pertama
                                </button>
                            </div>
                        ) : (
                            facilities.map((facility, index) => (
                                <div key={index} className="relative">
                                    {/* Numbering di luar card */}
                                    <div className="absolute -left-4 top-4 bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold shadow-lg z-10">
                                        {index + 1}
                                    </div>
                                    
                                    {/* Card */}
                                    <div className="bg-gradient-to-r from-gray-50 to-white p-5 rounded-xl border-2 border-gray-200 relative group hover:shadow-md transition-all pl-10">
                                        <button
                                            type="button"
                                            onClick={() => removeFacility(index)}
                                            className="absolute top-3 right-3 bg-red-500 text-white p-1.5 rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-10 hover:bg-red-600"
                                            title="Hapus fasilitas"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                        
                                        <div className="space-y-4">
                                            <div>
                                                <label className="block text-xs font-bold text-gray-700 uppercase mb-2 flex items-center gap-1">
                                                    <span>📝</span> Nama Fasilitas
                                                </label>
                                                <input
                                                    type="text"
                                                    value={facility.title || facility.name || ''}
                                                    onChange={(e) => updateFacility(index, 'title', e.target.value)}
                                                    className="w-full rounded-lg border-gray-300 focus:ring-primary focus:border-primary font-semibold"
                                                    placeholder="Contoh: Laboratorium Komputer"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-bold text-gray-700 uppercase mb-2 flex items-center gap-1">
                                                    <span>📷</span> Foto Fasilitas
                                                </label>
                                                <FileUploadField
                                                    id={`facility-image-${index}`}
                                                    label=""
                                                    previewUrl={getImageUrl(facility.image?.original_url || facility.image_url || facility.image)}
                                                    onChange={(file) => updateFacility(index, 'image_file', file)}
                                                />
                                                <p className="text-xs text-gray-500 mt-1">Upload foto fasilitas untuk ditampilkan di galeri profil sekolah.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        );
    };

    const renderOrganizationEditor = () => {
        const orgData = data.content || {};

        return (
            <div className="space-y-6">
                {/* Gambar Bagan Section */}
                <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                    <div className="border-b pb-3">
                        <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                            Gambar Bagan Struktur
                        </h3>
                        <p className="text-sm text-gray-600 mt-1">Upload gambar bagan organisasi sekolah</p>
                    </div>
                    <FileUploadField
                        id="organization-image"
                        label=""
                        previewUrl={getImageUrl(orgData.image_url)}
                        onChange={(file) => updateContent('image', file)}
                    />
                    <p className="text-sm text-gray-500">
                        Gambar bagan struktur organisasi sekolah. Gunakan resolusi tinggi (minimal 1200px lebar) agar teks pada bagan tetap terbaca jelas saat diperbesar oleh pengunjung.
                    </p>
                </div>
            </div>
        );
    };

    return (
        <ContentManagementPage
            headerTitle="Konten Profil Sekolah"
            headTitle="Konten Profil Sekolah"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={handleTabChange}
            processing={processing}
            onSave={handleSubmit}
            errors={errors}
        >
            <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                {activeTab === 'history' && renderHistoryEditor()}
                {activeTab === 'vision_mission' && renderVisionMissionEditor()}
                {activeTab === 'facilities' && renderFacilitiesEditor()}
                {activeTab === 'organization' && renderOrganizationEditor()}
            </div>
        </ContentManagementPage>
    );
}
