import { useState, useEffect } from 'react';
import { X, Cookie } from 'lucide-react';

/**
 * GDPR Consent Banner Component
 * Displays a cookie consent banner and handles user preferences
 */
export default function GdprConsent() {
    const [showBanner, setShowBanner] = useState(false);
    const [showPreferences, setShowPreferences] = useState(false);
    const [preferences, setPreferences] = useState({
        necessary: true,
        analytics: false,
        marketing: false,
    });
    
    useEffect(() => {
        // Check if user has already given consent
        const consent = localStorage.getItem('gdpr_consent');
        if (!consent) {
            setShowBanner(true);
        } else {
            const parsedConsent = JSON.parse(consent);
            setPreferences(parsedConsent);
        }
    }, []);
    
    const acceptAll = () => {
        const allConsent = {
            necessary: true,
            analytics: true,
            marketing: true,
            timestamp: new Date().toISOString(),
        };
        localStorage.setItem('gdpr_consent', JSON.stringify(allConsent));
        setPreferences(allConsent);
        setShowBanner(false);
    };
    
    const acceptNecessary = () => {
        const necessaryOnly = {
            necessary: true,
            analytics: false,
            marketing: false,
            timestamp: new Date().toISOString(),
        };
        localStorage.setItem('gdpr_consent', JSON.stringify(necessaryOnly));
        setPreferences(necessaryOnly);
        setShowBanner(false);
    };
    
    const savePreferences = () => {
        const consent = {
            ...preferences,
            timestamp: new Date().toISOString(),
        };
        localStorage.setItem('gdpr_consent', JSON.stringify(consent));
        setShowBanner(false);
        setShowPreferences(false);
    };
    
    const togglePreference = (key) => {
        if (key === 'necessary') return; // Cannot disable necessary cookies
        setPreferences(prev => ({
            ...prev,
            [key]: !prev[key],
        }));
    };
    
    if (!showBanner) return null;
    
    return (
        <div 
            className="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg"
            role="dialog"
            aria-label="Cookie Consent"
            aria-describedby="cookie-consent-description"
        >
            <div className="max-w-7xl mx-auto px-4 py-4">
                {!showPreferences ? (
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div className="flex items-start gap-3">
                            <Cookie className="w-6 h-6 text-primary mt-0.5" aria-hidden="true" />
                            <div>
                                <h3 className="font-semibold text-gray-900">
                                    Kami Menggunakan Cookies
                                </h3>
                                <p id="cookie-consent-description" className="text-sm text-gray-600 mt-1">
                                    Kami menggunakan cookies untuk meningkatkan pengalaman pengguna.
                                    Dengan melanjutkan, Anda menyetujui penggunaan cookies kami.
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <button
                                onClick={() => setShowPreferences(true)}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"
                                aria-label="Atur preferensi cookies"
                            >
                                Pengaturan
                            </button>
                            <button
                                onClick={acceptNecessary}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"
                                aria-label="Terima cookies yang diperlukan saja"
                            >
                                Necessary Saja
                            </button>
                            <button
                                onClick={acceptAll}
                                className="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-darker focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors"
                                aria-label="Terima semua cookies"
                            >
                                Terima Semua
                            </button>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="font-semibold text-gray-900">
                                Preferensi Cookies
                            </h3>
                            <button
                                onClick={() => setShowPreferences(false)}
                                className="p-1 text-gray-400 hover:text-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-300"
                                aria-label="Tutup pengaturan"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        
                        <div className="space-y-3" role="group" aria-label="Preferensi cookies">
                            {/* Necessary Cookies */}
                            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 className="font-medium text-gray-900">Cookies yang Diperlukan</h4>
                                    <p className="text-sm text-gray-600">
                                        Diperlukan untuk fungsi dasar website. Tidak dapat dinonaktifkan.
                                    </p>
                                </div>
                                <button
                                    role="switch"
                                    aria-checked={preferences.necessary}
                                    aria-label="Aktifkan cookies yang diperlukan"
                                    className="relative inline-flex h-6 w-11 items-center rounded-full bg-primary cursor-not-allowed"
                                    disabled
                                >
                                    <span
                                        className={`${
                                            preferences.necessary ? 'translate-x-6' : 'translate-x-1'
                                        } inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
                                    />
                                </button>
                            </div>
                            
                            {/* Analytics Cookies */}
                            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 className="font-medium text-gray-900">Cookies Analytics</h4>
                                    <p className="text-sm text-gray-600">
                                        Membantu kami memahami bagaimana pengunjung menggunakan website.
                                    </p>
                                </div>
                                <button
                                    onClick={() => togglePreference('analytics')}
                                    role="switch"
                                    aria-checked={preferences.analytics}
                                    aria-label={`${preferences.analytics ? 'Nonaktifkan' : 'Aktifkan'} cookies analytics`}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                        preferences.analytics ? 'bg-primary' : 'bg-gray-300'
                                    }`}
                                >
                                    <span
                                        className={`${
                                            preferences.analytics ? 'translate-x-6' : 'translate-x-1'
                                        } inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
                                    />
                                </button>
                            </div>
                            
                            {/* Marketing Cookies */}
                            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 className="font-medium text-gray-900">Cookies Marketing</h4>
                                    <p className="text-sm text-gray-600">
                                        Digunakan untuk menampilkan konten yang relevan untuk Anda.
                                    </p>
                                </div>
                                <button
                                    onClick={() => togglePreference('marketing')}
                                    role="switch"
                                    aria-checked={preferences.marketing}
                                    aria-label={`${preferences.marketing ? 'Nonaktifkan' : 'Aktifkan'} cookies marketing`}
                                    className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                        preferences.marketing ? 'bg-primary' : 'bg-gray-300'
                                    }`}
                                >
                                    <span
                                        className={`${
                                            preferences.marketing ? 'translate-x-6' : 'translate-x-1'
                                        } inline-block h-4 w-4 transform rounded-full bg-white transition-transform`}
                                    />
                                </button>
                            </div>
                        </div>
                        
                        <div className="flex justify-end gap-2">
                            <button
                                onClick={() => setShowPreferences(false)}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                onClick={savePreferences}
                                className="px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary-darker focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors"
                            >
                                Simpan Preferensi
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}