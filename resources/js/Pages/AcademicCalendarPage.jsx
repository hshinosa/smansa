import React, { useState, useEffect } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import Modal from '@/Components/Modal';
import ResponsiveImage, { HeroImage } from '@/Components/ResponsiveImage';
import SEOHead from '@/Components/SEOHead';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { normalizeUrl } from '@/Utils/imageUtils';
import { Calendar, X, Download, FileText, ExternalLink, ChevronRight } from 'lucide-react';

export default function AcademicCalendarPage({ 
    calendarContents = [],
    currentAcademicYear 
}) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = normalizeUrl(siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg');
    const navigationData = getNavigationData(siteSettings);
    
    const [selectedCalendar, setSelectedCalendar] = useState(null);
    const [showImageModal, setShowImageModal] = useState(false);

    // Initial selection logic
    useEffect(() => {
        if (Array.isArray(calendarContents) && calendarContents.length > 0 && !selectedCalendar) {
            // Find current semester or just the first one
            const currentYear = new Date().getFullYear();
            const month = new Date().getMonth() + 1;
            const currentSemester = (month >= 7 || month <= 1) ? 1 : 2; // Ganjil starts in July, Genap starts in Jan/Feb
            
            const match = calendarContents.find(cal => 
                cal.academic_year_start === currentYear && cal.semester === currentSemester
            ) || calendarContents[0];
            
            setSelectedCalendar(match);
        }
    }, [calendarContents, selectedCalendar]);

    const openImageModal = (calendar) => {
        setSelectedCalendar(calendar);
        setShowImageModal(true);
    };
    
    const closeImageModal = () => {
        setShowImageModal(false);
    };

    const getImageSrc = (cal) => {
        if (!cal) return '';
        // If media object exists (Spatie), use original_url
        if (cal.image && cal.image.original_url) return normalizeUrl(cal.image.original_url);
        // Legacy/Direct URL
        if (cal.image_url) return normalizeUrl(cal.image_url);
        // New mapped URL from controller
        if (cal.calendar_image_url) return normalizeUrl(cal.calendar_image_url);
        // Fallback
        return null;
    };

    return (
        <div className="bg-white min-h-screen font-sans text-gray-800 flex flex-col">
            <SEOHead 
                title={`Kalender Akademik - ${siteName}`}
                description={`Kalender akademik tahun ajaran ${siteName}. Jadwal kegiatan, ujian, libur, dan event penting sepanjang tahun akademik.`}
                keywords={`kalender akademik, jadwal sekolah, tahun ajaran, jadwal ujian, libur sekolah, ${siteName}`}
                image="/images/calendar-banner.jpg"
            />
            
            <Navbar 
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
            {/* 1. HERO SECTION (Consistent with other pages) */}
            <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                {/* Background Image */}
                <div className="absolute inset-0 z-0">
                    <img 
                        src={heroImage} 
                        alt="Background Kalender Akademik" 
                        className="w-full h-full object-cover"
                        loading="eager"
                        fetchpriority="high"
                        width="1920"
                        height="1080"
                    />
                    <div className="absolute inset-0 bg-black/60"></div>
                </div>

                <div className="relative z-10 container mx-auto px-4 text-center text-white">
                    <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                        Kalender <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">Akademik</span>
                    </h1>
                    <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                        Panduan jadwal kegiatan belajar mengajar dan agenda resmi sekolah tahun ajaran {currentAcademicYear}.
                    </p>
                </div>
            </section>

            {/* 2. MAIN CONTENT SECTION */}
            <section className="py-16 bg-gray-50 flex-grow">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Semester Navigation & Info */}
                    <div className="max-w-5xl mx-auto mb-12">
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-8">
                            <div className="text-center md:text-left">
                                <h2 className="text-2xl font-bold text-gray-900 mb-2">Pilih Tahun & Semester</h2>
                                <p className="text-gray-500 text-sm">Lihat detail agenda akademik berdasarkan periode yang tersedia.</p>
                            </div>
                            
                            <div className="flex flex-wrap justify-center gap-3">
                                {Array.isArray(calendarContents) && calendarContents.length > 0 ? (
                                    calendarContents.map((cal) => (
                                        <button
                                            key={cal.id}
                                            onClick={() => setSelectedCalendar(cal)}
                                            className={`px-5 py-2.5 rounded-full text-sm font-bold transition-all duration-300 border ${
                                                selectedCalendar?.id === cal.id 
                                                ? "bg-primary text-white border-primary shadow-md scale-105" 
                                                : "bg-white text-gray-600 border-gray-200 hover:bg-gray-50"
                                            }`}
                                        >
                                            {cal.academic_year} - {cal.semester_name}
                                        </button>
                                    ))
                                ) : (
                                    <p className="text-gray-400 italic">Tidak ada data kalender.</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Display Area */}
                    <div className="max-w-6xl mx-auto">
                        {selectedCalendar ? (
                            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                                {/* Left: Info Details (4 Cols) */}
                                <div className="lg:col-span-4 space-y-6">
                                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                                        <div className="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-6 text-primary">
                                            <Calendar size={24} />
                                        </div>
                                        <h3 className="text-xl font-bold text-gray-900 mb-4">{selectedCalendar.title}</h3>
                                        
                                        <div className="space-y-4 pt-4 border-t border-gray-50">
                                            <div className="flex justify-between items-center text-sm">
                                                <span className="text-gray-500">Tahun Ajaran</span>
                                                <span className="font-bold text-gray-900">{selectedCalendar.academic_year}</span>
                                            </div>
                                            <div className="flex justify-between items-center text-sm">
                                                <span className="text-gray-500">Semester</span>
                                                <span className="font-bold text-gray-900">{selectedCalendar.semester_name}</span>
                                            </div>
                                        </div>

                                        <div className="mt-8 space-y-3">
                                            <button 
                                                onClick={() => openImageModal(selectedCalendar)}
                                                className="w-full flex items-center justify-center gap-2 py-3 px-4 bg-primary text-white font-bold rounded-xl hover:bg-primary-dark transition-all shadow-lg shadow-primary/20"
                                            >
                                                <ExternalLink size={18} />
                                                Lihat Full Screen
                                            </button>
                                            <a 
                                                href={getImageSrc(selectedCalendar)} 
                                                download 
                                                className="w-full flex items-center justify-center gap-2 py-3 px-4 bg-white border-2 border-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-all"
                                            >
                                                <Download size={18} />
                                                Unduh Gambar
                                            </a>
                                        </div>
                                    </div>

                                    {/* Important Note Box */}
                                    <div className="bg-yellow-50 border-l-4 border-accent-yellow p-6 rounded-r-2xl">
                                        <div className="flex items-center gap-2 text-yellow-800 font-bold mb-2">
                                            <FileText size={18} />
                                            <span>Catatan Penting</span>
                                        </div>
                                        <p className="text-sm text-yellow-700 leading-relaxed">
                                            Jadwal sewaktu-waktu dapat berubah menyesuaikan dengan kebijakan Dinas Pendidikan Provinsi dan kalender pendidikan nasional.
                                        </p>
                                    </div>
                                </div>

                                {/* Right: Calendar Image (8 Cols) */}
                                <div className="lg:col-span-8 group relative rounded-3xl overflow-hidden bg-white shadow-xl border border-gray-100 p-2">
                                    <div 
                                        className="relative aspect-[3/4] md:aspect-[4/3] overflow-hidden rounded-2xl cursor-zoom-in"
                                        onClick={() => openImageModal(selectedCalendar)}
                                    >
                                        <ResponsiveImage 
                                            media={selectedCalendar.image}
                                            src={getImageSrc(selectedCalendar)} 
                                            alt={selectedCalendar.title} 
                                            className="w-full h-full object-contain bg-gray-50 transform group-hover:scale-[1.02] transition-transform duration-700"
                                            sizes="(max-width: 768px) 100vw, (max-width: 1200px) 70vw, 800px"
                                        />
                                        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300 flex items-center justify-center">
                                            <div className="bg-white/90 backdrop-blur-sm p-4 rounded-full shadow-xl opacity-0 group-hover:opacity-100 transition-opacity transform scale-90 group-hover:scale-100 duration-300">
                                                <ExternalLink size={24} className="text-primary" />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="p-4 text-center">
                                        <p className="text-xs text-gray-400 font-medium italic">Klik pada gambar untuk memperbesar detail jadwal.</p>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="bg-white rounded-[40px] shadow-sm border border-gray-100 p-20 text-center">
                                <div className="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <Calendar size={40} className="text-gray-300" />
                                </div>
                                <h3 className="text-2xl font-bold text-gray-900 mb-2">Belum Ada Kalender</h3>
                                <p className="text-gray-500 max-w-sm mx-auto leading-relaxed">
                                    Data kalender akademik belum tersedia untuk periode ini. Silakan hubungi bagian kurikulum atau cek kembali nanti.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {/* 3. FAQ / INFO ADDITIONAL SECTION */}
            <section className="py-20 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid lg:grid-cols-2 gap-16 items-center">
                        <div className="space-y-6">
                            <h2 className={TYPOGRAPHY.sectionHeading}>Informasi <span className="text-primary">Kurikulum</span></h2>
                            <p className={TYPOGRAPHY.bodyText}>
                                Kalender akademik ini disusun berdasarkan pedoman kurikulum yang berlaku di {siteName}. Untuk detail kegiatan belajar mengajar per mata pelajaran, silakan jelajahi halaman kurikulum kami.
                            </p>
                            <Link 
                                href="/akademik/kurikulum" 
                                className="inline-flex items-center px-6 py-3 bg-blue-50 text-primary font-bold rounded-xl hover:bg-blue-100 transition-all group"
                            >
                                Lihat Detail Kurikulum
                                <ChevronRight size={18} className="ml-2 transform group-hover:translate-x-1 transition-transform" />
                            </Link>
                        </div>
                        
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {[
                                { title: "Ujian Semester", desc: "Penilaian Sumatif Akhir Semester biasanya dilakukan di bulan Desember dan Juni." },
                                { title: "Libur Sekolah", desc: "Mengikuti ketetapan kalender pendidikan dari Dinas Pendidikan Provinsi Jawa Barat." }
                            ].map((item, idx) => (
                                <div key={idx} className="p-6 rounded-2xl bg-gray-50 border border-gray-100">
                                    <h4 className="font-bold text-gray-900 mb-2">{item.title}</h4>
                                    <p className="text-sm text-gray-600 leading-relaxed">{item.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* Full Image Modal */}
            <Modal show={showImageModal} onClose={closeImageModal} maxWidth="5xl">
                <div className="relative flex flex-col bg-white">
                    <button
                        onClick={closeImageModal}
                        className="absolute top-4 right-4 p-2 bg-white/80 hover:bg-white backdrop-blur-sm rounded-full text-gray-800 shadow-sm transition-all z-20 border border-gray-100"
                        aria-label="Tutup"
                    >
                        <X size={20} />
                    </button>
                    
                    {selectedCalendar && (
                        <div className="w-full">
                            <div className="bg-gray-100/50 p-4 md:p-8 flex items-center justify-center min-h-[400px]">
                                <img 
                                    src={getImageSrc(selectedCalendar)} 
                                    alt={selectedCalendar.title}
                                    className="max-w-full max-h-[75vh] object-contain shadow-xl rounded-lg bg-white"
                                />
                            </div>
                            <div className="p-6 text-center border-t border-gray-100">
                                <h3 className="font-bold text-xl text-gray-900 mb-1">{selectedCalendar.title}</h3>
                                <p className="text-gray-500 font-medium">{selectedCalendar.academic_year} - {selectedCalendar.semester_name}</p>
                            </div>
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

