import React from 'react';
import { TYPOGRAPHY } from '@/Utils/typography';

/**
 * Reusable content section component for academic pages
 * Provides consistent section layout with title, description and content
 */
export default function ContentSection({ 
    title, 
    subtitle,
    description, 
    children, 
    backgroundColor = 'bg-white',
    titleColor = 'text-primary'
}) {
    return (
        <section className={`py-12 ${backgroundColor} sm:py-16 lg:py-20`}>
            <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                {(title || description) && (
                    <div className="text-center md:text-left mb-8">
                        {title && (
                            <>
                                <h2 className={TYPOGRAPHY.sectionHeading}>
                                    {subtitle ? (
                                        <>
                                            {subtitle} <span className={titleColor}>{title}</span>
                                        </>
                                    ) : (
                                        title
                                    )}
                                </h2>
                                <div className="h-1 w-32 bg-primary mt-2 mx-auto md:mx-0 mb-4"></div>
                            </>
                        )}
                        {description && (
                            <p className={`${TYPOGRAPHY.bodyText} text-gray-600 max-w-2xl mx-auto md:mx-0`}>
                                {description}
                            </p>
                        )}
                    </div>
                )}
                {children}
            </div>
        </section>
    );
}