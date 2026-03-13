import { useState, useMemo, useCallback } from 'react';

/**
 * usePagination Hook
 * 
 * Manages pagination state and logic for lists and tables.
 * 
 * @param {Array} items - Array of items to paginate
 * @param {Object} options - Configuration options
 * @param {number} options.itemsPerPage - Number of items per page (default: 10)
 * @param {number} options.initialPage - Initial page number (default: 1)
 * @returns {Object} Pagination state and handlers
 */
export function usePagination(items = [], options = {}) {
    const { itemsPerPage = 10, initialPage = 1 } = options;
    
    const [currentPage, setCurrentPage] = useState(initialPage);

    const totalPages = useMemo(() => {
        return Math.max(1, Math.ceil(items.length / itemsPerPage));
    }, [items.length, itemsPerPage]);

    const paginatedItems = useMemo(() => {
        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        return items.slice(start, end);
    }, [items, currentPage, itemsPerPage]);

    const goToPage = useCallback((page) => {
        const validPage = Math.max(1, Math.min(page, totalPages));
        setCurrentPage(validPage);
    }, [totalPages]);

    const nextPage = useCallback(() => {
        if (currentPage < totalPages) {
            setCurrentPage(prev => prev + 1);
        }
    }, [currentPage, totalPages]);

    const previousPage = useCallback(() => {
        if (currentPage > 1) {
            setCurrentPage(prev => prev - 1);
        }
    }, [currentPage]);

    const resetPagination = useCallback(() => {
        setCurrentPage(initialPage);
    }, [initialPage]);

    const paginationInfo = useMemo(() => {
        const startItem = items.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, items.length);
        return {
            startItem,
            endItem,
            totalItems: items.length,
            hasNextPage: currentPage < totalPages,
            hasPreviousPage: currentPage > 1,
        };
    }, [currentPage, items.length, itemsPerPage, totalPages]);

    return {
        currentPage,
        totalPages,
        paginatedItems,
        goToPage,
        nextPage,
        previousPage,
        resetPagination,
        setCurrentPage,
        ...paginationInfo,
    };
}

/**
 * usePaginatedList Hook
 * 
 * Combines pagination with filtering for complete list management.
 * 
 * @param {Array} items - Array of items
 * @param {Object} options - Configuration options
 * @returns {Object} Combined pagination and filtered list state
 */
export function usePaginatedList(items = [], options = {}) {
    const { itemsPerPage = 10, initialPage = 1 } = options;
    
    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState('Semua');
    const [visibleCount, setVisibleCount] = useState(itemsPerPage);

    const filteredItems = useMemo(() => {
        return items.filter(item => {
            const matchesSearch = searchQuery
                ? (item.title?.toLowerCase().includes(searchQuery.toLowerCase()) ||
                   item.excerpt?.toLowerCase().includes(searchQuery.toLowerCase()) ||
                   item.description?.toLowerCase().includes(searchQuery.toLowerCase()))
                : true;
            
            const matchesCategory = activeCategory === 'Semua' 
                ? true 
                : item.category === activeCategory;
            
            return matchesSearch && matchesCategory;
        });
    }, [items, searchQuery, activeCategory]);

    const displayedItems = useMemo(() => {
        return filteredItems.slice(0, visibleCount);
    }, [filteredItems, visibleCount]);

    const hasMoreItems = filteredItems.length > visibleCount;

    const loadMore = useCallback(() => {
        setVisibleCount(prev => prev + itemsPerPage);
    }, [itemsPerPage]);

    const resetFilters = useCallback(() => {
        setSearchQuery('');
        setActiveCategory('Semua');
        setVisibleCount(itemsPerPage);
    }, [itemsPerPage]);

    const categories = useMemo(() => {
        const uniqueCats = new Set(
            items
                .map(item => item.category)
                .filter(cat => cat && typeof cat === 'string' && cat.trim() !== '')
        );
        return ['Semua', ...Array.from(uniqueCats).sort()];
    }, [items]);

    return {
        searchQuery,
        setSearchQuery,
        activeCategory,
        setActiveCategory,
        filteredItems,
        displayedItems,
        hasMoreItems,
        loadMore,
        resetFilters,
        categories,
        visibleCount,
    };
}

export default usePagination;
