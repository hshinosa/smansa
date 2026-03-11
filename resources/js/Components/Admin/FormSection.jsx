import React from 'react';

export default function FormSection({ title, icon: Icon, children, className = "" }) {
    return (
        <div className={`bg-blue-50/50 p-6 rounded-2xl border border-blue-100 ${className}`}>
            {title && (
                <h3 className="text-lg font-bold text-blue-900 mb-4 flex items-center">
                    {Icon && <Icon size={20} className="mr-2" />}
                    {title}
                </h3>
            )}
            <div className="space-y-6">
                {children}
            </div>
        </div>
    );
}
