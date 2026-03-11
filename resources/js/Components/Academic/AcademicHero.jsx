import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import { TYPOGRAPHY } from '@/Utils/typography';

/**
 * Reusable Hero component for academic pages
 * Provides consistent header section with title, description and SEO
 */
export default function AcademicHero({ 
    title, 
    description, 
    pageTitle,
    metaDescription 
}) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';

    return (
        <>
            <Head title={pageTitle || `${title} - ${siteName}`}>
                {metaDescription && (
                    <meta name="description" content={metaDescription} />
                )}
                <meta name="keywords" content={`${siteName}, ${title}, akademik, pendidikan, sekolah menengah atas`} />
                <meta property="og:title" content={pageTitle || `${title} - ${siteName}`} />
                {metaDescription && (
                    <meta property="og:description" content={metaDescription} />
                )}
                <meta property="og:type" content="website" />
            </Head>
            
            <section className="pt-24 pb-12 bg-white">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center md:text-left">
                        <h1 className={TYPOGRAPHY.pageTitle}>
                            {title}
                        </h1>
                        <div className="h-1 w-24 bg-primary mt-2 mx-auto md:mx-0 mb-4"></div>
                        <p className={TYPOGRAPHY.bodyText + " text-gray-600 max-w-3xl mx-auto md:mx-0"}>
                            {description}
                        </p>
                    </div>
                </div>
            </section>
        </>
    );
}