import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { 
    BookOpen, 
    MapPin, 
    TrendingUp, 
    Users, 
    Scale, 
    Briefcase, 
    Newspaper,
    Globe,
    Landmark,
    Calculator,
    Microscope,
    FlaskConical,
    Atom,
    Dna,
    Languages,
    Palette,
    Music,
    Activity,
    Code,
    Brain,
    Stethoscope,
    GraduationCap,
    Building,
    Cpu,
    HardHat,
    Plane,
    Ship
} from 'lucide-react';

import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import ResponsiveImage, { HeroImage } from '@/Components/ResponsiveImage';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { getPageMetadata, programStudyData } from '@/Utils/academicData';
import { usePage } from '@inertiajs/react';

export default function ProgramIpsPage({ content }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const navigationData = getNavigationData(siteSettings);
    const pageMetadata = getPageMetadata(siteName);
    const programData = programStudyData.ips;
    
    // Icon mapping for core subjects
    const iconMap = {
        BookOpen, Calculator, Microscope, FlaskConical, Atom, Dna, Globe,
        Languages, Palette, Music, Activity, Code, Brain, TrendingUp,
        Scale, Users, Landmark, Briefcase, Stethoscope, GraduationCap,
        Building, Cpu, Newspaper, HardHat, Plane, Ship
    };
    const { hero, core_subjects, facilities, career_paths } = content || {};

    // Helper to format image path correctly
    const formatImagePath = (path) => {
        if (!path) return null;
        if (typeof path !== 'string') return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const getHeroImageSrc = () => {
        return formatImagePath(heroImage) || "/images/program-ips.jpg";
    };

    const renderHighlightedTitle = (title) => {
        if (!title) return null;
        return (
            <>
                Program Studi <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{title}</span>
            </>
        );
    };

    return (
        <div className="bg-white font-sans text-gray-800">
            <SEOHead 
                title={hero?.title || pageMetadata?.ips?.title}
                description={hero?.description || pageMetadata?.ips?.description}
                keywords="peminatan IPS, jurusan IPS, ekonomi, sosiologi, geografi, sejarah, SMAN 1 Baleendah"
                image={getHeroImageSrc()}
            />
            
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />
            <main id="main-content" className="pt-20" tabIndex="-1">            {/* 1. HERO SECTION (Consistent with AcademicCalendarPage) */}
            <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                {/* Background Image */}
                <div className="absolute inset-0 z-0">
                    <HeroImage 
                        src={formatImagePath(heroImage)} 
                        alt="Ilmu Pengetahuan Sosial" 
                    />
                    <div className="absolute inset-0 bg-black/60"></div>
                </div>

                <div className="relative z-10 container mx-auto px-4 text-center text-white">
                    <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                        {renderHighlightedTitle(programData.subtitle)}
                    </h1>
                    <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                        {hero?.description || "Membentuk pemikir kritis dan analis sosial yang peka terhadap dinamika masyarakat global serta siap menjadi pemimpin masa depan."}
                    </p>
                </div>
            </section>

            {/* 2. CORE SUBJECTS (Glass Cards) */}
            <section className="py-24 bg-slate-50 relative overflow-hidden">
                {/* Decorative Elements */}
                <div className="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>
                <div className="absolute -top-40 -right-40 w-96 h-96 bg-orange-100 rounded-full blur-3xl opacity-50"></div>
                <div className="absolute -bottom-40 -left-40 w-96 h-96 bg-red-100 rounded-full blur-3xl opacity-50"></div>

                <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                            {core_subjects?.title || "Mata Pelajaran"} <span className="text-primary">Inti</span>
                        </h2>
                        <p className={TYPOGRAPHY.bodyText}>
                            {core_subjects?.description || "Kurikulum komprehensif untuk membangun wawasan sosial, ekonomi, dan pemahaman geopolitik."}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {core_subjects?.items && core_subjects.items.length > 0 ? (
                            core_subjects.items.map((item, idx) => {
                                const IconComponent = iconMap[item.icon_name] || Users;
                                return (
                                    <div key={idx} className="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 transition-all duration-300">
                                        <div className="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mb-6 transition-colors duration-300">
                                            <IconComponent className="w-8 h-8 text-orange-600 transition-colors" />
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-900 mb-3 font-serif">{item.title}</h3>
                                        <p className="text-gray-600 text-sm leading-relaxed">
                                            {item.description}
                                        </p>
                                    </div>
                                );
                            })
                        ) : (
                            <>
                                {/* Sosiologi */}
                                <div className="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 transition-all duration-300">
                                    <div className="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mb-6 transition-colors duration-300">
                                        <Users className="w-8 h-8 text-orange-600 transition-colors" />
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3 font-serif">Sosiologi</h3>
                                    <p className="text-gray-600 text-sm leading-relaxed">
                                        Studi interaksi sosial, struktur masyarakat, and pemecahan masalah sosial kontemporer.
                                    </p>
                                </div>

                                {/* Ekonomi */}
                                <div className="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 transition-all duration-300">
                                    <div className="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 transition-colors duration-300">
                                        <TrendingUp className="w-8 h-8 text-blue-600 transition-colors" />
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3 font-serif">Ekonomi</h3>
                                    <p className="text-gray-600 text-sm leading-relaxed">
                                        Prinsip ekonomi, akuntansi, dan manajemen untuk memahami dinamika pasar dan kesejahteraan.
                                    </p>
                                </div>

                                {/* Geografi */}
                                <div className="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 transition-all duration-300">
                                    <div className="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center mb-6 transition-colors duration-300">
                                        <MapPin className="w-8 h-8 text-green-600 transition-colors" />
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3 font-serif">Geografi</h3>
                                    <p className="text-gray-600 text-sm leading-relaxed">
                                        Analisis spasial, lingkungan, dan interaksi manusia dengan alam dalam konteks global.
                                    </p>
                                </div>

                                {/* Sejarah */}
                                <div className="bg-white rounded-2xl p-8 shadow-lg border border-gray-100 transition-all duration-300">
                                    <div className="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mb-6 transition-colors duration-300">
                                        <BookOpen className="w-8 h-8 text-red-600 transition-colors" />
                                    </div>
                                    <h3 className="text-xl font-bold text-gray-900 mb-3 font-serif">Sejarah</h3>
                                    <p className="text-gray-600 text-sm leading-relaxed">
                                        Memahami masa lalu untuk membangun masa depan melalui analisis peristiwa dan perubahan sosial.
                                    </p>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </section>

            {/* 3. FACILITIES (Photo Gallery) */}
            <section className="py-24 bg-white">
                <style>{`
                    @keyframes scroll-vertical {
                        0% { transform: translateY(0); }
                        100% { transform: translateY(-50%); }
                    }
                    .animate-scroll-vertical {
                        animation: scroll-vertical 20s linear infinite;
                    }
                    .pause-hover:hover .animate-scroll-vertical {
                        animation-play-state: paused;
                    }
                `}</style>
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row justify-between items-end mb-12">
                        <div className="max-w-2xl">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                                {facilities?.title || "Fasilitas Penunjang"}
                            </h2>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:h-[500px]">
                        {/* Left Column: Main Static Item */}
                        <div className="lg:col-span-2 relative rounded-3xl overflow-hidden group shadow-lg h-[300px] sm:h-[400px] lg:h-full">
                            <ResponsiveImage 
                                media={typeof facilities?.main_image === 'object' ? facilities.main_image : null}
                                src={typeof facilities?.main_image === 'string' ? formatImagePath(facilities.main_image) : null} 
                                alt={facilities?.main_title || "Fasilitas Utama"} 
                                className="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
                                loading="lazy"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-90"></div>
                            <div className="absolute bottom-0 left-0 p-6 sm:p-8">
                                <span className="px-3 py-1 bg-orange-500 text-white text-xs font-bold rounded-full mb-3 inline-block">Utama</span>
                                <h3 className="text-xl sm:text-2xl font-bold text-white font-serif">{facilities?.main_title || "Perpustakaan & Ruang Diskusi"}</h3>

                            </div>
                        </div>

                        {/* Right Column: Scrollable Grid on Mobile, Vertical Marquee on Desktop */}
                        <div className="lg:col-span-1 relative rounded-3xl overflow-hidden bg-gray-100 lg:h-full pause-hover">
                            {/* Mobile/Tablet: Horizontal scroll */}
                            <div className="lg:hidden flex gap-4 p-4 overflow-x-auto snap-x snap-mandatory scrollbar-hide">
                                {facilities?.items && facilities.items.length > 0 ? (
                                    facilities.items.map((item, idx) => (
                                        <div key={idx} className="relative rounded-2xl overflow-hidden shadow-md h-48 w-64 flex-shrink-0 snap-center group/item">
                                            <img 
                                                src={item.image} 
                                                alt={item.title} 
                                                className="w-full h-full object-cover"
                                            />
                                            <div className="absolute inset-0 bg-black/20 group-hover/item:bg-black/0 transition-colors"></div>
                                            <div className="absolute bottom-0 left-0 p-4 bg-gradient-to-t from-black/60 to-transparent w-full">
                                                <p className="text-white font-bold text-sm">{item.title}</p>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    [1, 2, 3].map((item, idx) => (
                                        <div key={idx} className="relative rounded-2xl overflow-hidden shadow-md h-48 w-64 flex-shrink-0 snap-center group/item">
                                            <div className="w-full h-full bg-gray-200"></div>
                                            <div className="absolute bottom-0 left-0 p-4 bg-gradient-to-t from-black/60 to-transparent w-full">
                                                <p className="text-white font-bold text-sm">
                                                    {idx % 2 === 0 ? "Lab Komputer IPS" : "Ruang Multimedia"}
                                                </p>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                            
                            {/* Desktop: Vertical Marquee */}
                            <div className="hidden lg:block absolute inset-0 overflow-hidden">
                                <div className="animate-scroll-vertical flex flex-col gap-4 p-4">
                                    {/* Duplicated items for seamless loop */}
                                    {facilities?.items && facilities.items.length > 0 ? (
                                        [...facilities.items, ...facilities.items].map((item, idx) => (
                                            <div key={idx} className="relative rounded-2xl overflow-hidden shadow-md h-48 flex-shrink-0 group/item">
                                                <img 
                                                    src={item.image} 
                                                    alt={item.title} 
                                                    className="w-full h-full object-cover"
                                                />
                                                <div className="absolute inset-0 bg-black/20 group-hover/item:bg-black/0 transition-colors"></div>
                                                <div className="absolute bottom-0 left-0 p-4 bg-gradient-to-t from-black/60 to-transparent w-full">
                                                    <p className="text-white font-bold text-sm">{item.title}</p>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        [1, 2, 3, 4, 1, 2, 3, 4].map((item, idx) => (
                                            <div key={idx} className="relative rounded-2xl overflow-hidden shadow-md h-48 flex-shrink-0 group/item">
                                                <div className="w-full h-full bg-gray-200"></div>
                                                <div className="absolute bottom-0 left-0 p-4 bg-gradient-to-t from-black/60 to-transparent w-full">
                                                    <p className="text-white font-bold text-sm">
                                                        {idx % 2 === 0 ? "Lab Komputer IPS" : "Ruang Multimedia"}
                                                    </p>
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                            {/* Gradient overlays - Desktop only */}
                            <div className="hidden lg:block absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-white/20 to-transparent z-10 pointer-events-none"></div>
                            <div className="hidden lg:block absolute bottom-0 left-0 w-full h-20 bg-gradient-to-t from-white/20 to-transparent z-10 pointer-events-none"></div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 4. PROSPEK KARIR */}
            <section className="py-24 bg-slate-50">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="max-w-5xl mx-auto">
                        <div>
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-6`}>
                                {career_paths?.title || "Membuka Jalan Menuju Karir Profesional"}
                            </h2>
                            <p className={`${TYPOGRAPHY.bodyText} mb-10`}>
                                {career_paths?.description || `Lulusan Program IPS ${siteName} memiliki kompetensi tinggi untuk bersaing di perguruan tinggi negeri dan berbagai sektor strategis.`}
                            </p>

                            <div className="space-y-6">
                                {career_paths?.items && career_paths.items.length > 0 ? (
                                    career_paths.items.map((item, idx) => {
                                        const IconComponent = iconMap[item.icon_name] || Briefcase;
                                        return (
                                            <div key={idx} className="flex items-start gap-4 p-4 rounded-xl bg-white border border-gray-100 shadow-sm transition-all duration-300">
                                                <div className="p-3 bg-blue-50 text-blue-600 rounded-lg w-12 h-12 flex items-center justify-center">
                                                    <IconComponent className="w-6 h-6" />
                                                </div>
                                                <div>
                                                    <h4 className="font-bold text-gray-900 text-lg">{item.title}</h4>
                                                    <p className="text-sm text-gray-600">{item.description}</p>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <>
                                        <div className="flex items-start gap-4 p-4 rounded-xl bg-white border border-gray-100 shadow-sm transition-all duration-300">
                                            <div className="p-3 bg-red-50 text-red-600 rounded-lg">
                                                <Scale className="w-6 h-6" />
                                            </div>
                                            <div>
                                                <h4 className="font-bold text-gray-900 text-lg">Hukum & Politik</h4>
                                                <p className="text-sm text-gray-600">Pengacara, Hakim, Diplomat, Analis Kebijakan Publik.</p>
                                            </div>
                                        </div>
                                        {/* ... other static items */}
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* 5. CTA SECTION - Updated Style */}
            <section className="py-20 bg-primary relative overflow-hidden rounded-3xl mx-4 mb-16">
                {/* Decorative Circles */}
                <div className="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div className="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/2 translate-y-1/2"></div>

                <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                    <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
                        Siap Menjadi Bagian dari <br/> Keluarga Besar {siteName}?
                    </h2>
                    <p className="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
                        Dapatkan informasi lengkap mengenai pendaftaran peserta didik baru, jadwal, dan persyaratan yang dibutuhkan.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link 
                            href="/informasi-spmb" 
                            className="px-8 py-4 bg-accent-yellow text-gray-900 font-bold rounded-xl hover:bg-yellow-400 transition-colors shadow-lg"
                        >
                            Daftar Sekarang
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

           </main> <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />
        </div>
    );
}
