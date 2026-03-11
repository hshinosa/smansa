import React, { useState, useMemo, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { Search, MapPin, GraduationCap, X, Phone, Mail, User } from 'lucide-react';

// Import Components
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import Modal from '@/Components/Modal';
import SEOHead from '@/Components/SEOHead';
import ResponsiveImage, { HeroImage } from '@/Components/ResponsiveImage';

// Import utilities
import { TYPOGRAPHY } from '@/Utils/typography';
import { getNavigationData } from '@/Utils/navigationData';
import { usePage } from '@inertiajs/react';

// Helper function for teacher photo
const getTeacherPhoto = (teacher) => {
    // Check for media library image first (photos collection)
    if (teacher.photosImage?.original_url) {
        return teacher.photosImage.original_url;
    }
    // Check for image_url and format it properly
    if (teacher.image_url) {
        // If already a full URL or absolute path, return as is
        if (teacher.image_url.startsWith('http') || teacher.image_url.startsWith('/')) {
            return teacher.image_url;
        }
        // Otherwise, prepend /storage/
        return `/storage/${teacher.image_url}`;
    }
    // Fallback to UI Avatars
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(teacher.name)}&background=0d47a1&color=fff&size=300`;
};

// Helper function to format subject names properly
const formatSubjectName = (name) => {
    if (!name) return name;
    
    const subjectMap = {
        'PJOK': 'Penjasorkes',
        'PKWU': 'Prakarya dan Kewirausahaan',
        'Penjasorkes': 'Penjasorkes',
        'Prakarya': 'Prakarya dan Kewirausahaan',
        'Pendidikan Agama Islam': 'PAI',
        'Pendidikan Pancasila': 'PPKN'
    };
    
    return subjectMap[name] || name;
};

// Helper to get display role (handles structural positions like Wakasek)
const getDisplayRole = (teacher) => {
    const { position, department } = teacher;
    
    // Kepala Sekolah
    if (position === 'Kepala Sekolah') {
        return { primary: 'Kepala Sekolah', secondary: department || 'Pimpinan' };
    }
    
    // Wakasek with bidang
    if (position === 'Wakasek') {
        const bidang = department || 'Umum';
        return { primary: `Wakasek ${bidang}`, secondary: bidang };
    }
    
    // Regular teachers - Guru with subject
    if (position === 'Guru') {
        const subject = department || 'Umum';
        return { 
            primary: `Guru ${formatSubjectName(subject)}`, 
            secondary: formatSubjectName(subject)
        };
    }
    
    // Staff with department
    if (position === 'Staff') {
        const unit = department || 'Umum';
        return { primary: `Staff ${unit}`, secondary: unit };
    }
    
    // Fallback - use position as is
    if (position) {
        return { primary: position, secondary: department || '-' };
    }
    
    return { primary: teacher.type === 'guru' ? 'Guru' : 'Staff', secondary: department || '-' };
};

// --- CONSTANTS & CONFIGURATION ---
const MGMP_GROUPS = [
    // Pimpinan & Manajemen
    { id: 'pimpinan', title: 'Pimpinan & Manajemen', departments: ['Pimpinan', 'Manajemen', 'Umum', 'Kesiswaan', 'Kurikulum', 'Humas', 'Sarana Prasarana'] },
    
    // MIPA - Individual subjects
    { id: 'matematika', title: 'Matematika', departments: ['Matematika'] },
    { id: 'fisika', title: 'Fisika', departments: ['Fisika'] },
    { id: 'kimia', title: 'Kimia', departments: ['Kimia'] },
    { id: 'biologi', title: 'Biologi', departments: ['Biologi'] },
    
    // IPS - Individual subjects
    { id: 'ekonomi', title: 'Ekonomi', departments: ['Ekonomi'] },
    { id: 'geografi', title: 'Geografi', departments: ['Geografi'] },
    { id: 'sejarah', title: 'Sejarah', departments: ['Sejarah'] },
    { id: 'sosiologi', title: 'Sosiologi', departments: ['Sosiologi'] },
    
    // Bahasa - Individual subjects
    { id: 'bahasa-indonesia', title: 'Bahasa Indonesia', departments: ['Bahasa Indonesia'] },
    { id: 'bahasa-inggris', title: 'Bahasa Inggris', departments: ['Bahasa Inggris'] },
    { id: 'bahasa-sunda', title: 'Bahasa Sunda', departments: ['Bahasa Sunda'] },
    { id: 'bahasa-jepang', title: 'Bahasa Jepang', departments: ['Bahasa Jepang'] },
    
    // Agama & Kewarganegaraan
    { id: 'pai', title: 'Pendidikan Agama Islam', departments: ['Pendidikan Agama Islam', 'PAI'] },
    { id: 'ppkn', title: 'Pendidikan Pancasila', departments: ['Pendidikan Pancasila', 'PPKN'] },
    { id: 'bk', title: 'Bimbingan Konseling', departments: ['Bimbingan Konseling'] },
    
    // Vokasi & Seni
    { id: 'penjasorkes', title: 'Penjasorkes', departments: ['PJOK', 'Penjasorkes', 'Pendidikan Jasmani'] },
    { id: 'pkwu', title: 'PKWU', departments: ['PKWU', 'Prakarya dan Kewirausahaan', 'Prakarya'] },
    { id: 'seni-budaya', title: 'Seni Budaya', departments: ['Seni Budaya', 'Seni'] },
    
    // Staff
    { id: 'staff', title: 'Staff, TU & Perpustakaan', departments: ['Staff TU', 'Perpustakaan', 'Administrasi', 'Tata Usaha'] },
];

// Helper to determine group for a teacher
const getTeacherGroup = (teacher) => {
    if (!teacher.department) return 'staff';

    const deptNormal = teacher.department.toUpperCase();

    // Check if teacher has a specific department that matches our groups
    for (const group of MGMP_GROUPS) {
        if (group.departments.some(d => d.toUpperCase() === deptNormal)) {
            return group.id;
        }
    }
    
    // Fallback search in position
    if (teacher.position) {
         const pos = teacher.position.toUpperCase();
         for (const group of MGMP_GROUPS) {
             if (group.departments.some(d => pos.includes(d.toUpperCase()))) {
                 return group.id;
             }
         }
    }

    return 'staff'; // Fallback
};

export default function GuruStaffPage({ teachers = [] }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    const heroImage = siteSettings?.general?.hero_image || '/images/hero-bg-sman1baleendah.jpeg';
    const heroSettings = siteSettings?.hero_teachers || {};

    const navigationData = getNavigationData(siteSettings);
    const [activeGroup, setActiveGroup] = useState(MGMP_GROUPS[0].id);
    const [selectedTeacher, setSelectedTeacher] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    // Helper to format image path correctly
    const formatImagePath = (path) => {
        if (!path) return null;
        if (path.startsWith('http') || path.startsWith('/')) return path;
        return `/storage/${path}`;
    };

    const renderHighlightedTitle = (title) => {
        if (!title) return null;
        const words = title.split(' ');
        if (words.length <= 1) return title;
        const lastWord = words.pop();
        return (
            <>
                {words.join(' ')} <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{lastWord}</span>
            </>
        );
    };

    // Grouping Logic
    const groupedTeachers = useMemo(() => {
        const groups = {};
        
        // Initialize groups
        MGMP_GROUPS.forEach(g => {
            groups[g.id] = [];
        });

        // Distribute teachers
        teachers.forEach(teacher => {
            // Filter by search query first
            if (searchQuery) {
                const query = searchQuery.toLowerCase();
                const match =
                    teacher.name.toLowerCase().includes(query) ||
                    teacher.position.toLowerCase().includes(query) ||
                    (teacher.department && teacher.department.toLowerCase().includes(query));
                if (!match) return;
            }

            const groupId = getTeacherGroup(teacher);
            if (groups[groupId]) {
                groups[groupId].push(teacher);
            }
        });

        return groups;
    }, [searchQuery, teachers]);

    // Scroll Spy - Update active group based on scroll position
    useEffect(() => {
        const handleScroll = () => {
            const scrollPosition = window.scrollY + 200; // offset for header
            
            // Find the section currently in view
            for (let i = MGMP_GROUPS.length - 1; i >= 0; i--) {
                const group = MGMP_GROUPS[i];
                const element = document.getElementById(group.id);
                if (element) {
                    const offsetTop = element.offsetTop;
                    if (scrollPosition >= offsetTop) {
                        setActiveGroup(group.id);
                        break;
                    }
                }
            }
        };

        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    // Scroll to Section & Navigation Logic
    const scrollToSection = (id) => {
        const element = document.getElementById(id);
        if (element) {
            const offset = 150; // Adjust for sticky header
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: "smooth"
            });
            setActiveGroup(id);
        }
    };

    // Modal Handlers
    const openModal = (teacher) => {
        setSelectedTeacher(teacher);
        setModalOpen(true);
        document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
        setModalOpen(false);
        setSelectedTeacher(null);
        document.body.style.overflow = 'unset';
    };

    // --- SUB-COMPONENTS ---

    const SidebarNav = () => (
        <div className="hidden lg:block w-1/4 pr-8">
            <div className="sticky top-32 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h3 className="font-bold text-gray-900 mb-4 px-4 text-lg font-serif">Kategori</h3>
                <div className="max-h-[60vh] overflow-y-auto pr-2 -mr-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                    <ul className="space-y-1">
                        {MGMP_GROUPS.map((group) => (
                            <li key={group.id}>
                                <button
                                    onClick={() => scrollToSection(group.id)}
                                    className={`w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 border-l-4 ${
                                        activeGroup === group.id
                                        ? "border-accent-yellow bg-blue-50 text-primary font-bold"
                                        : "border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                    }`}
                                >
                                    {group.title}
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </div>
    );

    const MobileNav = () => (
        <div className="lg:hidden sticky top-20 z-30 bg-white shadow-sm border-b border-gray-200 mb-8 -mx-4 px-4 py-3 overflow-x-auto flex gap-2 no-scrollbar">
            {MGMP_GROUPS.map((group) => (
                <button
                    key={group.id}
                    onClick={() => scrollToSection(group.id)}
                    className={`whitespace-nowrap px-4 py-2 rounded-full text-sm font-bold transition-all ${
                        activeGroup === group.id
                        ? "bg-primary text-white shadow-md"
                        : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                    }`}
                >
                    {group.title}
                </button>
            ))}
        </div>
    );

    const TeacherCard = ({ teacher }) => {
        const displayRole = getDisplayRole(teacher);
        
        return (
        <div
            className="bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group cursor-pointer border border-gray-100 flex flex-col h-full"
            onClick={() => openModal(teacher)}
        >
            <div className="relative aspect-[4/5] overflow-hidden bg-gray-100">
                <ResponsiveImage
                    src={getTeacherPhoto(teacher)}
                    alt={teacher.name}
                    className="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                    style={{ objectPosition: 'center 20%' }}
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>
            
            <div className="p-3 flex flex-col flex-grow">
                <h3 className={`font-bold text-gray-900 text-sm mb-0.5 group-hover:text-primary transition-colors line-clamp-2`}>
                    {teacher.name}
                </h3>
                <p className="text-xs text-primary font-medium mb-1 line-clamp-1">
                    {displayRole.primary}
                </p>
                
                <div className="mt-auto space-y-1 pt-2 border-t border-gray-100">
                    <div className="flex items-center text-gray-500 text-[11px]">
                        <MapPin className="w-3 h-3 mr-1.5 text-gray-400 flex-shrink-0" />
                        <span className="line-clamp-1">{displayRole.secondary}</span>
                    </div>
                    {teacher.nip && (
                        <div className="flex items-center text-gray-500 text-[11px]">
                            <GraduationCap className="w-3 h-3 mr-1.5 text-gray-400 flex-shrink-0" />
                            <span className="line-clamp-1">NIP: {teacher.nip}</span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );};

    const TeacherDetailModal = () => {
        if (!modalOpen || !selectedTeacher) return null;
        const displayRole = getDisplayRole(selectedTeacher);

        return (
            <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-fade-in">
                <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    {/* Header */}
                    <div className="relative h-32 bg-primary flex-shrink-0">
                        <button 
                            onClick={closeModal}
                            className="absolute top-4 right-4 p-2 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors"
                        >
                            <X className="w-6 h-6" />
                        </button>
                        <div className="absolute -bottom-16 left-8 p-1 bg-white rounded-full shadow-lg">
                            <ResponsiveImage 
                                src={getTeacherPhoto(selectedTeacher)} 
                                alt={selectedTeacher.name}
                                className="w-32 h-32 rounded-full object-cover border-4 border-white"
                                loading="lazy"
                            />
                        </div>
                    </div>

                    {/* Body */}
                    <div className="pt-20 px-8 pb-8 overflow-y-auto">
                        <div className="mb-6">
                            <h2 className="text-2xl font-bold text-gray-900 font-serif mb-1">{selectedTeacher.name}</h2>
                            <p className="text-primary font-medium text-lg mb-1">{displayRole.primary}</p>
                            {displayRole.primary !== displayRole.secondary && (
                                <p className="text-gray-600 text-sm">{displayRole.secondary}</p>
                            )}
                            <div className="flex gap-2 mt-3">
                                <span className="px-3 py-1 bg-blue-50 text-primary text-xs font-bold rounded-full">
                                    {selectedTeacher.type === 'guru' ? 'Tenaga Pendidik' : 'Tenaga Kependidikan'}
                                </span>
                                {selectedTeacher.department && (
                                    <span className="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded-full">
                                        {formatSubjectName(selectedTeacher.department)}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="space-y-4">
                                <h3 className="font-bold text-gray-900 border-b border-gray-100 pb-2">Informasi Akademik</h3>
                                <div className="space-y-3">
                                    {selectedTeacher.nip && (
                                        <div>
                                            <p className="text-xs text-gray-500 uppercase tracking-wider">NIP</p>
                                            <p className="text-gray-800 font-medium">{selectedTeacher.nip}</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-xs text-gray-500 uppercase tracking-wider">Jabatan</p>
                                        <p className="text-gray-800 font-medium">{displayRole.primary}</p>
                                    </div>
                                    {displayRole.primary !== displayRole.secondary && (
                                        <div>
                                            <p className="text-xs text-gray-500 uppercase tracking-wider">Unit/Bidang</p>
                                            <p className="text-gray-800 font-medium">{displayRole.secondary}</p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-4">
                                <h3 className="font-bold text-gray-900 border-b border-gray-100 pb-2">Kontak & Lainnya</h3>
                                <div className="space-y-3">
                                    <div className="flex items-center text-gray-600">
                                        <Mail className="w-4 h-4 mr-3 text-gray-400" />
                                        <span className="text-sm">{selectedTeacher.email || 'Email tidak tersedia'}</span>
                                    </div>
                                    <div className="flex items-center text-gray-600">
                                        <User className="w-4 h-4 mr-3 text-gray-400" />
                                        <span className="text-sm">{selectedTeacher.type === 'guru' ? 'Tenaga Pendidik' : 'Tenaga Kependidikan'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="bg-white min-h-screen font-sans text-gray-800 flex flex-col">
            <SEOHead 
                title={`Guru & Staff - ${siteName}`}
                description={`Profil guru dan tenaga kependidikan ${siteName}. Tim pengajar berkualitas dengan pengalaman dan dedikasi tinggi untuk pendidikan siswa.`}
                keywords={`guru, tenaga pengajar, staff sekolah, tenaga kependidikan, ${siteName}`}
                image="/images/teachers-banner.jpg"
            />

            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />
            <main id="main-content" className="pt-20" tabIndex="-1">            {/* HERO SECTION */}
            <section className="relative h-[40vh] min-h-[400px] flex items-center justify-center overflow-hidden">
                {/* Background Image */}
                <div className="absolute inset-0 z-0">
                    <HeroImage 
                        src={formatImagePath(heroImage)} 
                        alt="Background Guru & Staff" 
                    />
                    <div className="absolute inset-0 bg-black/60"></div>
                </div>

                <div className="relative z-10 container mx-auto px-4 text-center text-white">
                    <h1 className={`${TYPOGRAPHY.heroTitle} mb-4`}>
                        {renderHighlightedTitle(heroSettings.title || 'Guru & Staff')}
                    </h1>
                    <p className={`${TYPOGRAPHY.heroText} max-w-2xl mx-auto opacity-90`}>
                        {heroSettings.subtitle || `Tenaga pendidik dan kependidikan yang berdedikasi untuk kemajuan pendidikan di ${siteName}.`}
                    </p>
                </div>
            </section>

            {/* MAIN CONTENT */}
            <section className="py-16 bg-gray-50 flex-grow">
                <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Search Bar */}
                    <div className="max-w-xl mx-auto mb-12 relative">
                        <input
                            type="text"
                            placeholder="Cari nama guru, mata pelajaran, atau jabatan..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="w-full pl-12 pr-4 py-4 rounded-full border-0 shadow-md focus:ring-2 focus:ring-primary text-gray-700"
                        />
                        <Search className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                    </div>

                    <div className="flex flex-col lg:flex-row">
                        {/* Sidebar Navigation (Desktop) */}
                        <SidebarNav />

                        {/* Mobile Navigation (Tabs) */}
                        <MobileNav />

                        {/* Content Area */}
                        <div className="w-full lg:w-3/4 space-y-12">
                            {MGMP_GROUPS.map((group) => {
                                const teachersInGroup = groupedTeachers[group.id];
                                if (teachersInGroup.length === 0) return null;

                                return (
                                    <div key={group.id} id={group.id} className="scroll-mt-40">
                                        <div className="mb-6 border-b border-gray-200 pb-3">
                                            <h2 className={`${TYPOGRAPHY.sectionHeading} text-xl md:text-2xl`}>
                                                {group.title}
                                            </h2>
                                        </div>
                                        
                                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                            {teachersInGroup.map((teacher) => (
                                                <TeacherCard key={teacher.id} teacher={teacher} />
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}

                            {/* Empty State */}
                            {Object.values(groupedTeachers).every(g => g.length === 0) && (
                                <div className="text-center py-20">
                                    <div className="inline-block p-4 rounded-full bg-white mb-4 shadow-sm">
                                        <Search size={32} className="text-gray-400" />
                                    </div>
                                    <h3 className="text-lg font-bold text-gray-900">Tidak ada data ditemukan</h3>
                                    <p className="text-gray-500">Coba kata kunci lain.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </section>

           </main> <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                socialMediaLinks={navigationData.socialMediaLinks}
            />

            <TeacherDetailModal />
        </div>
    );
}