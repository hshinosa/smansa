import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import { useContentManagement } from '@/Hooks/useContentManagement';
import { BookOpen, Microscope, Briefcase, Layout } from 'lucide-react';

import CoreSubjectsSection from '@/Components/Admin/ProgramStudiSections/CoreSubjectsSection';
import FacilitiesSection from '@/Components/Admin/ProgramStudiSections/FacilitiesSection';
import CareerPathsSection from '@/Components/Admin/ProgramStudiSections/CareerPathsSection';
import ThumbnailCardSection from '@/Components/Admin/ProgramStudiSections/ThumbnailCardSection';

export default function Index({ currentSettings, activeProgram, thumbnailCardUrl }) {
    const {
        data,
        setData,
        processing,
        localSuccess,
        localErrors,
        handleSubmit,
    } = useContentManagement({
        core_subjects: currentSettings.core_subjects || { title: '', description: '', items: [] },
        facilities: currentSettings.facilities || { title: '', description: '', items: [] },
        career_paths: currentSettings.career_paths || { title: '', description: '', items: [] },
        thumbnail_card: { 
            image: null,
            _preview: thumbnailCardUrl // Store preview URL separately
        },
    }, route('admin.program-studi.update_all'));

    const [activeTab, setActiveTab] = useState('thumbnail_card');

    const tabs = [
        {
            key: 'thumbnail_card',
            label: 'Thumbnail Card',
            description: 'Foto untuk ditampilkan di Landing Page',
            icon: Layout,
        },
        {
            key: 'core_subjects',
            label: 'Mata Pelajaran Unggulan',
            description: 'Daftar mata pelajaran inti program studi.',
            icon: BookOpen,
        },
        {
            key: 'facilities',
            label: 'Fasilitas Riset',
            description: 'Fasilitas pendukung praktikum dan riset.',
            icon: Microscope,
        },
        {
            key: 'career_paths',
            label: 'Prospek Karir',
            description: 'Peluang karir bagi lulusan.',
            icon: Briefcase,
        },
    ];

    const handleProgramChange = (e) => {
        router.get(route('admin.program-studi.index'), { program: e.target.value });
    };

    // Helper to recursively append data to FormData
    const appendToFormData = (formData, data, parentKey = null) => {
        if (data === null || data === undefined) return;

        if (data instanceof File) {
            formData.append(parentKey, data);
            return;
        }

        if (Array.isArray(data)) {
            data.forEach((item, index) => {
                appendToFormData(formData, item, `${parentKey}[${index}]`);
            });
            return;
        }

        if (typeof data === 'object' && !(data instanceof Date)) {
            Object.keys(data).forEach(key => {
                const formKey = parentKey ? `${parentKey}[${key}]` : key;
                appendToFormData(formData, data[key], formKey);
            });
            return;
        }

        // Handle booleans as 1/0
        if (typeof data === 'boolean') {
            formData.append(parentKey, data ? '1' : '0');
            return;
        }

        // Primitives
        formData.append(parentKey, data);
    };

    const handleSave = (e) => {
        e.preventDefault();
        
        const formData = new FormData();
        
        // Handle thumbnail_card separately
        if (activeTab === 'thumbnail_card' && data.thumbnail_card.image instanceof File) {
            formData.append('thumbnail_card', data.thumbnail_card.image);
        } else {
            // Append all data using recursive helper for other tabs
            appendToFormData(formData, data[activeTab], activeTab);
        }
        
        // Append program_name
        formData.append('program_name', activeProgram);

        // Call handleSubmit with FormData
        // Note: useContentManagement's handleSubmit handles FormData via router.post
        handleSubmit(e, formData);
    };

    const renderActiveSection = () => {
        const sectionProps = {
            data: data[activeTab],
            setData: (key, value) => {
                const newData = { ...data[activeTab], [key]: value };
                setData(activeTab, newData);
            },
            errors: localErrors[activeTab] || {},
        };

        switch (activeTab) {
            case 'core_subjects': return <CoreSubjectsSection {...sectionProps} />;
            case 'facilities': return <FacilitiesSection {...sectionProps} />;
            case 'career_paths': return <CareerPathsSection {...sectionProps} />;
            case 'thumbnail_card': return <ThumbnailCardSection {...sectionProps} programName={activeProgram} />;
            default: return null;
        }
    };

    return (
        <ContentManagementPage
            headerTitle={`Edit Konten Program Studi ${activeProgram.toUpperCase()}`}
            headTitle={`Admin - Program Studi ${activeProgram.toUpperCase()}`}
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            processing={processing}
            onSave={handleSave}
            success={localSuccess}
            errors={localErrors}
            extraHeader={
                <div className="bg-white shadow-sm rounded-xl p-4 border border-gray-100">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div className="flex items-center gap-3">
                            <div className="bg-primary/10 p-2 rounded-lg">
                                <Layout className="w-5 h-5 text-primary" />
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-gray-900">Pilih Program Studi</h3>
                                <p className="text-xs text-gray-500">Pilih program yang ingin dikelola kontennya</p>
                            </div>
                        </div>
                        <div className="sm:ml-auto min-w-[280px]">
                            <select
                                id="program-select"
                                value={activeProgram}
                                onChange={handleProgramChange}
                                className="w-full border-gray-200 focus:border-primary focus:ring-primary rounded-lg shadow-sm text-sm transition-all"
                            >
                                <option value="mipa">MIPA (Matematika & Ilmu Pengetahuan Alam)</option>
                                <option value="ips">IPS (Ilmu Pengetahuan Sosial)</option>
                                <option value="bahasa">Bahasa</option>
                            </select>
                        </div>
                    </div>
                </div>
            }
        >
            {renderActiveSection()}
        </ContentManagementPage>
    );
}
