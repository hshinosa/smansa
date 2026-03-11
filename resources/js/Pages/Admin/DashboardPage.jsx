// FILE: resources/js/Pages/Admin/DashboardPage.jsx
// Fully responsive admin dashboard for desktop, tablet, and mobile

import React, { useState, useEffect, useCallback } from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import axios from 'axios';
import Modal from '@/Components/Modal';
import { Search, X, CalendarDays, ChevronLeft, ChevronRight, ExternalLink, AlertCircle, Info, Clock, Mail, Eye, FileText, Users, LayoutGrid } from 'lucide-react';
import { Line } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

// Responsive Stat Card Component
const StatCard = ({ title, value, icon: Icon, href, color = 'blue' }) => {
    const colorClasses = {
        blue: 'bg-accent-yellow/10 text-accent-yellow',
        green: 'bg-green-50 text-green-600',
        yellow: 'bg-accent-yellow/10 text-accent-yellow',
        purple: 'bg-purple-50 text-purple-600',
    };

    return (
        <Link 
            href={href}
            className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 flex items-center gap-3 sm:gap-4 hover:shadow-md hover:border-accent-yellow/20 transition-all duration-200 group"
        >
            <div className={`p-3 sm:p-4 rounded-xl ${colorClasses[color]} group-hover:scale-110 transition-transform flex-shrink-0`}>
                <Icon size={24} className="sm:w-7 sm:h-7" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-xs sm:text-sm text-gray-500 font-medium">{title}</p>
                <p className="text-xl sm:text-2xl font-bold text-gray-900 mt-0.5 sm:mt-1">{value}</p>
            </div>
            <ExternalLink size={18} className="text-gray-400 group-hover:text-accent-yellow transition-colors flex-shrink-0" />
        </Link>
    );
};

// Responsive Chart Component
const VisitorStatsChart = ({ chartData, loading, error, periodType }) => {
    if (loading) {
        return (
            <div className="w-full h-64 sm:h-72 lg:h-80 bg-gray-100 rounded-xl flex flex-col items-center justify-center text-gray-500 p-4">
                <div className="w-8 h-8 sm:w-10 sm:h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mb-3"></div>
                <p className="text-sm font-medium">Memuat data chart...</p>
            </div>
        );
    }

    if (error && (!chartData || !chartData.labels || chartData.labels.length === 0)) {
        return (
            <div className="w-full h-64 sm:h-72 lg:h-80 bg-red-50 border border-red-200 rounded-xl flex flex-col items-center justify-center text-red-600 p-4 sm:p-6 text-center">
                <AlertCircle className="w-10 h-10 sm:w-12 sm:h-12 text-red-400 mb-3" />
                <p className="font-semibold text-base sm:text-lg">Gagal memuat data chart</p>
                <p className="text-sm text-red-500 mt-1">{error}</p>
            </div>
        );
    }

    if (!chartData || !chartData.labels || !chartData.data || chartData.labels.length === 0) {
        return (
            <div className="w-full h-64 sm:h-72 lg:h-80 bg-gray-50 border border-gray-200 rounded-xl flex flex-col items-center justify-center text-gray-500 p-4">
                <Info size={32} className="sm:w-10 sm:h-10 text-gray-300 mb-3" />
                <p className="font-medium text-sm sm:text-base">Tidak ada data kunjungan</p>
            </div>
        );
    }

    const dataConfig = {
        labels: chartData.labels,
        datasets: [
            {
                label: `Kunjungan - ${periodType === 'hourly' ? 'Per Jam' : 'Per Hari'}`,
                data: chartData.data,
                fill: true,
                backgroundColor: 'rgba(13, 71, 161, 0.1)',
                borderColor: '#0D47A1',
                tension: 0.4,
                pointBackgroundColor: '#0D47A1',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#0D47A1',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
            },
        ],
    };

    const optionsConfig = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { 
                beginAtZero: true, 
                ticks: { 
                    precision: 0, 
                    color: '#6B7280',
                    font: { size: 11 }
                }, 
                grid: { color: '#E5E7EB' } 
            },
            x: { 
                ticks: { 
                    color: '#6B7280',
                    font: { size: 10 },
                    maxRotation: 45,
                    minRotation: 0,
                }, 
                grid: { display: false } 
            }
        },
        plugins: {
            legend: { 
                display: true, 
                position: 'bottom', 
                labels: { 
                    boxWidth: 12, 
                    padding: 15, 
                    color: '#374151',
                    font: { size: 11 }
                } 
            },
            tooltip: {
                mode: 'index', 
                intersect: false,
                titleFont: { weight: 'bold', size: 12 },
                bodyFont: { size: 11 },
                padding: 10,
                backgroundColor: '#1F2937',
                cornerRadius: 8,
            }
        },
        interaction: { mode: 'index', intersect: false },
    };

    return (
        <div className="h-64 sm:h-72 lg:h-80 w-full">
            <Line options={optionsConfig} data={dataConfig} />
        </div>
    );
};

const ITEMS_PER_LOG_PAGE_MODAL = 10;

export default function DashboardPage() {
    const { auth, unreadMessagesCount } = usePage().props;
    const [showActivityModal, setShowActivityModal] = useState(false);
    const [allActivityLogs, setAllActivityLogs] = useState([]);
    const [filteredActivityLogsInModal, setFilteredActivityLogsInModal] = useState([]);
    const [activitySearchTermModal, setActivitySearchTermModal] = useState('');
    const [activityDateRangeModal, setActivityDateRangeModal] = useState('all');
    const [currentActivityPageModal, setCurrentActivityPageModal] = useState(1);
    const [totalActivityPagesModal, setTotalActivityPagesModal] = useState(1);
    const [loadingLogsModal, setLoadingLogsModal] = useState(false);
    const [dashboardActivityLogs, setDashboardActivityLogs] = useState([]);
    const [loadingDashboardLogs, setLoadingDashboardLogs] = useState(false);

    const [visitorStats, setVisitorStats] = useState({
        total: null,
        totalMessage: "Memuat data...",
        totalLoading: true,
        totalError: null,
        dailyChart: null,
        chartLoading: true,
        chartError: null,
        lastCached: null,
        period: '7d',
        periodType: 'daily',
    });

    const fetchAllVisitorData = useCallback(async (currentPeriod) => {
        setVisitorStats(prev => ({
            ...prev,
            totalLoading: true,
            chartLoading: true,
            totalError: null,
            chartError: null,
            totalMessage: `Memuat data...`
        }));

        let totalData = null;
        let chartResponseData = null;
        let combinedMessage = `Data kunjungan`;
        let cachedTime = visitorStats.lastCached;

        try {
            const periodValue = currentPeriod === '1d' ? 1 : 7;
            const totalResponse = await axios.get(route('admin.api.cloudflare.stats', { period: periodValue }));
            totalData = {
                value: totalResponse.data.unique_visitors_total !== undefined ? totalResponse.data.unique_visitors_total : 'N/A',
                message: totalResponse.data.message,
            };
            if (totalResponse.data.cached_at) {
                cachedTime = new Date(totalResponse.data.cached_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            }
            if (totalResponse.data.message) combinedMessage = totalResponse.data.message;
        } catch (error) {
            totalData = { value: 'Error', message: error.response?.data?.error || "Gagal memuat data" };
            setVisitorStats(prev => ({ ...prev, totalError: totalData.message, totalLoading: false }));
        }

        try {
            const chartResponse = await axios.get(route('admin.api.cloudflare.chart.stats', { period: currentPeriod }));
            if (chartResponse.data && chartResponse.data.labels && chartResponse.data.data) {
                chartResponseData = {
                    labels: chartResponse.data.labels,
                    data: chartResponse.data.data,
                };
                if (!cachedTime && chartResponse.data.cached_at) {
                    cachedTime = new Date(chartResponse.data.cached_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
                }
            }
        } catch (error) {
            setVisitorStats(prev => ({ ...prev, chartError: error.response?.data?.error || "Gagal memuat chart", chartLoading: false }));
        }

        setVisitorStats(prev => ({
            ...prev,
            total: totalData ? totalData.value : 'Error',
            totalMessage: combinedMessage,
            totalLoading: false,
            dailyChart: chartResponseData,
            chartLoading: false,
            lastCached: cachedTime,
            periodType: currentPeriod === '1d' ? 'hourly' : 'daily',
        }));
    }, [visitorStats.lastCached]);

    useEffect(() => { fetchAllVisitorData(visitorStats.period); }, [visitorStats.period, fetchAllVisitorData]);

    const handleChangePeriod = (newPeriod) => {
        setVisitorStats(prev => ({ ...prev, period: newPeriod }));
    };

    const fetchActivityLogs = async (page = 1, perPage = ITEMS_PER_LOG_PAGE_MODAL, forModal = false) => {
        if (forModal) setLoadingLogsModal(true);
        else setLoadingDashboardLogs(true);
        try {
            const response = await axios.get(route('admin.api.activitylogs.index', { page, per_page: perPage }));
            if (forModal) {
                setAllActivityLogs(response.data.data);
                setFilteredActivityLogsInModal(response.data.data);
                setTotalActivityPagesModal(response.data.last_page);
                setCurrentActivityPageModal(response.data.current_page);
            } else {
                setDashboardActivityLogs(response.data.data.slice(0, 5));
            }
        } catch (error) { 
            console.error("Activity logs error:", error);
            toast.error('Gagal memuat log aktivitas. Coba refresh halaman.');
            // Set empty data on error
            if (forModal) {
                setAllActivityLogs([]);
                setFilteredActivityLogsInModal([]);
            } else {
                setDashboardActivityLogs([]);
            }
        }
        finally {
            if (forModal) setLoadingLogsModal(false);
            else setLoadingDashboardLogs(false);
        }
    };

    useEffect(() => { fetchActivityLogs(1, 5, false); }, []);

    const openActivityModal = () => {
        fetchActivityLogs(1, ITEMS_PER_LOG_PAGE_MODAL, true);
        setShowActivityModal(true);
    };

    const closeActivityModal = () => {
        setShowActivityModal(false);
        setActivitySearchTermModal('');
        setActivityDateRangeModal('all');
    };

    useEffect(() => {
        if (!showActivityModal) return;
        let logs = [...allActivityLogs];
        if (activitySearchTermModal) {
            const search = activitySearchTermModal.toLowerCase();
            logs = logs.filter(log =>
                (log.causer?.username?.toLowerCase() || log.causer?.name?.toLowerCase() || '').includes(search) ||
                log.action?.toLowerCase().includes(search) ||
                log.description?.toLowerCase().includes(search)
            );
        }
        const now = new Date();
        if (activityDateRangeModal !== 'all') {
            let startDate;
            if (activityDateRangeModal === '24h') startDate = new Date(now.getTime() - (24 * 60 * 60 * 1000));
            else if (activityDateRangeModal === '7d') startDate = new Date(now.getTime() - (7 * 24 * 60 * 60 * 1000));
            else if (activityDateRangeModal === '30d') startDate = new Date(now.getTime() - (30 * 24 * 60 * 60 * 1000));
            if (startDate) logs = logs.filter(log => new Date(log.created_at) >= startDate);
        }
        setFilteredActivityLogsInModal(logs);
        setTotalActivityPagesModal(Math.ceil(logs.length / ITEMS_PER_LOG_PAGE_MODAL));
        setCurrentActivityPageModal(1);
    }, [activitySearchTermModal, activityDateRangeModal, showActivityModal, allActivityLogs]);

    const currentLogItemsInModal = filteredActivityLogsInModal.slice(
        (currentActivityPageModal - 1) * ITEMS_PER_LOG_PAGE_MODAL,
        currentActivityPageModal * ITEMS_PER_LOG_PAGE_MODAL
    );

    const periodOptions = [
        { value: '1d', label: '24 Jam', icon: <Clock size={14} /> },
        { value: '7d', label: '7 Hari', icon: <CalendarDays size={14} /> }
    ];

    return (
        <AdminLayout headerTitle="Dashboard Utama">
            <Head title="Admin Dashboard" />
            
            <div className="max-w-screen-2xl mx-auto px-2 sm:px-3 lg:px-4 pb-4 sm:pb-6">
                {/* Quick Stats - Responsive Grid */}
                <div className="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <StatCard 
                        title="Pesan Baru" 
                        value={unreadMessagesCount || 0} 
                        icon={Mail} 
                        href={route('admin.contact-messages.index')}
                        color="blue"
                    />
                    <StatCard 
                        title="Berita Aktif" 
                        value="-" 
                        icon={FileText} 
                        href={route('admin.posts.index')}
                        color="green"
                    />
                    <StatCard 
                        title="Total Guru/Staff" 
                        value="-" 
                        icon={Users} 
                        href={route('admin.teachers.index')}
                        color="purple"
                    />
                    <StatCard 
                        title="Program Studi" 
                        value="3" 
                        icon={LayoutGrid} 
                        href={route('admin.program-studi.index')}
                        color="yellow"
                    />
                </div>

                {/* Visitor Statistics Card */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-5 lg:p-6 mb-4 sm:mb-6">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-5">
                        <div>
                            <h2 className="text-lg sm:text-xl font-bold text-gray-900">Statistik Pengunjung</h2>
                            <p className="text-xs sm:text-sm text-gray-500 mt-0.5">Data kunjungan website</p>
                        </div>
                        <div className="flex items-center gap-2 w-full sm:w-auto">
                            {periodOptions.map(opt => (
                                <button
                                    key={opt.value}
                                    onClick={() => handleChangePeriod(opt.value)}
                                    className={`flex items-center gap-1.5 px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-medium rounded-lg transition-colors flex-1 sm:flex-none ${
                                        visitorStats.period === opt.value 
                                        ? 'bg-primary text-white shadow-sm' 
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    {opt.icon} {opt.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Total Visitors & Chart - Responsive Layout */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
                        <div className="bg-blue-50 rounded-xl p-4 sm:p-5 lg:p-6 text-center lg:text-left">
                            {visitorStats.totalLoading ? (
                                <div className="py-6 sm:py-8">
                                    <div className="inline-block animate-spin rounded-full h-8 w-8 sm:h-10 sm:w-10 border-4 border-primary border-t-transparent"></div>
                                    <p className="text-xs sm:text-sm text-gray-500 mt-2">Memuat...</p>
                                </div>
                            ) : visitorStats.total === 'Error' ? (
                                <div className="py-4 bg-red-50 rounded-lg border border-red-200">
                                    <AlertCircle className="w-8 h-8 text-red-500 mx-auto mb-2" />
                                    <p className="text-xs sm:text-sm text-red-600">Gagal memuat</p>
                                </div>
                            ) : (
                                <>
                                    <p className="text-3xl sm:text-4xl lg:text-5xl font-bold text-primary">
                                        {typeof visitorStats.total === 'number' ? visitorStats.total.toLocaleString() : visitorStats.total}
                                    </p>
                                    <p className="text-sm sm:text-base text-gray-600 mt-1">Total Kunjungan</p>
                                    <p className="text-xs sm:text-sm text-gray-500 mt-1">
                                        {visitorStats.period === '1d' ? '24 Jam Terakhir' : '7 Hari Terakhir'}
                                    </p>
                                    {visitorStats.lastCached && (
                                        <p className="text-[10px] sm:text-xs text-gray-400 mt-3">Update: {visitorStats.lastCached}</p>
                                    )}
                                </>
                            )}
                        </div>

                        <div className="lg:col-span-2">
                            <VisitorStatsChart
                                chartData={visitorStats.dailyChart}
                                loading={visitorStats.chartLoading}
                                error={visitorStats.chartError && !visitorStats.dailyChart ? visitorStats.chartError : null}
                                periodType={visitorStats.periodType}
                            />
                        </div>
                    </div>
                </div>

                {/* Recent Activity Card */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h2 className="text-lg font-bold text-gray-900">Aktivitas Terbaru</h2>
                            <p className="text-xs sm:text-sm text-gray-500 mt-0.5">Log aktivitas sistem</p>
                        </div>
                        <button
                            onClick={openActivityModal}
                            className="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-darker transition-colors text-xs sm:text-sm font-medium w-full sm:w-auto justify-center"
                        >
                            <Eye size={16} />
                            Lihat Semua
                        </button>
                    </div>

                    {loadingDashboardLogs ? (
                        <div className="p-8 sm:p-10 text-center">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary border-t-transparent"></div>
                            <p className="text-sm text-gray-500 mt-2">Memuat aktivitas...</p>
                        </div>
                    ) : dashboardActivityLogs.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {dashboardActivityLogs.map((log) => (
                                        <tr key={log.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                                        <span className="text-xs font-semibold text-primary">{(log.causer?.username || log.causer?.name || 'S')[0].toUpperCase()}</span>
                                                    </div>
                                                    <span className="text-xs sm:text-sm font-medium text-gray-900 truncate max-w-[120px] sm:max-w-none">
                                                        {log.causer?.username || log.causer?.name || 'Sistem'}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                    {log.action}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap hidden sm:table-cell">
                                                <span className="text-xs sm:text-sm text-gray-500">
                                                    {new Date(log.created_at).toLocaleString('id-ID', {
                                                        day: '2-digit',
                                                        month: 'short',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    })}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="p-8 sm:p-10 text-center">
                            <Clock size={32} className="mx-auto text-gray-300 mb-2" />
                            <p className="text-sm text-gray-500">Belum ada aktivitas</p>
                        </div>
                    )}
                </div>

                {/* Activity Log Modal - Responsive */}
                <Modal show={showActivityModal} onClose={closeActivityModal} maxWidth="full" closeable={false}>
                    <div className="bg-white h-[85vh] sm:h-[80vh] flex flex-col rounded-xl sm:rounded-2xl overflow-hidden">
                        <div className="p-4 sm:p-5 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-gray-50">
                            <div>
                                <h2 className="text-lg sm:text-xl font-bold text-gray-900">Riwayat Aktivitas</h2>
                                <p className="text-xs sm:text-sm text-gray-500 mt-0.5">Log lengkap aktivitas</p>
                            </div>
                            <button 
                                onClick={closeActivityModal}
                                className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded-lg transition-colors self-end sm:self-auto"
                            >
                                <X size={20} />
                            </button>
                        </div>

                        <div className="p-4 border-b border-gray-200 bg-white">
                            <div className="flex flex-col sm:flex-row gap-3">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                                    <input
                                        type="text"
                                        placeholder="Cari..."
                                        value={activitySearchTermModal}
                                        onChange={(e) => setActivitySearchTermModal(e.target.value)}
                                        className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                    />
                                </div>
                                <select
                                    value={activityDateRangeModal}
                                    onChange={(e) => setActivityDateRangeModal(e.target.value)}
                                    className="py-2.5 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm bg-white min-w-[140px]"
                                >
                                    <option value="all">Semua</option>
                                    <option value="24h">24 Jam</option>
                                    <option value="7d">7 Hari</option>
                                    <option value="30d">30 Hari</option>
                                </select>
                            </div>
                        </div>

                        <div className="flex-1 overflow-y-auto">
                            {loadingLogsModal ? (
                                <div className="flex flex-col items-center justify-center h-full py-10">
                                    <div className="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mb-3"></div>
                                    <p className="text-sm text-gray-500">Memuat...</p>
                                </div>
                            ) : currentLogItemsInModal.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead className="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Admin</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Keterangan</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {currentLogItemsInModal.map((log, index) => (
                                                <tr key={log.id} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3 text-xs sm:text-sm text-gray-500">
                                                        {(currentActivityPageModal - 1) * ITEMS_PER_LOG_PAGE_MODAL + index + 1}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span className="text-xs sm:text-sm font-medium text-gray-900">{log.causer?.username || log.causer?.name || <span className="italic text-gray-400">Sistem</span>}</span>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                                            {log.action}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 hidden md:table-cell">
                                                        <span className="text-xs sm:text-sm text-gray-600 max-w-[200px] truncate block">{log.description || '-'}</span>
                                                    </td>
                                                    <td className="px-4 py-3 text-xs sm:text-sm text-gray-500 whitespace-nowrap">
                                                        {new Date(log.created_at).toLocaleString('id-ID', {
                                                            dateStyle: 'medium',
                                                            timeStyle: 'short'
                                                        })}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center h-full py-10">
                                    <Info size={40} className="text-gray-300 mb-2" />
                                    <p className="text-sm text-gray-500">Tidak ada data</p>
                                </div>
                            )}
                        </div>

                        {totalActivityPagesModal > 1 && (
                            <div className="p-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-3">
                                <p className="text-xs sm:text-sm text-gray-600">
                                    {currentLogItemsInModal.length} dari {filteredActivityLogsInModal.length}
                                </p>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => setCurrentActivityPageModal(prev => Math.max(1, prev - 1))}
                                        disabled={currentActivityPageModal === 1}
                                        className="p-2 rounded-lg border border-gray-300 hover:bg-gray-100 disabled:opacity-50"
                                    >
                                        <ChevronLeft size={18} />
                                    </button>
                                    <span className="text-xs sm:text-sm px-3 py-1">
                                        {currentActivityPageModal} / {totalActivityPagesModal}
                                    </span>
                                    <button
                                        onClick={() => setCurrentActivityPageModal(prev => Math.min(totalActivityPagesModal, prev + 1))}
                                        disabled={currentActivityPageModal === totalActivityPagesModal}
                                        className="p-2 rounded-lg border border-gray-300 hover:bg-gray-100 disabled:opacity-50"
                                    >
                                        <ChevronRight size={18} />
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </Modal>
            </div>
        </AdminLayout>
    );
}
