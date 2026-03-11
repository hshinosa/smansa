// FILE: resources/js/Layouts/AdminLayout.jsx
// Redesigned for accessibility (ages 20-50) with clearer navigation and larger touch targets

import React, { useState, useEffect } from 'react';
import { Link, usePage, Head } from '@inertiajs/react';
import {
    ChevronDown, ChevronRight, LayoutDashboard, LogOut, Newspaper,
    CalendarDays, Info, FileText, Image as ImageIcon, LayoutGrid,
    Menu as MenuIcon, X as XIcon, UserCog, Star, Mail, Settings,
    BookOpen, GraduationCap, Users, Award, Sparkles, Database
} from 'lucide-react';
import Dropdown from '@/Components/Dropdown';
import { Transition } from '@headlessui/react';
import ChatWidget from '@/Components/ChatWidget';
import { Toaster } from 'react-hot-toast';
import ErrorBoundary from '@/Components/ErrorBoundary';

// Icons mapping for consistent visual language
const getIconComponent = (iconName) => {
    const icons = {
        LayoutDashboard, LogOut, Newspaper, CalendarDays, Info, FileText,
        ImageIcon, LayoutGrid, MenuIcon, UserCog, Star, Mail, Settings,
        BookOpen, GraduationCap, Users, Award, Sparkles, Database
    };
    return icons[iconName] || LayoutGrid;
};

const SidebarItem = ({ href, icon: Icon, children, isActive, hasSubmenu, isOpen, onToggle, level = 0, isMobile = false, showAlert = false }) => {
    const activeClass = isActive 
        ? 'bg-accent-yellow text-gray-900 font-bold shadow-sm' 
        : isMobile 
            ? 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
            : 'text-white hover:bg-white/10 hover:text-white';
    
    const iconClass = isActive 
        ? 'text-gray-900' 
        : isMobile 
            ? 'text-gray-600' 
            : 'text-white';
    
    const IconComponent = Icon ? getIconComponent(Icon) : null;
    const paddingLeft = level === 0 ? 'pl-4' : `pl-${8 + level * 4}`;

    return (
        <li className="my-1">
            {hasSubmenu ? (
                <button
                    onClick={onToggle}
                    className={`flex items-center justify-between w-full py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 ${activeClass} ${paddingLeft}`}
                    aria-expanded={isOpen}
                    aria-controls={`submenu-${level}`}
                >
                    <span className="flex items-center">
                        {IconComponent && <IconComponent size={20} className={`mr-3 flex-shrink-0 ${iconClass}`} />}
                        <span className="text-base">{children}</span>
                        {showAlert && (
                            <span className="ml-2 inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        )}
                    </span>
                    <ChevronDown 
                        size={18} 
                        className={`transform transition-transform duration-200 ${isOpen ? 'rotate-180' : ''} ml-2 flex-shrink-0 ${iconClass}`} 
                    />
                </button>
            ) : (
                <Link
                    href={href || '#'}
                    className={`flex items-center py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 block ${activeClass} ${paddingLeft}`}
                >
                    {IconComponent && <IconComponent size={20} className={`mr-3 flex-shrink-0 ${iconClass}`} />}
                    <span className="text-base">{children}</span>
                    {showAlert && (
                        <span className="ml-2 inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    )}
                </Link>
            )}
        </li>
    );
};

const SubmenuItem = ({ href, children, isActive, level = 1, isMobile = false }) => {
    const activeClass = isActive 
        ? 'text-primary font-bold bg-primary/5 border-l-4 border-primary' 
        : isMobile
            ? 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            : 'text-white hover:text-white hover:bg-white/5';
    
    return (
        <li className="my-1">
            <Link
                href={href || '#'}
                className={`flex items-center py-2.5 px-4 text-sm rounded-md transition-all duration-200 ml-4 ${activeClass}`}
            >
                <span className="text-base">{children}</span>
            </Link>
        </li>
    );
};

export default function AdminLayout({ children, headerTitle = "Dashboard Utama" }) {
    const { auth, siteSettings, queueHealthBadge } = usePage().props;
    const admin = auth?.admin;
    
    // Image URL handling with proper fallback
    const getAssetUrl = (path, fallback) => {
        if (!path) return fallback;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    const logoSekolah = getAssetUrl(siteSettings?.general?.site_logo, '/images/logo-sman1-baleendah.png');
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    // Auto-expand menu based on current route
    const getDefaultOpenMenus = () => {
        const currentRoute = route().current() || '';
        const masterDataRoutes = ['admin.posts.*', 'admin.teachers.*', 'admin.extracurriculars.*', 'admin.alumni.*', 'admin.ptn-admissions.*', 'admin.tka-averages.*', 'admin.galleries.*', 'admin.faqs.*'];
        const kontenUtamaRoutes = ['admin.landingpage.content.*', 'admin.spmb.*', 'admin.program-studi.*', 'admin.school-profile.*', 'admin.curriculum.*', 'admin.academic-calendar.*'];
        const aiRagRoutes = ['admin.rag-documents.*', 'admin.ai-settings.*'];

        const isMasterDataActive = masterDataRoutes.some(route => currentRoute.startsWith(route.replace('.*', '')));
        const isKontenUtamaActive = kontenUtamaRoutes.some(route => currentRoute.startsWith(route.replace('.*', '')));
        const isAiRagActive = aiRagRoutes.some(route => currentRoute.startsWith(route.replace('.*', '')));

        return {
            konten_utama: isKontenUtamaActive,
            master_data: isMasterDataActive,
            ai_rag: isAiRagActive,
        };
    };

    const [openMenus, setOpenMenus] = useState(getDefaultOpenMenus);

    const toggleMenu = (menuName) => {
        setOpenMenus(prev => ({ ...prev, [menuName]: !prev[menuName] }));
    };

    const closeMobileMenu = () => setMobileMenuOpen(false);
    const toggleMobileMenu = () => setMobileMenuOpen(prev => !prev);

    // Navigation structure with clear labels for accessibility
    const navigationStructure = [
        { 
            title: "Dashboard Utama", 
            href: route('admin.dashboard'), 
            icon: 'LayoutDashboard', 
            routeName: 'admin.dashboard' 
        },
        {
            title: "Kelola Konten Utama",
            icon: 'LayoutGrid',
            submenuKey: 'konten_utama',
            sublinks: [
                { title: "Landing Page", href: route('admin.landingpage.content.index'), routeName: 'admin.landingpage.content.*' },
                { title: "Informasi SPMB", href: route('admin.spmb.index'), routeName: 'admin.spmb.*' },
                { title: "Program Studi", href: route('admin.program-studi.index'), routeName: 'admin.program-studi.*' },
                { title: "Profil Sekolah", href: route('admin.school-profile.index'), routeName: 'admin.school-profile.*' },
                { title: "Kurikulum", href: route('admin.curriculum.index'), routeName: 'admin.curriculum.*' },
                { title: "Kalender Akademik", href: route('admin.academic-calendar.index'), routeName: 'admin.academic-calendar.*' },
            ]
        },
        {
            title: "Master Data & Berita",
            icon: 'Newspaper',
            submenuKey: 'master_data',
            sublinks: [
                { title: "Berita & Pengumuman", href: route('admin.posts.index'), routeName: 'admin.posts.*' },
                { title: "Instagram Bots", href: route('admin.instagram-bots.index'), routeName: 'admin.instagram-bots.*' },
                { title: "Guru & Staff", href: route('admin.teachers.index'), routeName: 'admin.teachers.*' },
                { title: "Ekstrakurikuler", href: route('admin.extracurriculars.index'), routeName: 'admin.extracurriculars.*' },
                { title: "Jejak Alumni", href: route('admin.alumni.index'), routeName: 'admin.alumni.*' },
                { title: "Serapan PTN", href: route('admin.ptn-admissions.index'), routeName: 'admin.ptn-admissions.*' },
                { title: "Hasil TKA", href: route('admin.tka-averages.index'), routeName: 'admin.tka-averages.*' },
                { title: "Galeri Sekolah", href: route('admin.galleries.index'), routeName: 'admin.galleries.*' },
                { title: "FAQ", href: route('admin.faqs.index'), routeName: 'admin.faqs.*' },
            ]
        },
        {
            title: "Pesan Kontak",
            icon: 'Mail',
            href: route('admin.contact-messages.index'),
            routeName: 'admin.contact-messages.*',
        },
        {
            title: "AI & RAG System",
            icon: 'Sparkles',
            submenuKey: 'ai_rag',
            sublinks: [
                { title: "RAG Documents", href: route('admin.rag-documents.index'), routeName: 'admin.rag-documents.*' },
                {
                    title: "AI Settings",
                    href: route('admin.ai-settings.index'),
                    routeName: 'admin.ai-settings.*',
                    alert: queueHealthBadge?.status === 'alert',
                },
            ]
        },
        {
            title: "Pengaturan Situs",
            icon: 'Settings',
            href: route('admin.site-settings.index'),
            routeName: 'admin.site-settings.*',
        }
    ];

    const renderNavItemsRecursive = (items, parentKey, currentLevel, isMobile = false) => {
        return items.map((item, index) => {
            const menuKey = item.submenuKey || `${parentKey}-${index}`;
            const hasSubmenu = !!item.sublinks && item.sublinks.length > 0;
            const isActive = item.routeName 
                ? route().current(item.routeName)
                : (item.href && item.href !== '#' && route().current(item.href));

            return (
                <React.Fragment key={menuKey}>
                    <SidebarItem
                        href={!hasSubmenu ? item.href : undefined}
                        icon={item.icon}
                        isActive={isActive}
                        hasSubmenu={hasSubmenu}
                        isOpen={openMenus[menuKey]}
                        onToggle={hasSubmenu ? () => toggleMenu(menuKey) : undefined}
                        level={currentLevel}
                        isMobile={isMobile}
                        showAlert={!!item.alert}
                    >
                        {item.title}
                    </SidebarItem>
                    {hasSubmenu && openMenus[menuKey] && (
                        <ul id={`submenu-${menuKey}`} className="space-y-1">
                            {renderNavItemsRecursive(item.sublinks, menuKey, currentLevel + 1, isMobile)}
                        </ul>
                    )}
                </React.Fragment>
            );
        });
    };

    return (
        <ErrorBoundary homeUrl={route('admin.dashboard')}>
            <div className="h-screen flex bg-gray-100 overflow-hidden font-sans">
                <Head title={`${headerTitle} - Admin ${siteName}`} />

            {/* Desktop Sidebar - Fixed position with clear visual hierarchy */}
            <aside className="w-72 bg-primary border-r border-white/10 hidden lg:flex lg:flex-col fixed h-screen z-40 shadow-xl">
                {/* Logo Section */}
                <div className="p-4 border-b border-white/10 bg-primary-darker/50">
                    <Link href={route('admin.dashboard')} className="flex items-center px-2 py-2 group">
                        <div className="relative">
                            <img src={logoSekolah} alt={`Logo ${siteName}`} className="h-12 w-auto rounded-lg shadow-sm" />
                        </div>
                        <div className="ml-3 flex-1 min-w-0">
                            <span className="text-sm font-bold text-white block truncate">{siteName}</span>
                            <span className="text-xs text-white/60 block">Panel Admin</span>
                        </div>
                    </Link>
                </div>
                
                {/* Navigation - Scrollable with clear item separation */}
                <nav className="flex-grow p-3 space-y-1 overflow-y-auto scrollbar-thin scrollbar-thumb-white/20 scrollbar-track-transparent">
                    <ul>
                        {renderNavItemsRecursive(navigationStructure, 'sidebar', 0)}
                    </ul>
                </nav>

                {/* Profile Section - Fixed at bottom with clear status indicator */}
                <div className="p-4 border-t border-white/10 bg-primary-darker/50">
                    <Dropdown>
                        <Dropdown.Trigger>
                            <button className="flex items-center w-full text-left p-3 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-accent-yellow">
                                <div className="relative">
                                    <img 
                                        src={logoSekolah} 
                                        alt="Profil Admin" 
                                        className="h-11 w-11 rounded-full object-cover border-2 border-white/20" 
                                    />
                                    <span className="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 border-2 border-primary rounded-full"></span>
                                </div>
                                <div className="ml-3 flex-1 min-w-0">
                                    <p className="font-bold text-white text-sm truncate">
                                        {admin ? admin.username : 'Admin'}
                                    </p>
                                    <p className="text-xs text-white/50 capitalize">Administrator</p>
                                </div>
                                <ChevronDown size={18} className="text-white/40 flex-shrink-0" />
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content align="top" width="56" contentClasses="py-2 bg-white shadow-xl border border-gray-100 rounded-xl overflow-hidden">
                            <div className="px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <div className="flex items-center gap-2">
                                    <div className="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></div>
                                    <span className="text-sm font-medium text-gray-700">Status Online</span>
                                </div>
                            </div>
                            <Dropdown.Link href={route('admin.logout')} method="post" as="button" className="w-full text-left px-4 py-2.5 hover:bg-red-50 transition-colors">
                                <div className="flex items-center text-red-600">
                                    <LogOut size={18} className="mr-2.5" />
                                    <span className="font-semibold text-sm">Keluar / Logout</span>
                                </div>
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </aside>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col lg:pl-72 min-w-0">
                {/* Mobile Header */}
                <header className="lg:hidden bg-white border-b border-gray-200 h-16 flex items-center px-4 sticky top-0 z-40 shadow-sm">
                    <button 
                        onClick={toggleMobileMenu} 
                        className="p-2.5 -ml-2 text-gray-700 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary"
                        aria-label="Buka menu navigasi"
                    >
                        <MenuIcon size={24} />
                    </button>
                    <div className="ml-3 flex items-center gap-3 min-w-0">
                        <img src={logoSekolah} alt="Logo" className="h-9 w-auto flex-shrink-0" />
                        <div className="min-w-0">
                            <h1 className="text-sm font-bold text-gray-800 truncate uppercase tracking-wide">{headerTitle}</h1>
                            <p className="text-xs text-gray-500 truncate">{siteName}</p>
                        </div>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 overflow-y-auto pt-2 sm:pt-4 bg-gray-100">
                    {/* Desktop Page Header */}
                    <div className="px-4 sm:px-6 lg:px-8 mb-4 hidden lg:block">
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <h1 className="text-xl lg:text-2xl font-bold text-gray-900">{headerTitle}</h1>
                            <p className="text-sm text-gray-500 mt-1">Kelola konten dan pengaturan sekolah dengan mudah</p>
                        </div>
                    </div>
                    <div className="px-2 sm:px-4 lg:px-6 pb-6">
                        {children}
                    </div>
                </main>

                {/* Mobile Navigation Drawer */}
                <Transition show={mobileMenuOpen} as={React.Fragment}>
                    <div className="lg:hidden fixed inset-0 z-[70] flex" role="dialog" aria-modal="true" aria-label="Menu navigasi">
                        <Transition.Child
                            as={React.Fragment}
                            enter="transition-opacity ease-linear duration-300"
                            enterFrom="opacity-0"
                            enterTo="opacity-100"
                            leave="transition-opacity ease-linear duration-300"
                            leaveFrom="opacity-100"
                            leaveTo="opacity-0"
                        >
                            <div 
                                className="fixed inset-0 bg-black/50 backdrop-blur-sm" 
                                aria-hidden="true"
                                onClick={closeMobileMenu}
                            ></div>
                        </Transition.Child>
                        
                        <Transition.Child
                            as={React.Fragment}
                            enter="transition ease-in-out duration-300 transform"
                            enterFrom="-translate-x-full"
                            enterTo="translate-x-0"
                            leave="transition ease-in-out duration-300 transform"
                            leaveFrom="translate-x-0"
                            leaveTo="-translate-x-full"
                        >
                            <div className="relative flex flex-col w-80 max-w-[calc(100%-4rem)] bg-white h-full shadow-2xl">
                                {/* Mobile Header */}
                                <div className="p-4 border-b flex justify-between items-center bg-white sticky top-0 z-10">
                                    <Link href={route('admin.dashboard')} className="flex items-center gap-3" onClick={closeMobileMenu}>
                                        <img src={logoSekolah} alt={`Logo ${siteName}`} className="h-11 w-auto" />
                                        <div>
                                            <span className="text-sm font-bold text-gray-800 block">{siteName}</span>
                                            <span className="text-xs text-gray-500">Admin Panel</span>
                                        </div>
                                    </Link>
                                    <button 
                                        onClick={closeMobileMenu} 
                                        className="p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors"
                                        aria-label="Tutup menu"
                                    >
                                        <XIcon size={22} />
                                    </button>
                                </div>
                                
                                {/* Mobile Navigation */}
                                <nav className="flex-grow p-3 space-y-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-200 scrollbar-track-transparent">
                                    <ul>
                                        {renderNavItemsRecursive(navigationStructure, 'mobile', 0, true)}
                                    </ul>
                                </nav>
                                
                                {/* Mobile Profile */}
                                <div className="p-4 border-t border-gray-100 bg-gray-50">
                                    <div className="flex items-center justify-between p-3 bg-white rounded-xl shadow-sm border border-gray-200">
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className="relative flex-shrink-0">
                                                <img 
                                                    src={logoSekolah} 
                                                    alt="Profil Admin" 
                                                    className="h-11 w-11 rounded-full object-cover border-2 border-primary/20" 
                                                />
                                                <span className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                                            </div>
                                            <div className="min-w-0">
                                                <p className="font-bold text-gray-900 text-sm truncate">{admin ? admin.username : 'Admin'}</p>
                                                <p className="text-xs text-gray-500 capitalize">Administrator</p>
                                            </div>
                                        </div>
                                        <Link 
                                            href={route('admin.logout')} 
                                            method="post" 
                                            as="button" 
                                            className="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-colors"
                                            title="Keluar"
                                        >
                                            <LogOut size={22} />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </Transition.Child>
                    </div>
                </Transition>

                {/* <ChatWidget /> */}
                <Toaster position="top-right" reverseOrder={false} gutter={8} toastOptions={{
                    duration: 4000,
                    style: {
                        background: '#fff',
                        color: '#374151',
                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                        borderRadius: '0.75rem',
                        padding: '1rem',
                    },
                    success: {
                        iconTheme: {
                            primary: '#10B981',
                            secondary: '#fff',
                        },
                    },
                    error: {
                        iconTheme: {
                            primary: '#EF4444',
                            secondary: '#fff',
                        },
                    },
                }} />
            </div>
        </div>
        </ErrorBoundary>
    );
};