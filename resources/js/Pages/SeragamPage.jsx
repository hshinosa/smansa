import React from 'react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import ResponsiveImage from '@/Components/ResponsiveImage';
import { getNavigationData } from '@/Utils/navigationData';
import { TYPOGRAPHY } from '@/Utils/typography';
import { normalizeUrl } from '@/Utils/imageUtils';
import { usePage } from '@inertiajs/react';
import { Calendar, Shirt, Info } from 'lucide-react';

// Day names mapping
const DAYS = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
};

export default function SeragamPage({ seragams = [] }) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const renderHighlightedTitle = (title) => {
        if (!title) return 'Informasi Seragam';
        const words = title.split(' ');
        const lastWord = words.pop();
        return (
            <>
                {words.join(' ')}{' '}
                <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{lastWord}</span>
            </>
        );
    };

    const getUsageDays = (usageDays) => {
        if (!usageDays || !Array.isArray(usageDays) || usageDays.length === 0) {
            return [];
        }

        return usageDays.filter(day => DAYS[day]).map(day => DAYS[day]);
    };

    const parseRules = (rules) => {
        if (!rules || typeof rules !== 'string') return [];

        const normalized = rules.replace(/\s+/g, ' ').trim();
        if (!normalized) return [];

        const numbered = normalized
            .split(/\s(?=\d+\.\s)/)
            .map((item) => item.replace(/^\d+\.\s*/, '').trim())
            .filter(Boolean);

        if (numbered.length > 1) return numbered;

        const bySemicolon = normalized
            .split(';')
            .map((item) => item.trim())
            .filter(Boolean);

        if (bySemicolon.length > 1) return bySemicolon;

        return [normalized];
    };

    return (
        <div className="bg-secondary min-h-screen font-sans text-gray-800">
            <SEOHead 
                title={`Informasi Seragam - ${siteName}`}
                description={`Informasi lengkap seragam sekolah SMAN 1 Baleendah meliputi jenis seragam, jadwal penggunaan, dan peraturan penggunaan seragam.`}
                keywords="seragam sekolah, seragam SMAN 1 Baleendah, seragam dinas, jadwal seragam"
            />

            {/* Navbar */}
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
                {/* Hero Section */}
                <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                    <div className="absolute inset-0 z-0">
                        <img
                            src={normalizeUrl(heroImage)}
                            alt="Background Informasi Seragam"
                            className="w-full h-full object-cover"
                            loading="eager"
                            fetchpriority="high"
                        />
                        <div className="absolute inset-0 bg-black/60"></div>
                    </div>

                    <div className="relative z-10 container mx-auto px-4 text-center text-white">
                        <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                            {renderHighlightedTitle('Informasi Seragam')}
                        </h1>
                        <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                            Informasi lengkap mengenai jenis seragam, jadwal penggunaan, dan peraturan penggunaan seragam di {siteName}
                        </p>
                    </div>
                </section>

                {/* Seragam Cards Section */}
                <section className="py-16 md:py-24 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Section Title */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                            Jenis <span className="text-primary">Seragam</span>
                        </h2>
                        <p className="text-gray-600 text-lg">
                            Berikut adalah informasi lengkap seragam yang digunakan di {siteName}
                        </p>
                    </div>

                    {/* Cards Grid */}
                    {seragams.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12 md:gap-y-14">
                            {seragams.map((seragam, idx) => (
                                <article key={seragam.id} className="group flex flex-col h-full">
                                    {/* Visual */}
                                    <div className="relative z-20 mx-auto w-56 h-56 sm:w-56 sm:h-56 md:w-60 md:h-60 -mb-18 md:-mb-20">
                                        <div className="w-full h-full rounded-2xl bg-gradient-to-b from-gray-50 to-white border border-gray-100 shadow-lg overflow-hidden">
                                            {seragam.image_url ? (
                                                <ResponsiveImage
                                                    src={seragam.image_url}
                                                    alt={seragam.name}
                                                    className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            ) : (
                                                <div className="w-full h-full bg-gray-100 flex items-center justify-center">
                                                    <Shirt className="w-14 h-14 text-gray-300" />
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Card Content */}
                                    <div className="relative z-10 bg-white rounded-3xl p-4 md:p-6 pt-18 md:pt-24 shadow-sm border border-gray-100 flex flex-col w-full h-full hover:shadow-lg hover:shadow-primary/5 transition-all duration-300">
                                        {/* Category Badge */}
                                        <div className="mb-4">
                                            <span className="inline-flex items-center px-3 py-1 bg-primary/10 text-primary text-xs font-semibold rounded-full">
                                                {seragam.category || 'Seragam'}
                                            </span>
                                        </div>

                                        {/* Title */}
                                        <h3 className="text-lg md:text-2xl font-bold text-gray-900 mb-2.5 leading-tight">
                                            {seragam.name}
                                        </h3>

                                        {/* Description */}
                                        {seragam.description && (
                                            <p className="text-gray-600 text-xs md:text-base mb-4.5 leading-relaxed">
                                                {seragam.description}
                                            </p>
                                        )}

                                        {/* Schedule */}
                                        <div className="bg-blue-50/70 rounded-2xl p-3.5 mb-4.5 border border-blue-100">
                                            <div className="flex items-start gap-2.5">
                                                <div className="w-9 h-9 rounded-xl bg-primary/15 flex items-center justify-center flex-shrink-0">
                                                    <Calendar className="w-4 h-4 text-primary" />
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-[0.08em] mb-2">
                                                        Jadwal Penggunaan
                                                    </p>
                                                    {getUsageDays(seragam.usage_days).length > 0 ? (
                                                        <div className="flex flex-wrap gap-2">
                                                            {getUsageDays(seragam.usage_days).map((day) => (
                                                                <span
                                                                    key={`${seragam.id}-${day}`}
                                                                    className="inline-flex items-center rounded-lg bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-700 border border-blue-100"
                                                                >
                                                                    {day}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <p className="text-xs md:text-sm font-medium text-gray-600">Tidak ada jadwal</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Rules */}
                                        {seragam.rules && (
                                            <div className="mt-auto">
                                                <div className="flex items-start gap-2.5 text-[11px] md:text-sm text-gray-600">
                                                    <Info className="w-4 h-4 flex-shrink-0 mt-0.5 text-gray-400" />
                                                    <div className="space-y-1.5">
                                                        {parseRules(seragam.rules).map((rule, ruleIndex) => (
                                                            <p key={`${seragam.id}-rule-${ruleIndex}`} className="leading-relaxed">
                                                                <span className="font-medium text-gray-500">{ruleIndex + 1}.</span> {rule}
                                                            </p>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                            <Shirt className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">Belum ada informasi seragam</p>
                        </div>
                    )}
                </div>
                </section>
            </main>

            {/* Footer */}
            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />
        </div>
    );
}
