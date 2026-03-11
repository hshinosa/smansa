import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    BarChart3,
    Calendar,
    BookOpen,
    TrendingUp,
    Filter,
    ChevronRight,
    ArrowUp,
    ArrowDown,
    Minus
} from 'lucide-react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Bar } from 'react-chartjs-2';

import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
);

export default function HasilTkaPage({ tkaGroups = [] }) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const [selectedGroupIndex, setSelectedGroupIndex] = useState(0);
    const activeGroup = tkaGroups[selectedGroupIndex] || null;

    const chartData = useMemo(() => {
        if (!activeGroup) return null;

        const subjects = [...activeGroup.subjects].sort((a, b) => b.average_score - a.average_score);
        
        return {
            labels: subjects.map(s => s.subject_name),
            datasets: [
                {
                    label: 'Nilai Rata-rata',
                    data: subjects.map(s => s.average_score),
                    backgroundColor: '#0D47A1',
                    borderRadius: 4,
                    barThickness: 28,
                },
            ],
        };
    }, [activeGroup]);

    // Hitung max value untuk chart secara dinamis
    const chartMaxValue = useMemo(() => {
        if (!activeGroup) return 100;
        const scores = activeGroup.subjects.map(s => parseFloat(s.average_score));
        const maxScore = Math.max(...scores);
        // Round up ke kelipatan 100 terdekat untuk tampilan yang rapi
        return Math.ceil(maxScore / 100) * 100 + 50;
    }, [activeGroup]);

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#fff',
                titleColor: '#1e293b',
                bodyColor: '#475569',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                padding: 12,
                callbacks: {
                    label: (context) => ` Rata-rata: ${context.raw}`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                suggestedMax: chartMaxValue,
                grid: { color: '#f1f5f9' },
                ticks: { font: { family: "'Plus Jakarta Sans'" } },
                border: { display: false }
            },
            x: {
                grid: { display: false },
                ticks: { 
                    maxRotation: 45,
                    minRotation: 0,
                    font: { size: 11, family: "'Plus Jakarta Sans'" }
                },
                border: { display: false }
            }
        }
    };

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

    // Hitung statistik ringkas
    const summaryStats = useMemo(() => {
        if (!activeGroup) return null;
        const scores = activeGroup.subjects.map(s => parseFloat(s.average_score));
        const avg = scores.length > 0 ? (scores.reduce((a, b) => a + b, 0) / scores.length).toFixed(1) : 0;
        const max = scores.length > 0 ? Math.max(...scores) : 0;
        const min = scores.length > 0 ? Math.min(...scores) : 0;
        return { avg, max, min, total: scores.length };
    }, [activeGroup]);

    const getScoreIndicator = (score) => {
        const val = parseFloat(score);
        if (val >= 75) return <ArrowUp className="w-4 h-4 text-emerald-500" />;
        if (val >= 50) return <Minus className="w-4 h-4 text-amber-500" />;
        return <ArrowDown className="w-4 h-4 text-red-500" />;
    };

    return (
        <div className="bg-white min-h-screen font-sans text-gray-800 flex flex-col">
            <SEOHead 
                title={`Hasil TKA - ${siteName}`}
                description={`Rekapitulasi nilai rata-rata Tes Kompetensi Akademik siswa ${siteName}.`}
                keywords="hasil tka, nilai rata-rata, statistik akademik, UTBK"
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
                            {renderHighlightedTitle('Hasil Nilai TKA')}
                        </h1>
                        <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                            Analisis capaian akademik siswa melalui Tes Kompetensi Akademik
                        </p>
                    </div>
                </section>

                {/* CONTENT SECTION */}
                <section className="py-16 bg-gray-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        
                        {tkaGroups.length > 0 ? (
                            <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                                
                                {/* SIDEBAR */}
                                <div className="lg:col-span-1 space-y-6">
                                    {/* Filter Periode */}
                                    <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                        <div className="p-4 border-b border-gray-100">
                                            <h3 className="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                                                <Filter className="w-4 h-4 text-primary" />
                                                Pilih Periode
                                            </h3>
                                        </div>
                                        <div className="p-2">
                                            {tkaGroups.map((group, idx) => (
                                                <button
                                                    key={`${group.academic_year}-${group.exam_type}`}
                                                    onClick={() => setSelectedGroupIndex(idx)}
                                                    className={`w-full text-left p-3 rounded-xl text-sm transition-colors ${
                                                        selectedGroupIndex === idx 
                                                        ? 'bg-primary text-white' 
                                                        : 'text-gray-700 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    <div className="font-semibold">{group.exam_type}</div>
                                                    <div className={`text-xs mt-0.5 ${selectedGroupIndex === idx ? 'text-blue-200' : 'text-gray-400'}`}>
                                                        Tahun {group.academic_year}
                                                    </div>
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Summary Stats */}
                                    {summaryStats && (
                                        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                                            <h3 className="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Ringkasan</h3>
                                            <div className="space-y-3">
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-gray-500">Rata-rata Keseluruhan</span>
                                                    <span className="font-bold text-primary">{summaryStats.avg}</span>
                                                </div>
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-gray-500">Nilai Tertinggi</span>
                                                    <span className="font-bold text-emerald-600">{summaryStats.max}</span>
                                                </div>
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-gray-500">Nilai Terendah</span>
                                                    <span className="font-bold text-gray-600">{summaryStats.min}</span>
                                                </div>
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-gray-500">Jumlah Mapel</span>
                                                    <span className="font-bold text-gray-900">{summaryStats.total}</span>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Info */}
                                    <div className="bg-blue-50 rounded-2xl p-5 border border-blue-100 mb-6">
                                        <p className="text-xs text-blue-700 leading-relaxed">
                                            Data menampilkan nilai rata-rata gabungan seluruh siswa pada periode ujian yang dipilih.
                                        </p>
                                    </div>

                                    {/* Quick Links */}
                                    <div className="bg-gray-50 rounded-2xl p-6 shadow-sm border border-gray-100">
                                        <h3 className="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Lihat Juga</h3>
                                        <div className="space-y-2">
                                            <Link 
                                                href="/akademik/prestasi-akademik/serapan-ptn" 
                                                className="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-200 text-gray-700 hover:border-primary hover:text-primary transition-colors"
                                            >
                                                <span className="font-medium text-sm">Data Serapan PTN</span>
                                                <ChevronRight className="w-4 h-4" />
                                            </Link>
                                            <Link 
                                                href="/alumni" 
                                                className="flex items-center justify-between p-3 bg-white rounded-xl border border-gray-200 text-gray-700 hover:border-primary hover:text-primary transition-colors"
                                            >
                                                <span className="font-medium text-sm">Cerita Alumni</span>
                                                <ChevronRight className="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                {/* MAIN CONTENT */}
                                <div className="lg:col-span-3 space-y-6">
                                    {activeGroup && chartData ? (
                                        <>
                                            {/* Header */}
                                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                                <div>
                                                    <span className="text-xs font-bold text-primary uppercase tracking-wider">{activeGroup.academic_year}</span>
                                                    <h2 className="text-2xl font-bold text-gray-900">{activeGroup.exam_type}</h2>
                                                </div>
                                                <div className="bg-white px-4 py-2 rounded-xl border border-gray-200 shadow-sm">
                                                    <span className="text-2xl font-bold text-primary">{activeGroup.subjects.length}</span>
                                                    <span className="text-sm text-gray-500 ml-1">Mata Pelajaran</span>
                                                </div>
                                            </div>

                                            {/* Chart Card */}
                                            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                                                <h3 className="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                                                    <BarChart3 className="w-4 h-4 text-primary" />
                                                    Grafik Nilai Rata-rata
                                                </h3>
                                                <div className="h-[350px]">
                                                    <Bar data={chartData} options={chartOptions} />
                                                </div>
                                            </div>

                                            {/* Table Card */}
                                            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                                                <div className="px-6 py-4 border-b border-gray-100">
                                                    <h3 className="text-sm font-bold text-gray-900 flex items-center gap-2">
                                                        <BookOpen className="w-4 h-4 text-primary" />
                                                        Rincian Per Mata Pelajaran
                                                    </h3>
                                                </div>
                                                <div className="overflow-x-auto">
                                                    <table className="w-full text-sm">
                                                        <thead className="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                                                            <tr>
                                                                <th className="px-6 py-3 text-left font-semibold">Mata Pelajaran</th>
                                                                <th className="px-6 py-3 text-right font-semibold">Nilai Rata-rata</th>
                                                                <th className="px-6 py-3 text-center font-semibold w-20">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-gray-100">
                                                            {[...activeGroup.subjects]
                                                                .sort((a, b) => b.average_score - a.average_score)
                                                                .map((subject, idx) => (
                                                                <tr key={idx} className="hover:bg-gray-50 transition-colors">
                                                                    <td className="px-6 py-4 font-medium text-gray-900">
                                                                        {subject.subject_name}
                                                                    </td>
                                                                    <td className="px-6 py-4 text-right">
                                                                        <span className="font-bold text-gray-900 text-base">{subject.average_score}</span>
                                                                    </td>
                                                                    <td className="px-6 py-4">
                                                                        <div className="flex justify-center">
                                                                            {getScoreIndicator(subject.average_score)}
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-16 text-center">
                                            <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <BarChart3 className="w-8 h-8 text-gray-400" />
                                            </div>
                                            <h3 className="text-lg font-bold text-gray-900 mb-2">Pilih Periode</h3>
                                            <p className="text-gray-500">Silakan pilih periode ujian di sebelah kiri.</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-16 text-center">
                                <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <BarChart3 className="w-8 h-8 text-gray-400" />
                                </div>
                                <h2 className="text-xl font-bold text-gray-900 mb-2">Belum Ada Data TKA</h2>
                                <p className="text-gray-600 max-w-md mx-auto">
                                    Admin sekolah belum mengunggah data hasil TKA.
                                </p>
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
