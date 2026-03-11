// FILE: resources/js/Pages/Admin/InstagramSettings/Index.jsx
// Instagram Apify API Key Management UI

import React, { useState } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { Instagram, Key, Save, Activity, Play, RotateCcw, AlertCircle, CheckCircle, FileText, Trash2, Check, X, Image, Sparkles, ChevronLeft, ChevronRight, Link2 } from 'lucide-react';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import toast from 'react-hot-toast';

export default function Index({ apifyToken, statistics, logs, pendingPosts = { data: [] }, activeTab: initialTab }) {
    const { success, error } = usePage().props;
    const [activeTab, setActiveTab] = useState(initialTab || 'api-key');
    const [isRunningScaper, setIsRunningScraper] = useState(false);
    const [selectedPosts, setSelectedPosts] = useState([]);
    const [bulkCategory, setBulkCategory] = useState('berita');
    const [approvalModal, setApprovalModal] = useState({ show: false, post: null });
    const [approvalForm, setApprovalForm] = useState({ title: '', category: 'berita' });
    const [processingAI, setProcessingAI] = useState(null); // track which post is being processed
    const [singlePostUrl, setSinglePostUrl] = useState('');
    const [isScrapingSinglePost, setIsScrapingSinglePost] = useState(false);

    // Extract posts array from paginated object
    const postsData = pendingPosts?.data || [];
    const totalPosts = pendingPosts?.meta?.total || 0;

    // Form for API key
    const { data, setData, post, processing, errors } = useForm({
        apify_token: apifyToken || '',
    });

    const tabs = [
        { key: 'api-key', label: 'Apify API Key', description: 'Manage Apify API token for Instagram scraping', icon: Key },
        { key: 'pending', label: `Pending Posts (${totalPosts})`, description: 'Approve scraped posts as news drafts', icon: FileText },
        { key: 'statistics', label: 'Statistics', description: 'Scraping statistics and activity', icon: Activity },
        { key: 'logs', label: 'Scraper Logs', description: 'Scraper run history and logs', icon: Activity },
    ];

    const handleSaveApiKey = (e) => {
        e.preventDefault();
        post(route('admin.instagram-settings.update'), {
            preserveScroll: false,
            onSuccess: () => {
                toast.success('API key berhasil disimpan');
            },
            onError: (errors) => {
                console.error('Save errors:', errors);
                toast.error('Gagal menyimpan API key');
            }
        });
    };

    const handleRunScraper = () => {
        if (!apifyToken) {
            toast.error('Silakan atur API token terlebih dahulu');
            setActiveTab('api-key');
            return;
        }

        setIsRunningScraper(true);
        toast.success('Scraper dimulai di background. Pantau status di Scraper Logs.', { duration: 4000 });
        
        router.post(route('admin.instagram-scraper.run'), {
            username: 'sman1baleendah',
            max_posts: 25,
        }, {
            onSuccess: () => {
                setIsRunningScraper(false);
                setActiveTab('logs');
            },
            onError: (errors) => {
                toast.error('Gagal memulai scraper.');
                setIsRunningScraper(false);
            }
        });
    };

    const handleScrapeSinglePost = () => {
        if (!apifyToken) {
            toast.error('Silakan atur API token terlebih dahulu');
            setActiveTab('api-key');
            return;
        }

        if (!singlePostUrl.trim()) {
            toast.error('Masukkan URL Instagram post');
            return;
        }

        // Validate URL format
        if (!singlePostUrl.match(/instagram\.com\/(p|reel|tv)\/[A-Za-z0-9_-]+/)) {
            toast.error('URL tidak valid. Gunakan format: https://www.instagram.com/p/SHORTCODE');
            return;
        }

        setIsScrapingSinglePost(true);
        toast.loading('Scraping post dari Instagram...', { duration: 3000 });

        router.post(route('admin.instagram-scraper.single-post'), {
            post_url: singlePostUrl,
        }, {
            onSuccess: () => {
                setIsScrapingSinglePost(false);
                setSinglePostUrl('');
                setActiveTab('pending');
            },
            onError: (errors) => {
                toast.error('Gagal scrape post.');
                setIsScrapingSinglePost(false);
            }
        });
    };

    // Handle individual post approval
    const openApprovalModal = (post) => {
        // Extract first line of caption as suggested title
        const captionLines = (post.caption || '').split('\n');
        const suggestedTitle = captionLines[0]?.substring(0, 200) || `Instagram Post ${post.shortcode}`;
        
        setApprovalForm({
            title: suggestedTitle,
            category: 'berita',
            image_paths: post.image_paths || []
        });
        setApprovalModal({ show: true, post });
    };

    const handleApprovePost = () => {
        if (!approvalForm.title.trim()) {
            toast.error('Judul harus diisi');
            return;
        }

        router.post(route('admin.instagram-posts.approve', approvalModal.post.id), {
            title: approvalForm.title,
            category: approvalForm.category,
        }, {
            onSuccess: () => {
                toast.success('Post sedang diproses di background. Cek halaman berita untuk melihat hasilnya.');
                setApprovalModal({ show: false, post: null });
            },
            onError: (errors) => {
                toast.error('Gagal approve post');
            }
        });
    };

    const handleRejectPost = (postId) => {
        if (!confirm('Apakah Anda yakin ingin menghapus post ini? Tindakan ini tidak dapat dibatalkan.')) return;
        
        router.delete(route('admin.instagram-posts.reject', postId), {
            onSuccess: () => {
                toast.success('Post berhasil dihapus');
            },
            onError: () => {
                toast.error('Gagal menghapus post');
            }
        });
    };

    // Handle Process with AI
    const handleProcessAI = (postId) => {
        setProcessingAI(postId);
        toast.loading('Memproses dengan AI... Mohon tunggu.', { duration: 3000 });
        
        router.post(route('admin.instagram-posts.process-ai', postId), {}, {
            onSuccess: () => {
                toast.success('Post sedang diproses dengan AI di background. Cek halaman berita dalam beberapa menit.');
                setProcessingAI(null);
            },
            onError: () => {
                toast.error('Gagal memproses dengan AI. Coba lagi atau periksa konfigurasi AI.');
                setProcessingAI(null);
            }
        });
    };

    // Handle Reset Processing Status (for stuck posts)
    const handleResetStatus = (postId) => {
        router.post(route('admin.instagram-posts.reset-status', postId), {}, {
            onSuccess: () => {
                toast.success('Status berhasil direset. Post siap diproses ulang.');
            },
            onError: () => {
                toast.error('Gagal reset status.');
            }
        });
    };

    // Handle Cleanup All Stuck Posts
    const handleCleanupStuck = () => {
        if (!confirm('Reset semua post yang stuck? Post yang sedang diproses lebih dari 10 menit akan di-reset.')) return;
        
        router.post(route('admin.instagram-posts.cleanup-stuck'), {}, {
            onSuccess: () => {
                toast.success('Semua post stuck berhasil di-reset.');
            },
            onError: () => {
                toast.error('Gagal cleanup stuck posts.');
            }
        });
    };

    // Bulk selection handlers
    const toggleSelectPost = (postId) => {
        setSelectedPosts(prev => 
            prev.includes(postId) 
                ? prev.filter(id => id !== postId)
                : [...prev, postId]
        );
    };

    const toggleSelectAll = () => {
        if (selectedPosts.length === postsData.length) {
            setSelectedPosts([]);
        } else {
            setSelectedPosts(postsData.map(p => p.id));
        }
    };

    const handleBulkApprove = () => {
        if (selectedPosts.length === 0) {
            toast.error('Pilih minimal 1 post');
            return;
        }

        router.post(route('admin.instagram-posts.bulk-approve'), {
            ids: selectedPosts,
            category: bulkCategory,
        }, {
            onSuccess: () => {
                toast.success(`${selectedPosts.length} post sedang diproses di background. Halaman akan dimuat ulang untuk melihat status terbaru.`);
                setSelectedPosts([]);
                setTimeout(() => window.location.reload(), 1500);
            },
            onError: () => {
                toast.error('Gagal bulk approve');
            }
        });
    };

    // Auto-refresh logic (Polling) with safety timeout to prevent infinite loops
    React.useEffect(() => {
        const hasProcessing = postsData.some(post => post.processing_status);
        
        if (hasProcessing) {
            // Count polling attempts to detect stuck jobs
            let attempts = 0;
            const maxAttempts = 20; // ~1 minute timeout for polling (20 * 3s)

            const interval = setInterval(() => {
                attempts++;
                
                if (attempts > maxAttempts) {
                    clearInterval(interval);
                    console.warn('Polling timeout: Background process taking too long or stuck.');
                    return;
                }

                router.reload({
                    only: ['pendingPosts', 'statistics'],
                    preserveScroll: true,
                    onSuccess: (page) => {
                        const updatedPosts = page.props.pendingPosts?.data || [];
                        const stillProcessing = updatedPosts.some(p => p.processing_status);
                        
                        if (!stillProcessing) {
                            clearInterval(interval);
                            toast.success('Semua proses background selesai!', { id: 'bg-sync-done' });
                        }
                    },
                    onError: () => {
                        // If error occurs during reload, stop polling to be safe
                        clearInterval(interval);
                    }
                });
            }, 3000); // Poll every 3 seconds

            return () => clearInterval(interval);
        }
    }, [postsData]);

    React.useEffect(() => {
        if (success) toast.success(success);
        if (error) toast.error(error);
    }, [success, error]);

    return (
        <ContentManagementPage 
            headerTitle="Instagram Settings" 
            headTitle="Instagram Settings"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            noForm={true}
        >
            {/* API Key Tab */}
            {activeTab === 'api-key' && (
                <div className="space-y-6">
                    <div className="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                        <div className="flex items-start gap-4 mb-6">
                            <div className="p-3 bg-purple-100 rounded-lg">
                                <Key className="w-6 h-6 text-purple-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Apify API Configuration</h3>
                                <p className="text-sm text-gray-600">
                                    Configure Apify API token untuk scraping Instagram posts. 
                                    Dapatkan token dari <a href="https://console.apify.com/account/integrations" target="_blank" rel="noreferrer" className="text-purple-600 hover:underline">Apify Console</a>.
                                </p>
                            </div>
                        </div>

                        <form onSubmit={handleSaveApiKey} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="apify_token" value="Apify API Token" />
                                <TextInput
                                    id="apify_token"
                                    type="password"
                                    className="mt-1 block w-full"
                                    value={data.apify_token}
                                    onChange={(e) => setData('apify_token', e.target.value)}
                                    placeholder="apify_api_xxxxxxxxxxxxxxxxxx"
                                />
                                <InputError message={errors.apify_token} className="mt-2" />
                                <p className="mt-2 text-xs text-gray-500">
                                    Token akan disimpan di database
                                </p>
                            </div>

                            <div className="flex items-center gap-3 pt-4">
                                <PrimaryButton type="submit" disabled={processing}>
                                    <Save className="w-4 h-4 mr-2" />
                                    {processing ? 'Menyimpan...' : 'Simpan API Key'}
                                </PrimaryButton>
                                
                                {data.apify_token && (
                                    <span className="text-sm text-green-600 flex items-center gap-2">
                                        <span className="w-2 h-2 bg-green-600 rounded-full"></span>
                                        API Key tersimpan
                                    </span>
                                )}
                            </div>
                        </form>

                        <div className="mt-6 pt-6 border-t border-gray-200">
                            <h4 className="text-sm font-semibold text-gray-900 mb-3">Cara Mendapatkan API Token:</h4>
                            <ol className="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                                <li>Buka <a href="https://console.apify.com/account/integrations" target="_blank" rel="noreferrer" className="text-purple-600 hover:underline">Apify Console</a></li>
                                <li>Login atau buat akun baru (gratis untuk 100 credits/bulan)</li>
                                <li>Ke menu &quot;Settings&quot; → &quot;Integrations&quot;</li>
                                <li>Copy &quot;Personal API Token&quot;</li>
                                <li>Paste token di form di atas dan simpan</li>
                            </ol>
                        </div>

                        <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h4 className="text-sm font-semibold text-blue-900 mb-2">💡 Info Penggunaan</h4>
                            <ul className="text-sm text-blue-800 space-y-1">
                                <li>• Free tier: 100 Apify credits/bulan (~500 posts)</li>
                                <li>• Scraping 1 post ≈ 0.2 credits</li>
                                <li>• Scraping 25 posts ≈ 5 credits</li>
                                <li>• Token ini digunakan oleh command: <code className="bg-blue-100 px-2 py-0.5 rounded">php artisan instagram:scrape --apify</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            )}

            {/* Pending Posts Tab */}
            {activeTab === 'pending' && (
                <div className="space-y-6">
                    {/* Bulk Actions */}
                    {postsData.length > 0 && (
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 p-4">
                            <div className="flex flex-wrap items-center gap-4">
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={selectedPosts.length === postsData.length && postsData.length > 0}
                                        onChange={toggleSelectAll}
                                        className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    />
                                    <span className="text-gray-700">Pilih Semua ({selectedPosts.length}/{postsData.length})</span>
                                </label>

                                {selectedPosts.length > 0 && (
                                    <>
                                        <select
                                            value={bulkCategory}
                                            onChange={(e) => setBulkCategory(e.target.value)}
                                            className="text-sm border-gray-300 rounded-lg focus:ring-accent-yellow focus:border-accent-yellow"
                                        >
                                            <option value="berita">Berita</option>
                                            <option value="pengumuman">Pengumuman</option>
                                            <option value="prestasi">Prestasi</option>
                                            <option value="akademik">Akademik</option>
                                            <option value="kegiatan">Kegiatan</option>
                                        </select>

                                        <button
                                            onClick={handleBulkApprove}
                                            className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                                        >
                                            <Check className="w-4 h-4" />
                                            Approve {selectedPosts.length} Post
                                        </button>
                                    </>
                                )}

                                {/* Cleanup Stuck Posts Button - visible when there are stuck posts */}
                                {postsData.some(p => p.processing_status) && (
                                    <button
                                        onClick={handleCleanupStuck}
                                        className="ml-auto inline-flex items-center gap-2 px-3 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors text-sm font-medium"
                                        title="Reset semua post yang stuck"
                                    >
                                        <RotateCcw className="w-4 h-4" />
                                        Reset Stuck Posts
                                    </button>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Pending Posts List */}
                    {postsData.length === 0 ? (
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 p-12 text-center">
                            <FileText className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500 text-lg">Tidak ada post yang menunggu approval</p>
                            <p className="text-gray-400 text-sm mt-2">Jalankan scraper untuk mengambil post baru dari Instagram</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {postsData.map((post) => (
                                <div key={post.id} className="bg-white shadow-md rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-200">
                                    {/* Checkbox & Image */}
                                    <div className="relative group">
                                        <div className="absolute top-3 left-3 z-10">
                                            <input
                                                type="checkbox"
                                                checked={selectedPosts.includes(post.id)}
                                                onChange={() => toggleSelectPost(post.id)}
                                                className="w-5 h-5 rounded border-2 border-gray-400 text-accent-yellow focus:ring-accent-yellow focus:ring-offset-2 bg-white shadow-lg cursor-pointer"
                                            />
                                        </div>
                                        {post.first_image ? (
                                            <img
                                                src={post.first_image}
                                                alt="Instagram post"
                                                className="w-full h-56 object-cover bg-gray-100 group-hover:scale-105 transition-transform duration-200"
                                                onError={(e) => {
                                                    e.target.style.display = 'none';
                                                    e.target.nextSibling.style.display = 'flex';
                                                }}
                                            />
                                        ) : null}
                                        <div className={`w-full h-56 bg-gray-100 items-center justify-center ${post.first_image ? 'hidden' : 'flex'}`}>
                                            <Image className="w-16 h-16 text-gray-300" />
                                        </div>
                                    </div>

                                    {/* Content */}
                                    <div className="p-5">
                                        <div className="flex items-center gap-2 text-sm text-gray-600 mb-3">
                                            <Instagram className="w-4 h-4 text-pink-500" />
                                            <span className="font-medium">@{post.username}</span>
                                            <span className="text-gray-300">•</span>
                                            <span className="text-xs">{post.scraped_ago}</span>
                                        </div>

                                        <p className="text-sm text-gray-700 line-clamp-3 mb-4 leading-relaxed">
                                            {post.caption_preview || 'No caption'}
                                        </p>

                                        {/* Error Message Display outside processing block */}
                                        {post.error_message && !post.processing_status && (
                                            <div className="mb-4 p-2 bg-red-50 rounded border-l-2 border-red-400">
                                                <p className="text-[10px] text-red-700 font-medium">
                                                    ⚠️ Gagal: {post.error_message}
                                                </p>
                                                <button
                                                    onClick={() => handleProcessAI(post.id)}
                                                    className="mt-1 text-[10px] font-bold text-red-800 underline hover:no-underline"
                                                >
                                                    Coba lagi dengan AI
                                                </button>
                                            </div>
                                        )}

                                        <div className="flex items-center gap-4 text-xs text-gray-500 mb-4 pb-4 border-b border-gray-100">
                                            <span className="flex items-center gap-1">
                                                <span className="font-bold text-red-500">❤️</span> {post.likes_count}
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <span className="font-bold">💬</span> {post.comments_count}
                                            </span>
                                        </div>

                                        {/* Actions */}
                                        <div className="space-y-2">
                                            {post.processing_status ? (
                                                <div className="flex flex-col gap-2 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-xs font-bold text-blue-700 uppercase tracking-wider">Processing</span>
                                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                            <RotateCcw className="w-3 h-3 animate-spin" />
                                                            {post.processing_status === 'queued_ai' ? 'AI Working...' : post.processing_status === 'processing' ? 'Processing...' : 'In Queue'}
                                                        </span>
                                                    </div>
                                                    <p className="text-[10px] text-blue-600 italic font-mono truncate">
                                                        ID: {post.shortcode}
                                                    </p>
                                                    <button
                                                        onClick={() => handleResetStatus(post.id)}
                                                        className="mt-1 text-xs font-medium text-orange-700 bg-orange-100 px-2 py-1 rounded hover:bg-orange-200 transition-colors"
                                                    >
                                                        ↻ Reset jika stuck
                                                    </button>
                                                </div>
                                            ) : (
                                                <>
                                                    <div className="flex gap-2">
                                                        <button
                                                            onClick={() => openApprovalModal(post)}
                                                            className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 active:bg-green-800 transition-colors text-sm font-semibold shadow-sm hover:shadow-md"
                                                        >
                                                            <Check className="w-4 h-4" />
                                                            Approve
                                                        </button>
                                                        <button
                                                            onClick={() => handleRejectPost(post.id)}
                                                            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 active:bg-red-800 transition-colors text-sm font-semibold shadow-sm hover:shadow-md"
                                                            title="Hapus"
                                                        >
                                                            <Trash2 className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                    <button
                                                        onClick={() => handleProcessAI(post.id)}
                                                        disabled={processingAI === post.id}
                                                        className={`w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all text-sm font-semibold shadow-sm ${
                                                            processingAI === post.id
                                                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                                : 'bg-accent-yellow text-gray-900 hover:bg-yellow-500 hover:shadow-md active:bg-yellow-600'
                                                        }`}
                                                    >
                                                        {processingAI === post.id ? (
                                                            <>
                                                                <RotateCcw className="w-4 h-4 animate-spin" />
                                                                Processing...
                                                            </>
                                                        ) : (
                                                            <>
                                                                <Sparkles className="w-5 h-5" />
                                                                Process with AI
                                                            </>
                                                        )}
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {pendingPosts?.links && pendingPosts.links.length > 3 && (
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 px-4 sm:px-6 py-3 border-t">
                            <div className="flex items-center justify-between">
                                <div className="hidden sm:block">
                                    <p className="text-sm text-gray-700">
                                        Showing <span className="font-medium">{(pendingPosts.meta?.from || 1)}</span> to <span className="font-medium">{(pendingPosts.meta?.to || postsData.length)}</span> of <span className="font-medium">{totalPosts}</span> results
                                    </p>
                                </div>
                                <div className="flex gap-2">
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        {pendingPosts.links.map((link, index) => {
                                            if (link.label === '&laquo; Previous') {
                                                return (
                                                    <button
                                                        key={index}
                                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                                        disabled={!link.url}
                                                        className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    >
                                                        <span className="sr-only">Previous</span>
                                                        <ChevronLeft className="h-5 w-5" aria-hidden="true" />
                                                    </button>
                                                );
                                            }
                                            
                                            if (link.label === 'Next &raquo;') {
                                                return (
                                                    <button
                                                        key={index}
                                                        onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                                        disabled={!link.url}
                                                        className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                                    >
                                                        <span className="sr-only">Next</span>
                                                        <ChevronRight className="h-5 w-5" aria-hidden="true" />
                                                    </button>
                                                );
                                            }
                                            
                                            return (
                                                <button
                                                    key={index}
                                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                                    disabled={link.active}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-accent-yellow border-accent-yellow text-gray-900'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            );
                                        })}
                                    </nav>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Approval Modal */}
            {approvalModal.show && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                        <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onClick={() => setApprovalModal({ show: false, post: null })}></div>
                        
                        <div className="relative inline-block w-full max-w-lg p-6 my-8 text-left bg-white rounded-xl shadow-xl transform transition-all">
                            <div className="flex items-center gap-3 mb-4">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <Check className="w-5 h-5 text-green-600" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900">Approve sebagai Draft Berita</h3>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <InputLabel htmlFor="title" value="Judul Berita" />
                                    <TextInput
                                        id="title"
                                        type="text"
                                        className="mt-1 block w-full text-sm py-3 px-4"
                                        value={approvalForm.title}
                                        onChange={(e) => setApprovalForm(prev => ({ ...prev, title: e.target.value }))}
                                        placeholder="Masukkan judul berita"
                                    />
                                </div>

                                <div>
                                    <InputLabel htmlFor="category" value="Kategori" />
                                    <select
                                        id="category"
                                        value={approvalForm.category}
                                        onChange={(e) => setApprovalForm(prev => ({ ...prev, category: e.target.value }))}
                                        className="mt-1 block w-full border-gray-300 rounded-lg focus:ring-accent-yellow focus:border-accent-yellow"
                                    >
                                        <option value="berita">Berita</option>
                                        <option value="pengumuman">Pengumuman</option>
                                        <option value="prestasi">Prestasi</option>
                                        <option value="akademik">Akademik</option>
                                        <option value="kegiatan">Kegiatan</option>
                                    </select>
                                </div>

                                <div className="p-3 bg-gray-50 rounded-lg">
                                    <p className="text-xs text-gray-500 mb-2">Images ({approvalForm.image_paths?.length || 0}):</p>
                                    <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300">
                                        {approvalForm.image_paths?.map((img, idx) => (
                                            <div key={idx} className="relative flex-shrink-0">
                                                <img 
                                                    src={img} 
                                                    alt={`Post thumbnail ${idx + 1}`} 
                                                    className="w-20 h-20 object-cover rounded-md border border-gray-200"
                                                />
                                                <div className="absolute top-0 right-0 bg-black/50 text-white text-[8px] px-1 rounded-bl-md">
                                                    {idx + 1}
                                                </div>
                                            </div>
                                        ))}
                                        {(!approvalForm.image_paths || approvalForm.image_paths.length === 0) && (
                                            <div className="w-20 h-20 flex items-center justify-center bg-gray-100 rounded-md border border-dashed border-gray-300">
                                                <Image className="w-6 h-6 text-gray-400" />
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="p-3 bg-gray-50 rounded-lg">
                                    <p className="text-xs text-gray-500 mb-2">Preview Caption:</p>
                                    <p className="text-sm text-gray-700 line-clamp-4">{approvalModal.post?.caption_preview}</p>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 mt-6">
                                <button
                                    type="button"
                                    onClick={() => setApprovalModal({ show: false, post: null })}
                                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    onClick={handleApprovePost}
                                    className="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors"
                                >
                                    Approve sebagai Draft
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Statistics Tab */}
            {activeTab === 'statistics' && (
                <div className="space-y-6">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Total Scraped</p>
                                    <p className="text-2xl font-bold text-gray-900">{statistics.total_scraped}</p>
                                </div>
                                <div className="p-3 bg-blue-100 rounded-lg">
                                    <Instagram className="w-6 h-6 text-blue-600" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Pending Processing</p>
                                    <p className="text-2xl font-bold text-yellow-600">{statistics.pending_processing}</p>
                                </div>
                                <div className="p-3 bg-yellow-100 rounded-lg">
                                    <Activity className="w-6 h-6 text-yellow-600" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Processed</p>
                                    <p className="text-2xl font-bold text-green-600">{statistics.processed}</p>
                                </div>
                                <div className="p-3 bg-green-100 rounded-lg">
                                    <Instagram className="w-6 h-6 text-green-600" />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Errors</p>
                                    <p className="text-2xl font-bold text-red-600">{statistics.errors}</p>
                                </div>
                                <div className="p-3 bg-red-100 rounded-lg">
                                    <Activity className="w-6 h-6 text-red-600" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Activity by Source */}
                    <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900">Top Sources</h3>
                        </div>
                        <div className="p-6">
                            {statistics.by_source.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">No scraping activity yet</p>
                            ) : (
                                <div className="space-y-3">
                                    {statistics.by_source.map((source, index) => (
                                        <div key={index} className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <Instagram className="w-5 h-5 text-pink-500" />
                                                <span className="font-medium text-gray-900">@{source.username}</span>
                                            </div>
                                            <span className="text-sm text-gray-600">{source.count} posts</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900">Recent Activity</h3>
                        </div>
                        <div className="p-6">
                            {statistics.recent_activity.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">No recent activity</p>
                            ) : (
                                <div className="space-y-3">
                                    {statistics.recent_activity.map((activity, index) => (
                                        <div key={index} className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <Activity className="w-5 h-5 text-blue-500" />
                                                <span className="text-gray-900">@{activity.username}</span>
                                                <span className="text-sm text-gray-500">{activity.time}</span>
                                            </div>
                                            <span className={`text-xs px-2.5 py-1 rounded-full ${
                                                activity.status === 'Processed' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}>
                                                {activity.status}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Logs Tab */}
            {activeTab === 'logs' && (
                <div className="space-y-6">
                    {/* Single Post Scraper */}
                    <div className="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                        <div className="flex items-start gap-4">
                            <div className="p-3 bg-pink-100 rounded-lg">
                                <Link2 className="w-6 h-6 text-pink-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Scrape Post Tunggal</h3>
                                <p className="text-sm text-gray-600 mb-4">
                                    Masukkan link Instagram post untuk scrape satu postingan saja.
                                    Support: instagram.com/p/xxx, instagram.com/reel/xxx
                                </p>
                                <div className="flex gap-3">
                                    <TextInput
                                        type="url"
                                        value={singlePostUrl}
                                        onChange={(e) => setSinglePostUrl(e.target.value)}
                                        placeholder="https://www.instagram.com/p/SHORTCODE"
                                        className="flex-1"
                                        disabled={isScrapingSinglePost}
                                    />
                                    <button
                                        onClick={handleScrapeSinglePost}
                                        disabled={isScrapingSinglePost || !apifyToken || !singlePostUrl.trim()}
                                        className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors ${
                                            isScrapingSinglePost || !apifyToken || !singlePostUrl.trim()
                                                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                : 'bg-pink-600 text-white hover:bg-pink-700'
                                        }`}
                                    >
                                        {isScrapingSinglePost ? (
                                            <>
                                                <RotateCcw className="w-4 h-4 animate-spin" />
                                                Scraping...
                                            </>
                                        ) : (
                                            <>
                                                <Instagram className="w-4 h-4" />
                                                Scrape Post
                                            </>
                                        )}
                                    </button>
                                </div>
                                {!apifyToken && (
                                    <p className="text-xs text-red-600 mt-2 flex items-center gap-1">
                                        <AlertCircle className="w-3 h-3" />
                                        Silakan atur API token terlebih dahulu
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Run Scraper Button */}
                    <div className="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                        <div className="flex items-start gap-4">
                            <div className="p-3 bg-orange-100 rounded-lg">
                                <Play className="w-6 h-6 text-orange-600" />
                            </div>
                            <div className="flex-1">
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Jalankan Instagram Scraper</h3>
                                <p className="text-sm text-gray-600 mb-4">
                                    Klik tombol di bawah untuk menjalankan scraper Instagram secara manual. 
                                    Scraper akan mengambil 25 post terbaru dari @sman1baleendah.
                                </p>
                                <button
                                    onClick={handleRunScraper}
                                    disabled={isRunningScaper || !apifyToken}
                                    className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors ${
                                        isRunningScaper || !apifyToken
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            : 'bg-orange-600 text-white hover:bg-orange-700'
                                    }`}
                                >
                                    <Play className="w-4 h-4" />
                                    {isRunningScaper ? 'Scraping...' : 'Jalankan Scraper Sekarang'}
                                </button>
                                {!apifyToken && (
                                    <p className="text-xs text-red-600 mt-2 flex items-center gap-1">
                                        <AlertCircle className="w-3 h-3" />
                                        Silakan atur API token terlebih dahulu
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Scraper Logs */}
                    <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-900">Scraper Logs</h3>
                        </div>
                        <div className="p-6">
                            {!logs || logs.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">Belum ada log scraper</p>
                            ) : (
                                <div className="space-y-4 overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-gray-50 border-b border-gray-200">
                                            <tr>
                                                <th className="px-4 py-3 text-left font-semibold text-gray-900">Username</th>
                                                <th className="px-4 py-3 text-left font-semibold text-gray-900">Status</th>
                                                <th className="px-4 py-3 text-left font-semibold text-gray-900">Posts</th>
                                                <th className="px-4 py-3 text-left font-semibold text-gray-900">Waktu</th>
                                                <th className="px-4 py-3 text-left font-semibold text-gray-900">Durasi</th>
                                                <th className="px-4 py-3 text-left font-semibold text-gray-900">Message</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200">
                                            {logs.map((log, index) => (
                                                <tr key={index} className="hover:bg-gray-50">
                                                    <td className="px-4 py-3 text-gray-900 font-medium">@{log.username}</td>
                                                    <td className="px-4 py-3">
                                                        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${
                                                            log.status === 'completed'
                                                                ? 'bg-green-100 text-green-800'
                                                                : log.status === 'running'
                                                                ? 'bg-blue-100 text-blue-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {log.status === 'completed' && <CheckCircle className="w-3 h-3" />}
                                                            {log.status === 'running' && <RotateCcw className="w-3 h-3 animate-spin" />}
                                                            {log.status === 'failed' && <AlertCircle className="w-3 h-3" />}
                                                            {log.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600">
                                                        {log.posts_found ? `${log.posts_found} posts` : '-'}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600 whitespace-nowrap">{log.started_at}</td>
                                                    <td className="px-4 py-3 text-gray-600">{log.duration}</td>
                                                    <td className="px-4 py-3 text-gray-600">
                                                        {log.message ? (
                                                            <span title={log.message}>{log.message.substring(0, 30)}...</span>
                                                        ) : log.error_message ? (
                                                            <span title={log.error_message} className="text-red-600">{log.error_message.substring(0, 30)}...</span>
                                                        ) : (
                                                            '-'
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </ContentManagementPage>
    );
}

