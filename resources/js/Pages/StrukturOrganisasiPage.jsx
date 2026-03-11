import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';

// Import Components
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import Modal from '@/Components/Modal';
import SEOHead from '@/Components/SEOHead';
import { HeroImage, ContentImage } from '@/Components/ResponsiveImage';
import { X } from 'lucide-react';
// Import typography constants
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';

export default function StrukturOrganisasiPage({ auth, organization, hero }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const navigationData = getNavigationData(siteSettings);
    const [isImageModalOpen, setIsImageModalOpen] = useState(false);
    
    const chartMedia = organization?.chartImage;
    const chartFallback = organization?.image_url || '/images/hero-bg-sman1baleendah.jpeg';
    
    // For modal, prefer original high-res URL from Spatie if available
    const modalImageSrc = chartMedia?.original_url || chartFallback;

    const openImageModal = () => {
        setIsImageModalOpen(true);
    };

    const closeImageModal = () => {
        setIsImageModalOpen(false);
    };

    const renderHighlightedTitle = (title) => {
        if (!title) return null;
        
        // Check for specific phrase "SMAN 1 Baleendah"
        if (title.includes('SMAN 1 Baleendah')) {
            const parts = title.split('SMAN 1 Baleendah');
            return (
                <>
                    {parts[0]}<span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">SMAN 1 Baleendah</span>{parts[1]}
                </>
            );
        }

        const words = title.split(' ');
        if (words.length <= 1) return title;
        const lastWord = words.pop();
        return (
            <>
                {words.join(' ')} <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{lastWord}</span>
            </>
        );
    };

    return (
        <div className="bg-white font-sans text-gray-800">
            <SEOHead 
                title={`${hero?.title || 'Struktur Organisasi'} - ${siteName}`}
                description="Struktur organisasi dan manajemen SMAN 1 Baleendah. Kepala sekolah, wakil kepala, koordinator, dan tim manajemen sekolah."
                keywords="struktur organisasi, manajemen sekolah, kepala sekolah, organisasi sekolah, SMAN 1 Baleendah"
                image={modalImageSrc}
            />

            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
            {/* SECTION A: HERO BANNER */}
            <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                {/* Background Image */}
                <div className="absolute inset-0 z-0">
                    <img 
                        src={heroImage} 
                        alt={`Gedung ${siteName}`} 
                        className="w-full h-full object-cover"
                        loading="eager"
                        fetchpriority="high"
                        width="1920"
                        height="1080"
                    />
                    <div className="absolute inset-0 bg-black/60"></div>
                </div>

                {/* Content */}\
                <div className="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
                    <h1 className={`${TYPOGRAPHY.heroTitle} mb-4 drop-shadow-lg`}>
                        {renderHighlightedTitle(hero?.title || 'Struktur Organisasi')}
                    </h1>
                </div>
            </section>

            {/* SECTION B: CHART (Bagan) */}
            <section className="py-20 bg-secondary">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="bg-white p-6 md:p-10 rounded-3xl shadow-xl max-w-6xl mx-auto border border-gray-100">
                        <div onClick={openImageModal} className="cursor-pointer group">
                            <ContentImage
                                src={chartFallback}
                                media={chartMedia}
                                alt="Bagan Struktur Organisasi SMA Negeri 1 Baleendah - Klik untuk memperbesar"
                                className="w-full h-auto rounded-xl border border-gray-100 transition-all duration-300 transform group-hover:scale-[1.01] group-hover:shadow-lg"
                            />
                        </div>
                        <p className={`${TYPOGRAPHY.smallText} text-center mt-6 italic`}>
                            Klik pada gambar untuk memperbesar tampilan.
                        </p>
                    </div>
                </div>
            </section>

           </main> <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />

            {/* Modal */}
            <Modal show={isImageModalOpen} onClose={closeImageModal} maxWidth="5xl">
                <div className="p-4 relative bg-white rounded-lg">
                    <button
                        onClick={closeImageModal}
                        className="absolute top-4 right-4 text-gray-500 hover:text-gray-800 bg-white/80 rounded-full p-2 hover:bg-white transition-colors focus:outline-none z-10 shadow-sm"
                        aria-label="Tutup gambar"
                    >
                        <X size={24} />
                    </button>
                    <img
                        src={modalImageSrc}
                        alt="Bagan Struktur Organisasi SMA Negeri 1 Baleendah (diperbesar)"
                        className="w-full h-auto rounded-lg"
                    />
                </div>
            </Modal>
        </div>
    );
}
