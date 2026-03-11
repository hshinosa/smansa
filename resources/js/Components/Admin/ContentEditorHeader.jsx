import React from 'react';
import { Save } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';

export default function ContentEditorHeader({ title, description, icon: Icon, processing, onSave, customAction }) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 lg:p-6 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 className="text-xl lg:text-2xl font-bold text-gray-900 flex items-center gap-3">
                    {Icon && (
                        <div className="p-2.5 bg-accent-yellow/10 text-accent-yellow rounded-xl hidden sm:flex">
                            <Icon size={26} />
                        </div>
                    )}
                    {title}
                </h2>
                {description && (
                    <p className="text-sm lg:text-base text-gray-500 mt-1 ml-1">
                        {description}
                    </p>
                )}
            </div>
            {customAction ? customAction : onSave && (
                <PrimaryButton 
                    onClick={onSave}
                    disabled={processing} 
                    className="px-6 py-3 flex items-center gap-2 shadow-sm hover:shadow-md transition-all text-base font-semibold"
                >
                    <Save size={20} />
                    <span>{processing ? 'Menyimpan...' : 'Simpan Perubahan'}</span>
                </PrimaryButton>
            )}
        </div>
    );
}
