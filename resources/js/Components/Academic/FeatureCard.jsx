import React from 'react';
import { TYPOGRAPHY } from '@/Utils/typography';

/**
 * Reusable feature card component for displaying program features, facilities, etc.
 * Provides consistent card layout with icon, title, description and optional details
 */
export default function FeatureCard({ 
    icon: Icon, 
    title, 
    subtitle,
    description, 
    details = [],
    className = "",
    iconBgColor = "bg-blue-100",
    iconColor = "text-primary"
}) {
    return (
        <div className={`bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-shadow duration-300 ${className}`}>
            {Icon && (
                <div className="flex items-center mb-4">
                    <div className={`${iconBgColor} rounded-full w-14 h-14 flex items-center justify-center mr-4`}>
                        <Icon className={`w-7 h-7 ${iconColor}`} />
                    </div>
                    <div>
                        <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-1`}>{title}</h3>
                        {subtitle && (
                            <p className={`${TYPOGRAPHY.bodyText} text-primary font-semibold text-sm`}>{subtitle}</p>
                        )}
                    </div>
                </div>
            )}
            
            {!Icon && (
                <div className="text-center mb-4">
                    <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-1`}>{title}</h3>
                    {subtitle && (
                        <p className={`${TYPOGRAPHY.bodyText} text-primary font-semibold text-sm`}>{subtitle}</p>
                    )}
                </div>
            )}
            
            <p className={`${TYPOGRAPHY.bodyText} text-gray-600 mb-3`}>
                {description}
            </p>
            
            {details.length > 0 && (
                <div className="bg-gray-50 p-3 rounded-lg">
                    <h4 className={`${TYPOGRAPHY.bodyText} font-semibold mb-2 text-sm`}>
                        {details.length > 3 ? 'Highlights:' : 'Details:'}
                    </h4>
                    <ul className="text-xs text-gray-600 space-y-1">
                        {details.map((detail, index) => (
                            <li key={index}>• {detail}</li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}