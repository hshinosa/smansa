import React from 'react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import { getNavigationData } from '@/Utils/navigationData';

/**
 * Consistent layout wrapper for all academic pages
 * Provides navbar, footer and consistent navigation data
 */
export default function AcademicLayout({ children }) {
    const navigationData = getNavigationData();

    return (
        <div className="bg-secondary text-gray-800 font-sans">
            <Navbar
                logoSman1={navigationData.logoSman1}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
            />
            
            {children}
            
            <Footer
                logoSman1={navigationData.logoSman1}
                googleMapsEmbedUrl={navigationData.googleMapsEmbedUrl}
                profilLinks={navigationData.profilLinks}
                akademikLinks={navigationData.akademikLinks}
                programStudiLinks={navigationData.programStudiLinks}
                socialMediaLinks={navigationData.socialMediaLinks}
            />
        </div>
    );
}