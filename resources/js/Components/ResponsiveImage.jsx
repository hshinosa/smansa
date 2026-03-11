import React, { useState } from 'react';
import { normalizeUrl } from '@/Utils/imageUtils';

/**
 * ResponsiveImage Component
 * 
 * Automatically renders responsive images with WebP support and fallbacks
 * Supports both static images (public folder) and Spatie Media Library images
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
    ...props
}) {
    const [imageError, setImageError] = useState(false);
    
    // Handle image load error
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

    // If image failed to load and fallback is provided, render fallback
    if (imageError && fallback) {
        return fallback;
    }
    
    // If Spatie media object is provided, use media library conversions
    if (media) {
        const originalUrl = normalizeUrl(media.original_url);
        return (
            <picture>
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
                    className={className}
                    loading={loading}
                    fetchpriority={fetchpriority}
                    width={width}
                    height={height}
                    onError={handleError}
                    {...props}
                />
            </picture>
        );
    }

    // Normalize the source URL
    const normalizedSrc = normalizeUrl(src);
    
    // If no valid source, render fallback or nothing
    if (!normalizedSrc) {
        return fallback || null;
    }

    // For Spatie Media Library URLs - use original for now
    // Conversions will be created automatically on next upload
    // This provides faster page loads without regenerating all media

    // For static images or external URLs - just render img tag
    return (
        <img
            src={normalizedSrc}
            alt={alt}
            className={className}
            loading={loading}
            fetchpriority={fetchpriority}
            width={width}
            height={height}
            onError={handleError}
            {...props}
        />
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
