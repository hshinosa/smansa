import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Info, Menu, X } from 'lucide-react';
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';

export default function Navbar({
    logoSman1,
    profilLinks,
    akademikLinks,
    programStudiLinks
}) {
    const { siteSettings } = usePage().props;
    const navigationData = getNavigationData(siteSettings);

    const finalLogo = logoSman1 || navigationData.logoSman1;
    const finalProfilLinks = profilLinks || navigationData.profilLinks;
    const finalAkademikLinks = akademikLinks || navigationData.akademikLinks;
    const finalProgramStudiLinks = programStudiLinks || navigationData.programStudiLinks;

    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [openMenu, setOpenMenu] = useState(null);
    const [openSubMenu, setOpenSubMenu] = useState(null);
    const [mobileProfilOpen, setMobileProfilOpen] = useState(false);
    const [mobileAkademikOpen, setMobileAkademikOpen] = useState(false);
    const [mobilePrestasiOpen, setMobilePrestasiOpen] = useState(false);

    const toggleMenu = (key) => setOpenMenu((prev) => (prev === key ? null : key));
    const closeMenu = () => {
        setOpenMenu(null);
        setOpenSubMenu(null);
    };

    const toggleSubMenu = (key) => setOpenSubMenu((prev) => (prev === key ? null : key));

    const toggleMobileMenu = () => {
        setMobileMenuOpen((prev) => {
            const next = !prev;
            if (!next) {
                setMobileProfilOpen(false);
                setMobileAkademikOpen(false);
            }
            return next;
        });
    };

    const handleKeyToggle = (key) => (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleMenu(key);
        }
    };

    return (
        <>
            <a href="#main-content" className="skip-link">Lewati ke konten utama</a>
            <nav className="bg-white fixed w-full z-50 shadow-navbar font-sans" role="navigation" aria-label="Navigasi utama">
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between h-24">
                    {/* Logo and School Name */}
                    <div className="flex-shrink-0 flex items-center gap-4">
                        <Link href="/">
                            <img className="h-14 w-auto" src={finalLogo} alt={`Logo ${siteSettings?.general?.site_name || 'SMAN 1 Baleendah'}`} />
                        </Link>
                        <div className="hidden md:block">
                            <Link href="/" className="text-2xl font-bold text-gray-900 block leading-tight font-serif tracking-tight">
                                {siteSettings?.general?.site_name || 'SMA Negeri 1 Baleendah'}
                            </Link>
                            <p className="text-base text-primary font-medium italic mt-0.5 font-sans">
                                Unggul dalam Prestasi, Berkarakter Mulia
                            </p>
                        </div>
                    </div>

                    {/* Menu Navigasi Desktop */}
                    <div className="hidden lg:ml-8 lg:flex lg:space-x-8 items-center">
                        {/* Dropdown Profil */}
                        <div
                            className="relative"
                            onMouseEnter={() => setOpenMenu('profil')}
                            onMouseLeave={closeMenu}
                        >
                            <button
                                className={`${TYPOGRAPHY.navLink} inline-flex items-center focus:outline-none py-2`}
                                aria-haspopup="true"
                                aria-expanded={openMenu === 'profil'}
                                aria-controls="profil-menu"
                                onClick={() => toggleMenu('profil')}
                                onKeyDown={handleKeyToggle('profil')}
                            >
                                <span>Profil</span>
                                <ChevronDown size={20} className="ml-1 text-gray-400 transition-colors" />
                            </button>
                            <div
                                id="profil-menu"
                                className={`absolute left-0 mt-2 w-64 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 transition-all duration-200 ease-out z-20 transform origin-top-left ${openMenu === 'profil' ? 'opacity-100 visible translate-y-0' : 'opacity-0 invisible -translate-y-1'}`}
                                role="menu"
                                aria-orientation="vertical"
                            >
                                <div className="py-2">
                                    {finalProfilLinks.map(link => (
                                        <Link key={link.title} href={link.href} className="block px-6 py-3 text-base text-gray-700 hover:bg-blue-50 hover:text-primary transition-colors font-medium" role="menuitem">
                                            {link.title}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Dropdown Akademik */}
                        <div
                            className="relative"
                            onMouseEnter={() => setOpenMenu('akademik')}
                            onMouseLeave={closeMenu}
                        >
                            <button
                                className={`${TYPOGRAPHY.navLink} inline-flex items-center focus:outline-none py-2`}
                                aria-haspopup="true"
                                aria-expanded={openMenu === 'akademik'}
                                aria-controls="akademik-menu"
                                onClick={() => toggleMenu('akademik')}
                                onKeyDown={handleKeyToggle('akademik')}
                            >
                                <span>Akademik</span>
                                <ChevronDown size={20} className="ml-1 text-gray-400 transition-colors" />
                            </button>
                            <div
                                id="akademik-menu"
                                className={`absolute left-0 mt-2 w-72 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 transition-all duration-200 ease-out z-20 transform origin-top-left ${openMenu === 'akademik' ? 'opacity-100 visible translate-y-0' : 'opacity-0 invisible -translate-y-1'}`}
                                role="menu"
                                aria-orientation="vertical"
                            >
                                <div className="py-2">
                                    {finalAkademikLinks.map(link => (
                                        link.subItems ? (
                                            <div 
                                                key={link.title}
                                                className="relative"
                                                onMouseEnter={() => setOpenSubMenu(link.title)}
                                                onMouseLeave={() => setOpenSubMenu(null)}
                                            >
                                                <div 
                                                    className="flex justify-between items-center px-6 py-3 text-base text-gray-700 hover:bg-blue-50 hover:text-primary cursor-pointer font-medium" 
                                                    role="menuitem" 
                                                    aria-haspopup="true"
                                                    aria-expanded={openSubMenu === link.title}
                                                    onClick={() => toggleSubMenu(link.title)}
                                                >
                                                    <span>{link.title}</span>
                                                    <ChevronRight size={18} className="text-gray-400" />
                                                </div>
                                                <div className={`absolute left-full top-0 ml-1 w-64 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 transition-all duration-200 ease-out z-30 transform origin-top-left ${openSubMenu === link.title ? 'opacity-100 visible translate-x-0' : 'opacity-0 invisible -translate-x-1'}`}>
                                                    <div className="py-2" role="menu" aria-orientation="vertical">
                                                        {link.subItems.map(sub => (
                                                            <Link key={sub.title} href={sub.href} className="block px-6 py-3 text-base text-gray-700 hover:bg-blue-50 hover:text-primary transition-colors font-medium" role="menuitem">
                                                                {sub.title}
                                                            </Link>
                                                        ))}
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <Link key={link.title} href={link.href} className="block px-6 py-3 text-base text-gray-700 hover:bg-blue-50 hover:text-primary transition-colors font-medium" role="menuitem">
                                                {link.title}
                                            </Link>
                                        )
                                    ))}
                                    <div 
                                        className="relative border-t border-gray-100 mt-1 pt-1"
                                        onMouseEnter={() => setOpenSubMenu('program-studi')}
                                        onMouseLeave={() => setOpenSubMenu(null)}
                                    >
                                        <div 
                                            className="flex justify-between items-center px-6 py-3 text-base text-gray-700 hover:bg-blue-50 hover:text-primary cursor-pointer font-medium" 
                                            role="menuitem" 
                                            aria-haspopup="true"
                                            aria-expanded={openSubMenu === 'program-studi'}
                                            onClick={() => toggleSubMenu('program-studi')}
                                        >
                                            <span>Program Studi</span>
                                            <ChevronRight size={18} className="text-gray-400" />
                                        </div>
                                        <div className={`absolute left-full top-0 ml-2 w-64 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 transition-all duration-200 ease-out z-30 transform origin-top-left ${openSubMenu === 'program-studi' ? 'opacity-100 visible translate-x-0' : 'opacity-0 invisible -translate-x-1'}`}>
                                            <div className="py-2" role="menu" aria-orientation="vertical">
                                                {finalProgramStudiLinks.map(prog => (
                                                    <Link key={prog.title} href={prog.href} className="block px-6 py-3 text-base text-gray-700 hover:bg-blue-50 hover:text-primary transition-colors font-medium" role="menuitem">
                                                        {prog.title}
                                                    </Link>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Menu Berita Pengumuman */}
                        <Link href="/berita-pengumuman" className={TYPOGRAPHY.navLink}>
                            Berita
                        </Link>

                        {/* Menu Alumni */}
                        <Link href="/alumni" className={TYPOGRAPHY.navLink}>
                            Alumni
                        </Link>

                        {/* Menu Kontak */}
                        <Link href="/kontak" className={TYPOGRAPHY.navLink}>
                            Kontak
                        </Link>

                        {/* Tombol Info PPDB */}
                        <Link href="/informasi-spmb" className="bg-accent-yellow text-gray-900 hover:bg-[#EAB308] inline-flex items-center px-6 py-3 border border-transparent text-base font-bold rounded-full shadow-md hover:shadow-lg transition-all">
                            <Info size={20} className="mr-2" />
                            Info PPDB
                        </Link>
                    </div>

                    {/* Tombol Menu Mobile */}
                    <div className="-mr-2 flex items-center lg:hidden">
                        <button
                            type="button"
                            onClick={toggleMobileMenu}
                            className="bg-white inline-flex items-center justify-center p-3 rounded-md text-gray-400 hover:text-primary hover:bg-gray-100 focus:outline-none transition-colors"
                            aria-controls="mobile-menu"
                            aria-expanded={mobileMenuOpen}
                            aria-label="Toggle navigasi utama"
                        >
                            <span className="sr-only">Open main menu</span>
                            {mobileMenuOpen ? (
                                <X className="block h-8 w-8" aria-hidden="true" />
                            ) : (
                                <Menu className="block h-8 w-8" aria-hidden="true" />
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* Mobile Menu Content */}
            <div 
                className={`lg:hidden fixed inset-x-0 top-24 bg-white shadow-lg z-40 transition-all duration-300 ease-in-out transform origin-top ${mobileMenuOpen ? 'opacity-100 scale-y-100' : 'opacity-0 scale-y-0 h-0'}`}
                id="mobile-menu"
            >
                <div className="px-4 pt-4 pb-6 space-y-2 max-h-[calc(100vh-6rem)] overflow-y-auto">
                    <div className="border-b border-gray-100 pb-2 mb-2">
                        <div className="group">
                            <button
                                className="w-full text-left px-4 py-4 rounded-lg text-lg font-semibold text-gray-800 hover:bg-gray-50 flex justify-between items-center transition-colors"
                                onClick={() => setMobileProfilOpen((prev) => !prev)}
                                aria-expanded={mobileProfilOpen}
                                aria-controls="mobile-profil-menu"
                            >
                                Profil <ChevronDown size={22} className={`transition-transform duration-200 ${mobileProfilOpen ? 'rotate-180' : ''}`} />
                            </button>
                            <div
                                id="mobile-profil-menu"
                                className={`${mobileProfilOpen ? 'block' : 'hidden'} pl-4 space-y-1 bg-gray-50/50 rounded-b-lg mb-2`}
                            >
                                {finalProfilLinks.map(link => (
                                    <Link key={`mobile-${link.title}`} href={link.href} className="block px-4 py-3 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">
                                        {link.title}
                                    </Link>
                                ))}
                            </div>
                        </div>
                         <div className="group">
                            <button
                                className="w-full text-left px-4 py-4 rounded-lg text-lg font-semibold text-gray-800 hover:bg-gray-50 flex justify-between items-center transition-colors"
                                onClick={() => setMobileAkademikOpen((prev) => !prev)}
                                aria-expanded={mobileAkademikOpen}
                                aria-controls="mobile-akademik-menu"
                            >
                                Akademik <ChevronDown size={22} className={`transition-transform duration-200 ${mobileAkademikOpen ? 'rotate-180' : ''}`} />
                            </button>
                            <div
                                id="mobile-akademik-menu"
                                className={`${mobileAkademikOpen ? 'block' : 'hidden'} pl-4 space-y-1 bg-gray-50/50 rounded-b-lg mb-2`}
                            >
                                {finalAkademikLinks.map(link => (
                                    link.subItems ? (
                                        <div key={`mobile-${link.title}`} className="py-1">
                                            <button
                                                className="w-full text-left px-4 py-3 rounded-md text-base font-medium text-gray-600 hover:text-primary flex justify-between items-center transition-colors"
                                                onClick={() => setMobilePrestasiOpen(!mobilePrestasiOpen)}
                                            >
                                                {link.title} <ChevronDown size={18} className={`transition-transform ${mobilePrestasiOpen ? 'rotate-180' : ''}`} />
                                            </button>
                                            <div className={`${mobilePrestasiOpen ? 'block' : 'hidden'} pl-4 space-y-1 bg-white/50 rounded-lg`}>
                                                {link.subItems.map(sub => (
                                                    <Link key={`mobile-sub-${sub.title}`} href={sub.href} className="block px-4 py-3 rounded-md text-sm font-medium text-gray-500 hover:text-primary transition-colors">
                                                        {sub.title}
                                                    </Link>
                                                ))}
                                            </div>
                                        </div>
                                    ) : (
                                        <Link key={`mobile-${link.title}`} href={link.href} className="block px-4 py-3 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">
                                            {link.title}
                                        </Link>
                                    )
                                ))}
                                <div className="pt-2 border-t border-gray-200/50 mt-1">
                                    <p className="px-4 py-2 text-sm font-bold text-gray-400 uppercase tracking-wider">Program Studi</p>
                                    {finalProgramStudiLinks.map(prog => (
                                        <Link key={`mobile-pk-${prog.title}`} href={prog.href} className="block px-4 py-3 rounded-md text-base font-medium text-gray-600 hover:text-primary hover:bg-blue-50 transition-colors">
                                            {prog.title}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <Link href="/berita-pengumuman" className="block px-4 py-4 rounded-lg text-lg font-semibold text-gray-800 hover:bg-gray-50 transition-colors">
                        Berita Pengumuman
                    </Link>

                    <Link href="/alumni" className="block px-4 py-4 rounded-lg text-lg font-semibold text-gray-800 hover:bg-gray-50 transition-colors">
                        Alumni
                    </Link>

                    <Link href="/kontak" className="block px-4 py-4 rounded-lg text-lg font-semibold text-gray-800 hover:bg-gray-50 transition-colors">
                        Kontak
                    </Link>

                    <div className="pt-4 mt-4 border-t border-gray-100">
                        <Link href="/informasi-spmb" className="flex items-center justify-center w-full px-4 py-4 bg-accent-yellow text-gray-900 rounded-xl text-lg font-bold shadow-sm hover:bg-[#EAB308] transition-colors">
                            <Info size={24} className="mr-2" />
                            Info PPDB
                        </Link>
                    </div>
                </div>
            </div>
            </nav>
        </>
    );
}
