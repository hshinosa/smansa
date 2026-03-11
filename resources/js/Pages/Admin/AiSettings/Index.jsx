// FILE: resources/js/Pages/Admin/AiSettings/Index.jsx
import React, { useState, useEffect } from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { Cpu, Database, ChevronDown, Plus, Trash2, MessageSquare, FileText, Key } from 'lucide-react';
import axios from 'axios';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import ContentManagementPage from '@/Components/Admin/ContentManagementPage';
import toast from 'react-hot-toast';

export default function Index({ settings, apifyToken, embeddingHealth, queueHealth }) {
    const { success } = usePage().props;
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'groq';
    const [activeTab, setActiveTab] = useState(initialTab);
    const [showChatModelDropdown, setShowChatModelDropdown] = useState(false);
    const [showContentModelDropdown, setShowContentModelDropdown] = useState(false);
    const [availableModels, setAvailableModels] = useState([]);
    const [loadingModels, setLoadingModels] = useState(false);

    const settingsMap = {};
    settings.forEach(s => {
        settingsMap[s.key] = s.value;
    });

    // Parse API keys from JSON string or array
    const parseApiKeys = (val) => {
        if (Array.isArray(val)) return val;
        if (!val) return [''];
        try {
            const parsed = JSON.parse(val);
            return Array.isArray(parsed) && parsed.length > 0 ? parsed : [''];
        } catch {
            return val ? [val] : [''];
        }
    };

    const [apiKeys, setApiKeys] = useState(parseApiKeys(settingsMap.groq_api_keys));
    const [apifyTokenValue, setApifyTokenValue] = useState(apifyToken || '');

    const { data, setData, post, processing, errors } = useForm({
        settings: [
            { key: 'groq_api_keys', value: JSON.stringify(apiKeys.filter(k => k.trim())) },
            { key: 'groq_chat_model', value: settingsMap.groq_chat_model || 'llama-3.1-8b-instant' },
            { key: 'groq_content_model', value: settingsMap.groq_content_model || 'llama-3.3-70b-versatile' },
            { key: 'embedding_provider', value: settingsMap.embedding_provider || 'tei' },
            { key: 'embedding_base_url', value: settingsMap.embedding_base_url || 'http://embedding:8080' },
            { key: 'embedding_model', value: settingsMap.embedding_model || 'intfloat/multilingual-e5-small' },
            { key: 'embedding_dimensions', value: settingsMap.embedding_dimensions || '384' },
            { key: 'ai_max_tokens', value: settingsMap.ai_max_tokens || '2000' },
            { key: 'ai_temperature', value: settingsMap.ai_temperature || '0.7' },
            { key: 'rag_enabled', value: settingsMap.rag_enabled || '1' },
            { key: 'rag_top_k', value: settingsMap.rag_top_k || '5' },
            { key: 'queue_alert_pending_threshold', value: settingsMap.queue_alert_pending_threshold || '100' },
            { key: 'queue_alert_failed_threshold', value: settingsMap.queue_alert_failed_threshold || '1' },
        ]
    });

    const tabs = [
        { key: 'groq', label: 'Groq AI', description: 'Konfigurasi Groq API untuk chat dan generate konten.', icon: Cpu },
        { key: 'apify', label: 'Apify API', description: 'Konfigurasi Apify API untuk Instagram scraper.', icon: Key },
        { key: 'rag', label: 'RAG Settings', description: 'Pengaturan Retrieval Augmented Generation.', icon: Database },
    ];

    const fetchModels = async () => {
        setLoadingModels(true);
        try {
            const response = await axios.get(route('admin.ai-settings.models'));
            if (response.data.success) {
                setAvailableModels(response.data.models || []);
            }
        } catch (error) {
            console.error('Failed to fetch models:', error);
        } finally {
            setLoadingModels(false);
        }
    };

    useEffect(() => {
        const handleClickOutside = (event) => {
            if (!event.target.closest('.model-dropdown-container')) {
                setShowChatModelDropdown(false);
                setShowContentModelDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // No useEffect sync needed - we inject apiKeys at submit time

    const handleChange = (key, value) => {
        const updatedSettings = data.settings.map(s =>
            s.key === key ? { ...s, value } : s
        );
        setData('settings', updatedSettings);
    };

    const getSetting = (key) => {
        return data.settings.find(s => s.key === key)?.value || '';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const keysJson = JSON.stringify(apiKeys.filter(k => k.trim()));
        const finalSettings = data.settings.map(s =>
            s.key === 'groq_api_keys' ? { ...s, value: keysJson } : s
        );
        router.post(route('admin.ai-settings.update'), { settings: finalSettings }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => toast.success('Pengaturan AI berhasil diperbarui'),
        });
    };

    const handleApifyTokenSubmit = (e) => {
        e.preventDefault();
        router.post(route('admin.ai-settings.apify-token.update'), {
            apify_token: apifyTokenValue
        }, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => toast.success('Apify API key berhasil disimpan'),
        });
    };

    const handleModelSelect = (field, modelId) => {
        handleChange(field, modelId);
        setShowChatModelDropdown(false);
        setShowContentModelDropdown(false);
    };

    const triggerReindexDbContent = () => {
        router.post(route('admin.ai-settings.reindex-db-content'), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Reindex database content dimulai.'),
            onError: () => toast.error('Gagal memulai reindex database content.'),
        });
    };

    const addApiKey = () => {
        setApiKeys([...apiKeys, '']);
    };

    const removeApiKey = (index) => {
        if (apiKeys.length <= 1) return;
        setApiKeys(apiKeys.filter((_, i) => i !== index));
    };

    const updateApiKey = (index, value) => {
        const updated = [...apiKeys];
        updated[index] = value;
        setApiKeys(updated);
    };

    const ModelDropdown = ({ field, show, setShow }) => (
        <div className="relative model-dropdown-container z-50">
            <InputLabel
                htmlFor={field}
                value={field === 'groq_chat_model' ? 'Model Chat (Chatbot/RAG)' : 'Model Konten (Generate Berita)'}
            />
            <div className="flex items-center gap-2 mt-1">
                <div className="flex-1 flex items-center gap-1.5 text-xs text-gray-500">
                    {field === 'groq_chat_model' ? (
                        <><MessageSquare className="w-3.5 h-3.5" /> Untuk chatbot dan RAG</>
                    ) : (
                        <><FileText className="w-3.5 h-3.5" /> Untuk generate artikel berita</>
                    )}
                </div>
            </div>
            <div className="relative mt-1">
                <TextInput
                    id={field}
                    type="text"
                    className="mt-1 block w-full pr-10"
                    value={getSetting(field)}
                    onChange={(e) => handleChange(field, e.target.value)}
                    onFocus={() => {
                        setShow(true);
                        if (availableModels.length === 0) fetchModels();
                    }}
                    placeholder={field === 'groq_chat_model' ? "llama-3.1-8b-instant" : "llama-3.3-70b-versatile"}
                />
                <button
                    type="button"
                    onClick={() => {
                        setShow(!show);
                        if (!show && availableModels.length === 0) fetchModels();
                    }}
                    className="absolute right-0 top-1/2 -translate-y-1/2 pr-3 flex items-center"
                >
                    <ChevronDown className="w-5 h-5 text-gray-400" />
                </button>
            </div>
            {show && (
                <div className="absolute z-50 left-0 right-0 bottom-full mb-1 bg-white border border-gray-300 rounded-md shadow-xl max-h-60 overflow-y-auto">
                    {loadingModels ? (
                        <div className="p-4 text-sm text-gray-500">Loading models...</div>
                    ) : availableModels.length > 0 ? (
                        <div className="py-1">
                            {availableModels.map((model) => (
                                <button
                                    key={model.id}
                                    type="button"
                                    onClick={() => handleModelSelect(field, model.id)}
                                    className="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm flex items-center justify-between border-b border-gray-100 last:border-b-0"
                                >
                                    <span>{model.id}</span>
                                    {model.owned_by && (
                                        <span className="text-xs text-gray-400 capitalize">{model.owned_by}</span>
                                    )}
                                </button>
                            ))}
                        </div>
                    ) : (
                        <div className="p-4 text-sm text-gray-500">Tidak ada model. Tambahkan API Key terlebih dahulu.</div>
                    )}
                </div>
            )}
        </div>
    );

    return (
        <ContentManagementPage
            headerTitle="AI Settings"
            headTitle="AI Settings"
            tabs={tabs}
            activeTab={activeTab}
            setActiveTab={setActiveTab}
            onSave={handleSubmit}
        >
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Groq AI Settings */}
                {activeTab === 'groq' && (
                    <div className="space-y-6">
                        {/* API Keys Section */}
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="text-sm font-semibold text-gray-900">Groq API Keys</h3>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            Tambahkan beberapa API key untuk round-robin (jika rate limited, otomatis pakai key berikutnya).
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={addApiKey}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        Tambah Key
                                    </button>
                                </div>
                                <div className="space-y-2">
                                    {apiKeys.map((key, index) => (
                                        <div key={index} className="flex items-center gap-2">
                                            <span className="text-xs text-gray-400 w-6 text-right">{index + 1}.</span>
                                            <TextInput
                                                type="password"
                                                className="flex-1"
                                                value={key}
                                                onChange={(e) => updateApiKey(index, e.target.value)}
                                                placeholder="gsk_xxxxxxxxxxxxx"
                                            />
                                            {apiKeys.length > 1 && (
                                                <button
                                                    type="button"
                                                    onClick={() => removeApiKey(index)}
                                                    className="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded transition"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                                <p className="text-xs text-blue-600 bg-blue-50 p-2 rounded">
                                    Dapatkan API key gratis di <a href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer" className="underline font-medium">console.groq.com/keys</a>
                                </p>
                            </div>
                        </div>

                        {/* Model Selection */}
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200">
                            <div className="p-6 space-y-6">
                                <h3 className="text-sm font-semibold text-gray-900">Model AI</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <ModelDropdown
                                        field="groq_chat_model"
                                        show={showChatModelDropdown}
                                        setShow={setShowChatModelDropdown}
                                    />
                                    <ModelDropdown
                                        field="groq_content_model"
                                        show={showContentModelDropdown}
                                        setShow={setShowContentModelDropdown}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Embedding Service */}
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-semibold text-gray-900">Embedding Service (Docker)</h3>
                                    <span className={`text-xs px-2 py-1 rounded ${embeddingHealth?.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                        {embeddingHealth?.available ? 'Online' : 'Offline'}
                                    </span>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="embedding_provider" value="Provider" />
                                        <TextInput
                                            id="embedding_provider"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={getSetting('embedding_provider')}
                                            onChange={(e) => handleChange('embedding_provider', e.target.value)}
                                            placeholder="tei"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="embedding_base_url" value="Base URL" />
                                        <TextInput
                                            id="embedding_base_url"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={getSetting('embedding_base_url')}
                                            onChange={(e) => handleChange('embedding_base_url', e.target.value)}
                                            placeholder="http://embedding:8080"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="embedding_model" value="Embedding Model" />
                                        <TextInput
                                            id="embedding_model"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={getSetting('embedding_model')}
                                            onChange={(e) => handleChange('embedding_model', e.target.value)}
                                            placeholder="intfloat/multilingual-e5-small"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="embedding_dimensions" value="Embedding Dimensions" />
                                        <TextInput
                                            id="embedding_dimensions"
                                            type="number"
                                            className="mt-1 block w-full"
                                            value={getSetting('embedding_dimensions')}
                                            onChange={(e) => handleChange('embedding_dimensions', e.target.value)}
                                            placeholder="384"
                                        />
                                    </div>
                                </div>
                                <p className="text-xs text-gray-500">
                                    Health: {embeddingHealth?.base_url || '-'} | Model: {embeddingHealth?.model || '-'} | Dimensi: {embeddingHealth?.dimensions || '-'}
                                </p>
                            </div>
                        </div>

                        {/* General AI Params */}
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-4">
                                <h3 className="text-sm font-semibold text-gray-900">Parameter</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="ai_max_tokens" value="Max Tokens" />
                                        <TextInput
                                            id="ai_max_tokens"
                                            type="number"
                                            className="mt-1 block w-full"
                                            value={getSetting('ai_max_tokens')}
                                            onChange={(e) => handleChange('ai_max_tokens', e.target.value)}
                                            placeholder="2000"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="ai_temperature" value="Temperature (0-1)" />
                                        <TextInput
                                            id="ai_temperature"
                                            type="number"
                                            step="0.1"
                                            className="mt-1 block w-full"
                                            value={getSetting('ai_temperature')}
                                            onChange={(e) => handleChange('ai_temperature', e.target.value)}
                                            placeholder="0.7"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Apify API Settings */}
                {activeTab === 'apify' && (
                    <div className="space-y-6">
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-4">
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-900">Apify API Token</h3>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        Token API dari Apify untuk menjalankan Instagram scraper.
                                    </p>
                                </div>
                                <div>
                                    <InputLabel htmlFor="apify_token" value="API Token" />
                                    <TextInput
                                        id="apify_token"
                                        type="password"
                                        className="mt-1 block w-full"
                                        value={apifyTokenValue}
                                        onChange={(e) => setApifyTokenValue(e.target.value)}
                                        placeholder="xxxxxx..."
                                    />
                                    <p className="mt-1 text-xs text-blue-600">
                                        Dapatkan API token di <a href="https://console.apify.com/settings/integrations" target="_blank" rel="noopener noreferrer" className="underline font-medium">console.apify.com</a>
                                    </p>
                                </div>
                                <div>
                                    <PrimaryButton 
                                        type="button"
                                        onClick={handleApifyTokenSubmit}
                                        className="!bg-accent-yellow !text-gray-900 hover:!bg-yellow-500"
                                    >
                                        Simpan Apify Token
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* RAG Settings */}
                {activeTab === 'rag' && (
                    <div className="space-y-4">
                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-4">
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="rag_enabled"
                                        checked={getSetting('rag_enabled') === '1'}
                                        onChange={(e) => handleChange('rag_enabled', e.target.checked ? '1' : '0')}
                                        className="h-4 w-4 text-accent-yellow focus:ring-accent-yellow border-gray-300 rounded"
                                    />
                                    <label htmlFor="rag_enabled" className="ml-2 block text-sm font-medium text-gray-900">
                                        Aktifkan RAG (Retrieval Augmented Generation)
                                    </label>
                                </div>
                                <div>
                                    <InputLabel htmlFor="rag_top_k" value="Top K Documents" />
                                    <TextInput
                                        id="rag_top_k"
                                        type="number"
                                        className="mt-1 block w-full md:w-1/2"
                                        value={getSetting('rag_top_k')}
                                        onChange={(e) => handleChange('rag_top_k', e.target.value)}
                                        placeholder="5"
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        Jumlah dokumen yang diambil untuk setiap query (default: 5)
                                    </p>
                                </div>
                                <div className="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p className="text-xs text-yellow-700">
                                        <strong>Catatan:</strong> RAG menggunakan embedding service Docker + pgvector. Pastikan service embedding online.
                                    </p>
                                </div>

                                <div className="p-4 rounded-lg border border-gray-200 bg-gray-50 space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-semibold text-gray-900">Queue Monitoring Alert</h4>
                                        <span className={`text-xs px-2 py-1 rounded ${queueHealth?.status === 'alert' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`}>
                                            {queueHealth?.status === 'alert' ? 'Alert' : 'Normal'}
                                        </span>
                                    </div>
                                    <div className="text-xs text-gray-600 grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <p>Pending jobs saat ini: <strong>{queueHealth?.pending_jobs ?? 0}</strong></p>
                                        <p>Failed jobs saat ini: <strong>{queueHealth?.failed_jobs ?? 0}</strong></p>
                                    </div>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="queue_alert_pending_threshold" value="Alert jika Pending Jobs >=" />
                                            <TextInput
                                                id="queue_alert_pending_threshold"
                                                type="number"
                                                className="mt-1 block w-full"
                                                value={getSetting('queue_alert_pending_threshold')}
                                                onChange={(e) => handleChange('queue_alert_pending_threshold', e.target.value)}
                                                placeholder="100"
                                            />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="queue_alert_failed_threshold" value="Alert jika Failed Jobs >=" />
                                            <TextInput
                                                id="queue_alert_failed_threshold"
                                                type="number"
                                                className="mt-1 block w-full"
                                                value={getSetting('queue_alert_failed_threshold')}
                                                onChange={(e) => handleChange('queue_alert_failed_threshold', e.target.value)}
                                                placeholder="1"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                            <div className="p-6 space-y-3">
                                <h3 className="text-sm font-semibold text-gray-900">Index Database Content</h3>
                                <p className="text-xs text-gray-500">
                                    Sinkronkan konten database publik ke RAG documents/chunks agar retrieval lebih lengkap.
                                </p>
                                <div>
                                    <button
                                        type="button"
                                        onClick={triggerReindexDbContent}
                                        className="inline-flex items-center px-4 py-2 bg-accent-yellow text-black rounded-lg text-sm font-medium hover:opacity-90"
                                    >
                                        Reindex Database Content
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </form>
        </ContentManagementPage>
    );
}
