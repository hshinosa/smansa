import React, { useState } from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';
import { 
    MapPin, 
    Phone, 
    Mail, 
    Clock, 
    Send, 
    ChevronDown, 
    ChevronUp,
    Instagram,
    Youtube,
    Facebook,
    Twitter,
    CheckCircle
} from 'lucide-react';

import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import SEOHead from '@/Components/SEOHead';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';

export default function KontakPage({ auth, faqs = [] }) {
    const { siteSettings, flash } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const navigationData = getNavigationData(siteSettings);
    const [openFaqIndex, setOpenFaqIndex] = useState(null);

    const heroSettings = siteSettings?.hero_contact || {};
    
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

    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('kontak.store'), {
            onSuccess: () => reset(),
        });
    };

    const toggleFaq = (index) => {
        setOpenFaqIndex(openFaqIndex === index ? null : index);
    };

    const socialLinks = (navigationData.socialMediaLinks || [])
        .filter(Boolean)
        .map(social => ({
            ...social,
            color: social.name === 'Instagram' ? 'bg-pink-600' : 
                   social.name === 'YouTube' ? 'bg-red-600' : 
                   social.name === 'Facebook' ? 'bg-blue-600' : 
                   social.name === 'X' ? 'bg-gray-900' : 'bg-sky-500'
        }));

    // Fallback logic for title to prevent "Hubungi" only issue
    const heroTitle = heroSettings.title && heroSettings.title.length >= 8 ? heroSettings.title : 'Hubungi Kami';

    return (
        <div className="bg-white font-sans text-gray-800">
            <SEOHead 
                title={`Kontak Kami - ${siteName}`}
                description="Hubungi SMAN 1 Baleendah. Alamat, nomor telepon, email, dan lokasi sekolah. Kami siap membantu Anda."
                keywords="kontak, alamat sekolah, telepon, email, lokasi SMAN 1 Baleendah, hubungi kami"
                image="/images/contact-banner.jpg"
            />
            
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />

            <main id="main-content" className="pt-20" tabIndex="-1">
                {/* 1. HERO SECTION (Consistent with AcademicCalendarPage, preserving social media) */}
                <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                    {/* Background Image */}
                    <div className="absolute inset-0 z-0">
                        <img 
                            src={formatImagePath(heroImage)} 
                            alt={`Siswa ${siteName}`} 
                            className="w-full h-full object-cover"
                            loading="eager"
                            decoding="async"
                            fetchpriority="high"
                        />
                        <div className="absolute inset-0 bg-black/60"></div>
                    </div>

                    <div className="relative z-10 container mx-auto px-4 text-center text-white">
                        <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                            {renderHighlightedTitle(heroTitle)}
                        </h1>
                        
                        <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto mb-8 opacity-90`}>
                            {heroSettings.subtitle || `Kami menantikan kehadiran Anda. Silakan hubungi kami untuk informasi lebih lanjut atau jadwalkan kunjungan ke sekolah kami.`}
                        </p>

                        {/* Social Icons Row */}
                        <div className="flex gap-4 justify-center">
                            {socialLinks.map((social, idx) => {
                                const hasHref = Boolean(social.href);

                                return (
                                    <a 
                                        key={idx}
                                        href={hasHref ? social.href : '#'}
                                        className={`${social.color} w-12 h-12 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary ${hasHref ? '' : 'opacity-60 cursor-not-allowed'}`}
                                        title={social.name}
                                        aria-label={`Buka ${social.name} ${siteName}`}
                                        rel="noreferrer noopener"
                                        target={hasHref ? '_blank' : '_self'}
                                        aria-disabled={!hasHref}
                                        tabIndex={hasHref ? 0 : -1}
                                        onClick={(event) => {
                                            if (!hasHref) {
                                                event.preventDefault();
                                            }
                                        }}
                                    >
                                        <social.icon className="w-6 h-6" aria-hidden="true" />
                                    </a>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* 2. SECTION: CONTACT INFO & MAP (Integrated) */}
                <section className="relative py-20 bg-gray-50">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="relative rounded-3xl overflow-hidden shadow-xl h-[600px] border border-gray-200">
                            {/* Background Map (Static Placeholder for now, ideally interactive) */}
                            <iframe
                                src={navigationData.googleMapsEmbedUrl}
                                width="100%"
                                height="100%"
                                style={{ border: 0 }}
                                allowFullScreen
                                loading="lazy"
                                referrerPolicy="no-referrer-when-downgrade"
                                title={`Peta Lokasi ${siteName}`}
                            ></iframe>

                            {/* Floating Card (Overlay) */}
                            <div className="absolute top-1/2 left-4 lg:left-12 transform -translate-y-1/2 w-[90%] max-w-md bg-white/95 backdrop-blur-md p-8 rounded-2xl shadow-2xl border border-white/50">
                                <h3 className={`${TYPOGRAPHY.sectionHeading} text-2xl mb-6 border-b border-gray-100 pb-4`}>
                                    Informasi Kontak
                                </h3>
                                <div className="space-y-6">
                                    <div className="flex items-start gap-4">
                                        <div className="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0 text-primary">
                                            <MapPin className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="font-bold text-gray-900">Alamat</p>
                                            <p className="text-gray-600 text-sm leading-relaxed">
                                                {navigationData.address}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-4">
                                        <div className="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0 text-primary">
                                            <Phone className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="font-bold text-gray-900">Telepon</p>
                                            <p className="text-gray-600 text-sm">{navigationData.phone}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-4">
                                        <div className="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0 text-primary">
                                            <Mail className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="font-bold text-gray-900">Email</p>
                                            <p className="text-gray-600 text-sm">{navigationData.email}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-4">
                                        <div className="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0 text-primary">
                                            <Clock className="w-5 h-5" />
                                        </div>
                                        <div>
                                            <p className="font-bold text-gray-900">Jam Operasional</p>
                                            <p className="text-gray-600 text-sm">Senin - Jumat, 07:00 - 16:00 WIB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* 3. SECTION: MESSAGE FORM & FAQ (Combined) */}
                <section className="py-20 bg-white">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">
                            
                            {/* Left Column: Message Form */}
                            <div>
                                <div className="mb-8">
                                    <h2 className={TYPOGRAPHY.sectionHeading}>Kirim <span className="text-primary">Pesan</span></h2>
                                    <p className={`${TYPOGRAPHY.bodyText} mt-4`}>
                                        Punya pertanyaan? Isi formulir di bawah ini dan tim kami akan segera menghubungi Anda.
                                    </p>
                                </div>

                                {flash?.success && (
                                    <div className="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3" role="status" aria-live="polite">
                                        <CheckCircle className="w-5 h-5" aria-hidden="true" />
                                        <span>{flash.success}</span>
                                    </div>
                                )}

                                <form onSubmit={handleSubmit} className="space-y-6 text-left" aria-busy={processing}>
                                    <div className="grid md:grid-cols-2 gap-6">
                                        <div className="relative">
                                            <input 
                                                type="text" 
                                                id="name"
                                                value={data.name}
                                                onChange={e => setData('name', e.target.value)}
                                                className={`peer w-full px-4 py-3 bg-gray-50 border-0 border-b-2 ${errors.name ? 'border-red-500' : 'border-gray-200'} focus:border-primary focus:ring-0 rounded-t-lg transition-colors placeholder-transparent`}
                                                placeholder="Nama Lengkap"
                                                aria-required="true"
                                                aria-invalid={Boolean(errors.name)}
                                                aria-describedby={errors.name ? 'name-error' : undefined}
                                                autoComplete="name"
                                                required
                                            />
                                            <label 
                                                htmlFor="name"
                                                className="absolute left-4 -top-2.5 text-xs text-gray-500 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3.5 peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-primary"
                                            >
                                                Nama Lengkap
                                            </label>
                                            {errors.name && <p className="text-red-500 text-xs mt-1" id="name-error" role="alert">{errors.name}</p>}
                                        </div>
                                        <div className="relative">
                                            <input 
                                                type="email" 
                                                id="email"
                                                value={data.email}
                                                onChange={e => setData('email', e.target.value)}
                                                className={`peer w-full px-4 py-3 bg-gray-50 border-0 border-b-2 ${errors.email ? 'border-red-500' : 'border-gray-200'} focus:border-primary focus:ring-0 rounded-t-lg transition-colors placeholder-transparent`}
                                                placeholder="Email"
                                                aria-required="true"
                                                aria-invalid={Boolean(errors.email)}
                                                aria-describedby={errors.email ? 'email-error' : undefined}
                                                autoComplete="email"
                                                required
                                            />
                                            <label 
                                                htmlFor="email"
                                                className="absolute left-4 -top-2.5 text-xs text-gray-500 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3.5 peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-primary"
                                            >
                                                Email
                                            </label>
                                            {errors.email && <p className="text-red-500 text-xs mt-1" id="email-error" role="alert">{errors.email}</p>}
                                        </div>
                                    </div>

                                    <div className="relative">
                                        <input 
                                            type="text" 
                                            id="subject"
                                            value={data.subject}
                                            onChange={e => setData('subject', e.target.value)}
                                            className={`peer w-full px-4 py-3 bg-gray-50 border-0 border-b-2 ${errors.subject ? 'border-red-500' : 'border-gray-200'} focus:border-primary focus:ring-0 rounded-t-lg transition-colors placeholder-transparent`}
                                            placeholder="Subjek"
                                            aria-required="true"
                                            aria-invalid={Boolean(errors.subject)}
                                            aria-describedby={errors.subject ? 'subject-error' : undefined}
                                            autoComplete="organization-title"
                                            required
                                        />
                                        <label 
                                            htmlFor="subject"
                                            className="absolute left-4 -top-2.5 text-xs text-gray-500 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3.5 peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-primary"
                                        >
                                            Subjek
                                        </label>
                                        {errors.subject && <p className="text-red-500 text-xs mt-1" id="subject-error" role="alert">{errors.subject}</p>}
                                    </div>

                                    <div className="relative">
                                        <textarea 
                                            id="message"
                                            rows="5"
                                            value={data.message}
                                            onChange={e => setData('message', e.target.value)}
                                            className={`peer w-full px-4 py-3 bg-gray-50 border-0 border-b-2 ${errors.message ? 'border-red-500' : 'border-gray-200'} focus:border-primary focus:ring-0 rounded-t-lg transition-colors placeholder-transparent resize-none`}
                                            placeholder="Pesan Anda"
                                            aria-required="true"
                                            aria-invalid={Boolean(errors.message)}
                                            aria-describedby={errors.message ? 'message-error' : 'message-help'}
                                            autoComplete="off"
                                            required
                                        ></textarea>
                                        <label 
                                            htmlFor="message"
                                            className="absolute left-4 -top-2.5 text-xs text-gray-500 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-3.5 peer-focus:-top-2.5 peer-focus:text-xs peer-focus:text-primary"
                                        >
                                            Pesan Anda
                                        </label>
                                        <p id="message-help" className="text-xs text-gray-500 mt-1">Jelaskan kebutuhan atau pertanyaan Anda secara singkat.</p>
                                        {errors.message && <p className="text-red-500 text-xs mt-1" id="message-error" role="alert">{errors.message}</p>}
                                    </div>

                                    <button 
                                        type="submit"
                                        disabled={processing}
                                        className="w-full bg-primary text-white font-bold py-4 rounded-xl hover:bg-primary-darker transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                    >
                                        <Send className="w-5 h-5" />
                                        {processing ? 'Mengirim...' : 'Kirim Pesan Sekarang'}
                                    </button>
                                </form>
                            </div>

                            {/* Right Column: FAQ */}
                            <div>
                                <div className="mb-8">
                                    <h2 className={TYPOGRAPHY.sectionHeading}>Pertanyaan <span className="text-primary">Umum (FAQ)</span></h2>
                                    <p className={`${TYPOGRAPHY.bodyText} mt-4`}>
                                        Jawaban atas pertanyaan yang sering diajukan oleh calon siswa dan orang tua.
                                    </p>
                                </div>

                                <div className="space-y-4">
                                    {faqs.map((faq, index) => (
                                        <div 
                                            key={index} 
                                            className="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-md"
                                        >
                                            <button
                                                onClick={() => toggleFaq(index)}
                                                className="w-full px-6 py-4 text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                                aria-expanded={openFaqIndex === index}
                                                aria-controls={`faq-panel-${index}`}
                                            >
                                                <span className="font-bold text-gray-900 font-serif text-lg">{faq.question}</span>
                                                {openFaqIndex === index ? (
                                                    <ChevronUp className="w-5 h-5 text-primary" aria-hidden="true" />
                                                ) : (
                                                    <ChevronDown className="w-5 h-5 text-gray-400" aria-hidden="true" />
                                                )}
                                            </button>
                                            <div 
                                                className={`px-6 overflow-hidden transition-all duration-300 ease-in-out ${
                                                    openFaqIndex === index ? 'max-h-96 pb-6 opacity-100' : 'max-h-0 opacity-0'
                                                }`}
                                                id={`faq-panel-${index}`}
                                                role="region"
                                                aria-live="polite"
                                            >
                                                <p className="text-gray-600 leading-relaxed">
                                                    {faq.answer}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                    {faqs.length === 0 && (
                                        <p className="text-center text-gray-500 py-8">Belum ada FAQ yang tersedia.</p>
                                    )}
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
