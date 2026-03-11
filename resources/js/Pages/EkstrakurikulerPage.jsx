import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Dribbble,
    Activity,
    Music,
    Mic,
    Cpu,
    Bot,
    Flag,
    BookHeart,
    Target,
    Palette,
    Globe,
    Zap,
    Users,
    Trophy,
    Clock,
    MapPin,
    Phone,
    Calendar,
    ArrowRight,
    Star,
    Award,
    Heart,
    Microscope,
    Compass,
    Crown,
    Shield
} from 'lucide-react';

import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import Modal from '@/Components/Modal';
import ResponsiveImage, { HeroImage } from '@/Components/ResponsiveImage';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { getPageMetadata } from '@/Utils/academicData';
import { getImageUrl } from '@/Utils/imageUtils';
import { usePage } from '@inertiajs/react';

// --- Helper Functions for Visuals ---

const getCategoryTheme = (categoryName) => {
    const icons = {
        'Olahraga': Activity,
        'Seni & Budaya': Palette,
        'Akademik & Sains': Cpu,
        'Keagamaan & Sosial': Heart,
        'Organisasi Siswa': Crown,
        'Kepemimpinan & Bela Negara': Flag,
        'Teknologi & Inovasi': Bot
    };

    // Unified theme using project primary colors
    return {
        color: 'text-primary',
        bg: 'bg-blue-50',
        border: 'border-blue-100',
        accent: 'bg-primary',
        light: 'bg-blue-50',
        icon: icons[categoryName] || Star
    };
};

const getActivityIcon = (name, type = 'ekstrakurikuler') => {
    const lowerName = name.toLowerCase();
    
    // Organisasi icons
    if (type === 'organisasi') {
        if (lowerName.includes('osis')) return Crown;
        if (lowerName.includes('mpk')) return Shield;
        if (lowerName.includes('paskibra')) return Flag;
        return Crown;
    }
    
    // Ekstrakurikuler icons
    if (lowerName.includes('basket')) return Dribbble;
    if (lowerName.includes('futsal') || lowerName.includes('sepak bola')) return Target;
    if (lowerName.includes('voli')) return Activity;
    if (lowerName.includes('badminton') || lowerName.includes('bulu tangkis')) return Zap;
    if (lowerName.includes('musik') || lowerName.includes('band')) return Music;
    if (lowerName.includes('paduan suara') || lowerName.includes('vokal')) return Mic;
    if (lowerName.includes('tari')) return Users;
    if (lowerName.includes('teater') || lowerName.includes('drama')) return Palette;
    if (lowerName.includes('robotik') || lowerName.includes('it')) return Bot;
    if (lowerName.includes('kir') || lowerName.includes('ilmiah')) return Microscope;
    if (lowerName.includes('english') || lowerName.includes('bahasa')) return Globe;
    if (lowerName.includes('paskibra')) return Flag;
    if (lowerName.includes('pramuka')) return Compass;
    if (lowerName.includes('rohis') || lowerName.includes('keagamaan')) return BookHeart;
    if (lowerName.includes('pmr') || lowerName.includes('kesehatan')) return Heart;
    
    return Star; // Default icon
};

// --- Components ---

const ActivityCard = ({ activity, categoryTheme, onClick, isOrganisasi = false }) => {
    const Icon = getActivityIcon(activity.name, isOrganisasi ? 'organisasi' : 'ekstrakurikuler');
    const cardImage = getImageUrl(activity.image_url || activity.bg_image_url);
    
    return (
        <div className="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 flex flex-col h-full transform hover:-translate-y-1">
            {/* Header Visual */}
            <div className="h-48 relative overflow-hidden bg-gray-900">
                {cardImage ? (
                    <>
                        <ResponsiveImage
                            src={cardImage}
                            alt={activity.name}
                            className="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            loading="lazy"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
                    </>
                ) : (
                    <>
                        <div className={`absolute inset-0 ${categoryTheme.light}`}></div>
                        <div className="absolute inset-0 opacity-10 bg-pattern-dots"></div>
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className={`w-16 h-16 ${categoryTheme.accent} rounded-2xl rotate-3 flex items-center justify-center shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300`}>
                                <Icon className="w-8 h-8 text-white" />
                            </div>
                        </div>
                    </>
                )}
            </div>

            {/* Body */}
            <div className="p-6 flex-1 flex flex-col">
                <h3 className="font-serif text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition-colors">
                    {activity.name}
                </h3>

                {/* Description Truncated */}
                <p className="text-gray-600 text-sm leading-relaxed mb-6 line-clamp-2 flex-1">
                    {activity.description}
                </p>

                {/* Prestasi Highlight */}
                {activity.achievements && activity.achievements.length > 0 && (
                    <div className="mb-6 flex items-start gap-2 text-sm text-gray-700 bg-yellow-50 p-3 rounded-lg border border-yellow-100">
                        <Trophy className="w-4 h-4 text-yellow-600 flex-shrink-0 mt-0.5" />
                        <span className="font-medium line-clamp-1">{activity.achievements[0]}</span>
                    </div>
                )}

                {/* Action Button */}
                <button
                    onClick={onClick}
                    className="w-full py-2.5 rounded-xl border-2 border-primary text-primary font-bold text-sm transition-all duration-300 flex items-center justify-center gap-2 hover:bg-primary hover:text-white"
                >
                    Lihat Detail <ArrowRight className="w-4 h-4" />
                </button>
            </div>
        </div>
    );
};

const ActivityDetailModal = ({ show, onClose, activity, categoryTheme, isOrganisasi = false }) => {
    if (!activity) return null;
    const Icon = getActivityIcon(activity.name, isOrganisasi ? 'organisasi' : 'ekstrakurikuler');

    // Use specific detailed fields if available, otherwise fallback
    // Process URLs using getImageUrl helper
    const headerImage = getImageUrl(activity.bg_image_url || activity.image_url);
    // User requested to use main image (image_url) for the profile box, not the background image
    const mainImage = getImageUrl(activity.image_url);
    const description = activity.activity_description || activity.description;
    
    // Check if achievements_data exists and is an array (new format), otherwise use achievements (old format)
    const hasNewAchievements = Array.isArray(activity.achievements_data) && activity.achievements_data.length > 0;
    const achievementsList = hasNewAchievements ? activity.achievements_data : (activity.achievements || []);
    
    // Check if training_info exists and has the new structure (object with days array, start_time, end_time, etc.)
    const hasTrainingInfo = activity.training_info && typeof activity.training_info === 'object' && (activity.training_info.days || activity.training_info.start_time || activity.training_info.location || activity.training_info.coach);

    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
             <div className="bg-white rounded-2xl overflow-hidden flex flex-col max-h-[90vh]">
                {/* Modal Header Banner - Fixed height */}
                <div className="h-48 relative overflow-hidden bg-gray-900 shrink-0 rounded-t-2xl">
                    {/* Background Image */}
                    <ResponsiveImage
                        src={headerImage}
                        alt={activity.name}
                        className="absolute inset-0 w-full h-full object-cover opacity-80"
                        loading="lazy"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                    
                    <div className="absolute bottom-0 left-0 p-8 w-full">
                        <div className="flex items-end gap-6">
                            {/* Profile Image / Logo */}
                            <div className="w-20 h-20 bg-white rounded-2xl shadow-xl flex items-center justify-center transform translate-y-6 overflow-hidden border-2 border-white">
                                {mainImage ? (
                                    <img src={mainImage} alt={activity.name} className="w-full h-full object-cover" />
                                ) : (
                                    <Icon className={`w-10 h-10 ${categoryTheme.color}`} />
                                )}
                            </div>
                            
                            <div className="text-white flex-1 transform translate-y-3">
                                <div className="text-white/80 text-sm font-bold uppercase tracking-wider mb-1 flex items-center gap-2">
                                    <span className={`${categoryTheme.bg} ${categoryTheme.color} px-2 py-0.5 rounded text-xs`}>
                                        {isOrganisasi ? 'Organisasi' : 'Ekstrakurikuler'}
                                    </span>
                                    <span>{activity.category}</span>
                                </div>
                                <h2 className="text-xl md:text-2xl font-serif font-bold drop-shadow-md">{activity.name}</h2>
                            </div>
                        </div>
                    </div>
                    
                    <button
                        onClick={onClose}
                        className="absolute top-4 right-4 p-2 bg-black/30 hover:bg-black/50 backdrop-blur-sm rounded-full text-white transition-all z-10"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Modal Content - Scrollable */}
                <div className="overflow-y-auto p-8 pt-12">
                     <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Left Column: Main Info */}
                        <div className="lg:col-span-2 space-y-8">
                            {/* Description */}
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                    <BookHeart className="w-5 h-5 text-primary" />
                                    Tentang {isOrganisasi ? 'Organisasi' : 'Kegiatan'}
                                </h3>
                                <div className="text-gray-600 leading-relaxed text-sm prose prose-blue max-w-none">
                                    {description ? description.split('\n').map((line, i) => (
                                        <p key={i} className="mb-2">{line}</p>
                                    )) : <p className="italic text-gray-400">Belum ada deskripsi detail.</p>}
                                </div>
                            </div>

                            {/* Achievements - Scrollable Section */}
                            <div>
                                <h3 className="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                    <Trophy className="w-5 h-5 text-yellow-500" />
                                    Jejak Prestasi
                                </h3>
                                
                                <div className="max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                                    <div className="relative pl-4 border-l-2 border-gray-100 space-y-4">
                                        {achievementsList.length > 0 ? (
                                            achievementsList.map((achievement, idx) => {
                                                const title = hasNewAchievements ? achievement.title : achievement;
                                                const year = hasNewAchievements ? achievement.year : '2024';
                                                const desc = hasNewAchievements ? achievement.description : 'Tingkat Kota/Provinsi';

                                                return (
                                                    <div key={idx} className="relative group">
                                                        <div className={`absolute -left-[21px] top-1 w-4 h-4 rounded-full border-2 border-white shadow-sm transition-colors ${categoryTheme.accent} group-hover:scale-110`}></div>
                                                        <div className="bg-gray-50 p-3 rounded-xl border border-gray-100 hover:shadow-md transition-all hover:bg-white hover:border-blue-100">
                                                            <div className="flex justify-between items-start mb-1">
                                                                <p className="font-bold text-sm text-gray-800">{title}</p>
                                                                <span className="text-[10px] font-bold px-2 py-0.5 bg-white rounded-full border border-gray-200 text-gray-500">{year}</span>
                                                            </div>
                                                            <p className="text-xs text-gray-600">{desc}</p>
                                                        </div>
                                                    </div>
                                                );
                                            })
                                        ) : (
                                            <div className="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                                 <Trophy className="w-10 h-10 text-gray-300 mx-auto mb-2" />
                                                 <p className="text-gray-500 italic">Belum ada data prestasi tercatat.</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right Column: Info Box */}
                        <div className="lg:col-span-1">
                            <div className="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm sticky top-0">
                                <h3 className="font-bold text-gray-900 mb-6 text-base border-b pb-2">
                                    Informasi & Jadwal
                                </h3>
                                
                                <div className="space-y-5">
                                    {/* Training Info / Schedule */}
                                    {hasTrainingInfo ? (
                                        <div className="pb-4 border-b border-gray-50 last:border-0 last:pb-0">
                                            {activity.training_info.days && activity.training_info.days.length > 0 && (
                                                <div className="flex items-start gap-3 mb-3">
                                                    <div className={`p-1.5 rounded-lg shrink-0 ${categoryTheme.light} ${categoryTheme.color}`}>
                                                        <Calendar className="w-3.5 h-3.5" />
                                                    </div>
                                                    <div>
                                                        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Hari Latihan</p>
                                                        <p className="font-medium text-sm text-gray-900">{activity.training_info.days.join(', ')}</p>
                                                    </div>
                                                </div>
                                            )}
                                            {(activity.training_info.start_time || activity.training_info.end_time) && (
                                                <div className="flex items-start gap-3 mb-3">
                                                    <div className={`p-1.5 rounded-lg shrink-0 ${categoryTheme.light} ${categoryTheme.color}`}>
                                                        <Clock className="w-3.5 h-3.5" />
                                                    </div>
                                                    <div>
                                                        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Waktu Latihan</p>
                                                        <p className="font-medium text-sm text-gray-900">
                                                            {activity.training_info.start_time && activity.training_info.end_time
                                                                ? `${activity.training_info.start_time} - ${activity.training_info.end_time}`
                                                                : (activity.training_info.start_time || activity.training_info.end_time || 'Menyusul')}
                                                        </p>
                                                    </div>
                                                </div>
                                            )}
                                            {activity.training_info.location && (
                                                <div className="flex items-start gap-3 mb-3">
                                                    <div className={`p-1.5 rounded-lg shrink-0 ${categoryTheme.light} ${categoryTheme.color}`}>
                                                        <MapPin className="w-3.5 h-3.5" />
                                                    </div>
                                                    <div>
                                                        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Lokasi Latihan</p>
                                                        <p className="font-medium text-sm text-gray-900">{activity.training_info.location}</p>
                                                    </div>
                                                </div>
                                            )}
                                            {activity.training_info.coach && (
                                                <div className="flex items-start gap-3">
                                                    <div className={`p-1.5 rounded-lg shrink-0 ${categoryTheme.light} ${categoryTheme.color}`}>
                                                        <Users className="w-3.5 h-3.5" />
                                                    </div>
                                                    <div>
                                                        <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Pembina/Pelatih</p>
                                                        <p className="font-medium text-sm text-gray-900">{activity.training_info.coach}</p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <>
                                            <div className="flex items-start gap-3">
                                                <div className={`p-2 rounded-lg ${categoryTheme.light} ${categoryTheme.color}`}>
                                                    <Clock className="w-4 h-4" />
                                                </div>
                                                <div>
                                                    <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Jadwal Umum</p>
                                                    <p className="font-medium text-sm text-gray-900">{activity.schedule || 'Menyusul'}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-start gap-3">
                                                <div className={`p-2 rounded-lg ${categoryTheme.light} ${categoryTheme.color}`}>
                                                    <MapPin className="w-4 h-4" />
                                                </div>
                                                <div>
                                                    <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Lokasi</p>
                                                    <p className="font-medium text-sm text-gray-900">{isOrganisasi ? 'Sekretariat' : 'Area Sekolah'}</p>
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>

                                <div className="mt-8 pt-6 border-t border-gray-100">
                                    <div className={`${categoryTheme.bg} border ${categoryTheme.border} rounded-xl p-4 text-center`}>
                                        <p className={`text-sm font-bold ${categoryTheme.color} mb-1`}>
                                            Tertarik Bergabung?
                                        </p>
                                        <p className="text-xs text-gray-600">
                                            Hubungi pembina atau datang langsung saat jadwal latihan.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    );
};

// --- Main Page Component ---

export default function EkstrakurikulerPage({ extracurriculars = [] }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const navigationData = getNavigationData(siteSettings);
    const pageMetadata = getPageMetadata(siteName);
    const [selectedActivity, setSelectedActivity] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [activeCategoryTheme, setActiveCategoryTheme] = useState(null);
    const [selectedIsOrganisasi, setSelectedIsOrganisasi] = useState(false);

    const heroSettings = siteSettings?.hero_extracurricular || {};
    
    // Helper to format image path correctly
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

    const openDetailModal = (activity, categoryName, isOrganisasi = false) => {
        setSelectedActivity(activity);
        setActiveCategoryTheme(getCategoryTheme(categoryName));
        setSelectedIsOrganisasi(isOrganisasi);
        setShowModal(true);
    };

    const closeModal = () => {
        setShowModal(false);
        setTimeout(() => setSelectedActivity(null), 300); // Delay clear for animation
    };

    // Separate organisasi and ekstrakurikuler
    const organisasiItems = useMemo(() => {
        return extracurriculars.filter(e => e.type === 'organisasi');
    }, [extracurriculars]);

    const ekstrakurikulerItems = useMemo(() => {
        return extracurriculars.filter(e => e.type === 'ekstrakurikuler' || !e.type);
    }, [extracurriculars]);

    // Group ekstrakurikuler by category
    const groupedEkstrakurikuler = useMemo(() => {
        const categories = [...new Set(ekstrakurikulerItems.map(e => e.category))];
        return categories.map(cat => ({
            category: cat,
            activities: ekstrakurikulerItems.filter(e => e.category === cat)
        }));
    }, [ekstrakurikulerItems]);

    // Group organisasi by category
    const groupedOrganisasi = useMemo(() => {
        const categories = [...new Set(organisasiItems.map(e => e.category))];
        return categories.map(cat => ({
            category: cat,
            activities: organisasiItems.filter(e => e.category === cat)
        }));
    }, [organisasiItems]);

    return (
        <div className="bg-white min-h-screen font-sans text-gray-800 flex flex-col">
            <SEOHead
                title="Organisasi & Ekstrakurikuler"
                description={pageMetadata?.ekstrakurikuler?.description || 'Wadah pengembangan bakat, kreativitas, dan kepemimpinan siswa melalui kegiatan organisasi dan ekstrakurikuler.'}
                keywords={`organisasi, ekstrakurikuler, ekskul, kegiatan sekolah, OSIS, MPK, paskibra, pramuka, basket, futsal, musik, seni, sains, robotika, ${siteName}`}
                image="/images/ekskul-banner.jpg"
            />
            
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
            {/* 1. HERO SECTION (Consistent with KurikulumPage) */}
            <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                {/* Background Image */}
                <div className="absolute inset-0 z-0">
                    {formatImagePath(heroImage) && (
                        <HeroImage 
                            src={formatImagePath(heroImage)} 
                            alt={`Background Organisasi & Ekstrakurikuler ${siteName}`} 
                        />
                    )}
                    <div className="absolute inset-0 bg-black/60"></div>
                </div>

                <div className="relative z-10 container mx-auto px-4 text-center text-white">
                    <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                        {renderHighlightedTitle('Organisasi & Ekstrakurikuler')}
                    </h1>
                    <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                        {heroSettings.subtitle || `Wadah pengembangan bakat, kreativitas dan kepemimpinan siswa ${siteName} untuk membentuk karakter unggul.`}
                    </p>
                </div>
            </section>

            {/* 2. ORGANISASI SECTION - Langsung tampilkan kategori tanpa header section */}
            {organisasiItems.length > 0 && (
                <section className="py-20 bg-secondary">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        {/* Render Organisasi Groups */}
                        {groupedOrganisasi.map((group, idx) => {
                            const theme = getCategoryTheme(group.category);
                            
                            return (
                                <div key={idx} className="mb-20 last:mb-0">
                                    {/* Category Header - Aligned left */}
                                    <div className="text-left mb-12">
                                        <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                                            {group.category.split(' ').slice(0, -1).join(' ')} <span className="text-primary">{group.category.split(' ').slice(-1)}</span>
                                        </h2>
                                    </div>

                                    {/* Grid */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                        {group.activities.map((activity, index) => (
                                            <ActivityCard
                                                key={index}
                                                activity={activity}
                                                categoryTheme={theme}
                                                isOrganisasi={true}
                                                onClick={() => openDetailModal(activity, group.category, true)}
                                            />
                                        ))}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            {/* 3. EKSTRAKURIKULER SECTION - Langsung tampilkan kategori tanpa header section */}
            <section className="py-20 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Render Ekstrakurikuler Categories */}
                    {groupedEkstrakurikuler.length > 0 ? (
                        groupedEkstrakurikuler.map((group, idx) => {
                            const theme = getCategoryTheme(group.category);
                            
                            return (
                                <div key={idx} className="mb-20 last:mb-0">
                                    {/* Category Header - Aligned left */}
                                    <div className="text-left mb-12">
                                        <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>
                                            {group.category.split(' ').slice(0, -1).join(' ')} <span className="text-primary">{group.category.split(' ').slice(-1)}</span>
                                        </h2>
                                    </div>

                                    {/* Grid */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                        {group.activities.map((activity, index) => (
                                            <ActivityCard
                                                key={index}
                                                activity={activity}
                                                categoryTheme={theme}
                                                isOrganisasi={false}
                                                onClick={() => openDetailModal(activity, group.category, false)}
                                            />
                                        ))}
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <div className="text-center py-20 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                            <Activity className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <h3 className="text-xl font-bold text-gray-900">Belum ada data ekstrakurikuler</h3>
                            <p className="text-gray-500">Silakan kembali lagi nanti.</p>
                        </div>
                    )}

                </div>
            </section>

            {/* 4. CTA / JOIN SECTION - Updated Style */}
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

            {/* Detail Modal */}
            <ActivityDetailModal 
                show={showModal} 
                onClose={closeModal} 
                activity={selectedActivity} 
                categoryTheme={activeCategoryTheme}
                isOrganisasi={selectedIsOrganisasi}
            />
        </div>
    );
}
