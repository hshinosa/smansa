import { useMemo, useCallback } from 'react';

/**
 * useDate Hook
 * 
 * Provides date formatting utilities for Indonesian locale.
 * 
 * @returns {Object} Date formatting functions
 */
export function useDate() {
    const formatDate = useCallback((date, options = {}) => {
        const { 
            day = 'numeric', 
            month = 'long', 
            year = 'numeric',
            hour,
            minute
        } = options;

        const dateObj = date instanceof Date ? date : new Date(date);
        
        if (isNaN(dateObj.getTime())) {
            return '-';
        }

        const formatOptions = { day, month, year };
        if (hour) formatOptions.hour = hour;
        if (minute) formatOptions.minute = minute;

        return dateObj.toLocaleDateString('id-ID', formatOptions);
    }, []);

    const formatDateTime = useCallback((date) => {
        return formatDate(date, { 
            day: 'numeric', 
            month: 'short', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }, [formatDate]);

    const formatShortDate = useCallback((date) => {
        return formatDate(date, { 
            day: 'numeric', 
            month: 'short', 
            year: 'numeric' 
        });
    }, [formatDate]);

    const formatRelative = useCallback((date) => {
        const now = new Date();
        const dateObj = date instanceof Date ? date : new Date(date);
        const diffInMs = now - dateObj;
        const diffInSecs = Math.floor(diffInMs / 1000);
        const diffInMins = Math.floor(diffInSecs / 60);
        const diffInHours = Math.floor(diffInMins / 60);
        const diffInDays = Math.floor(diffInHours / 24);

        if (diffInSecs < 60) return 'Baru saja';
        if (diffInMins < 60) return `${diffInMins} menit yang lalu`;
        if (diffInHours < 24) return `${diffInHours} jam yang lalu`;
        if (diffInDays < 7) return `${diffInDays} hari yang lalu`;
        
        return formatShortDate(date);
    }, [formatShortDate]);

    return {
        formatDate,
        formatDateTime,
        formatShortDate,
        formatRelative,
    };
}

export default useDate;
