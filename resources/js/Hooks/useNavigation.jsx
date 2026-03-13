import { useState, useMemo, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { getNavigationData } from '@/Utils/navigationData';

/**
 * useNavigation Hook
 * 
 * Provides consistent access to navigation data across pages.
 * Automatically extracts site settings and navigation data from Inertia props.
 * 
 * @returns {Object} Navigation data and site settings
 */
export function useNavigation() {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);

    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const siteDescription = siteSettings?.general?.site_description || 'Website Resmi SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    return {
        siteSettings,
        navigationData,
        siteName,
        siteDescription,
        heroImage,
    };
}

/**
 * useHeroSettings Hook
 * 
 * Extracts hero section settings from site settings for a specific page.
 * 
 * @param {string} pageKey - The page key in siteSettings (e.g., 'hero_posts', 'hero_gallery')
 * @returns {Object} Hero settings and helper functions
 */
export function useHeroSettings(pageKey) {
    const { siteSettings } = usePage().props;
    
    const heroSettings = siteSettings?.[pageKey] || {};
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';

    const renderHighlightedTitle = useCallback((title) => {
        if (!title) return null;
        const words = title.split(' ');
        if (words.length <= 1) return title;
        const lastWord = words.pop();
        return (
            <>
                {words.join(' ')} <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{lastWord}</span>
            </>
        );
    }, []);

    const heroTitle = heroSettings.title || '';
    const heroSubtitle = heroSettings.subtitle || '';

    return {
        heroSettings,
        heroTitle,
        heroSubtitle,
        heroImage,
        siteName,
        renderHighlightedTitle,
    };
}

/**
 * useSocialLinks Hook
 * 
 * Processes social media links with proper colors and validation.
 * 
 * @returns {Array} Processed social media links
 */
export function useSocialLinks() {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);

    const socialLinks = useMemo(() => {
        return (navigationData.socialMediaLinks || [])
            .filter(Boolean)
            .map(social => ({
                ...social,
                color: social.name === 'Instagram' ? 'bg-pink-600' : 
                       social.name === 'YouTube' ? 'bg-red-600' : 
                       social.name === 'Facebook' ? 'bg-blue-600' : 
                       social.name === 'X' ? 'bg-gray-900' : 'bg-sky-500',
                hasHref: Boolean(social.href),
            }));
    }, [navigationData.socialMediaLinks]);

    return socialLinks;
}

export default useNavigation;
