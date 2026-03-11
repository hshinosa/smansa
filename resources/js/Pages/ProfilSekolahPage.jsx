import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { 
    Building, 
    Microscope, 
    Globe, 
    BookOpen,
    ArrowRight,
    X
} from 'lucide-react';

// Import Components
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import ResponsiveImage, { HeroImage, ContentImage } from '@/Components/ResponsiveImage';
import SanitizedContent from '@/Components/SanitizedContent';
import Modal from '@/Components/Modal';
// Import typography constants
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';

export default function ProfilSekolahPage({ auth, hero, history, facilities, aboutContent, programsContent, ctaContent }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const navigationData = getNavigationData(siteSettings);
    
    // Modal state for facilities
    const [selectedFacility, setSelectedFacility] = useState(null);
    const [isFacilityModalOpen, setIsFacilityModalOpen] = useState(false);
    
    // Icon mapping
    const iconMap = {
        Microscope,
        Globe,
        BookOpen,
        Building
    };
    
    // Helper to format image path correctly
    const formatImagePath = (path) => {
        if (!path) return null;
        if (typeof path !== 'string') return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };
    
    // Safety check and dynamic data extraction
    const timelineEvents = Array.isArray(history?.timeline) ? history.timeline : [];
    const historyTitle = history?.title || 'Jejak Langkah Kami';
    const historyDescription = history?.description_html || '';
    
    const facilityList = Array.isArray(facilities?.items) ? facilities.items : [];
    const facilitiesTitle = facilities?.title || 'Lingkungan Belajar Modern';
    const facilitiesDescription = facilities?.description || 'Fasilitas lengkap yang mendukung pengembangan akademik dan karakter siswa.';

    // Program studi data
    const programs = programsContent?.items || [
        {
            title: 'MIPA',
            fullName: 'Matematika & Ilmu Pengetahuan Alam',
            icon_name: 'Microscope',
            description: 'Program studi bagi siswa yang berminat dalam sains, teknologi, dan matematika.',
            subjects: 'Fisika, Kimia, Biologi, Matematika'
        },
        {
            title: 'IPS',
            fullName: 'Ilmu Pengetahuan Sosial',
            icon_name: 'Globe',
            description: 'Mendalami fenomena sosial, ekonomi, dan sejarah untuk membentuk karakter kritis.',
            subjects: 'Ekonomi, Sosiologi, Geografi, Sejarah'
        },
        {
            title: 'Bahasa',
            fullName: 'Ilmu Bahasa',
            icon_name: 'BookOpen',
            description: 'Eksplorasi bahasa asing dan seni budaya untuk komunikasi global.',
            subjects: 'Bahasa Inggris, Jepang, Mandarin, Indonesia'
        }
    ];

    const renderHighlightedTitle = (title) => {
        if (!title) return null;
        
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

    const openFacilityModal = (facility) => {
        setSelectedFacility(facility);
        setIsFacilityModalOpen(true);
    };

    const closeFacilityModal = () => {
        setIsFacilityModalOpen(false);
        setSelectedFacility(null);
    };

    return (
        <div className="bg-white font-sans text-gray-800">
            <SEOHead 
                title={`${hero?.title || 'Profil & Sejarah'} - ${siteName}`}
                description={`Mengenal lebih dekat sejarah, program studi, dan fasilitas ${siteName}. Sekolah unggulan dengan tradisi akademik yang kuat.`}
                keywords="profil sekolah, sejarah SMAN 1 Baleendah, program studi, fasilitas sekolah, tentang sekolah"
                image={heroImage}
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
                        <HeroImage 
                            src={formatImagePath(heroImage)} 
                            alt={`Gedung ${siteName}`} 
                        />
                        <div className="absolute inset-0 bg-black/60"></div>
                    </div>

                    {/* Content */}
                    <div className="relative z-10 container mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
                        <h1 className={`${TYPOGRAPHY.heroTitle} mb-4 drop-shadow-lg`}>
                            {renderHighlightedTitle(hero?.title || `Profil ${siteName}`)}
                        </h1>
                    </div>
                </section>

                {/* SECTION B: TENTANG SEKOLAH */}
                <section className="py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                            <div className="relative">
                                <div className="aspect-video rounded-2xl overflow-hidden shadow-xl bg-gray-200">
                                    <ContentImage 
                                        src={aboutContent?.image_url} 
                                        media={aboutContent?.aboutImage} 
                                        alt={aboutContent?.title || 'Tentang Sekolah'} 
                                    />
                                </div>
                                {/* Decorative dots */}
                                <div className="absolute -bottom-6 -right-6 w-24 h-24 bg-dots-pattern opacity-20 hidden md:block"></div>
                            </div>
                            <div>
                                <h2 className={`${TYPOGRAPHY.sectionHeading} mb-6`}>
                                    {aboutContent?.title?.split(' ').slice(0, -1).join(' ') || 'Tentang'} <span className="text-primary">{aboutContent?.title?.split(' ').slice(-1) || 'Sekolah'}</span>
                                </h2>
                                <SanitizedContent 
                                    className={`${TYPOGRAPHY.bodyText} mb-8 prose prose-blue max-w-none`}
                                    html={aboutContent?.description_html || '<p>SMAN 1 Baleendah adalah sekolah menengah atas unggulan yang berkomitmen mencetak generasi berprestasi dan berkarakter.</p>'}
                                />
                            </div>
                        </div>
                    </div>
                </section>

                {/* SECTION C: TIMELINE SEJARAH (Enhanced) */}
                <section className="py-20 bg-gray-50 relative overflow-hidden">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                        <div className="text-center mb-16">
                            <SanitizedContent 
                                as="h2"
                                className={`${TYPOGRAPHY.sectionHeading} mb-4`}
                                html={historyTitle.replace('Kami', '<span class="text-primary">Kami</span>')}
                            />
                            {historyDescription && (
                                <SanitizedContent 
                                    className={`${TYPOGRAPHY.bodyText} max-w-3xl mx-auto prose prose-blue`}
                                    html={historyDescription}
                                />
                            )}
                        </div>

                        <div className="relative max-w-4xl mx-auto">
                            {/* Center Line */}
                            <div className="absolute left-1/2 transform -translate-x-1/2 h-full w-1 bg-gradient-to-b from-primary/30 via-primary to-primary/30 rounded-full hidden md:block"></div>

                            <div className="space-y-12">
                                {timelineEvents.map((event, idx) => {
                                    return (
                                        <div key={idx} className={`flex flex-col md:flex-row items-center justify-between ${idx % 2 !== 0 ? 'md:flex-row-reverse' : ''}`}>
                                            {/* Content Side */}
                                            <div className="w-full md:w-5/12 mb-8 md:mb-0">
                                                <div className={`bg-white p-6 rounded-2xl shadow-lg border-l-4 border-primary hover:shadow-xl transition-all duration-300 hover:-translate-y-1 ${idx % 2 !== 0 ? 'md:text-right md:border-l-0 md:border-r-4' : ''}`}>
                                                    <div className={`flex items-center gap-3 mb-3 ${idx % 2 !== 0 ? 'md:justify-end' : ''}`}>
                                                        <span className="inline-block px-3 py-1 bg-blue-50 text-primary font-bold rounded-full text-sm">
                                                            {event.year}
                                                        </span>
                                                    </div>
                                                    <h3 className={`${TYPOGRAPHY.cardTitle} mb-2 text-gray-900`}>
                                                        {event.title}
                                                    </h3>
                                                    <p className={TYPOGRAPHY.bodyText}>
                                                        {event.description}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Center Dot */}
                                            <div className="absolute left-1/2 transform -translate-x-1/2 w-5 h-5 bg-accent-yellow rounded-full border-4 border-white shadow-lg hidden md:block z-10"></div>

                                            {/* Empty Side for Spacing */}
                                            <div className="w-full md:w-5/12 hidden md:block"></div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </section>

                {/* SECTION D: PROGRAM & KURIKULUM */}
                <section className="py-20 bg-secondary">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-16">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                                {programsContent?.title?.split(' ').slice(0, -1).join(' ') || 'Program'} <span className="text-primary">{programsContent?.title?.split(' ').slice(-1) || 'Studi'}</span>
                            </h2>
                            <p className={TYPOGRAPHY.bodyText}>
                                {programsContent?.description || 'Pilihan program studi yang dirancang untuk mempersiapkan siswa menuju jenjang pendidikan tinggi.'}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {programs.map((program, idx) => {
                                const IconComponent = iconMap[program.icon_name] || Microscope;
                                return (
                                    <div key={idx} className="bg-white rounded-3xl p-8 shadow-lg border border-gray-100">
                                        <div className={`w-16 h-16 rounded-2xl flex items-center justify-center mb-6 ${program.color_class || 'bg-blue-50 text-primary'}`}>
                                            <IconComponent size={32} />
                                        </div>
                                        <h3 className={`${TYPOGRAPHY.cardTitle} mb-2`}>{program.title}</h3>
                                        <p className="text-sm text-primary font-medium mb-4">{program.fullName || program.subtitle}</p>
                                        <p className={`${TYPOGRAPHY.smallText} mb-6 leading-relaxed`}>
                                            {program.description}
                                        </p>
                                        <div className="pt-4 border-t border-gray-100">
                                            <p className="text-xs text-gray-500 font-medium mb-1">Mata Pelajaran Unggulan:</p>
                                            <p className="text-sm text-gray-700">{program.subjects}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* SECTION E: FASILITAS (Enhanced with Modal) */}
                <section className="py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                                {facilitiesTitle}
                            </h2>
                            <p className={`${TYPOGRAPHY.bodyText} max-w-2xl mx-auto`}>
                                {facilitiesDescription}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            {facilityList.map((facility, idx) => {
                                const imageUrl = facility.image_url || facility.image || '';
                                const title = facility.title || facility.name || 'Fasilitas';
                                const description = facility.description || '';

                                return (
                                    <div 
                                        key={idx} 
                                        className="group relative rounded-2xl overflow-hidden shadow-lg aspect-[4/3] cursor-pointer hover:shadow-xl transition-all duration-300"
                                        onClick={() => openFacilityModal(facility)}
                                    >
                                        {imageUrl ? (
                                            <ResponsiveImage 
                                                src={imageUrl} 
                                                alt={title} 
                                                className="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700"
                                                loading="lazy"
                                            />
                                        ) : (
                                            <div className="w-full h-full bg-blue-600 flex items-center justify-center text-white">
                                                <Building className="w-16 h-16 opacity-20" />
                                                <span className="absolute inset-0 flex items-center justify-center font-bold text-lg p-4 text-center">
                                                    {title}
                                                </span>
                                            </div>
                                        )}
                                        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-90 transition-opacity"></div>
                                        <div className="absolute bottom-0 left-0 right-0 p-6 transform translate-y-2 group-hover:translate-y-0 transition-transform">
                                            <h3 className="text-xl font-bold text-white mb-1">{title}</h3>
                                            <p className="text-white/80 text-sm line-clamp-2 mb-3">{description}</p>
                                            <span className="inline-flex items-center text-accent-yellow text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                                Lihat Detail <ArrowRight size={16} className="ml-1" />
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* SECTION F: CTA */}
                <section className="py-20 bg-primary relative overflow-hidden rounded-3xl mx-4 mb-16">
                    {/* Decorative Circles */}
                    <div className="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                    <div className="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/2 translate-y-1/2"></div>

                    <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                        <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
                            {ctaContent?.title || 'Siap Menjadi Bagian dari Keluarga Besar SMAN 1 Baleendah?'}
                        </h2>
                        <p className="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
                            {ctaContent?.description || 'Dapatkan informasi lengkap mengenai pendaftaran peserta didik baru, jadwal, dan persyaratan.'}
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link 
                                href="/informasi-spmb" 
                                className="px-8 py-4 bg-accent-yellow text-gray-900 font-bold rounded-xl hover:bg-yellow-400 transition-colors shadow-lg"
                            >
                                Info PPDB
                            </Link>
                            <Link 
                                href="/kontak" 
                                className="px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-xl hover:bg-white hover:text-primary transition-colors"
                            >
                                Hubungi Kami
                            </Link>
                        </div>
                    </div>
                </section>
            </main>

            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />

            {/* Facility Detail Modal */}
            <Modal show={isFacilityModalOpen} onClose={closeFacilityModal} maxWidth="2xl">
                <div className="p-0 relative bg-white rounded-lg overflow-hidden">
                    <button
                        onClick={closeFacilityModal}
                        className="absolute top-4 right-4 text-gray-500 hover:text-gray-800 bg-white/90 backdrop-blur rounded-full p-2 hover:bg-white transition-colors focus:outline-none z-10 shadow-sm"
                        aria-label="Tutup"
                    >
                        <X size={24} />
                    </button>
                    
                    {selectedFacility && (
                        <div>
                            {/* Image Header */}
                            <div className="h-64 bg-gray-200 relative">
                                {selectedFacility.image_url || selectedFacility.image ? (
                                    <ResponsiveImage 
                                        src={selectedFacility.image_url || selectedFacility.image} 
                                        alt={selectedFacility.title || selectedFacility.name}
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <div className="w-full h-full bg-blue-600 flex items-center justify-center">
                                        <Building className="w-20 h-20 text-white/30" />
                                    </div>
                                )}
                                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <div className="absolute bottom-4 left-6 right-6">
                                    <h3 className="text-2xl font-bold text-white">
                                        {selectedFacility.title || selectedFacility.name}
                                    </h3>
                                </div>
                            </div>
                            
                            {/* Content */}
                            <div className="p-6">
                                <p className="text-gray-600 leading-relaxed">
                                    {selectedFacility.description || 'Fasilitas ini mendukung kegiatan belajar mengajar dan pengembangan bakat siswa.'}
                                </p>
                            </div>
                        </div>
                    )}
                </div>
            </Modal>
        </div>
    );
}
