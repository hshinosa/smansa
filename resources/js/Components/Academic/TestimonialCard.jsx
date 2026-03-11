import React from 'react';
import { TYPOGRAPHY } from '@/Utils/typography';

/**
 * Reusable testimonial card component for alumni success stories
 * Provides consistent layout for testimonials with avatar, name, role and quote
 */
export default function TestimonialCard({ 
    name, 
    role, 
    education,
    testimonial, 
    initials,
    className = ""
}) {
    return (
        <div className={`bg-white rounded-lg p-6 border border-gray-200 ${className}`}>
            <div className="flex items-center mb-4">
                <div className="bg-gray-100 rounded-full w-12 h-12 flex items-center justify-center text-gray-600 font-bold text-lg">
                    {initials}
                </div>
                <div className="ml-4">
                    <h4 className={`${TYPOGRAPHY.bodyText} font-semibold mb-1`}>{name}</h4>
                    <p className={`${TYPOGRAPHY.bodyText} text-gray-500 text-sm`}>{role}</p>
                </div>
            </div>
            <p className={`${TYPOGRAPHY.bodyText} text-gray-600 mb-4 text-sm leading-relaxed`}>
                {testimonial}
            </p>
            {education && (
                <div className="text-xs text-gray-500 border-t pt-3">
                    <span className="font-medium">Pendidikan:</span> {education}
                </div>
            )}
        </div>
    );
}