import { Camera, Facebook, Linkedin, Twitter, Youtube, Shirt } from 'lucide-react';

/**
 * Centralized navigation data for consistent use across all academic pages
 * Provides single source of truth for navigation links and social media
 */
export function getNavigationData(siteSettings = {}) {
    const general = siteSettings.general || {};
    const social = siteSettings.social_media || {};
    const siteName = general.site_name || 'SMAN 1 Baleendah';

    return {
        logoSman1: general.site_logo || '/images/logo-sman1-baleendah.png',
        googleMapsEmbedUrl: general.google_maps_embed_url || "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.8947!2d107.6298!3d-6.9876!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e62c87267923%3A0x7ef8aaa0fcd59ec9!2sSMAN%201%20Baleendah%2C%20Baleendah%2C%20Kabupaten%20Bandung%2C%20Jawa%20Barat!5e0!3m2!1sid!2sid!4v1746550004423!5m2!1sid!2sid",
        address: general.address || 'Jl. R.A.A. Wiranatakoesoemah No.30, Baleendah, Kec. Baleendah, Kabupaten Bandung, Jawa Barat 40375',
        phone: general.phone || '(022) 5940262',
        whatsapp: general.whatsapp || '+6281234567890',
        email: general.email || 'info@sman1baleendah.sch.id',
        
        profilLinks: [
            { title: "Profil & Sejarah", href: "/profil-sekolah" },
            { title: "Visi Misi", href: "/visi-misi" },
            { title: "Struktur Organisasi", href: "/struktur-organisasi" },
            { title: "Program Sekolah", href: "/program" },
            { title: "Galeri", href: "/galeri" },
            { title: "Guru & Staff", href: "/guru-staff" },
            { title: "Seragam Sekolah", href: "/seragam" },
        ],

        akademikLinks: [
            { title: "Kalender Akademik", href: "/kalender-akademik" },
            { title: "Kurikulum", href: "/akademik/kurikulum" },
            { title: "Organisasi & Ekstrakurikuler", href: "/akademik/organisasi-ekstrakurikuler" },
            { title: "Prestasi Akademik", href: "/akademik/prestasi-akademik" },
        ],

        programStudiLinks: [
            { title: "MIPA", href: "/akademik/program-studi/mipa" },
            { title: "IPS", href: "/akademik/program-studi/ips" },
            { title: "Bahasa", href: "/akademik/program-studi/bahasa" },
        ],

        socialMediaLinks: [
            { 
                name: "Instagram", 
                href: social.instagram || "https://www.instagram.com/sman1baleendah", 
                handle: social.instagram ? `@${social.instagram.split('/').pop()}` : "@sman1baleendah",
                icon: Camera
            },
            { 
                name: "Facebook", 
                href: social.facebook || "https://www.facebook.com/SMAN1Baleendah", 
                handle: siteName,
                icon: Facebook
            },
            { 
                name: "LinkedIn", 
                href: social.linkedin || "https://www.linkedin.com/school/sman-1-baleendah/", 
                handle: siteName,
                icon: Linkedin
            },
            { 
                name: "X", 
                href: social.twitter || "https://twitter.com/sman1baleendah", 
                handle: social.twitter ? `@${social.twitter.split('/').pop()}` : "@sman1baleendah",
                icon: Twitter
            },
            { 
                name: "YouTube", 
                href: social.youtube || "https://www.youtube.com/@sman1baleendah", 
                handle: siteName,
                icon: Youtube
            },
        ]
    };
}