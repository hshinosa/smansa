import { useState, useMemo, useCallback } from 'react';

/**
 * useSearchFilter Hook
 * 
 * Manages search and filter state for lists and tables.
 * Supports multiple filter categories and search queries.
 * 
 * @param {Array} items - Array of items to filter
 * @param {Object} options - Configuration options
 * @param {string} options.searchFields - Fields to search in (default: ['title', 'description'])
 * @param {string} options.categoryField - Field name for category filtering (default: 'category')
 * @returns {Object} Filter state and handlers
 */
export function useSearchFilter(items = [], options = {}) {
    const { 
        searchFields = ['title', 'description', 'excerpt'],
        categoryField = 'category',
        initialCategory = 'Semua'
    } = options;

    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState(initialCategory);

    const filteredItems = useMemo(() => {
        return items.filter(item => {
            const matchesSearch = searchQuery
                ? searchFields.some(field => 
                    item[field]?.toLowerCase().includes(searchQuery.toLowerCase())
                )
                : true;
            
            const matchesCategory = activeCategory === initialCategory
                ? true
                : item[categoryField] === activeCategory;
            
            return matchesSearch && matchesCategory;
        });
    }, [items, searchQuery, activeCategory, searchFields, categoryField, initialCategory]);

    const categories = useMemo(() => {
        const uniqueCats = new Set(
            items
                .map(item => item[categoryField])
                .filter(cat => cat && typeof cat === 'string' && cat.trim() !== '')
        );
        return [initialCategory, ...Array.from(uniqueCats).sort()];
    }, [items, categoryField, initialCategory]);

    const resetFilters = useCallback(() => {
        setSearchQuery('');
        setActiveCategory(initialCategory);
    }, [initialCategory]);

    const hasActiveFilters = searchQuery !== '' || activeCategory !== initialCategory;

    return {
        searchQuery,
        setSearchQuery,
        activeCategory,
        setActiveCategory,
        filteredItems,
        categories,
        resetFilters,
        hasActiveFilters,
    };
}

/**
 * useToggle Hook
 * 
 * Simple boolean toggle state for modals, dropdowns, etc.
 * 
 * @param {boolean} initialState - Initial state (default: false)
 * @returns {Object} Toggle state and handlers
 */
export function useToggle(initialState = false) {
    const [isOpen, setIsOpen] = useState(initialState);

    const open = useCallback(() => setIsOpen(true), []);
    const close = useCallback(() => setIsOpen(false), []);
    const toggle = useCallback(() => setIsOpen(prev => !prev), []);

    return {
        isOpen,
        setIsOpen,
        open,
        close,
        toggle,
    };
}

export default useSearchFilter;
