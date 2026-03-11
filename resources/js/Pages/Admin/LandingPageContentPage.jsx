// resources/js/Pages/Admin/LandingPageContentPage.jsx
import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Layout, Info, User, BookOpen, Image, Megaphone } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import { useContentManagement } from '@/Hooks/useContentManagement';
import { getImageUrl } from '@/Utils/imageUtils';

// Import Section Components
import HeroSection from '@/Components/Admin/LandingPageSections/HeroSection';
import AboutSection from '@/Components/Admin/LandingPageSections/AboutSection';
import KepsekSection from '@/Components/Admin/LandingPageSections/KepsekSection';
import ProgramsSection from '@/Components/Admin/LandingPageSections/ProgramsSection';
import GallerySection from '@/Components/Admin/LandingPageSections/GallerySection';
import CtaSection from '@/Components/Admin/LandingPageSections/CtaSection';

export default function LandingPageContentPage() {
    const { currentSettings } = usePage().props;

    const {
        data,
        processing,
        formErrors,
        localSuccess,
        localErrors,
        selectedFiles,
        previewUrls,
        setPreviewUrls,
        handleFileChange,
        handleSectionInputChange,
        handleSubmit: baseHandleSubmit,
    } = useContentManagement({
        hero: currentSettings.hero || {},
        about_lp: currentSettings.about_lp || {},
        kepsek_welcome_lp: currentSettings.kepsek_welcome_lp || {},
        programs_lp: currentSettings.programs_lp || { title: '', description: '', items: [] },
        gallery_lp: currentSettings.gallery_lp || { title: '', description: '', images: [] },
        cta_lp: currentSettings.cta_lp || { title: '', description: '' },
    }, route('admin.landingpage.content.update_all'));

    // Initialize preview URLs from current settings
    useState(() => {
        setPreviewUrls({
            heroBackgroundImage: getImageUrl(currentSettings.hero?.background_image_url || ''),
            heroStudentImage: getImageUrl(currentSettings.hero?.student_image_url || ''),
            aboutImage: getImageUrl(currentSettings.about_lp?.image_url || ''),
            kepsekImage: getImageUrl(currentSettings.kepsek_welcome_lp?.kepsek_image_url || ''),
        });
    });

    const { activeTab: initialTab } = usePage().props;
    const [activeTab, setActiveTab] = useState(initialTab || 'hero');

    // Tab configuration
    const tabs = [
        { key: 'hero', label: 'Hero (Utama)', description: 'Bagian paling atas halaman, berisi judul besar dan gambar latar.', icon: Layout },
        { key: 'about', label: 'Tentang Sekolah', description: 'Informasi singkat mengenai profil sekolah.', icon: Info },
        { key: 'kepsek', label: 'Sambutan Kepsek', description: 'Foto dan pesan sambutan dari Kepala Sekolah.', icon: User },
        { key: 'programs', label: 'Program Akademik', description: 'Judul untuk bagian program studi/sekolah.', icon: BookOpen },
        { key: 'gallery', label: 'Galeri Foto', description: 'Judul untuk bagian galeri kegiatan.', icon: Image },
        { key: 'cta', label: 'Ajakan (CTA)', description: 'Tombol ajakan bertindak di bagian bawah.', icon: Megaphone },
    ];

    const handleSubmit = (e) => {
        const formData = new FormData();
        
        // Add text data
        formData.append('hero[title_line1]', data.hero?.title_line1 || '');
        formData.append('hero[title_line2]', data.hero?.title_line2 || '');
        formData.append('hero[hero_text]', data.hero?.hero_text || '');
        
        // Add stats
        if (data.hero?.stats) {
            data.hero.stats.forEach((stat, index) => {
                formData.append(`hero[stats][${index}][label]`, stat.label || '');
                formData.append(`hero[stats][${index}][value]`, stat.value || '');
                formData.append(`hero[stats][${index}][icon_name]`, stat.icon_name || '');
            });
        }

        formData.append('about_lp[title]', data.about_lp?.title || '');
        formData.append('about_lp[description_html]', data.about_lp?.description_html || '');
        formData.append('kepsek_welcome_lp[title]', data.kepsek_welcome_lp?.title || '');
        formData.append('kepsek_welcome_lp[kepsek_name]', data.kepsek_welcome_lp?.kepsek_name || '');
        formData.append('kepsek_welcome_lp[kepsek_title]', data.kepsek_welcome_lp?.kepsek_title || '');
        formData.append('kepsek_welcome_lp[welcome_text_html]', data.kepsek_welcome_lp?.welcome_text_html || '');
        
        formData.append('programs_lp[title]', data.programs_lp?.title || '');
        formData.append('programs_lp[description]', data.programs_lp?.description || '');
        
        formData.append('gallery_lp[title]', data.gallery_lp?.title || '');
        formData.append('gallery_lp[description]', data.gallery_lp?.description || '');
        
        formData.append('cta_lp[title]', data.cta_lp?.title || '');
        formData.append('cta_lp[description]', data.cta_lp?.description || '');
        
        // Add files
        if (selectedFiles.heroBackgroundImage) {
            formData.append('hero[background_image]', selectedFiles.heroBackgroundImage);
        }
        if (selectedFiles.heroStudentImage) {
            formData.append('hero[student_image]', selectedFiles.heroStudentImage);
        }
        if (selectedFiles.aboutImage) {
            formData.append('about_lp[image]', selectedFiles.aboutImage);
        }
        if (selectedFiles.kepsekImage) {
            formData.append('kepsek_welcome_lp[kepsek_image]', selectedFiles.kepsekImage);
        }

        // Add explicit preserve options for Inertia
        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                // Determine active tab based on what was saved or use default fallback if server doesn't return
                // Although server should redirect with ?tab=...
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('tab')) {
                    setActiveTab(urlParams.get('tab'));
                }
            }
        };

        baseHandleSubmit(e, formData, options);
    };

    // Render content based on active tab
    const renderTabContent = () => {
        const commonProps = {
            data,
            localErrors,
            formErrors,
            handleSectionInputChange,
        };

        switch (activeTab) {
            case 'hero':
                return (
                    <HeroSection 
                        {...commonProps}
                        previewUrls={previewUrls}
                        handleFileChange={handleFileChange}
                    />
                );
            case 'about':
                return (
                    <AboutSection 
                        {...commonProps}
                        previewUrls={previewUrls}
                        handleFileChange={handleFileChange}
                    />
                );
            case 'kepsek':
                return (
                    <KepsekSection 
                        {...commonProps}
                        previewUrls={previewUrls}
                        handleFileChange={handleFileChange}
                    />
                );
            case 'programs':
                return <ProgramsSection {...commonProps} />;
            case 'gallery':
                return <GallerySection {...commonProps} />;
            case 'cta':
                return <CtaSection {...commonProps} />;
            default:
                return <div className="p-12 text-center text-gray-400">Pilih tab di sebelah kiri untuk mulai mengedit konten.</div>;
        }
    };

    return (
        <ContentManagementPage
            headerTitle="Kelola Konten Landing Page"
            headTitle="Kelola Landing Page"
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
