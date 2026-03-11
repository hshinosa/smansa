import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    GraduationCap,
    TrendingUp,
    Award,
    Building2,
    ChevronDown,
    Users,
    BarChart3,
    Calendar,
    Trophy,
    ChevronRight,
    ExternalLink
} from 'lucide-react';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    Title,
    BarElement,
} from 'chart.js';
import { Bar } from 'react-chartjs-2';

import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';

ChartJS.register(
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    Title,
    BarElement
);

// Accordion Item untuk PTN
const PtnAccordionItem = ({ ptn, isOpen, onToggle }) => {
    return (
        <div className="border-b border-gray-100 last:border-b-0">
            <button 
                onClick={onToggle}
                className="w-full flex items-center justify-between py-4 px-1 text-left group"
            >
                <div className="flex items-center gap-4">
                    <div 
                        className="w-3 h-3 rounded-full shrink-0" 
                        style={{ backgroundColor: ptn.color || '#0D47A1' }}
                    />
                    <span className="font-semibold text-gray-800 group-hover:text-primary transition-colors">
                        {ptn.name}
                    </span>
                </div>
                <div className="flex items-center gap-3">
                    <span className="text-sm font-bold text-primary bg-blue-50 px-3 py-1 rounded-full">
                        {ptn.count} siswa
                    </span>
                    <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} />
                </div>
            </button>
            
            {isOpen && ptn.majors && ptn.majors.length > 0 && (
                <div className="pb-4 pl-7">
                    <div className="bg-gray-50 rounded-xl p-4">
                        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Program Studi</p>
                        <div className="space-y-2">
                            {ptn.majors.map((major, idx) => (
                                <div key={idx} className="flex justify-between items-center text-sm">
                                    <span className="text-gray-700">{major.name}</span>
                                    <span className="font-semibold text-gray-900">{major.count}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

// Batch Card dengan gaya FAQ
const BatchCard = ({ data, isExpanded, onToggle }) => {
    const [openPtnId, setOpenPtnId] = useState(null);

    const chartData = {
        labels: data.byPTN?.slice(0, 10).map(item => item.name) || [],
        datasets: [{
            label: 'Jumlah Siswa',
            data: data.byPTN?.slice(0, 10).map(item => item.count) || [],
            backgroundColor: data.byPTN?.slice(0, 10).map(item => item.color || '#0D47A1') || [],
            borderRadius: 4,
            barThickness: 20,
        }],
    };

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1e293b',
                bodyColor: '#475569',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                padding: 12,
                displayColors: true,
                callbacks: {
                    label: (context) => ` ${context.raw} Siswa`
                }
            }
        },
        scales: {
            x: { 
                beginAtZero: true, 
                grid: { display: false },
                ticks: { display: false }
            },
            y: { 
                grid: { display: false },
                ticks: { 
                    font: { size: 11, family: "'Plus Jakarta Sans'" },
                    color: '#64748b'
                }
            }
        }
    };

    const typeStyles = {
        'SNBP': 'bg-blue-600',
        'SNBT': 'bg-emerald-600',
        'Mandiri': 'bg-purple-600'
    };

    return (
        <div className="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-md">
            {/* Header - Samakan gaya FAQ */}
            <button 
                onClick={onToggle}
                className="w-full p-6 flex items-center justify-between text-left focus:outline-none"
            >
                <div className="flex items-center gap-4">
                    <div className={`w-12 h-12 ${typeStyles[data.type] || 'bg-gray-600'} rounded-xl flex items-center justify-center shadow-sm`}>
                        <GraduationCap className="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2 mb-0.5">
                            <span className="text-xs font-bold text-gray-400 uppercase tracking-wider">{data.type}</span>
                        </div>
                        <h3 className="text-xl font-bold text-gray-900 font-serif">Angkatan {data.year} ({data.total} Siswa)</h3>
                    </div>
                </div>
                {isExpanded ? (
                    <ChevronDown className="w-5 h-5 text-primary rotate-180 transition-transform duration-300" />
                ) : (
                    <ChevronDown className="w-5 h-5 text-gray-400 transition-transform duration-300" />
                )}
            </button>

            {/* Expanded Content - Samakan transisi FAQ */}
            <div 
                className={`overflow-hidden transition-all duration-300 ease-in-out ${
                    isExpanded ? 'max-h-[1000px] opacity-100' : 'max-h-0 opacity-0'
                }`}
            >
                <div className="border-t border-gray-200 bg-white">
                    <div className="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-100">
                        {/* Chart Section */}
                        <div className="p-6">
                            <h4 className="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 uppercase tracking-wide">
                                <BarChart3 className="w-4 h-4 text-primary" />
                                Top 10 PTN
                            </h4>
                            {data.byPTN?.length > 0 ? (
                                <div className="h-[300px]">
                                    <Bar data={chartData} options={chartOptions} />
                                </div>
                            ) : (
                                <div className="h-[300px] flex items-center justify-center text-gray-400 text-sm">
                                    Data tidak tersedia
                                </div>
                            )}
                        </div>

                        {/* List Section */}
                        <div className="p-6">
                            <h4 className="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 uppercase tracking-wide">
                                <Building2 className="w-4 h-4 text-primary" />
                                Daftar Lengkap PTN
                            </h4>
                            <div className="max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                                {data.byPTN?.map((ptn) => (
                                    <PtnAccordionItem 
                                        key={ptn.id}
                                        ptn={ptn}
                                        isOpen={openPtnId === ptn.id}
                                        onToggle={() => setOpenPtnId(openPtnId === ptn.id ? null : ptn.id)}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default function PrestasiAkademikPage({ batches = [], universities = [], stats = {}, ptnFavorites = [] }) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const [expandedBatchId, setExpandedBatchId] = useState(() => {
        return batches.length > 0 ? batches[0].id : null;
    });

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

    const totalAdmissions = stats.totalAdmissions || 0;
    const totalPTN = stats.totalPtn || 0;

    // Yearly trend data untuk chart kecil
    const yearlyData = useMemo(() => {
        const grouped = {};
        batches.forEach(batch => {
            if (!grouped[batch.year]) grouped[batch.year] = { snbp: 0, snbt: 0 };
            if (batch.type === 'SNBP') grouped[batch.year].snbp += batch.total;
            if (batch.type === 'SNBT') grouped[batch.year].snbt += batch.total;
        });
        return Object.entries(grouped)
            .map(([year, data]) => ({ year, ...data, total: data.snbp + data.snbt }))
            .sort((a, b) => b.year - a.year);
    }, [batches]);

    const hasData = batches.length > 0;

    return (
        <div className="bg-white min-h-screen font-sans text-gray-800 flex flex-col">
            <SEOHead 
                title={`Data Serapan PTN - ${siteName}`}
                description={`Data statistik penerimaan siswa ${siteName} di Perguruan Tinggi Negeri melalui SNBP dan SNBT.`}
                keywords="prestasi akademik, SNBP, SNBT, PTN, alumni, SMAN 1 Baleendah"
                image={heroImage}
            />

            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20 flex-grow" tabIndex="-1">
                {/* HERO SECTION - Konsisten dengan halaman lain */}
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
                            {renderHighlightedTitle('Data Serapan PTN')}
                        </h1>
                        <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                            Rekam jejak penerimaan siswa di Perguruan Tinggi Negeri terbaik Indonesia
                        </p>
                    </div>
                </section>

                {/* STATS SECTION */}
                <section className="py-16 bg-gray-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                            <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                        <GraduationCap className="w-5 h-5 text-primary" />
                                    </div>
                                </div>
                                <p className="text-3xl font-bold text-gray-900">{totalAdmissions}</p>
                                <p className="text-sm text-gray-500 mt-1">Total Diterima</p>
                            </div>

                            <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                        <Building2 className="w-5 h-5 text-emerald-600" />
                                    </div>
                                </div>
                                <p className="text-3xl font-bold text-gray-900">{totalPTN}+</p>
                                <p className="text-sm text-gray-500 mt-1">PTN Tujuan</p>
                            </div>

                            <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                                        <Award className="w-5 h-5 text-amber-600" />
                                    </div>
                                </div>
                                <p className="text-3xl font-bold text-gray-900">{batches.filter(b => b.type === 'SNBP').reduce((a, b) => a + b.total, 0)}</p>
                                <p className="text-sm text-gray-500 mt-1">Via SNBP</p>
                            </div>

                            <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                                <div className="flex items-center gap-3 mb-3">
                                    <div className="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                        <TrendingUp className="w-5 h-5 text-purple-600" />
                                    </div>
                                </div>
                                <p className="text-3xl font-bold text-gray-900">{batches.filter(b => b.type === 'SNBT').reduce((a, b) => a + b.total, 0)}</p>
                                <p className="text-sm text-gray-500 mt-1">Via SNBT</p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* MAIN CONTENT */}
                <section className="py-16 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        {hasData ? (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Sidebar - PTN Favorit & Trend */}
                                <div className="lg:col-span-1 space-y-6">
                                    {/* PTN Favorit */}
                                    {ptnFavorites.length > 0 && (
                                        <div className="bg-primary rounded-2xl p-6 text-white">
                                            <h3 className="text-lg font-bold mb-4 flex items-center gap-2">
                                                <Trophy className="w-5 h-5 text-accent-yellow" />
                                                PTN Favorit
                                            </h3>
                                            <div className="space-y-3">
                                                {ptnFavorites.slice(0, 5).map((ptn, idx) => (
                                                    <div key={idx} className="flex items-center gap-3">
                                                        <span className="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center text-xs font-bold">
                                                            {idx + 1}
                                                        </span>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="font-medium text-sm truncate">{ptn.name}</p>
                                                        </div>
                                                        <span className="text-xs bg-white/20 px-2 py-1 rounded-full">
                                                            {ptn.total}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Trend per Tahun */}
                                    {yearlyData.length > 0 && (
                                        <div className="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm">
                                            <h3 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                                                <Calendar className="w-5 h-5 text-primary" />
                                                Rekap Tahunan
                                            </h3>
                                            <div className="space-y-3">
                                                {yearlyData.slice(0, 5).map((year, idx) => (
                                                    <div key={idx} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                                        <span className="font-semibold text-gray-900">{year.year}</span>
                                                        <div className="flex items-center gap-2 text-sm">
                                                            <span className="bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-medium">{year.snbp}</span>
                                                            <span className="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-medium">{year.snbt}</span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {/* Quick Links */}
                                    <div className="bg-gray-50 rounded-2xl p-6 shadow-sm border border-gray-100">
                                        <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Lihat Juga</h3>
                                        <div className="space-y-2">
                                            <Link 
                                                href="/akademik/prestasi-akademik/hasil-tka" 
                                                className="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-200 shadow-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                                            >
                                                <span className="font-medium text-sm">Hasil Nilai TKA</span>
                                                <ChevronRight className="w-4 h-4" />
                                            </Link>
                                            <Link 
                                                href="/alumni" 
                                                className="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-200 shadow-sm text-gray-700 hover:border-primary hover:text-primary transition-colors"
                                            >
                                                <span className="font-medium text-sm">Cerita Alumni</span>
                                                <ChevronRight className="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                {/* Main Content - Batch List */}
                                <div className="lg:col-span-2 space-y-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <h2 className={`${TYPOGRAPHY.sectionHeading}`}>Rincian Data</h2>
                                        <div className="flex gap-2 text-xs">
                                            <span className="flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2 py-1 rounded-full font-medium">
                                                <span className="w-2 h-2 bg-blue-600 rounded-full"></span> SNBP
                                            </span>
                                            <span className="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2 py-1 rounded-full font-medium">
                                                <span className="w-2 h-2 bg-emerald-600 rounded-full"></span> SNBT
                                            </span>
                                        </div>
                                    </div>

                                    {batches.map((batch) => (
                                        <BatchCard 
                                            key={batch.id}
                                            data={batch}
                                            isExpanded={expandedBatchId === batch.id}
                                            onToggle={() => setExpandedBatchId(expandedBatchId === batch.id ? null : batch.id)}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="text-center py-20">
                                <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <BarChart3 className="w-8 h-8 text-gray-400" />
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-2">Data Belum Tersedia</h3>
                                <p className="text-gray-600">Admin belum mengunggah data serapan PTN.</p>
                            </div>
                        )}
                    </div>
                </section>

                {/* CTA SECTION - Konsisten dengan Landing Page */}
                <section className="py-20 bg-primary relative overflow-hidden mt-16 rounded-3xl mx-4 mb-16">
                    {/* Decorative Circles */}
                    <div className="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                    <div className="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/2 translate-y-1/2"></div>

                    <div className="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
                        <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
                            Siap Menjadi Bagian dari Kami?
                        </h2>
                        <p className="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
                            Bergabunglah dengan {siteName} dan raih impianmu masuk ke Perguruan Tinggi Negeri favorit.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-4 justify-center">
                            <Link 
                                href="/informasi-spmb" 
                                className="px-8 py-4 bg-accent-yellow text-gray-900 font-bold rounded-xl hover:bg-yellow-400 transition-colors shadow-lg"
                            >
                                Informasi PPDB
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
        </div>
    );
}
