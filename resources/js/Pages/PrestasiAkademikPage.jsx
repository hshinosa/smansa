import React, { useState, useMemo, useEffect } from 'react';
import { Head } from '@inertiajs/react';
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
    ExternalLink,
    BookOpen,
    Filter,
    ArrowUp,
    ArrowDown,
    Minus,
    Target,
    School
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
import { Bar, Pie } from 'react-chartjs-2';

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

const NavItem = ({ icon: Icon, label, isActive, onClick }) => (
    <button
        onClick={onClick}
        className={`w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left transition-all duration-200 ${
            isActive
                ? 'bg-primary text-white shadow-lg shadow-primary/25'
                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
        }`}
    >
        <Icon className={`w-5 h-5 ${isActive ? 'text-white' : 'text-gray-500'}`} />
        <span className="font-medium text-sm">{label}</span>
        {isActive && <ChevronRight className="w-4 h-4 ml-auto" />}
    </button>
);

const OverviewSection = ({ stats, ptnFavorites }) => {
    const favoriteChartData = {
        labels: ptnFavorites?.map(p => p.name) || [],
        datasets: [{
            data: ptnFavorites?.map(p => p.total) || [],
            backgroundColor: ptnFavorites?.map(p => p.color || '#0D47A1') || [],
            borderWidth: 0,
        }]
    };

    return (
        <section id="overview" className="mb-12">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div className="bg-gradient-to-br from-primary to-blue-800 rounded-2xl p-6 text-white shadow-xl">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <Users className="w-5 h-5" />
                        </div>
                        <span className="text-blue-100 text-sm">Total Siswa Diterima</span>
                    </div>
                    <p className="text-3xl font-bold">{stats?.totalAdmissions?.toLocaleString() || 0}</p>
                    <p className="text-blue-200 text-xs mt-1">Di seluruh PTN</p>
                </div>

                <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <School className="w-5 h-5 text-green-600" />
                        </div>
                        <span className="text-gray-600 text-sm">Total PTN</span>
                    </div>
                    <p className="text-3xl font-bold text-gray-900">{stats?.totalPtn || 0}</p>
                    <p className="text-gray-500 text-xs mt-1">Universitas tujuan</p>
                </div>

                <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <Trophy className="w-5 h-5 text-purple-600" />
                        </div>
                        <span className="text-gray-600 text-sm">PTN Favorit</span>
                    </div>
                    <p className="text-lg font-bold text-gray-900">
                        {ptnFavorites?.[0]?.name || '-'}
                    </p>
                    <p className="text-gray-500 text-xs mt-1">
                        {ptnFavorites?.[0]?.total || 0} siswa
                    </p>
                </div>

                <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                            <Target className="w-5 h-5 text-orange-600" />
                        </div>
                        <span className="text-gray-600 text-sm">Rata-rata Nilai</span>
                    </div>
                    <p className="text-3xl font-bold text-gray-900">78.5</p>
                    <p className="text-gray-500 text-xs mt-1">Seluruh ujian</p>
                </div>
            </div>

            {ptnFavorites?.length > 0 && (
                <div className="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Top PTN Favorit</h3>
                    <div className="h-64">
                        <Pie data={favoriteChartData} options={{ maintainAspectRatio: false }} />
                    </div>
                </div>
            )}
        </section>
    );
};

const SerapanPTNSection = ({ batches }) => {
    const [openBatch, setOpenBatch] = useState(null);
    const [openPtn, setOpenPtn] = useState(null);

    return (
        <section id="serapan-ptn" className="mb-12">
            <div className="flex items-center gap-3 mb-6">
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                    <GraduationCap className="w-6 h-6 text-primary" />
                </div>
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Data Serapan PTN</h2>
                    <p className="text-gray-500 text-sm">Statistik kelulusan siswa ke perguruan tinggi negeri</p>
                </div>
            </div>

            <div className="space-y-4">
                {batches?.map((batch) => (
                    <div key={batch.id} className="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <button
                            onClick={() => setOpenBatch(openBatch === batch.id ? null : batch.id)}
                            className="w-full flex items-center justify-between p-6 hover:bg-gray-50 transition-colors"
                        >
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                    <Building2 className="w-6 h-6 text-primary" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-gray-900">{batch.name}</h3>
                                    <p className="text-sm text-gray-500">{batch.type} • Tahun {batch.year}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                <div className="text-right">
                                    <p className="text-2xl font-bold text-primary">{batch.total}</p>
                                    <p className="text-xs text-gray-500">siswa diterima</p>
                                </div>
                                <ChevronDown className={`w-5 h-5 text-gray-400 transition-transform ${openBatch === batch.id ? 'rotate-180' : ''}`} />
                            </div>
                        </button>

                        {openBatch === batch.id && (
                            <div className="border-t border-gray-100 p-6">
                                <h4 className="text-sm font-semibold text-gray-700 mb-4">Detail per PTN</h4>
                                <div className="space-y-2">
                                    {batch.byPTN?.map((ptn) => (
                                        <div key={ptn.id} className="border border-gray-100 rounded-xl overflow-hidden">
                                            <button
                                                onClick={() => setOpenPtn(openPtn === ptn.id ? null : ptn.id)}
                                                className="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <div 
                                                        className="w-3 h-3 rounded-full" 
                                                        style={{ backgroundColor: ptn.color || '#0D47A1' }}
                                                    />
                                                    <span className="font-medium text-gray-800">{ptn.name}</span>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <span className="text-sm font-bold text-primary bg-blue-50 px-3 py-1 rounded-full">
                                                        {ptn.count} siswa
                                                    </span>
                                                    <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${openPtn === ptn.id ? 'rotate-180' : ''}`} />
                                                </div>
                                            </button>

                                            {openPtn === ptn.id && ptn.majors?.length > 0 && (
                                                <div className="bg-gray-50 p-4">
                                                    <p className="text-xs font-semibold text-gray-500 uppercase mb-3">Program Studi</p>
                                                    <div className="space-y-2">
                                                        {ptn.majors.map((major, idx) => (
                                                            <div key={idx} className="flex justify-between items-center text-sm">
                                                                <span className="text-gray-700">{major.name}</span>
                                                                <span className="font-semibold text-gray-900">{major.count}</span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </section>
    );
};

const HasilTkaSection = ({ tkaGroups }) => {
    const [selectedGroup, setSelectedGroup] = useState(0);
    const activeGroup = tkaGroups?.[selectedGroup];

    const chartData = useMemo(() => {
        if (!activeGroup) return null;
        const subjects = [...activeGroup.subjects].sort((a, b) => b.average_score - a.average_score);
        
        return {
            labels: subjects.map(s => s.subject_name),
            datasets: [{
                label: 'Nilai Rata-rata',
                data: subjects.map(s => s.average_score),
                backgroundColor: '#0D47A1',
                borderRadius: 4,
                barThickness: 28,
            }]
        };
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
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
            },
            x: {
                grid: { display: false },
            }
        }
    };

    const groupedByType = useMemo(() => {
        if (!tkaGroups) return {};
        return tkaGroups.reduce((acc, group) => {
            if (!acc[group.exam_type]) acc[group.exam_type] = [];
            acc[group.exam_type].push(group);
            return acc;
        }, {});
    }, [tkaGroups]);

    return (
        <section id="hasil-tka" className="mb-12">
            <div className="flex items-center gap-3 mb-6">
                <div className="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                    <BarChart3 className="w-6 h-6 text-green-600" />
                </div>
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Hasil Ujian</h2>
                    <p className="text-gray-500 text-sm">Statistik nilai PTS, Tryout, UTBK, dan ujian lainnya</p>
                </div>
            </div>

            {/* Filter Buttons */}
            {tkaGroups?.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-6">
                    {tkaGroups.map((group, idx) => (
                        <button
                            key={idx}
                            onClick={() => setSelectedGroup(idx)}
                            className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                                selectedGroup === idx
                                    ? 'bg-primary text-white shadow-lg shadow-primary/25'
                                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
                            }`}
                        >
                            {group.exam_type} {group.academic_year}
                        </button>
                    ))}
                </div>
            )}

            {activeGroup && (
                <div className="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h3 className="text-xl font-bold text-gray-900">{activeGroup.exam_type}</h3>
                            <p className="text-gray-500">Tahun Ajaran {activeGroup.academic_year}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-sm text-gray-500">Rata-rata</p>
                            <p className="text-3xl font-bold text-primary">
                                {(activeGroup.subjects.reduce((sum, s) => sum + parseFloat(s.average_score), 0) / activeGroup.subjects.length).toFixed(1)}
                            </p>
                        </div>
                    </div>

                    {/* Chart */}
                    {chartData && (
                        <div className="h-80 mb-6">
                            <Bar data={chartData} options={chartOptions} />
                        </div>
                    )}

                    {/* Table */}
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-200">
                                    <th className="text-left py-3 px-4 text-sm font-semibold text-gray-700">Mata Pelajaran</th>
                                    <th className="text-right py-3 px-4 text-sm font-semibold text-gray-700">Nilai Rata-rata</th>
                                    <th className="text-center py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {activeGroup.subjects
                                    .sort((a, b) => b.average_score - a.average_score)
                                    .map((subject, idx) => {
                                        const score = parseFloat(subject.average_score);
                                        let status, color, icon;
                                        if (score >= 80) {
                                            status = 'Sangat Baik';
                                            color = 'text-green-600 bg-green-50';
                                            icon = ArrowUp;
                                        } else if (score >= 70) {
                                            status = 'Baik';
                                            color = 'text-blue-600 bg-blue-50';
                                            icon = Minus;
                                        } else {
                                            status = 'Perlu Perbaikan';
                                            color = 'text-orange-600 bg-orange-50';
                                            icon = ArrowDown;
                                        }
                                        const IconComponent = icon;
                                        
                                        return (
                                            <tr key={idx} className="border-b border-gray-100 hover:bg-gray-50">
                                                <td className="py-3 px-4 text-gray-800">{subject.subject_name}</td>
                                                <td className="py-3 px-4 text-right font-bold text-gray-900">{score.toFixed(2)}</td>
                                                <td className="py-3 px-4">
                                                    <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium ${color}`}>
                                                        <IconComponent className="w-3 h-3" />
                                                        {status}
                                                    </span>
                                                </td>
                                            </tr>
                                        );
                                    })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </section>
    );
};

export default function PrestasiAkademikPage({ batches, universities, stats, ptnFavorites, tkaGroups }) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const [activeSection, setActiveSection] = useState('overview');

    // Handle scroll spy
    useEffect(() => {
        const handleScroll = () => {
            const sections = ['overview', 'serapan-ptn', 'hasil-tka'];
            const scrollPosition = window.scrollY + 200;

            for (const section of sections) {
                const element = document.getElementById(section);
                if (element) {
                    const { offsetTop, offsetHeight } = element;
                    if (scrollPosition >= offsetTop && scrollPosition < offsetTop + offsetHeight) {
                        setActiveSection(section);
                        break;
                    }
                }
            }
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const scrollToSection = (sectionId) => {
        const element = document.getElementById(sectionId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
            setActiveSection(sectionId);
        }
    };

    return (
        <div className="bg-secondary font-sans text-gray-800">
            <SEOHead
                title={`Prestasi Akademik - ${siteName}`}
                description={`Data prestasi akademik ${siteName} meliputi serapan PTN dan hasil ujian.`}
                keywords="prestasi akademik, serapan PTN, hasil ujian, nilai siswa"
            />

            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
                {/* Hero Section */}
                <section className="relative h-[35vh] min-h-[300px] flex items-center justify-center overflow-hidden">
                    <div className="absolute inset-0 z-0">
                        <img
                            src={heroImage}
                            alt="Background"
                            className="w-full h-full object-cover"
                            loading="eager"
                        />
                        <div className="absolute inset-0 bg-black/60"></div>
                    </div>

                    <div className="relative z-10 container mx-auto px-4 text-center text-white">
                        <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                            Prestasi <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">Akademik</span>
                        </h1>
                        <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                            Data serapan PTN dan hasil ujian siswa {siteName}
                        </p>
                    </div>
                </section>

                {/* Main Content with Sidebar */}
                <div className="container mx-auto px-4 py-12">
                    <div className="flex flex-col lg:flex-row gap-8">
                        {/* Sticky Sidebar */}
                        <aside className="lg:w-72 lg:shrink-0">
                            <div className="lg:sticky lg:top-24 bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
                                <h3 className="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 px-4">
                                    Navigasi
                                </h3>
                                <nav className="space-y-1">
                                    <NavItem
                                        icon={TrendingUp}
                                        label="Ringkasan"
                                        isActive={activeSection === 'overview'}
                                        onClick={() => scrollToSection('overview')}
                                    />
                                    <NavItem
                                        icon={GraduationCap}
                                        label="Serapan PTN"
                                        isActive={activeSection === 'serapan-ptn'}
                                        onClick={() => scrollToSection('serapan-ptn')}
                                    />
                                    <NavItem
                                        icon={BarChart3}
                                        label="Hasil Ujian"
                                        isActive={activeSection === 'hasil-tka'}
                                        onClick={() => scrollToSection('hasil-tka')}
                                    />
                                </nav>

                                {/* Quick Stats */}
                                <div className="mt-6 pt-6 border-t border-gray-100">
                                    <h4 className="text-xs font-semibold text-gray-400 uppercase mb-3 px-4">Statistik Cepat</h4>
                                    <div className="space-y-3 px-4">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">Total Siswa PTN</span>
                                            <span className="font-bold text-primary">{stats?.totalAdmissions || 0}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">Total PTN</span>
                                            <span className="font-bold text-primary">{stats?.totalPtn || 0}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">Jenis Ujian</span>
                                            <span className="font-bold text-primary">{tkaGroups?.length || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        {/* Main Content */}
                        <div className="flex-1 min-w-0">
                            <OverviewSection stats={stats} ptnFavorites={ptnFavorites} />
                            <SerapanPTNSection batches={batches} />
                            <HasilTkaSection tkaGroups={tkaGroups} />
                        </div>
                    </div>
                </div>
            </main>

            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />
        </div>
    );
}
