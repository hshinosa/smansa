// resources/js/Pages/Admin/SpmbContentPage.jsx
import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { Info, Calendar, Users, FileText, ClipboardList, HelpCircle } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import { useContentManagement } from '@/Hooks/useContentManagement';
import toast from 'react-hot-toast';

// Import Section Components
import GeneralSettingsSection from '@/Components/Admin/SpmbSections/GeneralSettingsSection';
import RegistrationPathsSection from '@/Components/Admin/SpmbSections/RegistrationPathsSection';
import ScheduleSection from '@/Components/Admin/SpmbSections/ScheduleSection';
import RequirementsSection from '@/Components/Admin/SpmbSections/RequirementsSection';
import ProcedureSection from '@/Components/Admin/SpmbSections/ProcedureSection';
import FaqSection from '@/Components/Admin/SpmbSections/FaqSection';

export default function SpmbContentPage() {
    const { currentSettings, activeSection } = usePage().props;

    const {
        data,
        setData,
        processing,
        formErrors,
        localSuccess,
        localErrors,
        setLocalErrors,
        handleSectionInputChange,
        handleSubmit: originalHandleSubmit,
    } = useContentManagement({
        pengaturan_umum: currentSettings.pengaturan_umum || {},
        jalur_pendaftaran: currentSettings.jalur_pendaftaran || { items: [] },
        jadwal_penting: currentSettings.jadwal_penting || { items: [] },
        persyaratan: currentSettings.persyaratan || { items: [] },
        prosedur: currentSettings.prosedur || { items: [] },
        faq: currentSettings.faq || { items: [] },
    }, route('admin.spmb.update_all'), 'put');

    // Custom submit handler
    const handleSubmit = (e) => {
        if (e) e.preventDefault();
        setLocalErrors({});

        // Use standard PUT request with scroll preservation
        // Note: useContentManagement hooks internal submit calls router.visit/post/put
        // We might need to ensure preserveScroll is passed if available in that hook,
        // but typically inertia helpers have it as option.
        // Since we don't control the hook args here directly for options...
        // Let's rely on the controller redirect with param & react state init to "feel" like persistence.
        originalHandleSubmit(e);
    };

    const [activeTab, setActiveTab] = useState(activeSection || 'pengaturan_umum');

    // Tab configuration
    const tabs = [
        { key: 'pengaturan_umum', label: 'Pengaturan Umum', icon: Info, description: 'Konfigurasi dasar SPMB' },
        { key: 'jalur_pendaftaran', label: 'Jalur Pendaftaran', icon: Users, description: 'Jalur reguler dan prestasi' },
        { key: 'jadwal_penting', label: 'Jadwal Penting', icon: Calendar, description: 'Timeline kegiatan SPMB' },
        { key: 'persyaratan', label: 'Persyaratan', icon: FileText, description: 'Dokumen yang diperlukan' },
        { key: 'prosedur', label: 'Prosedur', icon: ClipboardList, description: 'Langkah-langkah pendaftaran' },
        { key: 'faq', label: 'FAQ', icon: HelpCircle, description: 'Pertanyaan yang sering diajukan' }
    ];

    // Helper functions for array fields (kept here as they are specific to SPMB)
    const addArrayItem = (section, field, newItem) => {
        const currentArray = data[section][field] || [];
        setData(section, {
            ...data[section],
            [field]: [...currentArray, newItem]
        });
    };

    const removeArrayItem = (section, field, index) => {
        const currentArray = data[section][field] || [];
        const newArray = currentArray.filter((_, i) => i !== index);
        setData(section, {
            ...data[section],
            [field]: newArray
        });
    };

    const updateArrayItem = (section, field, index, key, value) => {
        const currentArray = data[section][field] || [];
        const newArray = [...currentArray];
        newArray[index] = { ...newArray[index], [key]: value };
        setData(section, {
            ...data[section],
            [field]: newArray
        });
    };

    // Render different sections based on active tab
    const renderTabContent = () => {
        const commonProps = {
            data,
            setData,
            localErrors,
            formErrors,
            handleSectionInputChange,
        };

        switch (activeTab) {
            case 'pengaturan_umum':
                return <GeneralSettingsSection {...commonProps} />;
            case 'jalur_pendaftaran':
                return <RegistrationPathsSection {...commonProps} />;
            case 'jadwal_penting':
                return (
                    <ScheduleSection 
                        {...commonProps} 
                        addEvent={() => addArrayItem('jadwal_penting', 'items', { date: '', title: '', description: '' })}
                        removeEvent={(index) => removeArrayItem('jadwal_penting', 'items', index)}
                        updateEvent={(index, key, value) => updateArrayItem('jadwal_penting', 'items', index, key, value)}
                    />
                );
            case 'persyaratan':
                return (
                    <RequirementsSection 
                        {...commonProps}
                        addRequirement={() => addArrayItem('persyaratan', 'items', { name: '', description: '', required: true })}
                        removeRequirement={(index) => removeArrayItem('persyaratan', 'items', index)}
                        updateRequirement={(index, key, value) => updateArrayItem('persyaratan', 'items', index, key, value)}
                    />
                );
            case 'prosedur':
                return (
                    <ProcedureSection 
                        {...commonProps}
                        addStep={() => addArrayItem('prosedur', 'items', { title: '', description: '' })}
                        removeStep={(index) => removeArrayItem('prosedur', 'items', index)}
                        updateStep={(index, key, value) => updateArrayItem('prosedur', 'items', index, key, value)}
                    />
                );
            case 'faq':
                return (
                    <FaqSection 
                        {...commonProps}
                        addFaq={() => addArrayItem('faq', 'items', { question: '', answer: '' })}
                        removeFaq={(index) => removeArrayItem('faq', 'items', index)}
                        updateFaq={(index, key, value) => updateArrayItem('faq', 'items', index, key, value)}
                    />
                );
            default:
                return <div className="p-12 text-center text-gray-400">Pilih tab di sebelah kiri untuk mulai mengedit konten.</div>;
        }
    };

    return (
        <ContentManagementPage
            headerTitle="Kelola Konten SPMB"
            headTitle="Kelola SPMB"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            processing={processing}
            onSave={handleSubmit}
            success={localSuccess}
            errors={localErrors}
        >
            {renderTabContent()}
        </ContentManagementPage>
    );
}
