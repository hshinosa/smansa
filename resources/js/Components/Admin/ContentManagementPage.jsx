// FILE: resources/js/Components/Admin/ContentManagementPage.jsx
// Redesigned for accessibility (ages 20-50) with larger touch targets and clearer UI

import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import ContentEditorSidebar from '@/Components/Admin/ContentEditorSidebar';
import ContentEditorHeader from '@/Components/Admin/ContentEditorHeader';
import { CheckCircle, AlertCircle } from 'lucide-react';

export default function ContentManagementPage({ 
    headerTitle, 
    headTitle, 
    tabs, 
    activeTab, 
    setActiveTab, 
    processing, 
    onSave, 
    success, 
    errors = {}, 
    extraHeader,
    noForm = false,
    customAction,
    children 
}) {
    const activeTabData = tabs.find(t => t.key === activeTab);
    const Wrapper = noForm ? 'div' : 'form';
    const wrapperProps = noForm ? { className: "space-y-6" } : { onSubmit: onSave, className: "space-y-6" };

    return (
        <AdminLayout headerTitle={headerTitle}>
            <Head title={headTitle} />
            <div className="max-w-screen-2xl mx-auto px-2 sm:px-4">
                {/* Extra Header (e.g., Program Selector) */}
                {extraHeader && (
                    <div className="mb-6">
                        {extraHeader}
                    </div>
                )}

                {/* Success Message */}
                {success && (
                    <div className="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 shadow-sm flex items-start gap-3 animate-fade-in-up">
                        <CheckCircle className="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <p className="font-semibold text-base">Berhasil!</p>
                            <p className="text-sm mt-1">{success}</p>
                        </div>
                    </div>
                )}

                {/* Error Messages */}
                {(errors.general || Object.keys(errors).length > 0) && (
                    <div className="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 shadow-sm animate-fade-in-up">
                        <div className="flex items-start gap-3">
                            <AlertCircle className="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-semibold text-base">Ada Kesalahan</p>
                                <p className="text-sm mt-1">
                                    {errors.general || 'Mohon periksa kembali form yang ditandai merah.'}
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
                    {/* Sidebar Navigation */}
                    <ContentEditorSidebar 
                        tabs={tabs} 
                        activeTab={activeTab} 
                        setActiveTab={setActiveTab} 
                    />

                    {/* Content Area */}
                    <div className="lg:col-span-3 space-y-6">
                        <Wrapper {...wrapperProps}>
                            {/* Header Content */}
                            <ContentEditorHeader 
                                title={activeTabData?.label}
                                description={activeTabData?.description}
                                icon={activeTabData?.icon}
                                processing={processing}
                                onSave={onSave}
                                customAction={customAction}
                            />

                            {/* Form Content */}
                            <div className="animate-fade-in">
                                {children}
                            </div>
                        </Wrapper>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
