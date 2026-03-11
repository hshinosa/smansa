import { useState, useEffect, useRef } from 'react';
import { clsx } from 'clsx';

/**
 * LazyImage Component
 * Implements native lazy loading with blur-up effect and progressive loading
 * 
 * @param {string} src - Image source URL
 * @param {string} alt - Alt text for accessibility
 * @param {object} conversions - Object containing different image sizes { mobile, tablet, desktop, webp }
 * @param {string} fallbackSrc - Fallback image URL
 * @param {string} className - Additional CSS classes
 * @param {object} style - Inline styles
 * @param {function} onLoad - Callback when image loads
 * @param {function} onError - Callback when image fails to load
 */
export default function LazyImage({
    src,
    alt = '',
    conversions = {},
    fallbackSrc = null,
    className = '',
    style = {},
    onLoad,
    onError,
    loading = 'lazy',
    decoding = 'async',
    ...props
}) {
    const [isLoaded, setIsLoaded] = useState(false);
    const [isInView, setIsInView] = useState(false);
    const [currentSrc, setCurrentSrc] = useState(null);
    const [hasError, setHasError] = useState(false);
    const imgRef = useRef(null);

    // Determine the best image source based on viewport
    const getResponsiveSrc = () => {
        if (!src && !conversions.webp && !conversions.mobile) {
            return fallbackSrc || null;
        }

        const width = typeof window !== 'undefined' ? window.innerWidth : 1920;

        if (conversions.desktop && width >= 1280) {
            return conversions.desktop;
        }
        if (conversions.tablet && width >= 768) {
            return conversions.tablet;
        }
        if (conversions.mobile && width >= 375) {
            return conversions.mobile;
        }
        if (conversions.webp) {
            return conversions.webp;
        }
        if (conversions.original_url) {
            return conversions.original_url;
        }

        return src || fallbackSrc;
    };

    // Use Intersection Observer for lazy loading
    useEffect(() => {
        if (!imgRef.current) return;

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setIsInView(true);
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                rootMargin: '50px 0px', // Start loading 50px before entering viewport
                threshold: 0.01,
            }
        );

        observer.observe(imgRef.current);

        return () => {
            if (imgRef.current) {
                observer.unobserve(imgRef.current);
            }
        };
    }, []);

    // Handle successful load
    const handleLoad = () => {
        setIsLoaded(true);
        onLoad?.();
    };

    // Handle error
    const handleError = () => {
        setHasError(true);
        onError?.();
    };

    // Get current source
    const imageSrc = isInView ? getResponsiveSrc() : null;

    return (
        <div
            ref={imgRef}
            className={clsx(
                'lazy-image-container',
                className
            )}
            style={{
                position: 'relative',
                overflow: 'hidden',
                backgroundColor: '#f3f4f6',
                ...style,
            }}
            {...props}
        >
            {/* Placeholder / Loading state */}
            {!isLoaded && !hasError && imageSrc && (
                <div
                    className="lazy-image-placeholder"
                    style={{
                        position: 'absolute',
                        inset: 0,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: 'linear-gradient(90deg, #f3f4f6 0%, #e5e7eb 50%, #f3f4f6 100%)',
                        backgroundSize: '200% 100%',
                        animation: 'lazy-image-shimmer 1.5s infinite',
                    }}
                >
                    <svg
                        className="w-8 h-8 text-gray-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        style={{
                            animation: 'lazy-image-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        }}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={1.5}
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                </div>
            )}

            {/* Main image */}
            {imageSrc && !hasError && (
                <img
                    src={imageSrc}
                    alt={alt}
                    loading={loading}
                    decoding={decoding}
                    onLoad={handleLoad}
                    onError={handleError}
                    className="lazy-image"
                    style={{
                        opacity: isLoaded ? 1 : 0,
                        transition: 'opacity 0.3s ease-in-out',
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover',
                        display: 'block',
                    }}
                    srcSet={
                        conversions.mobile && conversions.tablet && conversions.desktop
                            ? `${conversions.mobile} 375w, ${conversions.tablet} 768w, ${conversions.desktop} 1280w`
                            : undefined
                    }
                    sizes={
                        conversions.mobile && conversions.tablet && conversions.desktop
                            ? '(max-width: 640px) 375px, (max-width: 1024px) 768px, 1280px'
                            : undefined
                    }
                />
            )}

            {/* Error fallback */}
            {hasError && (
                <img
                    src={fallbackSrc}
                    alt={alt}
                    loading="eager"
                    className="lazy-image-error"
                    style={{
                        opacity: 1,
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover',
                    }}
                />
            )}

            <style jsx>{`
                @keyframes lazy-image-shimmer {
                    0% {
                        background-position: 200% 0;
                    }
                    100% {
                        background-position: -200% 0;
                    }
                }
                @keyframes lazy-image-pulse {
                    0%, 100% {
                        opacity: 1;
                    }
                    50% {
                        opacity: 0.5;
                    }
                }
            `}</style>
        </div>
    );
}

/**
 * LazyBackground Component
 * For background images that should be lazy loaded
 */
export function LazyBackground({ 
    imageUrl, 
    children, 
    className = '',
    style = {},
    ...props 
}) {
    const [isLoaded, setIsLoaded] = useState(false);
    const [isInView, setIsInView] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        if (!containerRef.current) return;

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setIsInView(true);
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                rootMargin: '100px 0px',
                threshold: 0.01,
            }
        );

        observer.observe(containerRef.current);

        return () => {
            if (containerRef.current) {
                observer.unobserve(containerRef.current);
            }
        };
    }, []);

    const backgroundImage = isInView && imageUrl ? `url(${imageUrl})` : 'none';

    return (
        <div
            ref={containerRef}
            className={className}
            style={{
                backgroundImage,
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                transition: 'background-image 0.3s ease-in-out',
                opacity: isLoaded ? 1 : 0,
                ...style,
            }}
            {...props}
        >
            {children}
        </div>
    );
}