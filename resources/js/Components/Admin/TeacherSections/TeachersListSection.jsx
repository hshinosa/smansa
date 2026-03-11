import React from 'react';
import { Edit2, Trash2, Plus, User, MapPin, GraduationCap } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';

// Helper function to format subject names
const formatSubjectName = (name) => {
    if (!name) return name;
    const subjectMap = {
        'PJOK': 'Penjasorkes',
        'PKWU': 'Prakarya dan Kewirausahaan',
        'Penjasorkes': 'Penjasorkes',
        'Prakarya': 'Prakarya dan Kewirausahaan',
        'Pendidikan Agama Islam': 'PAI',
        'Pendidikan Pancasila': 'PPKN'
    };
    return subjectMap[name] || name;
};

// Helper to get display role for admin list
const getDisplayRole = (teacher) => {
    const { position, department } = teacher;
    
    // Kepala Sekolah
    if (position === 'Kepala Sekolah') {
        return { primary: 'Kepala Sekolah', secondary: department || 'Pimpinan' };
    }
    
    // Wakasek with bidang
    if (position === 'Wakasek') {
        const bidang = department || 'Umum';
        return { primary: `Wakasek ${bidang}`, secondary: bidang };
    }
    
    // Regular teachers - Guru with subject
    if (position === 'Guru') {
        const subject = department || 'Umum';
        return { 
            primary: `Guru ${formatSubjectName(subject)}`, 
            secondary: formatSubjectName(subject)
        };
    }
    
    // Staff with department
    if (position === 'Staff') {
        const unit = department || 'Umum';
        return { primary: `Staff ${unit}`, secondary: unit };
    }
    
    // Fallback
    if (position) {
        return { primary: position, secondary: department || '-' };
    }
    
    return { primary: teacher.type === 'guru' ? 'Guru' : 'Staff', secondary: department || '-' };
};

export default function TeachersListSection({ teachers, title, onEdit, onDelete, onAdd }) {
    const displayTitle = title || 'Daftar Guru/Staff';

    return (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 className="text-lg font-bold text-gray-900">{displayTitle}</h3>
                    <p className="text-sm text-gray-500">Kelola data yang akan ditampilkan di website.</p>
                </div>
                <PrimaryButton 
                    onClick={onAdd}
                    className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500 shadow-sm flex items-center gap-2 border-0"
                >
                    <Plus className="w-4 h-4" />
                    Tambah Data
                </PrimaryButton>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil</th>
                            <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Jabatan & Unit</th>
                            <th className="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kontak</th>
                            <th className="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-100">
                        {teachers.map((teacher) => (
                            <tr key={teacher.id} className="hover:bg-gray-50 transition-colors">
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="flex items-center">
                                        <div className="h-12 w-12 flex-shrink-0 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-100">
                                            {teacher.image_url ? (
                                                <img src={teacher.image_url} alt={teacher.name} className="h-full w-full object-cover" />
                                            ) : (
                                                <div className="h-full w-full flex items-center justify-center">
                                                    <User className="text-gray-400 w-6 h-6" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="ml-4">
                                            <div className="text-sm font-bold text-gray-900">{teacher.name}</div>
                                            <div className="text-xs text-gray-500 flex items-center mt-0.5">
                                                <GraduationCap className="w-3 h-3 mr-1" />
                                                NIP: {teacher.nip || '-'}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900 font-medium">{getDisplayRole(teacher).primary}</div>
                                    <div className="text-xs text-gray-500 flex items-center mt-1">
                                        <MapPin className="w-3 h-3 mr-1" />
                                        {formatSubjectName(getDisplayRole(teacher).secondary)}
                                    </div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-600">{teacher.email || '-'}</div>
                                    <div className="text-xs text-gray-400">{teacher.phone || '-'}</div>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div className="flex justify-end gap-2">
                                        <button 
                                            onClick={() => onEdit(teacher)} 
                                            className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <Edit2 className="w-4 h-4" />
                                        </button>
                                        <button 
                                            onClick={() => onDelete(teacher.id)} 
                                            className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Hapus"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {filteredTeachers.length === 0 && (
                            <tr>
                                <td colSpan="4" className="px-6 py-10 text-center text-gray-500">
                                    Belum ada data {type} yang ditambahkan.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
