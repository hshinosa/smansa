import React, { useState } from 'react';
import { ChevronRight, X, ChevronDown } from 'lucide-react';
import '../../../../resources/css/scrollbar.css';

export default function ContentEditorSidebar({ tabs, activeTab, setActiveTab }) {
    const [isExpanded, setIsExpanded] = useState(false);
    const activeTabData = tabs.find(t => t.key === activeTab);
    const ActiveIcon = activeTabData?.icon;

    return (
        <div className="lg:col-span-1 lg:sticky lg:top-6 z-30">
            {/* Mobile Header - Collapsible with clear visual feedback */}
            <div 
                className="lg:hidden bg-primary text-white p-4 rounded-t-xl flex justify-between items-center cursor-pointer hover:bg-primary-darker transition-colors"
                onClick={() => setIsExpanded(!isExpanded)}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => e.key === 'Enter' && setIsExpanded(!isExpanded)}
                aria-expanded={isExpanded}
            >
                <div className="flex items-center gap-3">
                    {ActiveIcon && <ActiveIcon size={22} />}
                    <div>
                        <p className="text-[10px] uppercase font-bold opacity-80 leading-none mb-1">Menu Editor</p>
                        <h3 className="font-bold text-base leading-none">{activeTabData?.label}</h3>
                    </div>
                </div>
                <div className="bg-white/20 p-2 rounded-lg">
                    {isExpanded ? <X size={20} /> : <ChevronDown size={20} />}
                </div>
            </div>

            {/* Desktop Title */}
            <div className="hidden lg:block mb-6 px-6 pt-6">
                <h1 className="text-xl font-bold text-gray-900">Menu Editor</h1>
                <p className="text-sm text-gray-500 mt-1">Pilih bagian yang ingin diubah</p>
            </div>

            {/* Navigation - Card-based design with clear active states */}
            <nav className={`lg:block ${isExpanded ? 'block' : 'hidden'}`} aria-label="Menu navigasi editor">
                <div className="bg-white rounded-b-xl lg:rounded-xl shadow-sm border border-gray-100 lg:border-none lg:shadow-none overflow-y-auto max-h-[550px] custom-scrollbar">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        const isActive = activeTab === tab.key;
                        return (
                            <button
                                key={tab.key}
                                onClick={() => {
                                    setActiveTab(tab.key);
                                    setIsExpanded(false);
                                }}
                                className={`w-full flex items-center gap-4 px-5 py-4 text-left transition-all duration-200 border-b lg:border-b-0 lg:border-none border-gray-100 last:border-b-0 ${
                                    isActive 
                                    ? 'bg-accent-yellow/10 text-accent-yellow shadow-sm border-l-4 border-l-accent-yellow' 
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                                }`}
                                aria-current={isActive ? 'page' : undefined}
                            >
                                <div className={`p-2.5 rounded-lg transition-colors flex-shrink-0 ${
                                    isActive 
                                    ? 'bg-white text-accent-yellow shadow-sm border border-accent-yellow/20' 
                                    : 'bg-gray-100 text-gray-600'
                                }`}>
                                    <Icon size={22} />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <span className={`block font-semibold text-base truncate ${isActive ? 'text-gray-900' : ''}`}>
                                        {tab.label}
                                    </span>
                                    <span className="text-sm text-gray-500 hidden xl:block mt-1 line-clamp-1">
                                        {tab.description}
                                    </span>
                                </div>
                                {isActive && <ChevronRight size={20} className="text-accent-yellow flex-shrink-0" />}
                            </button>
                        );
                    })}
                </div>
            </nav>
        </div>
    );
}
