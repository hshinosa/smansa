import React from 'react';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Link, usePage } from '@inertiajs/react';
import { TYPOGRAPHY } from '@/Utils/typography';
import { Award, Globe, BookOpen, Users, GraduationCap, TrendingUp, Clock, CheckCircle } from 'lucide-react';

export default function ProgramStudiModal({ show, onClose, program }) {
    const { siteSettings } = usePage().props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    
    if (!program) return null;

    // Data lengkap untuk setiap program studi
    const programDetails = {
        MIPA: {
            fullName: "Matematika dan Ilmu Pengetahuan Alam",
            icon: Award,
            color: "bg-blue-500",
            description: "Program MIPA dirancang untuk siswa yang memiliki minat dan bakat dalam bidang sains dan matematika. Program ini mempersiapkan siswa untuk melanjutkan pendidikan ke perguruan tinggi di bidang teknik, kedokteran, farmasi, dan ilmu pengetahuan alam lainnya.",
            subjects: [
                { name: "Matematika Peminatan", hours: "4 jam/minggu" },
                { name: "Fisika", hours: "4 jam/minggu" },
                { name: "Kimia", hours: "4 jam/minggu" },
                { name: "Biologi", hours: "4 jam/minggu" }
            ],
            facilities: [
                "Laboratorium Fisika dengan peralatan modern",
                "Laboratorium Kimia berstandar nasional", 
                "Laboratorium Biologi dan mikroskop digital",
                "Perpustakaan khusus sains dan matematika"
            ],
            achievements: [
                "Juara 1 OSN Fisika Tingkat Provinsi 2024",
                "Medali Emas OSN Matematika Tingkat Kabupaten",
                "95% siswa diterima di PTN favorit",
                "Juara 2 Kompetisi Sains Nasional 2024"
            ],
            careerPaths: [
                "Dokter dan Tenaga Medis",
                "Insinyur dan Teknisi",
                "Peneliti dan Ilmuwan",
                "Dosen dan Pendidik"
            ],
            universities: [
                "ITB", "UI", "UGM", "ITS", "UNPAD", "UNDIP"
            ]
        },
        IPS: {
            fullName: "Ilmu Pengetahuan Sosial",
            icon: Globe,
            color: "bg-green-500",
            description: "Program IPS mengembangkan pemahaman tentang fenomena sosial, ekonomi, dan budaya. Program ini mempersiapkan siswa untuk berkarier di bidang hukum, ekonomi, politik, dan ilmu sosial lainnya dengan kemampuan berpikir kritis dan analitis.",
            subjects: [
                { name: "Sejarah Peminatan", hours: "4 jam/minggu" },
                { name: "Geografi", hours: "4 jam/minggu" },
                { name: "Ekonomi", hours: "4 jam/minggu" },
                { name: "Sosiologi", hours: "4 jam/minggu" }
            ],
            facilities: [
                "Laboratorium IPS dengan teknologi multimedia",
                "Perpustakaan khusus ilmu sosial dan ekonomi",
                "Ruang diskusi dan debat",
                "Akses database jurnal ilmiah"
            ],
            achievements: [
                "Juara 1 Debat Bahasa Indonesia Tingkat Provinsi",
                "Prestasi Olimpiade Ekonomi Tingkat Nasional",
                "92% siswa diterima di PTN favorit",
                "Juara 3 Kompetisi Geografi Nasional 2024"
            ],
            careerPaths: [
                "Pengacara dan Hakim",
                "Ekonom dan Analis Bisnis",
                "Diplomat dan PNS",
                "Jurnalis dan Media"
            ],
            universities: [
                "UI", "UGM", "UNPAD", "UNDIP", "UNAIR", "USU"
            ]
        },
        BAHASA: {
            fullName: "Ilmu Bahasa dan Budaya",
            icon: BookOpen,
            color: "bg-purple-500",
            description: "Program Bahasa mengembangkan kemampuan komunikasi, apresiasi sastra, dan pemahaman budaya. Program ini mempersiapkan siswa untuk berkarier di bidang pendidikan, komunikasi, sastra, dan industri kreatif dengan kemampuan multibahasa.",
            subjects: [
                { name: "Bahasa Indonesia", hours: "4 jam/minggu" },
                { name: "Bahasa Inggris", hours: "4 jam/minggu" },
                { name: "Bahasa Asing Pilihan", hours: "4 jam/minggu" },
                { name: "Antropologi", hours: "4 jam/minggu" }
            ],
            facilities: [
                "Laboratorium Bahasa dengan audio-visual",
                "Perpustakaan sastra dan linguistik",
                "Studio rekaman dan multimedia",
                "Ruang teater dan pertunjukan"
            ],
            achievements: [
                "Juara 1 Lomba Cipta Puisi Tingkat Nasional",
                "Prestasi Debat Bahasa Inggris Internasional",
                "90% siswa diterima di PTN favorit",
                "Juara 2 Festival Teater Pelajar 2024"
            ],
            careerPaths: [
                "Guru dan Dosen Bahasa",
                "Penerjemah dan Interpreter",
                "Penulis dan Jurnalis",
                "Diplomat dan Budayawan"
            ],
            universities: [
                "UI", "UGM", "UNPAD", "UNJ", "UNDIP", "UPI"
            ]
        }
    };

    const currentProgram = programDetails[program.type];
    const IconComponent = currentProgram.icon;

    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
            <div className="relative">
                {/* Header dengan gambar background */}
                <div className="relative h-48 sm:h-64 bg-gradient-to-r from-primary to-primary-darker overflow-hidden">
                    <div className="absolute inset-0 bg-black/20"></div>
                    <div className="absolute inset-0 bg-gradient-to-r from-transparent via-transparent to-black/30"></div>
                    <img 
                        src="/images/anak-sma.png" 
                        alt={`Siswa ${siteName}`}
                        className="absolute right-0 top-0 h-full w-auto object-cover opacity-80 transform hover:scale-105 transition-transform duration-500"
                    />
                    <div className="relative z-10 p-6 sm:p-8 text-white h-full flex flex-col justify-between">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center">
                                <div className={`w-12 h-12 sm:w-16 sm:h-16 ${currentProgram.color} rounded-full flex items-center justify-center mr-3 sm:mr-4 shadow-lg transform hover:rotate-12 transition-transform duration-300`}>
                                    <IconComponent className="w-6 h-6 sm:w-8 sm:h-8 text-white" />
                                </div>
                                <div>
                                    <h2 className={`${TYPOGRAPHY.sectionHeading} text-white mb-1 text-xl sm:text-2xl`}>
                                        Program {program.type}
                                    </h2>
                                    <p className={`${TYPOGRAPHY.bodyText} text-blue-100 text-sm sm:text-base`}>
                                        {currentProgram.fullName}
                                    </p>
                                </div>
                            </div>
                            <button
                                onClick={onClose}
                                className="text-white hover:text-gray-300 transition-colors p-2 hover:bg-white/10 rounded-full"
                            >
                                <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        {/* Stats mini di header */}
                        <div className="flex items-center gap-4 mt-4">
                            <div className="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-1">
                                <span className="text-white text-xs font-medium">95% Lulus PTN</span>
                            </div>
                            <div className="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-1">
                                <span className="text-white text-xs font-medium">Akreditasi A</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="p-6 sm:p-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                        {/* Kolom Kiri - Informasi Utama */}
                        <div className="space-y-6 animate-fade-in-up"
                             style={{ animationDelay: '0.1s' }}>
                            {/* Deskripsi Program */}
                            <div>
                                <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-4 flex items-center`}>
                                    <Users className="w-5 h-5 text-primary mr-2" />
                                    Tentang Program
                                </h3>
                                <p className={`${TYPOGRAPHY.bodyText} text-gray-700 leading-relaxed`}>
                                    {currentProgram.description}
                                </p>
                            </div>

                            {/* Mata Pelajaran Inti */}
                            <div>
                                <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-4 flex items-center`}>
                                    <BookOpen className="w-5 h-5 text-primary mr-2" />
                                    Mata Pelajaran Inti
                                </h3>
                                <div className="grid grid-cols-1 gap-3">
                                    {currentProgram.subjects.map((subject, index) => (
                                        <div key={index} className="flex flex-col sm:flex-row sm:items-center sm:justify-between p-3 bg-gray-50 rounded-lg">
                                            <span className={`${TYPOGRAPHY.bodyText} font-medium text-gray-800 mb-1 sm:mb-0`}>
                                                {subject.name}
                                            </span>
                                            <span className={`${TYPOGRAPHY.bodyText} text-primary font-semibold text-sm`}>
                                                {subject.hours}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Fasilitas */}
                            <div>
                                <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-4 flex items-center`}>
                                    <CheckCircle className="w-5 h-5 text-primary mr-2" />
                                    Fasilitas Pembelajaran
                                </h3>
                                <ul className="space-y-2">
                                    {currentProgram.facilities.map((facility, index) => (
                                        <li key={index} className="flex items-start">
                                            <div className="w-2 h-2 bg-primary rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                            <span className={`${TYPOGRAPHY.bodyText} text-gray-700`}>
                                                {facility}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        {/* Kolom Kanan - Prestasi & Prospek */}
                        <div className="space-y-6 animate-fade-in-up"
                             style={{ animationDelay: '0.2s' }}>
                            {/* Prestasi Terbaru */}
                            <div>
                                <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-4 flex items-center`}>
                                    <Award className="w-5 h-5 text-primary mr-2" />
                                    Prestasi Terbaru
                                </h3>
                                <div className="space-y-3">
                                    {currentProgram.achievements.map((achievement, index) => (
                                        <div key={index} className="flex items-start p-3 bg-blue-50 rounded-lg border-l-4 border-primary">
                                            <span className="inline-block w-6 h-6 bg-primary text-white rounded-full flex items-center justify-center text-xs font-semibold mr-3 mt-0.5 flex-shrink-0">
                                                {index + 1}
                                            </span>
                                            <span className={`${TYPOGRAPHY.bodyText} text-gray-800 font-medium`}>
                                                {achievement}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Prospek Karir */}
                            <div>
                                <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-4 flex items-center`}>
                                    <GraduationCap className="w-5 h-5 text-primary mr-2" />
                                    Prospek Karir
                                </h3>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {currentProgram.careerPaths.map((career, index) => (
                                        <div key={index} className="p-3 bg-gray-50 rounded text-center">
                                            <span className={`${TYPOGRAPHY.bodyText} text-gray-700 text-sm font-medium`}>
                                                {career}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Perguruan Tinggi Tujuan */}
                            <div>
                                <h3 className={`${TYPOGRAPHY.subsectionHeading} mb-4 flex items-center`}>
                                    <TrendingUp className="w-5 h-5 text-primary mr-2" />
                                    Perguruan Tinggi Tujuan
                                </h3>
                                <div className="flex flex-wrap gap-2">
                                    {currentProgram.universities.map((university, index) => (
                                        <span key={index} className="px-3 py-1 bg-primary text-white rounded-full text-sm font-medium">
                                            {university}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* Info Tambahan */}
                            <div className="p-4 bg-gradient-to-r from-blue-50 to-primary/5 rounded-lg border border-blue-200">
                                <h4 className={`${TYPOGRAPHY.bodyText} font-semibold text-blue-800 mb-2 flex items-center`}>
                                    <Clock className="w-4 h-4 mr-2" />
                                    Informasi Pendaftaran
                                </h4>
                                <p className={`${TYPOGRAPHY.bodyText} text-blue-700 text-sm`}>
                                    Pendaftaran program studi dilakukan saat PPDB. Siswa dapat memilih program sesuai minat dan hasil tes penempatan.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Footer Modal */}
                    <div className="flex flex-col sm:flex-row justify-between items-center mt-6 lg:mt-8 pt-6 border-t border-gray-200 gap-4">
                        <div className="text-center sm:text-left">
                            <p className={`${TYPOGRAPHY.bodyText} text-gray-600 text-sm`}>
                                Ingin tahu lebih detail? Kunjungi halaman program studi lengkap
                            </p>
                        </div>
                        <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <SecondaryButton onClick={onClose} className="w-full sm:w-auto">
                                Tutup
                            </SecondaryButton>
                            <Link href={program.link} className="w-full sm:w-auto">
                                <PrimaryButton className="w-full sm:w-auto">
                                    Pelajari Lebih Lanjut
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    );
}