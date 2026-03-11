import React from 'react';
import { Head, Link } from '@inertiajs/react';
import CountUp from 'react-countup';
import { 
    Users, 
    Trophy, 
    GraduationCap, 
    ArrowRight, 
    Microscope, 
    Globe, 
    BookOpen, 
    Calendar,
    MapPin,
    Phone,
    Mail,
    Leaf
} from 'lucide-react';

// Import Components
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import ResponsiveImage, { HeroImage, ContentImage, GalleryImage } from '@/Components/ResponsiveImage';
import SanitizedContent from '@/Components/SanitizedContent';
import { getNavigationData } from '@/Utils/navigationData';
import { TYPOGRAPHY } from '@/Utils/typography';
import { usePage } from '@inertiajs/react';

export default function LandingPage({ 
    heroContent, 
    aboutLpContent, 
    kepsekWelcomeLpContent, 
    programsLpContent,
    galleryLpContent,
    ctaLpContent,
    latestPosts = []
}) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    
    // Map icon names to Lucide components
    const iconMap = {
        Microscope,
        Globe,
        BookOpen,
        Users,
        Trophy,
        GraduationCap,
        Calendar,
        Leaf
    };

    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const siteDescription = siteSettings?.general?.site_description || 'Website Resmi SMAN 1 Baleendah';

    return (
        <div className="bg-secondary min-h-screen font-sans text-gray-800">
            <SEOHead 
                title={`${siteName} - Generasi Unggul & Berkarakter`}
                description={siteDescription || "SMAN 1 Baleendah adalah sekolah menengah atas unggulan di Bandung dengan program peminatan MIPA, IPS, dan Bahasa. Dapatkan informasi PPDB, program akademik, ekstrakurikuler, dan prestasi siswa."}
                keywords="SMAN 1 Baleendah, SMA Baleendah, sekolah Bandung, PPDB Bandung, SMA negeri Bandung, pendidikan Baleendah, peminatan IPA IPS Bahasa, sekolah unggulan"
                image={heroContent?.background_image_url || null}
            />

            {/* Navbar */}
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            {/* HERO SECTION */}
            <section className="relative pt-32 md:pt-40 overflow-hidden">
                {/* Hero Background Image */}
                <div className="absolute inset-0 z-0">
                    {(heroContent?.background_image_url || heroContent?.backgroundImage) && (
                        <ResponsiveImage 
                            src={heroContent?.background_image_url} 
                            media={heroContent?.backgroundImage}
                            alt="Background"
                            className="w-full h-full object-cover"
                        />
                    )}
                </div>
                {/* Overlay for readability */}
                <div className="absolute inset-0 bg-black/60 z-0"></div>

                {/* Blue Rectangle behind Student */}
                <div className="absolute top-0 left-1/2 h-full w-full md:w-[720px] bg-primary/80 z-0 hidden md:block"></div>

                <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        {/* Left Column: Text */}
                        <div className="space-y-6">
                            <h1 className={TYPOGRAPHY.heroTitle}>
                                {heroContent?.title_line1 || 'Selamat Datang di'} <br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{heroContent?.title_line2 || 'SMA Negeri 1 Baleendah'}</span>
                            </h1>
                            <p className={`${TYPOGRAPHY.heroText} max-w-lg`}>
                                {heroContent?.hero_text || 'Sekolah penggerak prestasi dan inovasi masa depan.'}
                            </p>
                            <div className="flex flex-wrap gap-4 pt-4">
                                <Link 
                                    href="/profil-sekolah" 
                                    className="px-8 py-3.5 bg-accent-yellow text-gray-900 font-bold rounded-full shadow-lg hover:shadow-xl hover:bg-yellow-400 transition-all"
                                >
                                    Jelajahi Profil
                                </Link>
                                <Link 
                                    href="/informasi-spmb" 
                                    className="px-8 py-3.5 bg-white border-2 border-primary text-primary font-bold rounded-full hover:bg-blue-50 transition-all"
                                >
                                    Info PPDB
                                </Link>
                            </div>
                        </div>

                        {/* Right Column: Visual */}
                        <div className="relative mx-auto max-w-lg md:max-w-none w-full h-[420px] md:h-[480px] lg:h-[540px] flex justify-center items-end">
                            {/* Main Image */}
                            {(heroContent?.student_image_url || heroContent?.studentImage) && (
                                <ResponsiveImage 
                                    src={heroContent?.student_image_url} 
                                    media={heroContent?.studentImage}
                                    alt={`Siswa Berprestasi ${siteName}`}
                                    className="relative z-10 h-[380px] md:h-[440px] lg:h-[500px] w-auto object-contain drop-shadow-[0_20px_50px_rgba(0,0,0,0.5)]"
                                    loading="eager"
                                />
                            )}

                            {/* Floating Glass Cards - Stats */}
                            {(heroContent?.stats || [
                                { label: 'Akreditasi', value: 'A', icon_name: 'Trophy' },
                                { label: 'Lulusan', value: '15k+', icon_name: 'GraduationCap' },
                                { label: 'Siswa Aktif', value: '1.2k+', icon_name: 'Users' }
                            ]).map((stat, idx) => {
                                const Icon = iconMap[stat.icon_name] || Trophy;
                                const positions = [
                                    'top-12 -left-2 md:-left-8',        // Top Left (Akreditasi) - Adjusted
                                    'top-1/2 -right-4 md:-right-12',    // Middle Right (Lulusan) - Moved up & adjusted
                                    'bottom-8 left-2 md:-left-8'        // Bottom Left (Siswa) - Adjusted
                                ];
                                const colors = [
                                    'bg-yellow-100 text-yellow-600',
                                    'bg-blue-100 text-primary',
                                    'bg-green-100 text-green-600'
                                ];
                                return (
                                    <div 
                                        key={idx}
                                        className={`absolute ${positions[idx % 3]} bg-white/70 backdrop-blur-md border border-white/40 p-4 rounded-xl shadow-lg flex items-center gap-3 max-w-[220px] z-20`}
                                    >
                                        <div className={`p-2 rounded-full ${colors[idx % 3]}`}>
                                            <Icon size={24} fill="currentColor" />
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500 font-medium">{stat.label}</p>
                                            <p className="text-sm font-bold text-gray-900">{stat.value}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </section>

            {/* ABOUT SECTION */}
            <section className="py-20 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        <div className="relative">
                            <div className="aspect-video rounded-2xl overflow-hidden shadow-xl bg-gray-200">
                                <ContentImage 
                                    src={aboutLpContent.image_url} 
                                    media={aboutLpContent?.aboutImage} 
                                    alt={aboutLpContent.title} 
                                />
                            </div>
                            {/* Decorative dots */}
                            <div className="absolute -bottom-6 -right-6 w-24 h-24 bg-dots-pattern opacity-20 hidden md:block"></div>
                        </div>
                        <div>
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-6`}>
                                {aboutLpContent.title.split(' ').slice(0, -1).join(' ')} <span className="text-primary">{aboutLpContent.title.split(' ').slice(-1)}</span>
                            </h2>
                            <SanitizedContent 
                                className={`${TYPOGRAPHY.bodyText} mb-8 prose prose-blue max-w-none`}
                                html={aboutLpContent.description_html}
                            />

                            <Link 
                                href="/profil/sejarah" 
                                className="inline-flex items-center mt-8 text-primary font-bold hover:text-primary-darker transition-colors"
                            >
                                Selengkapnya tentang kami <ArrowRight size={20} className="ml-2" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* KEPSEK WELCOME SECTION */}
            <section className="py-20 bg-secondary">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Section Title */}
                    <div className="text-center mb-12">
                        <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                            Sambutan <span className="text-primary">Kepala Sekolah</span>
                        </h2>
                    </div>

                    {/* Centered Photo & Profile Info */}
                    <div className="flex flex-col items-center mb-12">
                        {/* Photo Container */}
                        <div className="relative w-72 h-80 md:w-80 md:h-96 flex-shrink-0 mb-6">
                            <div className="absolute inset-0 bg-primary rounded-2xl rotate-6 opacity-20"></div>
                            <ContentImage
                                src={kepsekWelcomeLpContent.kepsek_image_url}
                                media={kepsekWelcomeLpContent?.kepsekImage}
                                alt={kepsekWelcomeLpContent.kepsek_name}
                                className="relative w-full h-full object-cover rounded-2xl shadow-lg"
                            />
                        </div>
                        
                        {/* Profile Info */}
                        <div className="text-center">
                            <h3 className="text-xl font-bold text-gray-900">
                                {kepsekWelcomeLpContent.kepsek_name}
                            </h3>
                            <p className="text-primary font-medium mt-1">
                                {kepsekWelcomeLpContent.kepsek_title}
                            </p>
                            <div className="w-12 h-1 bg-accent-yellow mt-4 mx-auto rounded-full"></div>
                        </div>
                    </div>

                    {/* Welcome Text - Full Width */}
                    <div className="bg-white p-8 md:p-12 lg:p-14 rounded-3xl shadow-sm border border-gray-100 relative">
                        {/* Quote Icon Decoration */}
                        <div className="absolute top-6 right-8 text-gray-100">
                            <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H15.017C14.4647 8 14.017 7.55228 14.017 7V3H19.017C20.6739 3 22.017 4.34315 22.017 6V15C22.017 16.6569 20.6739 18 19.017 18H16.017V21H14.017ZM5.0166 21L5.0166 18C5.0166 16.8954 5.91203 16 7.0166 16H10.0166C10.5689 16 11.0166 15.5523 11.0166 15V9C11.0166 8.44772 10.5689 8 10.0166 8H6.0166C5.46432 8 5.0166 7.55228 5.0166 7V3H10.0166C11.6735 3 13.0166 4.34315 13.0166 6V15C13.0166 16.6569 11.6735 18 10.0166 18H7.0166V21H5.0166Z" />
                            </svg>
                        </div>
                        
                        <SanitizedContent
                            className="prose prose-lg prose-blue text-gray-600 leading-relaxed max-w-none [&>p]:mb-3 [&>p:last-child]:mb-0"
                            html={kepsekWelcomeLpContent.welcome_text_html}
                        />
                    </div>
                </div>
            </section>

            {/* ACADEMIC PROGRAMS SECTION */}
            <section className="py-20 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                            {programsLpContent.title.split(' ').slice(0, -1).join(' ')} <span className="text-primary">{programsLpContent.title.split(' ').slice(-1)}</span>
                        </h2>
                        <p className={TYPOGRAPHY.bodyText}>
                            {programsLpContent.description}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 pt-64">
                        {(programsLpContent.items || []).map((program, idx) => {
                            const IconComponent = iconMap[program.icon_name] || Microscope;
                            return (
                                <div key={idx} className="relative group">
                                    {/* Image Area - Floating above card */}
                                    <div className="absolute bottom-[calc(100%-3rem)] left-0 right-0 z-0 pointer-events-none flex justify-center">
                                        <ResponsiveImage 
                                            src={program.image_url || "/images/anak-sma-programstudi.png"} 
                                            media={program.image}
                                            alt={program.title}
                                            className="h-80 w-auto max-w-full object-contain drop-shadow-xl transition-transform duration-500 group-hover:scale-105" 
                                        />
                                    </div>
                                    
                                    {/* Content Section */}
                                    <div className="bg-white rounded-3xl p-8 pt-12 shadow-lg border border-gray-100 flex flex-col z-10 w-full h-full relative">
                                        <div className={`w-12 h-12 rounded-xl flex items-center justify-center mb-4 ${program.color_class || 'bg-blue-50 text-primary'}`}>
                                            <IconComponent size={24} />
                                        </div>
                                        <h3 className={`${TYPOGRAPHY.cardTitle} mb-2`}>{program.title}</h3>
                                        <p className={`${TYPOGRAPHY.smallText} mb-8 leading-relaxed flex-1`}>
                                            {program.description}
                                        </p>
                                        <Link 
                                            href={program.link || '#'}
                                            className="w-full py-3 rounded-xl border-2 border-primary text-primary font-bold hover:bg-primary hover:text-white transition-all text-center block"
                                        >
                                            Lihat Selengkapnya
                                        </Link>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* NEWS & ANNOUNCEMENTS */}
            <section className="py-20 bg-slate-50">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col md:flex-row justify-between items-end mb-12">
                        <div className="max-w-2xl">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                                Berita <span className="text-primary">Smansa</span>
                            </h2>
                            <p className={TYPOGRAPHY.bodyText}>
                                Informasi terbaru seputar kegiatan sekolah, prestasi siswa, dan pengumuman akademik.
                            </p>
                        </div>
                        <Link href="/berita-pengumuman" className="hidden md:flex items-center text-primary font-bold hover:underline mt-4 md:mt-0">
                            Lihat Semua Berita <ArrowRight size={20} className="ml-2" />
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {latestPosts.map((post) => (
                            <div key={post.id} className="bg-white rounded-xl overflow-hidden shadow-lg border border-gray-100 hover:shadow-xl transition-all">
                                <div className="h-48 bg-gray-200 relative overflow-hidden">
                                    <ContentImage 
                                        src={post.featured_image} 
                                        className="w-full h-full object-cover hover:scale-105 transition-transform duration-500" 
                                        alt={post.title} 
                                    />
                                    <div className="absolute top-4 left-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full">
                                        {post.category}
                                    </div>
                                </div>
                                <div className="p-6">
                                    <div className="flex items-center gap-2 text-gray-500 text-sm mb-3">
                                        <Calendar size={16} />
                                        <span>{new Date(post.published_at || post.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</span>
                                    </div>
                                    <h3 className={`${TYPOGRAPHY.cardTitle} mb-3 line-clamp-2 hover:text-primary cursor-pointer`}>
                                        {post.title}
                                    </h3>
                                    <Link href={`/berita/${post.slug}`} className="text-primary font-semibold text-sm hover:underline">
                                        Baca Selengkapnya
                                    </Link>
                                </div>
                            </div>
                        ))}
                        {latestPosts.length === 0 && (
                            <div className="col-span-3 text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                                <p className="text-gray-500">Belum ada berita terbaru.</p>
                            </div>
                        )}
                    </div>

                    <div className="mt-8 text-center md:hidden">
                        <Link href="/berita-pengumuman" className="inline-flex items-center text-primary font-bold hover:underline">
                            Lihat Semua Berita <ArrowRight size={20} className="ml-2" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* GALLERY SECTION - Full Width Infinite Carousel */}
            <section className="py-20 bg-white overflow-hidden">
                {/* Animation Styles */}
                <style>{`
                    @keyframes scroll {
                        0% { transform: translateX(0); }
                        100% { transform: translateX(-50%); }
                    }
                    @keyframes scroll-reverse {
                        0% { transform: translateX(-50%); }
                        100% { transform: translateX(0); }
                    }
                    .animate-scroll {
                        animation: scroll 30s linear infinite;
                    }
                    .animate-scroll-reverse {
                        animation: scroll-reverse 30s linear infinite;
                    }
                    .animate-scroll:hover,
                    .animate-scroll-reverse:hover {
                        animation-play-state: paused;
                    }
                `}</style>
                
                <div className="mb-12">
                    <div className="text-center max-w-3xl mx-auto mb-12 px-4 sm:px-6 lg:px-8">
                        <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                            {galleryLpContent.title.split(' ').slice(0, -1).join(' ')} <span className="text-primary">{galleryLpContent.title.split(' ').slice(-1)}</span>
                        </h2>
                        <p className={TYPOGRAPHY.bodyText}>
                            {galleryLpContent.description}
                        </p>
                    </div>
                </div>
                
                {/* Gallery Section - Full Width Horizontal Scroll */}
                {(() => {
                    // Filter only valid image URLs (not videos)
                    const validImages = (galleryLpContent.images || []).filter(url => {
                        if (!url) return false;
                        const lowerUrl = url.toLowerCase();
                        // Exclude video URLs
                        if (lowerUrl.includes('youtube.com') || lowerUrl.includes('youtu.be')) return false;
                        if (lowerUrl.endsWith('.mp4') || lowerUrl.endsWith('.webm') || lowerUrl.endsWith('.mov')) return false;
                        return true;
                    });
                    
                    // Need at least some images for the carousel
                    if (validImages.length === 0) {
                        return (
                            <div className="text-center py-12 text-gray-500">
                                Belum ada galeri foto.
                            </div>
                        );
                    }
                    
                    // Duplicate images enough times for seamless infinite scroll
                    const row1Images = [...validImages, ...validImages, ...validImages, ...validImages];
                    const row2Images = [...validImages, ...validImages, ...validImages, ...validImages].reverse();
                    
                    return (
                        <div className="flex flex-col gap-4 w-full">
                            {/* Row 1 - Scroll Left */}
                            <div className="flex gap-4 animate-scroll" style={{ width: 'max-content', animationDuration: '80s' }}>
                                {row1Images.map((imgUrl, idx) => (
                                    <div key={`row1-${idx}`} className="w-72 h-72 md:w-80 md:h-80 flex-shrink-0 rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all relative group">
                                        <GalleryImage 
                                            src={imgUrl} 
                                            alt={`Gallery ${idx}`} 
                                            className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                        />
                                        <div className="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition-colors"></div>
                                    </div>
                                ))}
                            </div>

                            {/* Row 2 - Scroll Right (Reverse) */}
                            <div className="flex gap-4 animate-scroll-reverse" style={{ width: 'max-content', animationDuration: '80s' }}>
                                {row2Images.map((imgUrl, idx) => (
                                    <div key={`row2-${idx}`} className="w-72 h-72 md:w-80 md:h-80 flex-shrink-0 rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all relative group">
                                        <GalleryImage 
                                            src={imgUrl} 
                                            alt={`Gallery ${idx}`} 
                                            className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                        />
                                        <div className="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition-colors"></div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                })()}
                
                {/* View All Button */}
                <div className="mt-12 text-center">
                    <Link 
                        href="/galeri" 
                        className="inline-flex items-center px-8 py-3.5 bg-primary text-white font-bold rounded-full hover:bg-primary-darker transition-all shadow-lg hover:shadow-xl"
                    >
                        Lihat Galeri Lengkap <ArrowRight size={20} className="ml-2" />
                    </Link>
                </div>
            </section>

            {/* CTA SECTION - Updated Style */}
            <section className="py-20 bg-primary relative overflow-hidden rounded-3xl mx-4 mb-16">
                {/* Decorative Circles */}
                <div className="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                <div className="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/2 translate-y-1/2"></div>

                <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                    <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
                        {ctaLpContent.title}
                    </h2>
                    <p className="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
                        {ctaLpContent.description}
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

            {/* Footer */}
            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />
        </div>
    );
}
