import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Save, Globe, Share2, Layout, Image as ImageIcon } from 'lucide-react';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import FileUploadField from '@/Components/Admin/FileUploadField';
import MapsPicker from '@/Components/Admin/MapsPicker';
import toast from 'react-hot-toast';

export default function Index({ auth, sections, activeSection: initialActiveSection }) {
    const [activeTab, setActiveTab] = useState(initialActiveSection || 'general');

    // Sync activeTab with initialActiveSection whenever it changes (due to Inertia reload)
    React.useEffect(() => {
        if (initialActiveSection) {
            setActiveTab(initialActiveSection);
        }
    }, [initialActiveSection]);

    const { data, setData, post, processing, errors } = useForm({
        section: activeTab,
        content: sections[activeTab] || {},
    });

    const tabs = [
        { key: 'general', label: 'Umum', description: 'Pengaturan dasar situs, logo, dan informasi kontak.', icon: Globe },
        { key: 'social_media', label: 'Media Sosial', description: 'Tautan ke berbagai platform media sosial sekolah.', icon: Share2 },
        { key: 'footer', label: 'Footer', description: 'Pengaturan tampilan bagian bawah situs dan hak cipta.', icon: Layout },
    ];

    const handleTabChange = (tabId) => {
        setActiveTab(tabId);
        setData({
            section: tabId,
            content: sections[tabId] || {},
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        
        post(route('admin.site-settings.update'), {
            preserveScroll: true,
            preserveState: false, // Ensure we get fresh props back
            forceFormData: true,
            transform: (data) => {
                // Create a clean copy
                const cleanContent = { ...data.content };
                
                // Remove null or undefined file fields to prevent them from overwriting existing files
                Object.keys(cleanContent).forEach(key => {
                    if (cleanContent[key] === null || cleanContent[key] === undefined) {
                        // Only remove if it's a file field (not text fields)
                        if (key === 'site_logo' || key === 'hero_image') {
                            delete cleanContent[key];
                        }
                    }
                });
                
                return {
                    ...data,
                    content: cleanContent
                };
            },
            onSuccess: () => {
                toast.success('Pengaturan berhasil disimpan!');
            },
            onError: (errors) => {
                console.error('Save errors:', errors);
                toast.error('Gagal menyimpan pengaturan.');
            }
        });
    };

    const handleInputChange = (field, value) => {
        setData('content', {
            ...data.content,
            [field]: value,
        });
    };

    const handleFileChange = (field, file) => {
        setData('content', {
            ...data.content,
            [field]: file,
        });
    };

    const renderGeneralForm = () => (
        <div className="space-y-6">
            {/* Section 1: Identitas Situs */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Identitas Situs
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Nama dan logo website</p>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Nama Situs</label>
                    <input
                        type="text"
                        value={data.content.site_name || ''}
                        onChange={(e) => handleInputChange('site_name', e.target.value)}
                        className={`mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary ${errors['content.site_name'] ? 'border-red-500' : ''}`}
                    />
                    <p className="text-sm text-gray-500 mt-1">Nama resmi sekolah</p>
                    {errors['content.site_name'] && <p className="text-red-500 text-xs mt-1">{errors['content.site_name']}</p>}
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Logo Situs</label>
                    <div className="flex items-center gap-4 mt-1">
                        {sections.general.site_logo && (
                            <img 
                                src={sections.general.site_logo.startsWith('http') || sections.general.site_logo.startsWith('/') ? sections.general.site_logo : `/storage/${sections.general.site_logo}`} 
                                alt="Logo" 
                                className="h-12 w-auto bg-gray-100 p-1 rounded" 
                            />
                        )}
                        <input
                            type="file"
                            onChange={(e) => handleFileChange('site_logo', e.target.files[0])}
                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-primary hover:file:bg-blue-100"
                        />
                    </div>
                    <p className="text-sm text-gray-500 mt-1">Logo utama yang muncul di header</p>
                    {errors['content.site_logo'] && <p className="text-red-500 text-xs mt-1">{errors['content.site_logo']}</p>}
                </div>
            </div>

            {/* Section 2: Informasi Kontak */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">2</span>
                        Informasi Kontak
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Detail alamat dan kontak sekolah</p>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Alamat Lengkap</label>
                    <textarea
                        value={data.content.address || ''}
                        onChange={(e) => handleInputChange('address', e.target.value)}
                        rows={3}
                        className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                    />
                    <p className="text-sm text-gray-500 mt-1">Alamat lengkap lokasi sekolah</p>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                        <input
                            type="text"
                            value={data.content.phone || ''}
                            onChange={(e) => handleInputChange('phone', e.target.value)}
                            className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                        />
                        <p className="text-sm text-gray-500 mt-1">Nomor telepon yang bisa dihubungi</p>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            type="email"
                            value={data.content.email || ''}
                            onChange={(e) => handleInputChange('email', e.target.value)}
                            className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                        />
                        <p className="text-sm text-gray-500 mt-1">Alamat email resmi sekolah</p>
                    </div>
                </div>
            </div>

            {/* Section 3: Google Maps */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">3</span>
                        Google Maps
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Integrasi peta lokasi sekolah</p>
                </div>
                <div>
                    <MapsPicker
                        initialLat={data.content.latitude}
                        initialLng={data.content.longitude}
                        onChange={(locationData) => {
                            setData('content', {
                                ...data.content,
                                latitude: locationData.lat,
                                longitude: locationData.lng,
                                google_maps_url: locationData.mapsUrl,
                                google_maps_embed_url: locationData.embedUrl
                            });
                        }}
                    />
                </div>
            </div>

            {/* Section 4: Hero Banner */}
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">4</span>
                        Hero Banner Halaman
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Banner utama yang muncul di semua halaman internal</p>
                </div>
                <div>
                    <FileUploadField
                        id="hero_image_input"
                        label="Background Hero"
                        previewUrl={sections.general?.hero_image?.startsWith('http') || sections.general?.hero_image?.startsWith('/') 
                            ? sections.general?.hero_image 
                            : sections.general?.hero_image 
                                ? `/storage/${sections.general.hero_image}` 
                                : null}
                        onChange={(file) => handleFileChange('hero_image', file)}
                        description="Gambar banner yang digunakan di semua halaman seperti Guru & Staff, Berita, Galeri, Alumni, Ekstrakurikuler, Kontak, SPMB, Kurikulum, dll. Ukuran rekomendasi: 1920x600px"
                    />
                    {errors['content.hero_image'] && <p className="text-red-500 text-xs mt-1">{errors['content.hero_image']}</p>}
                </div>
            </div>
        </div>
    );

    const renderSocialMediaForm = () => (
        <div className="space-y-6">
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Link Media Sosial
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Tautan ke akun media sosial sekolah</p>
                </div>
                {['instagram', 'facebook', 'youtube', 'twitter', 'linkedin'].map((platform) => (
                    <div key={platform}>
                        <label className="block text-sm font-medium text-gray-700 capitalize">{platform}</label>
                        <input
                            type="text"
                            value={data.content[platform] || ''}
                            onChange={(e) => handleInputChange(platform, e.target.value)}
                            className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                            placeholder={`https://${platform}.com/...`}
                        />
                        <p className="text-sm text-gray-500 mt-1">URL lengkap akun {platform}</p>
                    </div>
                ))}
            </div>
        </div>
    );

    const renderFooterForm = () => (
        <div className="space-y-6">
            <div className="bg-white border-2 border-gray-200 rounded-xl p-6 space-y-4">
                <div className="border-b pb-3">
                    <h3 className="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span className="bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        Konten Footer
                    </h3>
                    <p className="text-sm text-gray-600 mt-1">Informasi di bagian bawah website</p>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Teks Hak Cipta (Copyright)</label>
                    <input
                        type="text"
                        value={data.content.copyright_text || ''}
                        onChange={(e) => handleInputChange('copyright_text', e.target.value)}
                        className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                    />
                    <p className="text-sm text-gray-500 mt-1">Teks copyright yang muncul di footer</p>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700">Deskripsi Singkat Footer</label>
                    <textarea
                        value={data.content.footer_description || ''}
                        onChange={(e) => handleInputChange('footer_description', e.target.value)}
                        rows={4}
                        className="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                    />
                    <p className="text-sm text-gray-500 mt-1">Deskripsi singkat tentang sekolah di footer</p>
                </div>
            </div>
        </div>
    );

    return (
        <ContentManagementPage
            headerTitle="Pengaturan Situs"
            headTitle="Pengaturan Situs"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={handleTabChange}
            processing={processing}
            onSave={handleSubmit}
            errors={errors}
        >
            {activeTab === 'general' && renderGeneralForm()}
            {activeTab === 'social_media' && renderSocialMediaForm()}
            {activeTab === 'footer' && renderFooterForm()}
        </ContentManagementPage>
    );
}
