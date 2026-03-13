import { useState, useEffect, useCallback } from 'react';
import { normalizeUrl } from '@/Utils/imageUtils';

/**
 * useMedia Hook
 * 
 * Handles media (images) with loading states, error handling, and URL normalization.
 * Supports Spatie Media Library objects and regular URLs.
 * 
 * @param {Object} options - Configuration options
 * @param {string|Object} options.src - Image source URL or media object
 * @param {Object} options.media - Spatie Media Library object with conversions
 * @param {string} options.fallbackSrc - Fallback image URL on error
 * @param {boolean} options.lazy - Whether to lazy load (default: true)
 * @returns {Object} Media state and handlers
 */
export function useMedia({
    src,
    media,
    fallbackSrc = '/images/panen-karya-sman1-baleendah.jpg',
    lazy = true
} = {}) {
    const [isLoaded, setIsLoaded] = useState(false);
    const [hasError, setHasError] = useState(false);
    const [currentSrc, setCurrentSrc] = useState(null);

    // Determine the best image source
    useEffect(() => {
        let source = null;

        if (media) {
            // Spatie Media Library object
            source = normalizeUrl(media.original_url);
        } else if (src) {
            source = normalizeUrl(src);
        }

        setCurrentSrc(source);
        setIsLoaded(false);
        setHasError(false);
    }, [src, media]);

    // Handle successful load
    const handleLoad = useCallback(() => {
        setIsLoaded(true);
        setHasError(false);
    }, []);

    // Handle error with fallback
    const handleError = useCallback(() => {
        if (!hasError && currentSrc !== fallbackSrc) {
            setCurrentSrc(fallbackSrc);
            setHasError(true);
        }
    }, [hasError, currentSrc, fallbackSrc]);

    // Get responsive source set for srcSet
    const getSrcSet = useCallback(() => {
        if (!media?.conversions) return null;
        
        const sources = [];
        if (media.conversions.mobile) sources.push(`${normalizeUrl(media.conversions.mobile)} 375w`);
        if (media.conversions.tablet) sources.push(`${normalizeUrl(media.conversions.tablet)} 768w`);
        if (media.conversions.desktop) sources.push(`${normalizeUrl(media.conversions.desktop)} 1280w`);
        
        return sources.length > 0 ? sources.join(', ') : null;
    }, [media]);

    return {
        src: currentSrc,
        isLoaded,
        hasError,
        handleLoad,
        handleError,
        srcSet: getSrcSet(),
        loading: lazy ? 'lazy' : 'eager',
    };
}

/**
 * useImagePath Hook
 * 
 * Formats image paths consistently across the application.
 * Handles storage paths, external URLs, and fallbacks.
 * 
 * @param {Object} options - Configuration options
 * @param {string} options.path - Raw image path
 * @param {string} options.fallback - Fallback path if main is invalid
 * @returns {string} Normalized image path
 */
export function useImagePath({
    path,
    fallback = '/images/hero-bg-sman1baleendah.jpeg'
} = {}) {
    const [formattedPath, setFormattedPath] = useState(fallback);

    useEffect(() => {
        if (!path) {
            setFormattedPath(fallback);
            return;
        }

        // If it's already a full URL or starts with /, use as-is
        if (path.startsWith('http') || path.startsWith('/')) {
            setFormattedPath(path);
            return;
        }

        // Otherwise, prepend storage path
        setFormattedPath(`/storage/${path}`);
    }, [path, fallback]);

    return formattedPath;
}

/**
 * useImageGallery Hook
 * 
 * Manages image gallery state including lightbox, navigation, and filtering.
 * 
 * @param {Array} items - Gallery items array
 * @param {Object} options - Configuration options
 * @returns {Object} Gallery state and handlers
 */
export function useImageGallery(items = [], options = {}) {
    const { itemsPerPage = 8 } = options;
    
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [currentIndex, setCurrentIndex] = useState(0);
    const [currentItem, setCurrentItem] = useState(null);
    const [currentPage, setCurrentPage] = useState(1);

    const totalPages = Math.ceil(items.length / itemsPerPage);
    const paginatedItems = items.slice(
        (currentPage - 1) * itemsPerPage,
        currentPage * itemsPerPage
    );

    const openLightbox = useCallback((item, index) => {
        setCurrentItem(item);
        setCurrentIndex(index);
        setLightboxOpen(true);
        document.body.style.overflow = 'hidden';
    }, []);

    const closeLightbox = useCallback(() => {
        setLightboxOpen(false);
        setCurrentItem(null);
        document.body.style.overflow = 'unset';
    }, []);

    const navigateLightbox = useCallback((direction) => {
        const newIndex = direction === 'next'
            ? (currentIndex + 1) % items.length
            : (currentIndex - 1 + items.length) % items.length;
        
        setCurrentIndex(newIndex);
        setCurrentItem(items[newIndex]);
    }, [currentIndex, items]);

    const goToPage = useCallback((page) => {
        setCurrentPage(Math.max(1, Math.min(page, totalPages)));
    }, [totalPages]);

    // Keyboard navigation
    useEffect(() => {
        if (!lightboxOpen) return;

        const handleKeyDown = (e) => {
            switch (e.key) {
                case 'Escape':
                    closeLightbox();
                    break;
                case 'ArrowLeft':
                    if (items.length > 1) navigateLightbox('prev');
                    break;
                case 'ArrowRight':
                    if (items.length > 1) navigateLightbox('next');
                    break;
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [lightboxOpen, items.length, navigateLightbox, closeLightbox]);

    return {
        // Lightbox state
        lightboxOpen,
        currentItem,
        currentIndex,
        openLightbox,
        closeLightbox,
        navigateLightbox,
        
        // Pagination state
        currentPage,
        totalPages,
        paginatedItems,
        goToPage,
        setCurrentPage,
    };
}

export default useMedia;
