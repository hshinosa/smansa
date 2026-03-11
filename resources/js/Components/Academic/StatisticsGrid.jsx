import React from 'react';
import { TYPOGRAPHY } from '@/Utils/typography';

/**
 * Reusable statistics grid component for displaying achievement numbers
 * Provides consistent layout for statistics with numbers and descriptions
 */
export default function StatisticsGrid({ 
    title = "Prestasi & Pencapaian", 
    statistics = [],
    className = ""
}) {
    return (
        <div className={`bg-white rounded-xl shadow-lg p-8 mb-8 border border-gray-100 ${className}`}>
            <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-6 text-center`}>{title}</h3>
            <div className="grid md:grid-cols-4 gap-6">
                {statistics.map((stat, index) => (
                    <div key={index} className="text-center border-r border-gray-200 last:border-r-0">
                        <div className="mb-3">
                            <div className="text-4xl font-bold text-primary mb-1">{stat.value}</div>
                            <p className={`${TYPOGRAPHY.bodyText} text-gray-600 text-sm font-medium`}>
                                {stat.label}
                            </p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}