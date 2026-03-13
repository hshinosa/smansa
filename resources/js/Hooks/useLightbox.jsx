import { useState, useCallback, useEffect } from 'react';

/**
 * useLightbox Hook
 * 
 * Manages lightbox/modal state for image galleries.
 * Includes keyboard navigation and body scroll locking.
 * 
 * @param {Array} items - Gallery items array
 * @returns {Object} Lightbox state and handlers
 */
export function useLightbox(items = []) {
    const [isOpen, setIsOpen] = useState(false);
    const [currentIndex, setCurrentIndex] = useState(0);
    const [currentItem, setCurrentItem] = useState(null);

    const open = useCallback((item, index) => {
        setCurrentItem(item);
        setCurrentIndex(index);
        setIsOpen(true);
        document.body.style.overflow = 'hidden';
    }, []);

    const close = useCallback(() => {
        setIsOpen(false);
        setCurrentItem(null);
        document.body.style.overflow = 'unset';
    }, []);

    const next = useCallback(() => {
        if (items.length <= 1) return;
        const newIndex = (currentIndex + 1) % items.length;
        setCurrentIndex(newIndex);
        setCurrentItem(items[newIndex]);
    }, [currentIndex, items]);

    const prev = useCallback(() => {
        if (items.length <= 1) return;
        const newIndex = (currentIndex - 1 + items.length) % items.length;
        setCurrentIndex(newIndex);
        setCurrentItem(items[newIndex]);
    }, [currentIndex, items]);

    const goTo = useCallback((index) => {
        if (index >= 0 && index < items.length) {
            setCurrentIndex(index);
            setCurrentItem(items[index]);
        }
    }, [items]);

    // Keyboard navigation
    useEffect(() => {
        if (!isOpen) return;

        const handleKeyDown = (e) => {
            switch (e.key) {
                case 'Escape':
                    close();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    prev();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    next();
                    break;
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, close, next, prev]);

    return {
        isOpen,
        currentItem,
        currentIndex,
        open,
        close,
        next,
        prev,
        goTo,
        hasNext: items.length > 1,
        hasPrev: items.length > 1,
        total: items.length,
    };
}

export default useLightbox;
