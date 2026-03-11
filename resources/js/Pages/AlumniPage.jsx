import React, { useState } from 'react';
import { 
    GraduationCap, 
    Quote, 
    Users, 
    ChevronLeft,
    ChevronRight,
    X,
    Play
} from 'lucide-react';

import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import Modal from '@/Components/Modal';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';
import { ThumbnailImage, ContentImage } from '@/Components/ResponsiveImage';

// Import Swiper React components
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination } from 'swiper/modules';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

// Featured Card untuk Hero Carousel
const FeaturedCard = ({ alumni }) => {
    const [isPlaying, setIsPlaying] = useState(false);
    const [videoError, setVideoError] = useState(false);

    const getYouTubeId = (url) => {
        if (!url) return null;
        const patterns = [
            /youtube\.com\/watch\?v=([^&]+)/,
            /youtube\.com\/embed\/([^?]+)/,
            /youtu\.be\/([^?]+)/,
            /youtube\.com\/v\/([^?]+)/,
        ];

        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match) return match[1];
        }
        return null;
    };

    // Get the actual video URL
    const videoUrl = alumni.video_url;
    const thumbnailUrl = alumni.video_thumbnail_url;
    const isVideo = alumni.content_type === 'video';

    const handleVideoError = (e) => {
        console.error('Video load error:', e, 'URL:', videoUrl);
        setVideoError(true);
    };

    if (isPlaying && isVideo) {
        const youtubeId = alumni.video_source === 'youtube' ? getYouTubeId(alumni.video_url) : null;
        return (
            <div className="relative h-[500px] bg-black rounded-2xl overflow-hidden shadow-xl">
                {alumni.video_source === 'youtube' && youtubeId ? (
                    <iframe
                        src={`https://www.youtube.com/embed/${youtubeId}?autoplay=1&rel=0`}
                        className="w-full h-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowFullScreen
                        title={`Video testimonial ${alumni.name}`}
                    />
                ) : videoError ? (
                    <div className="w-full h-full flex items-center justify-center text-white">
                        <div className="text-center">
                            <p className="text-lg mb-2">Video tidak dapat diputar</p>
                            <p className="text-sm text-gray-400">Format video mungkin tidak didukung</p>
                        </div>
                    </div>
                ) : (
                    <video
                        src={videoUrl}
                        className="w-full h-full"
                        controls
                        autoPlay
                        playsInline
                        onError={handleVideoError}
                    >
                        Your browser does not support the video tag.
                    </video>
                )}
            </div>
        );
    }

    return (
        <div
            className={`relative h-[500px] bg-gray-900 rounded-2xl overflow-hidden group ${isVideo ? 'cursor-pointer' : ''}`}
            onClick={isVideo ? () => setIsPlaying(true) : undefined}
        >
            <div className="relative h-full">
                {isVideo && alumni.video_source === 'upload' && videoUrl && !videoError ? (
                    <video
                        src={`${videoUrl}#t=0.1`}
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 pointer-events-none"
                        muted
                        playsInline
                        preload="metadata"
                        onLoadedMetadata={(e) => { e.target.currentTime = 0.1; }}
                        onError={() => setVideoError(true)}
                    />
                ) : (
                    <img
                        src={thumbnailUrl}
                        alt={alumni.name}
                        className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy"
                        onError={(e) => { e.target.style.display = 'none'; }}
                    />
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent"></div>
                
                {/* Play Button - Only for video */}
                {isVideo && (
                    <div className="absolute inset-0 flex items-center justify-center">
                        <div className="w-20 h-20 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center transform transition-all duration-300 group-hover:scale-110 group-hover:bg-primary shadow-2xl">
                            <Play className="w-10 h-10 text-primary group-hover:text-white ml-1" fill="currentColor" />
                        </div>
                    </div>
                )}

                {/* Content */}
                <div className="absolute bottom-0 left-0 right-0 p-8 text-white pointer-events-none">
                    <div>
                        <h3 className="text-2xl font-bold mb-1 drop-shadow-md">{alumni.name}</h3>
                        <p className="text-gray-200 text-sm mt-1 drop-shadow-md">Angkatan {alumni.graduation_year}</p>
                    </div>
                </div>
            </div>
        </div>
    );
};

// Testimonial Card untuk Grid
const TestimonialCard = ({ alumni, onClick }) => {
    const primaryImage = alumni?.testimonialImages?.[0]?.original_url || alumni?.image_url || null;
    const formatImagePath = (path) => {
        if (!path) return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    return (
        <button
            type="button"
            onClick={onClick}
            className="w-full text-left bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col h-full group p-6 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
            aria-label={`Lihat detail testimoni ${alumni.name}`}
        >
            <div className="flex-grow flex items-center justify-center text-center mb-6 relative">
                 <Quote className="absolute top-0 left-0 w-6 h-6 text-primary/10 -translate-x-2 -translate-y-2 rotate-180" />
                 <p className="text-gray-600 italic leading-relaxed text-sm relative z-10">
                    "{alumni.testimonial}"
                </p>
            </div>

            <div className="flex flex-col items-center text-center pt-4 border-t border-gray-50">
                <div className="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-100 mb-3 shadow-sm bg-gray-50">
                     {primaryImage ? (
                        <ThumbnailImage
                            src={formatImagePath(primaryImage)}
                            alt={alumni.name}
                            className="w-full h-full object-cover"
                        />
                    ) : (
                        <div className="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                            <Users size={24} />
                        </div>
                    )}
                </div>

                <h3 className="font-bold text-gray-900 text-base mb-1">{alumni.name}</h3>
                <div className="flex items-center justify-center gap-1.5 text-xs text-primary font-medium bg-primary/5 px-3 py-1 rounded-full">
                    <GraduationCap className="w-3.5 h-3.5" />
                    <span>Angkatan {alumni.graduation_year}</span>
                </div>
            </div>
        </button>
    );
};

export default function AlumniPage({ auth, alumnis = [] }) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const [currentPage, setCurrentPage] = useState(1);
    const [isTestimonialModalOpen, setIsTestimonialModalOpen] = useState(false);
    const [selectedAlumni, setSelectedAlumni] = useState(null);
    const itemsPerPage = 16; // 4x4 grid

    const heroSettings = siteSettings?.hero_alumni || {};
    
    const formatImagePath = (path) => {
        if (!path) return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    const renderHighlightedTitle = (title) => {
        if (!title) return null;
        const words = title.split(' ');
        if (words.length <= 1) return title;
        const lastWord = words.pop();
        return (
            <>
                {words.join(' ')} <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{lastWord}</span>
            </>
        );
    };

    // Filter dan sort alumni
    const publishedAlumni = alumnis.filter(a => a.is_published).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    
    // Featured Alumni for Top Section
    const featuredAlumni = publishedAlumni.filter(a => a.is_featured);
    
    // Regular Alumni for Browsing Section (exclude featured to avoid duplication, or keep all?)
    // Usually "Browsing" implies everything or everything else. Let's exclude featured to make it distinct.
    const regularAlumni = publishedAlumni.filter(a => !a.is_featured);

    // Pagination Logic
    const totalPages = Math.ceil(regularAlumni.length / itemsPerPage);
    const paginatedAlumni = regularAlumni.slice(
        (currentPage - 1) * itemsPerPage,
        currentPage * itemsPerPage
    );

    const selectedAlumniImages = selectedAlumni?.testimonialImages?.length
        ? selectedAlumni.testimonialImages.map((media) => media?.original_url).filter(Boolean)
        : [];

    const openTestimonialModal = (alumniId) => {
        const alumni = regularAlumni.find((item) => item.id === alumniId);
        if (!alumni) return;

        setSelectedAlumni(alumni);
        setIsTestimonialModalOpen(true);
    };

    const closeTestimonialModal = () => {
        setIsTestimonialModalOpen(false);
        setSelectedAlumni(null);
    };


    return (
        <div className="bg-secondary font-sans text-gray-800">
            <SEOHead 
                title={`Jejak Alumni - ${siteName}`}
                description={`Profil alumni sukses ${siteName}. Testimoni, kisah sukses, dan jaringan alumni di berbagai bidang profesi.`}
                keywords={`alumni, lulusan, alumni sukses, testimoni alumni, ${siteName}`}
                image="/images/alumni-banner.jpg"
            />
            
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
                {/* HERO SECTION */}
                <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                    <div className="absolute inset-0 z-0">
                        <img 
                            src={formatImagePath(heroImage)} 
                            alt="Background" 
                            className="w-full h-full object-cover"
                            loading="eager"
                            fetchpriority="high"
                        />
                        <div className="absolute inset-0 bg-black/60"></div>
                    </div>

                    <div className="relative z-10 container mx-auto px-4 text-center text-white">
                        <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                            {renderHighlightedTitle(heroSettings.title || 'Apa Kata Alumni?')}
                        </h1>
                        <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                            {heroSettings.subtitle || `Dengar langsung pengalaman mereka selama bersekolah di ${siteName}.`}
                        </p>
                    </div>
                </section>

                {/* FEATURED ALUMNI SECTION */}
                {featuredAlumni.length > 0 && (
                    <section className="py-16 bg-white">
                        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="relative max-w-6xl mx-auto">
                                <div className="text-center mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 font-serif mb-2">Alumni Berprestasi</h2>
                                    <p className="text-gray-500">Kisah inspiratif dari alumni terbaik kami</p>
                                </div>

                                <Swiper
                                    modules={[Navigation, Pagination]}
                                    spaceBetween={30}
                                    slidesPerView={1}
                                    navigation={{
                                        prevEl: '.swiper-button-prev-hero',
                                        nextEl: '.swiper-button-next-hero',
                                    }}
                                    pagination={{
                                        clickable: true,
                                        dynamicBullets: false,
                                    }}
                                    loop={featuredAlumni.length > 1}
                                    className="pb-12"
                                >
                                    {featuredAlumni.map((alumni) => (
                                        <SwiperSlide key={alumni.id}>
                                            <FeaturedCard alumni={alumni} />
                                        </SwiperSlide>
                                    ))}
                                </Swiper>

                                {/* Navigation Buttons */}
                                {featuredAlumni.length > 1 && (
                                    <>
                                        <button
                                            className="swiper-button-prev-hero absolute left-0 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white shadow-lg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all duration-300 -ml-6"
                                            aria-label="Previous"
                                        >
                                            <ChevronLeft className="w-6 h-6" />
                                        </button>
                                        <button
                                            className="swiper-button-next-hero absolute right-0 top-1/2 -translate-y-1/2 z-10 w-12 h-12 rounded-full bg-white shadow-lg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all duration-300 -mr-6"
                                            aria-label="Next"
                                        >
                                            <ChevronRight className="w-6 h-6" />
                                        </button>
                                    </>
                                )}
                            </div>
                        </div>
                    </section>
                )}

                {/* BROWSING ALUMNI SLIDER SECTION */}
                <section className="py-16 bg-gray-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-12">
                            <h2 className={TYPOGRAPHY.sectionHeading}>Alumni Kami</h2>
                            <p className={`${TYPOGRAPHY.bodyText} mt-4 max-w-2xl mx-auto`}>
                                Jejak langkah para alumni yang telah menjadi bagian dari keluarga besar kami.
                            </p>
                        </div>

                        {regularAlumni.length > 0 ? (
                            <>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                                    {paginatedAlumni.map((alumni) => (
                                        <div key={alumni.id} className="h-full">
                                            <TestimonialCard
                                                alumni={alumni}
                                                onClick={() => openTestimonialModal(alumni.id)}
                                            />
                                        </div>
                                    ))}
                                </div>

                                {/* Pagination Controls */}
                                {totalPages > 1 && (
                                    <div className="flex justify-center items-center gap-2">
                                        <button
                                            onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                                            disabled={currentPage === 1}
                                            className="p-2 rounded-full border border-gray-200 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        >
                                            <ChevronLeft className="w-5 h-5 text-gray-600" />
                                        </button>
                                        
                                        {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                                            <button
                                                key={page}
                                                onClick={() => setCurrentPage(page)}
                                                className={`w-8 h-8 rounded-full font-bold text-xs transition-all duration-300 ${
                                                    currentPage === page
                                                    ? "bg-primary text-white shadow-md transform scale-110"
                                                    : "bg-white text-gray-600 hover:bg-gray-100 border border-gray-200"
                                                }`}
                                            >
                                                {page}
                                            </button>
                                        ))}

                                        <button
                                            onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                                            disabled={currentPage === totalPages}
                                            className="p-2 rounded-full border border-gray-200 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        >
                                            <ChevronRight className="w-5 h-5 text-gray-600" />
                                        </button>
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="text-center py-20">
                                <div className="inline-block p-4 rounded-full bg-white mb-4 shadow-sm">
                                    <Users size={48} className="text-gray-400" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Belum ada testimonial lain</h3>
                                <p className="text-gray-500">Testimonial alumni akan ditampilkan di sini.</p>
                            </div>
                        )}
                    </div>
                </section>

                <Modal show={isTestimonialModalOpen} onClose={closeTestimonialModal} maxWidth="4xl">
                    <div className="relative bg-white">
                        <button
                            type="button"
                            onClick={closeTestimonialModal}
                            className="absolute right-4 top-4 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-gray-600 shadow hover:bg-white hover:text-gray-900"
                            aria-label="Tutup modal testimoni"
                        >
                            <X className="h-5 w-5" />
                        </button>

                        {selectedAlumni && (
                            <div className="p-4 sm:p-6">
                                <Swiper
                                    modules={[Navigation, Pagination]}
                                    spaceBetween={16}
                                    slidesPerView={1}
                                    loop={selectedAlumniImages.length > 1}
                                    navigation={{
                                        prevEl: '.swiper-button-prev-testimonial-modal',
                                        nextEl: '.swiper-button-next-testimonial-modal',
                                    }}
                                    pagination={{ clickable: true }}
                                    className="pb-10"
                                >
                                    {selectedAlumniImages.length > 0 ? selectedAlumniImages.map((imageUrl, index) => (
                                        <SwiperSlide key={`${selectedAlumni.id}-${index}`}>
                                            <div className="mx-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-gray-100">
                                                {imageUrl ? (
                                                    <ContentImage
                                                        src={formatImagePath(imageUrl)}
                                                        alt={`${selectedAlumni.name} - foto ${index + 1}`}
                                                        className="h-[340px] w-full object-cover sm:h-[420px]"
                                                    />
                                                ) : (
                                                    <div className="flex h-[340px] w-full items-center justify-center bg-gray-200 text-gray-400 sm:h-[420px]">
                                                        <Users size={60} />
                                                    </div>
                                                )}
                                            </div>
                                        </SwiperSlide>
                                    )) : (
                                        <SwiperSlide key={`fallback-${selectedAlumni.id}`}>
                                            <div className="mx-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-gray-100">
                                                <div className="flex h-[340px] w-full items-center justify-center bg-gray-200 text-gray-400 sm:h-[420px]">
                                                    <Users size={60} />
                                                </div>
                                            </div>
                                        </SwiperSlide>
                                    )}
                                </Swiper>

                                {selectedAlumniImages.length > 1 && (
                                    <>
                                        <button
                                            className="swiper-button-prev-testimonial-modal absolute left-3 top-[42%] z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md text-primary hover:bg-primary hover:text-white transition-all duration-300"
                                            aria-label="Sebelumnya"
                                        >
                                            <ChevronLeft className="h-5 w-5" />
                                        </button>
                                        <button
                                            className="swiper-button-next-testimonial-modal absolute right-3 top-[42%] z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white shadow-md text-primary hover:bg-primary hover:text-white transition-all duration-300"
                                            aria-label="Berikutnya"
                                        >
                                            <ChevronRight className="h-5 w-5" />
                                        </button>
                                    </>
                                )}

                                {selectedAlumni && (
                                    <div className="mx-auto mt-4 max-w-3xl text-center">
                                        <h3 className="text-2xl font-bold text-gray-900">{selectedAlumni.name}</h3>
                                        <p className="mt-2 text-sm font-medium text-primary">Angkatan {selectedAlumni.graduation_year}</p>
                                        <p className="mt-4 text-base leading-relaxed text-gray-600">
                                            "{selectedAlumni.testimonial}"
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </Modal>
            </main>

            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />

        </div>
    );
}
