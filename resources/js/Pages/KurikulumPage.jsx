import React from 'react';
import { 
    BookOpen, 
    Microscope, 
    Globe, 
    Languages, 
    CheckCircle, 
    Lightbulb, 
    Users, 
    Monitor,
    Award,
    TrendingUp,
    Target,
    Activity,
    Repeat,
    Network
} from 'lucide-react';

// Import Components
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import ResponsiveImage from '@/Components/ResponsiveImage';

// Import utilities
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { getPageMetadata } from '@/Utils/academicData';
import { usePage, Link } from '@inertiajs/react';

export default function KurikulumPage({ curriculumData }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const navigationData = getNavigationData(siteSettings);
    const pageMetadata = getPageMetadata(siteName);
    const {
        hero,
        problem,
        definition,
        principles,
        learning_cycle,
        design_framework,
        competency_dimensions,
        learner_profile,
        infographic_deep_learning,
        infographic_education_2045,
    } = curriculumData || {};

    // Helper to format image path correctly
    const formatImagePath = (path) => {
        if (!path) return null;
        if (typeof path !== 'string') return null;
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

    // Map icon names to Lucide components
    const iconMap = {
        Microscope,
        Globe,
        BookOpen,
        Languages,
        Monitor,
        Award,
        TrendingUp,
        Target,
        Users,
        Activity,
        Repeat,
        Network
    };

    return (
        <div className="bg-white min-h-screen font-sans text-gray-800 flex flex-col">
            <SEOHead 
                title={pageMetadata?.kurikulum?.title || 'Kurikulum'}
                description={pageMetadata?.kurikulum?.description || ''}
                keywords="kurikulum, kurikulum merdeka, mata pelajaran, sistem pembelajaran, SMAN 1 Baleendah"
                image="/images/kurikulum-banner.jpg"
            />

            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
            {/* 1. HERO SECTION (Redefined) */}
            <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                <div className="absolute inset-0 z-0">
                    <ResponsiveImage
                        media={hero?.image}
                        src={formatImagePath(heroImage)}
                        alt="Background Kurikulum"
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
                        {renderHighlightedTitle(hero?.title || 'Pembelajaran Mendalam')}
                    </h1>
                    <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                        {hero?.subtitle || 'Solusi Menuju Pendidikan Bermutu 2045'}
                    </p>
                </div>
            </section>

            {/* 2. SECTION: MASALAH & PISA 2022 */}
            {problem && (
                <section className="py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-12">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>{problem.title}</h2>
                            <p className={TYPOGRAPHY.bodyText}>{problem.description}</p>
                        </div>
                        <div className="grid md:grid-cols-3 gap-6">
                            {(problem.stats || []).map((stat, idx) => (
                                <div key={idx} className="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">{stat.label}</h3>
                                    <div className="text-sm text-gray-600">LOTS: <span className="font-semibold">{stat.lots}</span></div>
                                    <div className="text-sm text-gray-600">HOTS: <span className="font-semibold">{stat.hots}</span></div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* 3. SECTION: DEFINISI & 4 OLAH */}
            {definition && (
                <section className="py-20 bg-gray-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-12">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>{definition.title}</h2>
                            <p className={TYPOGRAPHY.bodyText}>{definition.description}</p>
                        </div>
                        <div className="grid md:grid-cols-4 gap-6">
                            {(definition.items || []).map((item, idx) => (
                                <div key={idx} className="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">{item.title}</h3>
                                    <p className="text-sm text-gray-600">{item.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* 4. SECTION: PRINSIP 3M */}
            {principles && (
                <section className="py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-12">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>{principles.title}</h2>
                        </div>
                        <div className="grid md:grid-cols-3 gap-6">
                            {(principles.items || []).map((item, idx) => (
                                <div key={idx} className="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">{item.title}</h3>
                                    <p className="text-sm text-gray-600">{item.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* 5. SECTION: SIKLUS BELAJAR */}
            {learning_cycle && (
                <section className="py-20 bg-gray-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-12">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>{learning_cycle.title}</h2>
                        </div>
                        <div className="grid md:grid-cols-3 gap-6">
                            {(learning_cycle.steps || []).map((step, idx) => (
                                <div key={idx} className="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">{step.title}</h3>
                                    <p className="text-sm text-gray-600">{step.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* 6. SECTION: KERANGKA IMPLEMENTASI */}
            {design_framework && (
                <section className="py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center max-w-3xl mx-auto mb-12">
                            <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>{design_framework.title}</h2>
                        </div>
                        <div className="grid md:grid-cols-4 gap-6">
                            {(design_framework.items || []).map((item, idx) => (
                                <div key={idx} className="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">{item.title}</h3>
                                    <p className="text-sm text-gray-600">{item.description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* 7. SECTION: DIMENSI KOMPETENSI & PROFIL PELAJAR */}
            <section className="py-20 bg-gray-900 text-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid lg:grid-cols-2 gap-16">
                        {competency_dimensions && (
                            <div>
                                <h2 className="text-3xl md:text-4xl font-bold font-serif mb-8 text-white">
                                    {competency_dimensions.title}
                                </h2>
                                <div className="space-y-4">
                                    {(competency_dimensions.items || []).map((item, idx) => (
                                        <div key={idx} className="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10">
                                            <div className="mt-1 w-6 h-6 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                                                <CheckCircle className="w-4 h-4 text-white" />
                                            </div>
                                            <p className="text-gray-300 leading-relaxed">{item}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {learner_profile && (
                            <div className="bg-white/5 rounded-[40px] p-8 md:p-12 border border-white/10">
                                <h3 className="text-2xl font-bold mb-8 flex items-center gap-3">
                                    <Users className="text-accent-yellow" />
                                    {learner_profile.title}
                                </h3>
                                <div className="space-y-3">
                                    {(learner_profile.items || []).map((item, idx) => (
                                        <div key={idx} className="flex items-center gap-3 text-gray-200">
                                            <span className="w-2 h-2 bg-accent-yellow rounded-full"></span>
                                            <span>{item}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {/* 8. SECTION: INFOGRAFIS */}
            <section className="py-20 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                    {[infographic_deep_learning, infographic_education_2045].filter(Boolean).map((info, idx) => (
                        <div key={idx} className="grid lg:grid-cols-2 gap-10 items-center">
                            <div>
                                <h2 className={`${TYPOGRAPHY.sectionHeading} mb-4`}>{info.title}</h2>
                                <p className={TYPOGRAPHY.bodyText}>{info.description}</p>
                                {info.source && <p className="text-sm text-gray-500 mt-4">Sumber: {info.source}</p>}
                            </div>
                            <div className="rounded-2xl overflow-hidden border border-gray-200 shadow-sm">
                                <ResponsiveImage
                                    media={info.image}
                                    src={info.image?.original_url || info.image_url}
                                    alt={info.title}
                                    className="w-full h-full object-cover"
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* CTA SECTION - Updated Style */}
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
        </div>
    );
}
