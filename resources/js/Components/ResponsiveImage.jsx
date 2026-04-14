import React, { useState } from 'react';
import { normalizeUrl } from '@/Utils/imageUtils';

/**
 * ResponsiveImage Component
 * 
 * Automatically renders responsive images with WebP support and fallbacks
 * Supports both static images (public folder) and Spatie Media Library images
 * Features skeleton placeholder and fade-in transition for better UX
 */
export default function ResponsiveImage({
    src,
    media,
    alt = '',
    className = '',
    loading = 'lazy',
    fetchpriority,
    width,
    height,
    sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 80vw, 1280px',
    fallback = null,
    eager = false,
    skeleton = false,
    ...props
}) {
    const [imageError, setImageError] = useState(false);
    const [isLoaded, setIsLoaded] = useState(eager);
    
    const handleLoad = () => {
        setIsLoaded(true);
    };
    
    const handleError = () => {
        setImageError(true);
    };
    // Helper to check if URL is a Spatie Media Library path
    const isSpatieMediaPath = (url) => {
        if (!url) return false;
        const normalized = normalizeUrl(url);
        // Pattern: /storage/{id or hash}/{filename} - but NOT /storage/conversions/
        return normalized && 
               normalized.startsWith('/storage/') && 
               !normalized.includes('/conversions/') &&
               /^\/storage\/[^\/]+\/[^\/]+$/.test(normalized);
    };

    // Helper to get Spatie media conversion URL
    const getSpatieConversionUrl = (url, conversionName) => {
        const normalized = normalizeUrl(url);
        if (!normalized) return null;
        
        // Pattern: /storage/{id}/{filename}
        const match = normalized.match(/^\/storage\/([^\/]+)\/([^\/]+)$/);
        if (!match) return null;
        
        const [, id, filename] = match;
        // Conversion path: /storage/{id}/conversions/{basename}-{conversion}.webp
        const lastDot = filename.lastIndexOf('.');
        const baseFilename = lastDot > 0 ? filename.substring(0, lastDot) : filename;
        return `/storage/${id}/conversions/${baseFilename}-${conversionName}.webp`;
    };

    // If image failed to load, render fallback when provided.
    // Otherwise return nothing so the parent placeholder can remain visible.
    if (imageError) {
        return fallback || null;
    }
    
    // If Spatie media object is provided, use media library conversions
    if (media) {
        const originalUrl = normalizeUrl(media.original_url);
        return (
            <div className={skeleton && !isLoaded ? 'relative' : ''}>
                {skeleton && !isLoaded && !imageError && (
                    <div 
                        className="absolute inset-0 bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 animate-pulse rounded"
                        style={{ minHeight: height || 200 }}
                    />
                )}
                <picture className={skeleton && !isLoaded ? 'opacity-0' : ''}>
                    {media.conversions?.mobile && (
                        <source
                            media="(max-width: 640px)"
                            srcSet={normalizeUrl(media.conversions.mobile)}
                            type="image/webp"
                        />
                    )}
                    {media.conversions?.tablet && (
                        <source
                            media="(max-width: 1024px)"
                            srcSet={normalizeUrl(media.conversions.tablet)}
                            type="image/webp"
                        />
                    )}
                    {media.conversions?.desktop && (
                        <source
                            media="(min-width: 1025px)"
                            srcSet={normalizeUrl(media.conversions.desktop)}
                            type="image/webp"
                        />
                    )}
                    <source
                        srcSet={normalizeUrl(media.conversions?.webp) || originalUrl}
                        type="image/webp"
                    />
                    <img
                        src={originalUrl}
                        alt={alt}
                        className={`${className} transition-opacity duration-300`}
                        loading={eager ? 'eager' : loading}
                        fetchpriority={fetchpriority}
                        width={width}
                        height={height}
                        onLoad={handleLoad}
                        onError={handleError}
                        {...props}
                    />
                </picture>
            </div>
        );
    }

    // Normalize the source URL
    const normalizedSrc = normalizeUrl(src);
    
    // If no valid source, render fallback or nothing
    if (!normalizedSrc) {
        return fallback || null;
    }

    return (
        <div className={skeleton && !isLoaded ? 'relative' : ''}>
            {skeleton && !isLoaded && !imageError && (
                <div 
                    className="absolute inset-0 bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 animate-pulse rounded"
                    style={{ minHeight: height || 200 }}
                />
            )}
            <img
                src={normalizedSrc}
                alt={alt}
                className={`${className} transition-opacity duration-300`}
                loading={eager ? 'eager' : loading}
                fetchpriority={fetchpriority}
                width={width}
                height={height}
                onLoad={handleLoad}
                onError={handleError}
                {...props}
            />
        </div>
    );
}

/**
 * HeroImage Component
 * Preset for hero/banner images with optimal settings
 */
export function HeroImage({ src, media, alt, className = '', ...props }) {
    return (
        <ResponsiveImage
            src={src}
            media={media}
            alt={alt}
            className={`w-full h-full object-cover ${className}`}
            loading="eager"
            fetchpriority="high"
            width={1920}
            height={1080}
            {...props}
        />
    );
}

/**
 * ContentImage Component  
 * Preset for content/article images
 */
export function ContentImage({ src, media, alt, className = '', ...props }) {
    return (
        <ResponsiveImage
            src={src}
            media={media}
            alt={alt}
            className={`w-full h-auto object-cover ${className}`}
            loading="lazy"
            width={1200}
            height={675}
            {...props}
        />
    );
}

/**
 * GalleryImage Component
 * Preset for gallery/grid images
 */
export function GalleryImage({ src, media, alt, className = '', ...props }) {
    return (
        <ResponsiveImage
            src={src}
            media={media}
            alt={alt}
            className={`w-full h-full object-cover ${className}`}
            loading="lazy"
            width={800}
            height={600}
            {...props}
        />
    );
}

/**
 * ThumbnailImage Component
 * Preset for small thumbnail images
 */
export function ThumbnailImage({ src, media, alt, className = '', ...props }) {
    return (
        <ResponsiveImage
            src={src}
            media={media}
            alt={alt}
            className={`object-cover ${className}`}
            loading="lazy"
            width={200}
            height={200}
            {...props}
        />
    );
}
