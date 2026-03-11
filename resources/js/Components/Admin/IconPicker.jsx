import React, { useState, useRef, useEffect } from 'react';
import { 
    BookOpen, Calculator, Microscope, FlaskConical, Atom, Dna, Globe,
    Languages, Palette, Music, Activity, Code, Brain, TrendingUp,
    Scale, Users, Landmark, Trophy, GraduationCap, Calendar, Leaf,
    Briefcase, Stethoscope, Building, Cpu, Newspaper, HardHat, Plane, Ship,
    ChevronDown, Search
} from 'lucide-react';

const iconMap = {
    BookOpen, Calculator, Microscope, FlaskConical, Atom, Dna, Globe,
    Languages, Palette, Music, Activity, Code, Brain, TrendingUp,
    Scale, Users, Landmark, Trophy, GraduationCap, Calendar, Leaf,
    Briefcase, Stethoscope, Building, Cpu, Newspaper, HardHat, Plane, Ship
};

const iconOptions = {
    // Umum
    BookOpen: 'Buku Terbuka',
    Trophy: 'Piala/Trophy',
    GraduationCap: 'Toga Wisuda',
    Users: 'Pengguna/Grup',
    Calendar: 'Kalender',
    Leaf: 'Daun',
    
    // Akademik/Sains
    Calculator: 'Kalkulator',
    Microscope: 'Mikroskop',
    FlaskConical: 'Lab Kimia',
    Atom: 'Atom/Fisika',
    Dna: 'DNA/Biologi',
    Brain: 'Otak/Psikologi',
    
    // Sosial/Bahasa
    Globe: 'Dunia/Global',
    Languages: 'Bahasa',
    Landmark: 'Landmark/Sejarah',
    TrendingUp: 'Ekonomi/Grafik',
    Scale: 'Timbangan/Hukum',
    
    // Seni/Kreativitas
    Palette: 'Palet/Seni',
    Music: 'Musik',
    Activity: 'Aktivitas/Olahraga',
    Code: 'Kode/Programming',
    
    // Karir
    Briefcase: 'Tas Kerja/Bisnis',
    Stethoscope: 'Stetoskop/Kesehatan',
    Building: 'Gedung/Arsitektur',
    Cpu: 'CPU/Teknik',
    Newspaper: 'Koran/Jurnalisme',
    HardHat: 'Helm/Konstruksi',
    Plane: 'Pesawat/Penerbangan',
    Ship: 'Kapal/Maritim',
};

export default function IconPicker({ value, onChange, label, placeholder = 'Pilih Ikon' }) {
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const dropdownRef = useRef(null);
    
    const SelectedIcon = iconMap[value] || BookOpen;
    
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };
        
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);
    
    const filteredIcons = Object.entries(iconOptions).filter(([iconName, iconLabel]) =>
        iconLabel.toLowerCase().includes(searchTerm.toLowerCase()) ||
        iconName.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    const handleSelect = (iconName) => {
        onChange(iconName);
        setIsOpen(false);
        setSearchTerm('');
    };
    
    return (
        <div className="relative" ref={dropdownRef}>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-1">
                    {label}
                </label>
            )}
            
            {/* Trigger Button */}
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full flex items-center justify-between px-3 py-2 bg-white border border-gray-300 rounded-xl shadow-sm hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
            >
                <div className="flex items-center gap-2">
                    <div className="p-1.5 bg-gray-100 rounded-lg">
                        <SelectedIcon size={18} className="text-gray-700" />
                    </div>
                    <span className="text-sm text-gray-700">
                        {iconOptions[value] || placeholder}
                    </span>
                </div>
                <ChevronDown size={16} className={`text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} />
            </button>
            
            {/* Dropdown */}
            {isOpen && (
                <div className="absolute z-50 bottom-full mb-1 right-0 w-96 bg-white border border-gray-200 rounded-xl shadow-lg max-h-[500px] overflow-hidden">
                    {/* Search */}
                    <div className="p-2 border-b border-gray-200">
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                            <input
                                type="text"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                placeholder="Cari ikon..."
                                className="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                    
                    {/* Icon Grid */}
                    <div className="p-2 overflow-y-auto max-h-[440px]">
                        <div className="grid grid-cols-3 gap-1">
                            {filteredIcons.map(([iconName, iconLabel]) => {
                                const IconComponent = iconMap[iconName];
                                const isSelected = value === iconName;
                                
                                return (
                                    <button
                                        key={iconName}
                                        type="button"
                                        onClick={() => handleSelect(iconName)}
                                        className={`flex items-center gap-2 p-2 rounded-lg text-left transition-colors ${
                                            isSelected 
                                                ? 'bg-indigo-50 border-2 border-indigo-500' 
                                                : 'hover:bg-gray-50 border-2 border-transparent'
                                        }`}
                                    >
                                        <div className={`p-1.5 rounded-md ${isSelected ? 'bg-indigo-100' : 'bg-gray-100'}`}>
                                            <IconComponent 
                                                size={20} 
                                                className={isSelected ? 'text-indigo-600' : 'text-gray-600'} 
                                            />
                                        </div>
                                        <span className={`text-xs ${isSelected ? 'text-indigo-700 font-semibold' : 'text-gray-700'}`}>
                                            {iconLabel}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        
                        {filteredIcons.length === 0 && (
                            <p className="text-center text-sm text-gray-500 py-4">
                                Tidak ada ikon yang cocok
                            </p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
