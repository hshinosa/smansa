import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { 
    CheckCircle2, 
    MapPin, 
    Trophy, 
    HeartHandshake, 
    Truck, 
    Calendar, 
    ChevronDown, 
    ChevronUp, 
    Download, 
    ExternalLink, 
    HelpCircle,
    Info,
    MessageCircle,
    PlayCircle,
    Phone,
    Mail
} from 'lucide-react';

import Navbar from '@/Components/Navbar';
import SEOHead from '@/Components/SEOHead';
import { HeroImage } from '@/Components/ResponsiveImage';
import SanitizedContent from '@/Components/SanitizedContent';
import Footer from '@/Components/Footer';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';

// --- FALLBACK DATA: JALUR PENDAFTARAN ---
const fallbackJalurData = [
    {
        id: 'zonasi',
        label: 'Zonasi',
        icon: MapPin,
        quota: '50%',
        description: 'Jalur pendaftaran bagi calon peserta didik yang berdomisili di dalam wilayah zonasi yang ditetapkan.',
        requirements: [
            'Kartu Keluarga (minimal 1 tahun)',
            'Akta Kelahiran / Surat Keterangan Lahir',
            'Ijazah / Surat Keterangan Lulus',
            'Titik koordinat tempat tinggal'
        ]
    },
    {
        id: 'prestasi',
        label: 'Prestasi',
        icon: Trophy,
        quota: '25%',
        description: 'Jalur bagi siswa dengan pencapaian akademik (nilai rapor) atau non-akademik (kejuaraan/lomba).',
        requirements: [
            'Nilai Rapor semester 1-5',
            'Sertifikat/Piagam Kejuaraan (min. tingkat Kota/Kab)',
            'Surat Tanggung Jawab Mutlak (SPTJM)',
            'Akta Kelahiran & KK'
        ]
    },
    {
        id: 'afirmasi',
        label: 'Afirmasi',
        icon: HeartHandshake,
        quota: '20%',
        description: 'Diperuntukkan bagi calon peserta didik dari keluarga ekonomi tidak mampu (KETM) dan penyandang disabilitas.',
        requirements: [
            'Kartu KIP / PKH / KKS / Terdaftar DTKS',
            'Surat Keterangan Tidak Mampu (jika ada)',
            'Akta Kelahiran & KK',
            'Surat Pernyataan Orang Tua'
        ]
    },
    {
        id: 'perpindahan',
        label: 'Perpindahan',
        icon: Truck,
        quota: '5%',
        description: 'Bagi calon peserta didik yang mengikuti perpindahan tugas orang tua/wali atau anak guru.',
        requirements: [
            'Surat Keputusan Pindah Tugas Orang Tua',
            'Surat Keterangan Domisili',
            'Akta Kelahiran & KK',
            'Ijazah / Surat Keterangan Lulus'
        ]
    }
];

// --- FALLBACK DATA: TIMELINE ---
const fallbackTimelineData = [
    { date: '15-20 Jun', title: 'Pendaftaran & Verifikasi', status: 'completed' },
    { date: '21-25 Jun', title: 'Uji Kompetensi (Prestasi)', status: 'active' },
    { date: '30 Jun', title: 'Pengumuman Hasil', status: 'upcoming' },
    { date: '1-3 Jul', title: 'Daftar Ulang', status: 'upcoming' },
];

// --- FALLBACK DATA: FAQ ---
const fallbackFaqData = [
    {
        question: "Bagaimana cara menghitung jarak zonasi?",
        answer: "Jarak zonasi dihitung berdasarkan garis lurus (point to point) dari koordinat domisili tempat tinggal calon peserta didik ke titik koordinat sekolah menggunakan sistem teknologi informasi."
    },
    {
        question: "Apakah bisa mendaftar di 2 jalur sekaligus?",
        answer: "Calon peserta didik dapat mendaftar pada tahap 1 (Afirmasi/Prestasi/Perpindahan) and jika tidak lolos, dapat mendaftar kembali pada tahap 2 (Zonasi). Namun, tidak bisa mendaftar 2 jalur berbeda dalam satu tahap yang sama."
    },
    {
        question: "Berapa nilai minimal untuk jalur prestasi rapor?",
        answer: "Tidak ada passing grade tetap. Seleksi dilakukan melalui pemeringkatan berdasarkan akumulasi rata-rata nilai rapor semester 1 s.d. 5 dari seluruh pendaftar."
    },
    {
        question: "Apakah sertifikat online diakui untuk jalur prestasi?",
        answer: "Sertifikat kejuaraan yang diakui adalah yang diselenggarakan oleh instansi pemerintah atau induk organisasi yang kompeten. Sertifikat webinar/partisipasi online biasanya tidak memiliki bobot poin yang signifikan."
    }
];

export default function InformasiSpmbPage({ spmbData }) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    const { 
        pengaturan_umum, 
        jalur_pendaftaran, 
        jadwal_penting, 
        persyaratan, 
        prosedur, 
        faq 
    } = spmbData || {};

    // Map icons for jalur
    const getIconForJalur = (label) => {
        const l = label?.toLowerCase() || '';
        if (l.includes('zonasi')) return MapPin;
        if (l.includes('prestasi')) return Trophy;
        if (l.includes('afirmasi')) return HeartHandshake;
        if (l.includes('perpindahan')) return Truck;
        return CheckCircle2;
    };

    // Process Jalur Data
    const displayJalurData = jalur_pendaftaran?.items?.length > 0 
        ? jalur_pendaftaran.items.map((item, idx) => ({
            id: item.label?.toLowerCase().replace(/\s+/g, '-') || `jalur-${idx}`,
            label: item.label,
            icon: getIconForJalur(item.label),
            quota: item.quota || '0%',
            description: item.description || '',
            requirements: item.requirements || []
        }))
        : fallbackJalurData;

    const [activeTab, setActiveTab] = useState(displayJalurData[0]?.id || 'zonasi');
    const [openFaqIndex, setOpenFaqIndex] = useState(null);

    const toggleFaq = (index) => {
        setOpenFaqIndex(openFaqIndex === index ? null : index);
    };

    const activeJalur = displayJalurData.find(j => j.id === activeTab) || displayJalurData[0];

    // Process Timeline Data
    const displayTimelineData = jadwal_penting?.items?.length > 0
        ? jadwal_penting.items
        : fallbackTimelineData;

    // Process FAQ Data
    const displayFaqData = faq?.items?.length > 0
        ? faq.items
        : fallbackFaqData;

    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const renderHighlightedTitle = (title) => {
        if (!title) return null;
        
        // Target names to highlight
        const targets = [
            'SMA Negeri 1 Baleendah',
            'SMAN 1 Baleendah',
            siteName
        ].filter(Boolean);

        for (const schoolName of targets) {
            if (title.includes(schoolName)) {
                const parts = title.split(schoolName);
                return (
                    <>
                        {parts[0]}
                        <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">
                            {schoolName}
                        </span>
                        {parts[1]}
                    </>
                );
            }
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

    // Helper to format image path correctly
    const formatImagePath = (path) => {
        if (!path) return null;
        if (typeof path !== 'string') return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    return (
        <div className="bg-white font-sans text-gray-800">
            <SEOHead 
                title={`${pengaturan_umum?.title || "Informasi PPDB"} 2025 - ${siteName}`}
                description="Informasi lengkap Penerimaan Peserta Didik Baru (PPDB) 2025 SMAN 1 Baleendah. Jadwal, syarat, jalur pendaftaran (zonasi, prestasi, afirmasi, perpindahan), prosedur, dan FAQ."
                keywords="PPDB 2025, PPDB Bandung, pendaftaran SMA, jalur zonasi, jalur prestasi, PPDB online, SMAN 1 Baleendah"
                image={pengaturan_umum?.hero_background_image || "/images/ppdb-2025-banner.jpg"}
            />
            
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-0" tabIndex="-1">
                
                {/* ========== HERO SECTION: Organic Flow ========== */}
                <section className="relative min-h-[600px] lg:h-[85vh] flex items-center overflow-hidden pt-36 pb-16 lg:pt-44 lg:pb-20">
                    {/* Background Image with Overlay */}
                    <div className="absolute inset-0">
                        <img src={formatImagePath(heroImage)} alt="" className="w-full h-full object-cover" />
                        <div className="absolute inset-0 bg-black/50"></div>
                    </div>
                    
                    {/* (Deleted Blobs & Particles) */}

                    <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                        <div className="text-center max-w-4xl mx-auto">
                            {/* Title with Gradient Mesh */}
                            <h1 className="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-serif font-bold text-white leading-tight mb-4 sm:mb-6">
                                <span className="block">Masa Depan</span>
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 via-blue-100 to-cyan-300">Cemerlang</span>
                                <span className="block text-white/80 text-xl sm:text-3xl md:text-4xl mt-2 sm:mt-3">Dimulai Dari Sini</span>
                            </h1>
                            
                            {/* Description */}
                            <p className="text-base sm:text-lg md:text-xl text-gray-200 max-w-2xl mx-auto mb-8 leading-relaxed font-light px-4">
                                {siteName} mengundangmu untuk menjadi bagian dari perjalanan transformatif yang akan membentuk karakter dan masa depanmu.
                            </p>
                            
                            {/* CTA Buttons */}
                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center px-4 sm:px-0">
                                {pengaturan_umum?.is_registration_open ? (
                                    <a href="https://ppdb.jabarprov.go.id/" target="_blank" rel="noopener noreferrer"
                                        className="group w-full sm:w-auto px-6 py-3.5 sm:px-10 sm:py-5 bg-accent-yellow text-gray-900 font-bold rounded-xl sm:rounded-2xl hover:shadow-[0_0_40px_rgba(255,193,7,0.4)] transition-all flex items-center justify-center gap-2 text-sm sm:text-base">
                                        <ExternalLink className="w-4 h-4 sm:w-5 sm:h-5" />
                                        Mulai Pendaftaran
                                    </a>
                                ) : (
                                    <div className="w-full sm:w-auto px-6 py-3.5 sm:px-8 sm:py-5 bg-white/10 backdrop-blur-xl border border-white/20 text-white font-semibold rounded-xl sm:rounded-2xl text-sm sm:text-base">
                                        Pendaftaran Segera Dibuka
                                    </div>
                                )}
                                <a 
                                    href="#jalur-pendaftaran" 
                                    onClick={(e) => {
                                        e.preventDefault();
                                        document.getElementById('jalur-pendaftaran')?.scrollIntoView({ behavior: 'smooth' });
                                    }}
                                    className="w-full sm:w-auto px-6 py-3.5 sm:px-10 sm:py-5 bg-white/5 backdrop-blur-xl border border-white/20 text-white font-bold rounded-xl sm:rounded-2xl hover:bg-white/10 transition-all flex items-center justify-center gap-2 text-sm sm:text-base"
                                >
                                    Eksplorasi Jalur
                                    <ChevronDown className="w-4 h-4 sm:w-4 sm:h-4" />
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {/* Bottom Wave - Organic */}
                    <div className="absolute -bottom-1 left-0 right-0">
                        <svg viewBox="0 0 1440 200" className="w-full h-auto fill-white">
                            <path d="M0,128L48,138.7C96,149,192,171,288,165.3C384,160,480,128,576,128C672,128,768,160,864,170.7C960,181,1056,171,1152,149.3C1248,128,1344,96,1392,80L1440,64L1440,200L1392,200C1344,200,1248,200,1152,200C1056,200,960,200,864,200C768,200,672,200,576,200C480,200,384,200,288,200C192,200,96,200,48,200L0,200Z"></path>
                        </svg>
                    </div>
                </section>

                {/* 2. SECTION: JALUR PENDAFTARAN (Interactive Tabs) */}
                <section id="jalur-pendaftaran" className="py-16 sm:py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-8 sm:mb-12">
                            <h2 className={TYPOGRAPHY.sectionHeading}>Jalur <span className="text-primary">Pendaftaran</span></h2>
                            <p className={`${TYPOGRAPHY.bodyText} mt-4`}>
                                {jalur_pendaftaran?.description || "Pilih jalur yang sesuai dengan kualifikasi dan kebutuhan Anda."}
                            </p>
                        </div>

                        {/* Cards Selection Navigation */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8 sm:mb-12">
                            {displayJalurData.map((jalur) => {
                                const Icon = jalur.icon;
                                const isActive = activeTab === jalur.id;
                                return (
                                    <button
                                        key={jalur.id}
                                        onClick={() => setActiveTab(jalur.id)}
                                        className={`p-4 sm:p-6 rounded-2xl sm:rounded-3xl border-2 transition-colors duration-200 text-left ${
                                            isActive 
                                                ? 'bg-primary border-primary text-white shadow-lg' 
                                                : 'bg-white border-gray-100 text-gray-500 hover:border-blue-200 hover:bg-blue-50/50'
                                        }`}
                                    >
                                        <div className={`mb-3 sm:mb-4 w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl flex items-center justify-center ${
                                            isActive ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-400'
                                        }`}>
                                            <Icon className="w-5 h-5 sm:w-6 sm:h-6" />
                                        </div>
                                        <h3 className={`font-bold text-sm sm:text-lg mb-0.5 sm:mb-1 ${isActive ? 'text-white' : 'text-gray-900'}`}>
                                            {jalur.label}
                                        </h3>
                                        <p className={`text-xs sm:text-sm ${isActive ? 'text-blue-100' : 'text-gray-400'}`}>
                                            Kuota: {jalur.quota}
                                        </p>
                                    </button>
                                );
                            })}
                        </div>

                        {/* Selected Content Area */}
                        {activeJalur && (
                            <div className="bg-white rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-8 md:p-12 shadow-xl border border-gray-100 relative overflow-hidden">
                                {/* Decorative Background */}
                                <div className="absolute top-0 right-0 w-[300px] sm:w-[500px] h-[300px] sm:h-[500px] bg-gradient-to-br from-blue-50 to-transparent rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 opacity-60 pointer-events-none"></div>
                                
                                <div className="flex flex-col lg:flex-row gap-8 lg:gap-16 relative z-10">
                                    {/* Left: Description */}
                                    <div className="lg:w-7/12">
                                        <h3 className="text-2xl sm:text-3xl md:text-4xl font-serif font-bold text-gray-900 mb-4 sm:mb-6 leading-tight">
                                            {activeJalur.label}
                                        </h3>
                                        <p className="text-gray-600 text-base sm:text-lg leading-relaxed mb-6 sm:mb-8">
                                            {activeJalur.description}
                                        </p>
                                        
                                        <div className="bg-accent-yellow/10 rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-accent-yellow/20">
                                            <h4 className="font-bold text-yellow-800 mb-2 flex items-center gap-2 text-sm sm:text-base">
                                                <Info className="w-4 h-4 sm:w-5 sm:h-5" />
                                                Informasi Kuota
                                            </h4>
                                            <p className="text-yellow-800/80 text-xs sm:text-sm">
                                                Kuota jalur ini sebesar <span className="font-bold">{activeJalur.quota}</span> dari total daya tampung sekolah. Persaingan mungkin berbeda setiap tahunnya.
                                            </p>
                                        </div>
                                    </div>

                                    {/* Right: Requirements */}
                                    <div className="lg:w-5/12">
                                        <div className="bg-gray-50 rounded-2xl sm:rounded-3xl p-6 sm:p-8 border border-gray-100 h-full">
                                            <h4 className="font-bold text-gray-900 text-base sm:text-lg mb-4 sm:mb-6 flex items-center gap-3">
                                                <div className="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center shadow-sm text-green-600">
                                                    <CheckCircle2 className="w-5 h-5" />
                                                </div>
                                                Syarat Dokumen
                                            </h4>
                                            <ul className="space-y-3 sm:space-y-4">
                                                {activeJalur.requirements?.map((req, idx) => (
                                                    <li key={idx} className="flex items-start gap-3 sm:gap-4 p-3 bg-white rounded-xl border border-gray-100 shadow-sm">
                                                        <div className="mt-1 w-5 h-5 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                                            <div className="w-2 h-2 rounded-full bg-blue-600"></div>
                                                        </div>
                                                        <span className="text-gray-700 text-sm font-medium">{req}</span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </section>

                {/* 2.5 SECTION: PROSEDUR & PERSYARATAN UMUM */}
                <section className="py-16 sm:py-24 bg-slate-50 relative overflow-hidden">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-24">
                            {/* Prosedur Pendaftaran */}
                            <div>
                                <div className="mb-8 sm:mb-10">
                                    <h2 className="text-2xl sm:text-3xl md:text-4xl font-serif font-bold text-gray-900 mb-3 sm:mb-4">Prosedur <span className="text-primary">Pendaftaran</span></h2>
                                    <p className="text-gray-500 text-sm sm:text-base">Ikuti langkah-langkah berikut untuk melakukan pendaftaran secara online.</p>
                                </div>
                                
                                <div className="space-y-6 sm:space-y-8 relative before:absolute before:left-6 before:top-6 before:bottom-6 before:w-0.5 before:bg-gray-200 before:border-l before:border-dashed before:border-gray-300">
                                    {(prosedur?.items || []).map((step, idx) => (
                                        <div key={idx} className="relative flex gap-4 sm:gap-6">
                                            <div className="flex-shrink-0 w-12 h-12 rounded-2xl bg-white shadow-sm border border-gray-100 flex items-center justify-center text-primary font-bold text-lg z-10">
                                                {idx + 1}
                                            </div>
                                            <div className="pt-2">
                                                <h3 className="text-lg sm:text-xl font-bold text-gray-900 mb-1 sm:mb-2">{step.title}</h3>
                                                <SanitizedContent
                                                    className="text-gray-600 leading-relaxed text-sm"
                                                    html={step.description}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                    {(!prosedur?.items || prosedur.items.length === 0) && (
                                        <p className="text-gray-500 italic pl-20">Informasi prosedur pendaftaran akan segera diperbarui.</p>
                                    )}
                                </div>
                                
                                {/* Help Card */}
                                <div className="mt-10 sm:mt-12 bg-white rounded-2xl p-5 sm:p-6 border border-gray-200 shadow-sm flex items-center gap-4 sm:gap-6">
                                    <div className="w-10 h-10 sm:w-12 sm:h-12 bg-blue-50 rounded-full flex items-center justify-center text-primary flex-shrink-0">
                                        <Phone className="w-5 h-5 sm:w-6 sm:h-6" />
                                    </div>
                                    <div>
                                        <p className="text-xs sm:text-sm text-gray-500 font-medium mb-0.5 sm:mb-1">Butuh bantuan teknis?</p>
                                        <p className="font-bold text-gray-900 text-sm sm:text-base">Hubungi Helpdesk: {siteSettings?.general?.phone || '(022) 5940262'}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Persyaratan Umum */}
                            <div>
                                <div className="mb-8 sm:mb-10">
                                    <h2 className="text-2xl sm:text-3xl md:text-4xl font-serif font-bold text-gray-900 mb-3 sm:mb-4">Persyaratan <span className="text-primary">Umum</span></h2>
                                    <p className="text-gray-500 text-sm sm:text-base">Dokumen dan syarat yang wajib dipenuhi oleh seluruh calon peserta didik.</p>
                                </div>
                                
                                <div className="bg-white rounded-2xl sm:rounded-[2rem] p-6 sm:p-8 shadow-lg border border-gray-100">
                                    <div className="space-y-3 sm:space-y-4">
                                        {(persyaratan?.items || []).map((doc, idx) => (
                                            <div key={idx} className="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-gray-50 border border-transparent">
                                                <div className="flex items-start gap-3 sm:gap-4">
                                                    <div className={`mt-1 w-7 h-7 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl flex items-center justify-center flex-shrink-0 ${
                                                        doc.required ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'
                                                    }`}>
                                                        {doc.required ? <Info className="w-3.5 h-3.5 sm:w-4 sm:h-4" /> : <CheckCircle2 className="w-3.5 h-3.5 sm:w-4 sm:h-4" />}
                                                    </div>
                                                    <div className="flex-1">
                                                        <div className="flex items-center justify-between mb-1">
                                                            <span className="font-bold text-gray-900 text-sm sm:text-base">{doc.name}</span>
                                                            {doc.required && (
                                                                <span className="px-1.5 py-0.5 sm:px-2 sm:py-1 bg-white text-red-600 text-[9px] sm:text-[10px] font-bold rounded-md uppercase tracking-wider border border-red-100">Wajib</span>
                                                            )}
                                                        </div>
                                                        <p className="text-xs sm:text-sm text-gray-500">{doc.description}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {persyaratan?.additional_notes && (
                                        <div className="mt-8 p-6 bg-accent-yellow/10 rounded-2xl border border-accent-yellow/20">
                                            <h4 className="font-bold text-yellow-800 mb-3 flex items-center gap-2">
                                                <Info className="w-5 h-5" />
                                                Catatan Penting
                                            </h4>
                                            <SanitizedContent
                                                className="text-sm text-yellow-800/80 leading-relaxed prose prose-sm max-w-none"
                                                html={persyaratan.additional_notes}
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 3. SECTION: ALUR & JADWAL (Timeline) */}
                <section className="py-16 sm:py-24 bg-white overflow-hidden">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-12 sm:mb-20">
                            <h2 className={TYPOGRAPHY.sectionHeading}>Alur & Jadwal <span className="text-primary">Penting</span></h2>
                            <p className={`${TYPOGRAPHY.bodyText} mt-4 sm:mt-6 text-gray-500`}>
                                {jadwal_penting?.description || "Catat tanggal-tanggal penting agar tidak tertinggal proses seleksi."}
                            </p>
                        </div>

                        <div className="relative max-w-6xl mx-auto">
                            {/* Horizontal Line (Desktop) */}
                            <div className="hidden lg:block absolute top-[50px] sm:top-[60px] left-0 w-full h-1 bg-blue-100/50"></div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 relative z-10">
                                {displayTimelineData.map((item, index) => (
                                    <div key={index} className="relative group flex flex-col items-center">
                                        {/* Step Number & Icon */}
                                        <div className={`w-[100px] h-[100px] sm:w-[120px] sm:h-[120px] mx-auto rounded-full flex flex-col items-center justify-center border-[5px] sm:border-[6px] transition-colors duration-300 mb-6 sm:mb-8 bg-white relative z-10 ${
                                            item.status === 'completed' ? 'border-green-100 text-green-600' :
                                            item.status === 'active' ? 'border-blue-100 text-primary shadow-xl shadow-blue-900/10' :
                                            'border-blue-50 text-gray-400'
                                        }`}>
                                            <span className={`text-[10px] sm:text-xs font-bold uppercase tracking-widest mb-1 ${
                                                item.status === 'completed' ? 'text-green-400' :
                                                item.status === 'active' ? 'text-blue-400' :
                                                'text-gray-400'
                                            }`}>Tahap {index + 1}</span>
                                            <Calendar className={`w-6 h-6 sm:w-8 sm:h-8 ${
                                                item.status === 'completed' ? 'text-green-600' :
                                                item.status === 'active' ? 'text-primary' :
                                                'text-gray-400'
                                            }`} />
                                            
                                            {/* Status Badge */}
                                            {item.status === 'active' && (
                                                <div className="absolute -bottom-3 px-2 sm:px-3 py-0.5 sm:py-1 bg-accent-yellow text-yellow-900 text-[9px] sm:text-[10px] font-bold uppercase tracking-wider rounded-full shadow-sm whitespace-nowrap">
                                                    Berlangsung
                                                </div>
                                            )}
                                        </div>
                                        
                                        {/* Content Card */}
                                        <div className="text-center w-full">
                                            <div className={`inline-block px-3 sm:px-4 py-1 sm:py-1.5 rounded-lg font-bold text-xs sm:text-sm mb-3 sm:mb-4 border ${
                                                item.status === 'active' 
                                                    ? 'bg-blue-50 text-primary border-blue-100' 
                                                    : 'bg-gray-50 text-gray-600 border-gray-100'
                                            }`}>
                                                {item.date}
                                            </div>
                                            <h3 className={`font-serif font-bold text-lg sm:text-xl mb-2 sm:mb-3 ${
                                                item.status === 'active' ? 'text-primary' : 'text-gray-900'
                                            }`}>{item.title}</h3>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* 4 & 5. COMBINED SECTION: FAQ & HELP CENTER */}
                <section className="py-20 bg-primary relative overflow-hidden rounded-3xl mx-4 mb-16">
                    {/* Decorative Circles */}
                    <div className="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                    <div className="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/2 translate-y-1/2"></div>

                    <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
                            
                            {/* Left Side: Help Center */}
                            <div className="text-center lg:text-left">
                                <div className="w-16 h-16 bg-accent-yellow rounded-full flex items-center justify-center text-primary shadow-lg mb-6 mx-auto lg:mx-0">
                                    <MessageCircle className="w-8 h-8" />
                                </div>
                                <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
                                    Kesulitan Mendaftar?
                                </h2>
                                <p className="text-blue-100 text-lg mb-10 max-w-xl mx-auto lg:mx-0">
                                    Tim Helpdesk PPDB kami siap membantu Anda setiap hari kerja melalui saluran komunikasi berikut.
                                </p>
                                
                                <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                                    <a 
                                        href={`https://wa.me/${siteSettings?.general?.whatsapp?.replace(/[^0-9]/g, '') || '6281234567890'}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="px-6 py-3.5 bg-green-500 hover:bg-green-600 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2 shadow-lg shadow-green-900/20"
                                    >
                                        <MessageCircle className="w-5 h-5" />
                                        Chat WhatsApp
                                    </a>
                                    <a 
                                        href={`tel:${siteSettings?.general?.phone?.replace(/[^0-9]/g, '') || '0225940262'}`}
                                        className="px-6 py-3.5 bg-white text-primary font-bold rounded-xl hover:bg-gray-100 transition-colors flex items-center justify-center gap-2 shadow-lg"
                                    >
                                        <MessageCircle className="w-5 h-5" />
                                        Telepon Kami
                                    </a>
                                </div>
                            </div>

                            {/* Right Side: FAQ */}
                            <div className="bg-white/10 backdrop-blur-md rounded-3xl p-6 sm:p-8 border border-white/20">
                                <h3 className="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                    <HelpCircle className="w-6 h-6 text-accent-yellow" />
                                    Pertanyaan Umum
                                </h3>
                                <div className="space-y-3">
                                    {displayFaqData.map((faqItem, index) => (
                                        <div 
                                            key={index} 
                                            className="bg-white/5 rounded-xl border border-white/10 overflow-hidden transition-all duration-300"
                                        >
                                            <button
                                                onClick={() => toggleFaq(index)}
                                                className="w-full px-5 py-4 text-left flex items-center justify-between focus:outline-none group"
                                            >
                                                <span className="font-bold text-white text-base leading-snug group-hover:text-accent-yellow transition-colors">{faqItem.question}</span>
                                                {openFaqIndex === index ? (
                                                    <ChevronUp className="w-4 h-4 text-accent-yellow" />
                                                ) : (
                                                    <ChevronDown className="w-4 h-4 text-white/40" />
                                                )}
                                            </button>
                                            <div 
                                                className={`px-5 overflow-hidden transition-all duration-300 ease-in-out ${
                                                    openFaqIndex === index ? 'max-h-96 pb-5 opacity-100' : 'max-h-0 opacity-0'
                                                }`}
                                            >
                                                <p className="text-blue-100/80 text-sm leading-relaxed border-t border-white/10 pt-4">
                                                    {faqItem.answer}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
                socialMediaLinks={navigationData.socialMediaLinks}
            />
        </div>
    );
}
