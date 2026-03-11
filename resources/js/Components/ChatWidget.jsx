import React, { useState, useRef, useEffect } from 'react';
import { MessageCircle, X, Send, Bot, Trash2 } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import rehypeRaw from 'rehype-raw';

// Debug logging helper
const DEBUG_CHAT = true; // Set to false to disable debug logs
const logChat = (message, data = {}) => {
    if (DEBUG_CHAT) {
        console.log(`[ChatWidget] ${message}`, data);
    }
};

export default function ChatWidget() {
    const page = usePage();
    if (!page) return null;
    
    const { siteSettings } = page.props;
    const siteName = siteSettings?.general?.site_name || 'SMAN 1 Baleendah';
    
    // Get initial WhatsApp number from site settings (state for dynamic updates from API)
    const [whatsappNumber, setWhatsappNumber] = useState(() => {
        const general = siteSettings?.general || {};
        const wa = general.whatsapp || '+6281234567890';
        return wa.replace('+', '');
    });
    
    const [isOpen, setIsOpen] = useState(false);
    const [isTyping, setIsTyping] = useState(false);
    const [isSearching, setIsSearching] = useState(false);
    const [countdown, setCountdown] = useState(0);
    const [inputValue, setInputValue] = useState("");
    const [isHidden, setIsHidden] = useState(false);
    const [sessionId, setSessionId] = useState(null);
    const [showSuggestions, setShowSuggestions] = useState(true);
    const [isLoadingHistory, setIsLoadingHistory] = useState(false);
    const [historyLoaded, setHistoryLoaded] = useState(false);
    const [streamError, setStreamError] = useState(null);
    const [retryCount, setRetryCount] = useState(0);
    const maxRetries = 3;
    const [suggestionChips, setSuggestionChips] = useState([
        "Info PPDB?",
        "Program studi?",
        "Ekstrakurikuler?",
        "Lokasi sekolah?",
    ]);
    const messagesEndRef = useRef(null);
    const sessionInitializedRef = useRef(false);

    // Handle countdown timer for rate limits
    useEffect(() => {
        if (countdown > 0) {
            const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
            return () => clearTimeout(timer);
        }
    }, [countdown]);

    // Initialize or get session ID from localStorage with lock mechanism
    useEffect(() => {
        // Prevent concurrent initialization
        if (sessionInitializedRef.current) return;
        
        const initializeSession = () => {
            // Use atomic flag in localStorage to prevent race condition
            const lockKey = 'chat_session_lock';
            const lockTimeout = 5000; // 5 seconds
            
            try {
                // Check if another tab is initializing
                const existingLock = localStorage.getItem(lockKey);
                if (existingLock) {
                    const lockTime = parseInt(existingLock);
                    if (Date.now() - lockTime < lockTimeout) {
                        // Another tab is initializing, wait and retry
                        setTimeout(initializeSession, 100);
                        return;
                    }
                }
                
                // Acquire lock
                localStorage.setItem(lockKey, Date.now().toString());
                
                let sid = localStorage.getItem('chat_session_id');
                
                // Generate unique session ID if not exists
                if (!sid) {
                    // More robust unique ID: timestamp + random + user agent hash
                    const timestamp = Date.now();
                    const random = Math.random().toString(36).substr(2, 12);
                    const userAgentHash = navigator.userAgent.split('').reduce((a, b) => {
                        a = ((a << 5) - a) + b.charCodeAt(0);
                        return a & a;
                    }, 0);
                    
                    sid = `session_${timestamp}_${random}_${Math.abs(userAgentHash)}`;
                    localStorage.setItem('chat_session_id', sid);
                }
                
                // Validate session ID format from backend perspective
                if (!/^session_\d+_[a-z0-9]+_\d+$/.test(sid)) {
                    console.warn('Invalid session ID format, regenerating...');
                    localStorage.removeItem('chat_session_id');
                    initializeSession();
                    return;
                }
                
                setSessionId(sid);
                sessionInitializedRef.current = true;
                
                // Release lock
                localStorage.removeItem(lockKey);
            } catch (error) {
                console.error('Session initialization error:', error);
                localStorage.removeItem(lockKey);
            }
        };
        
        initializeSession();
    }, []);

    // Load chat history when widget opens
    const loadChatHistory = async () => {
        if (!sessionId || historyLoaded || isLoadingHistory) return;
        
        setIsLoadingHistory(true);
        try {
            const csrfToken = getCsrfToken();
            const response = await axios.get('/api/chat/history', {
                params: {
                    session_id: sessionId,
                    limit: 20
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            if (response.data.success && response.data.history.length > 0) {
                // Convert history to message format
                const historyMessages = response.data.history.map(msg => ({
                    id: `history_${msg.id}`,
                    sender: msg.sender,
                    text: msg.message,
                    isRagEnhanced: msg.is_rag_enhanced,
                    timestamp: msg.timestamp,
                }));
                
                // Replace initial greeting with history (keep greeting at start)
                setMessages([
                    {
                        id: 1,
                        sender: 'bot',
                        text: `Halo lagi! 👋 Aku AI SMANSA~ Ini percakapan kita sebelumnya ya ✨`,
                        isRagEnhanced: false,
                    },
                    ...historyMessages,
                ]);
                
                // Hide default suggestions if we have history
                setShowSuggestions(false);
            }
            setHistoryLoaded(true);
        } catch (error) {
            console.error('Failed to load chat history:', error);
            setHistoryLoaded(true); // Mark as loaded even on error to prevent retry
        } finally {
            setIsLoadingHistory(false);
        }
    };

    // Load history when widget opens
    useEffect(() => {
        if (isOpen && sessionId && !historyLoaded) {
            loadChatHistory();
        }
    }, [isOpen, sessionId]);

    // Initial Messages
    const [messages, setMessages] = useState([
        {
            id: 1,
            sender: 'bot',
            text: `Halo! 👋 Aku **AI SMANSA**, teman virtualmu buat ngobrolin SMAN 1 Baleendah! ✨`,
            isRagEnhanced: false,
        },
        {
            id: 2,
            sender: 'bot',
            text: `Mau tanya apa nih tentang SMANSA? PPDB, jurusan, ekskul, atau yang lain? Yuk, tanya aja~ 😊`,
            isRagEnhanced: false,
        }
    ]);

    useEffect(() => {
        // Load site settings from first message or use props
        if (messages.length === 2) {
            // Use siteSettings from props initially
            // WhatsApp number will be from siteSettings.general.whatsapp in sendMessageToAPIStream
        }
    }, [messages.length]);

    useEffect(() => {
        const checkHidden = () => {
            const path = window.location.pathname;
            setIsHidden(path.startsWith('/admin') || path.startsWith('/login'));
        };

        checkHidden();
        const removeListener = router.on('finish', checkHidden);

        return () => {
            removeListener();
        };
    }, []);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        if (!isHidden) {
            scrollToBottom();
        }
    }, [messages, isOpen, isTyping, isHidden]);

    if (isHidden) {
        return null;
    }

    // Get CSRF token from meta tag
    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    };

    // Send message to RAG API with streaming
    const sendMessageToAPIStream = async (userMessage) => {
        const requestStartTime = Date.now();
        logChat('========== NEW MESSAGE REQUEST ==========');
        logChat('Sending message to API', { 
            userMessage: userMessage.substring(0, 50),
            sessionId,
            timestamp: new Date().toISOString()
        });
        
        return new Promise(async (resolve, reject) => {
            try {
                // Create a temporary message for streaming (this will replace typing indicator)
                const botMessageId = 'bot_' + Date.now();
                
                // Turn off typing indicator immediately when streaming starts
                setIsTyping(false);
                
                // Add empty bot message that will be filled during streaming
                addMessage('', 'bot', false, botMessageId);
                
                let streamedText = '';
                let isRagEnhanced = false;

                const csrfToken = getCsrfToken();
                logChat('Making fetch request', {
                    url: '/api/chat/send',
                    csrfToken: csrfToken ? 'present' : 'missing',
                    sessionId,
                });
                
                const response = await fetch('/api/chat/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        message: userMessage,
                        session_id: sessionId,
                        stream: true
                    })
                });

                logChat('Fetch response received', {
                    status: response.status,
                    statusText: response.statusText,
                    ok: response.ok,
                    contentType: response.headers.get('content-type'),
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    logChat('Response not OK', { status: response.status, errorText: errorText.substring(0, 200) });
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let chunkCount = 0;

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) {
                        logChat('Stream completed', { 
                            totalChunks: chunkCount, 
                            totalLength: streamedText.length,
                            elapsedMs: Date.now() - requestStartTime
                        });
                        break;
                    }

                    const chunk = decoder.decode(value);
                    chunkCount++;
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(line.slice(6));

                                if (data.type === 'metadata') {
                                    setIsSearching(false);
                                    isRagEnhanced = data.is_rag_enhanced;
                                    logChat('Metadata received', { isRagEnhanced, sessionId: data.session_id });
                                } else if (data.type === 'content') {
                                    setIsSearching(false);
                                    streamedText += data.content;
                                    updateMessage(botMessageId, streamedText, isRagEnhanced);
                                } else if (data.type === 'done') {
                                    logChat('Done event received', { 
                                        messageLength: data.full_message?.length,
                                        elapsedMs: Date.now() - requestStartTime
                                    });
                                    updateMessage(botMessageId, data.full_message, isRagEnhanced);
                                    
                                    // Update WhatsApp number from site settings if provided
                                    if (data.site_settings?.general?.whatsapp) {
                                        setWhatsappNumber(data.site_settings.general.whatsapp.replace('+', ''));
                                    }
                                    
                                    resolve({
                                        text: data.full_message,
                                        isRagEnhanced: isRagEnhanced
                                    });
                                    return;
                                } else if (data.type === 'error') {
                                    logChat('Error event received', { message: data.message });
                                    updateMessage(botMessageId, `❌ ${data.message}`, false);
                                }
                            } catch (err) {
                                logChat('Error parsing SSE data', { error: err.message, line: line.substring(0, 100) });
                            }
                        }
                    }
                }

                resolve({ text: streamedText, isRagEnhanced });

            } catch (error) {
                const elapsedMs = Date.now() - requestStartTime;
                logChat('========== CHAT ERROR ==========');
                logChat('Chat streaming error', { 
                    error: error.message, 
                    name: error.name,
                    elapsedMs,
                    stack: error.stack?.substring(0, 300)
                });
                console.error('Chat streaming error:', error);
                setIsTyping(false);
                
                // Determine error type and provide user-friendly message
                let errorMessage = 'Maaf, terjadi kesalahan saat memproses pesan Anda.';
                let shouldRetry = false;
                
                if (error.name === 'AbortError' || error.message.includes('timeout')) {
                    errorMessage = 'Koneksi timeout. Server memerlukan waktu terlalu lama untuk merespons.';
                    shouldRetry = true;
                } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                    errorMessage = 'Gagal terhubung ke server. Periksa koneksi internet Anda.';
                    shouldRetry = true;
                } else if (error.message.includes('419')) {
                    errorMessage = 'Sesi Anda telah berakhir. Silakan refresh halaman.';
                } else if (error.message.includes('429')) {
                    errorMessage = 'Terlalu banyak permintaan. Mohon tunggu sebentar.';
                    setCountdown(60);
                }
                
                // Add error message to chat
                updateMessage(botMessageId, `❌ ${errorMessage}`, false);
                setStreamError(errorMessage);
                
                // Retry logic with exponential backoff
                if (shouldRetry && retryCount < maxRetries) {
                    const delay = Math.pow(2, retryCount) * 1000; // 1s, 2s, 4s
                    setTimeout(() => {
                        setRetryCount(prev => prev + 1);
                        updateMessage(botMessageId, `🔄 Mencoba kembali (${retryCount + 1}/${maxRetries})...`, false);
                        sendMessageToAPIStream(userMessage)
                            .then(resolve)
                            .catch(reject);
                    }, delay);
                } else {
                    setRetryCount(0); // Reset retry count
                    reject(error);
                }
            }
        });
    };

    // Generate smart follow-up questions based on last bot response
    const generateFollowUpQuestions = (botResponse) => {
        const response = botResponse.toLowerCase();
        
        // Define contextual follow-ups based on keywords
        const followUpMap = {
            ppdb: [
                "Syarat pendaftaran?",
                "Jadwal PPDB?",
                "Cara daftar online?",
                "Jalur zonasi?",
            ],
            program: [
                "Peminatan IPA?",
                "Peminatan IPS?",
                "Peminatan Bahasa?",
                "Mata pelajaran?",
            ],
            ekstra: [
                "Jadwal ekskul?",
                "Cara daftar ekskul?",
                "Prestasi ekskul?",
                "Biaya ekskul?",
            ],
            lokasi: [
                "Jam operasional?",
                "Akses transportasi?",
                "Kontak sekolah?",
                "Fasilitas sekolah?",
            ],
            fasilitas: [
                "Lab komputer?",
                "Perpustakaan?",
                "Lapangan olahraga?",
                "Ruang kelas?",
            ],
            prestasi: [
                "Prestasi akademik?",
                "Prestasi non-akademik?",
                "Alumni berprestasi?",
                "Lomba yang diikuti?",
            ],
            guru: [
                "Jumlah guru?",
                "Kualifikasi guru?",
                "Guru bimbingan?",
                "Staff sekolah?",
            ]
        };
        
        // Check which context matches
        for (const [key, questions] of Object.entries(followUpMap)) {
            if (response.includes(key) || 
                response.includes(key.replace('ekstra', 'ekstrakurikuler')) ||
                response.includes('peminatan') && key === 'program' ||
                response.includes('jurusan') && key === 'program') {
                return questions;
            }
        }
        
        // Default follow-ups if no specific context
        return [
            "Info PPDB?",
            "Program studi?",
            "Ekstrakurikuler?",
            "Lokasi sekolah?",
        ];
    };

    const openWhatsApp = () => {
        const waNumber = whatsappNumber; // Ambil dari site settings
        const waMessage = encodeURIComponent("Halo, saya butuh bantuan informasi sekolah.");
        window.open(`https://wa.me/${waNumber}?text=${waMessage}`, '_blank');
    };

    // Handle Text Input with streaming
    const handleSend = async (e) => {
        e.preventDefault();
        if (!inputValue.trim() || isTyping) return;

        const userMessage = inputValue.trim().toLowerCase();
        
        // Check if user wants to chat via WhatsApp
        const waKeywords = ['ke wa', 'chat wa', 'whatsapp', 'kontak via wa', 'hubungi via wa', 'admin wa'];
        if (waKeywords.some(keyword => userMessage.includes(keyword))) {
            addMessage(inputValue.trim(), 'user');
            
            // Show typing briefly then open WA
            setIsTyping(true);
            setTimeout(() => {
                setIsTyping(false);
                
                // Add bot message about WhatsApp
                addMessage(
                    `Baik, saya akan arahkankan Anda langsung ke admin sekolah via WhatsApp.

Silakan klik tombol di bawah ini untuk langsung chat:`,
                    'bot', false, 'wa-' + Date.now()
                );
                
                setSuggestionChips(["Info PPDB?", "Biaya sekolah?"]);
                setShowSuggestions(false);
            }, 500);
            setInputValue("");
            return;
        }
        
        addMessage(inputValue.trim(), 'user');
        setInputValue("");
        setShowSuggestions(false); // Hide suggestions during response
        
        setIsTyping(true); // Show typing indicator
        setIsSearching(true); // Show "searching" status

        try {
            const result = await sendMessageToAPIStream(inputValue.trim());
            
            // Generate smart follow-up questions based on response
            if (result && result.text) {
                const followUps = generateFollowUpQuestions(result.text);
                setSuggestionChips(followUps);
                setShowSuggestions(true); // Show new suggestions
            }
            
            // Typing indicator is already turned off inside sendMessageToAPIStream
        } catch (error) {
            console.error('Send message error:', error);
            setIsTyping(false);
            addMessage('Maaf, saya sedang mengalami masalah koneksi. Silakan coba beberapa saat lagi.', 'bot', false);
        }
    };
    
    // Handle suggestion chip click
    const handleChipClick = (suggestion) => {
        setInputValue(suggestion);
        setShowSuggestions(false);
        
        // Automatically send the message
        addMessage(suggestion, 'user');
        setIsTyping(true);
        
        sendMessageToAPIStream(suggestion)
            .then(result => {
                // Generate smart follow-up questions
                if (result && result.text) {
                    const followUps = generateFollowUpQuestions(result.text);
                    setSuggestionChips(followUps);
                    setShowSuggestions(true);
                }
            })
            .catch(error => {
                console.error('Send message error:', error);
                setIsTyping(false);
                addMessage('Maaf, saya sedang mengalami masalah koneksi. Silakan coba beberapa saat lagi.', 'bot', false);
            });
    };

    const addMessage = (text, sender, isRagEnhanced = false, id = null) => {
        setMessages(prev => [...prev, {
            id: id ? String(id) : 'msg_' + Date.now(),
            sender,
            text,
            isRagEnhanced,
            timestamp: new Date().toISOString(),
        }]);
    };

    const updateMessage = (id, text, isRagEnhanced = false) => {
        setMessages(prev => prev.map(msg => 
            msg.id === id ? { ...msg, text, isRagEnhanced } : msg
        ));
    };

    const clearConversation = () => {
        // Clear messages except initial greeting
        setMessages([
            {
                id: 1,
                sender: 'bot',
                text: `Halo! 👋 Aku **AI SMANSA**, teman virtualmu buat ngobrolin SMAN 1 Baleendah! ✨`,
                isRagEnhanced: false,
            },
            {
                id: 2,
                sender: 'bot',
                text: `Mau tanya apa nih tentang SMANSA? PPDB, jurusan, ekskul, atau yang lain? Yuk, tanya aja~ 😊`,
                isRagEnhanced: false,
            }
        ]);
        
        // Show suggestions again
        setShowSuggestions(true);
        
        // Reset history loaded state so it will load fresh for new session
        setHistoryLoaded(true); // Set to true so we don't load old history for new session
        
        // Generate new session ID for fresh start
        const timestamp = Date.now();
        const random = Math.random().toString(36).substr(2, 12);
        const userAgentHash = navigator.userAgent.split('').reduce((a, b) => {
            a = ((a << 5) - a) + b.charCodeAt(0);
            return a & a;
        }, 0);
        
        const newSessionId = `session_${timestamp}_${random}_${Math.abs(userAgentHash)}`;
        localStorage.setItem('chat_session_id', newSessionId);
        setSessionId(newSessionId);
    };

    return (
        <div className="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-4 font-sans">
            {/* Chat Dialog Card */}
            {isOpen && (
                <div className="w-[420px] h-[600px] bg-white rounded-2xl shadow-2xl border border-gray-200 flex flex-col overflow-hidden animate-fade-in-up origin-bottom-right">
                    {/* Header */}
                    <div className="bg-primary p-4 flex items-center justify-between shadow-md">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center border border-white/20">
                                <Bot className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 className="text-white font-bold text-sm">AI Assistant</h3>
                                <span className="text-blue-100 text-xs">AI SMANSA</span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <button 
                                onClick={clearConversation}
                                className="text-white/80 hover:text-white hover:bg-white/10 p-1 rounded-full transition-colors"
                                title="Mulai percakapan baru"
                            >
                                <Trash2 size={18} />
                            </button>
                            <button 
                                onClick={() => setIsOpen(false)}
                                className="text-white/80 hover:text-white hover:bg-white/10 p-1 rounded-full transition-colors"
                            >
                                <X size={20} />
                            </button>
                        </div>
                    </div>

                    {/* Chat Body */}
                    <div className="flex-1 bg-gray-50 p-4 overflow-y-auto">
                        <div className="space-y-4">
                            {/* Loading History Indicator */}
                            {isLoadingHistory && (
                                <div className="flex justify-center items-center py-4">
                                    <div className="flex items-center gap-2 text-gray-500 text-sm">
                                        <div className="w-4 h-4 border-2 border-primary/30 border-t-primary rounded-full animate-spin"></div>
                                        <span>Memuat percakapan sebelumnya...</span>
                                    </div>
                                </div>
                            )}
                            
                            {messages.map((msg) => (
                                <div key={msg.id} className={`flex ${msg.sender === 'user' ? 'justify-end' : 'justify-start'}`}>
                                    {/* Avatar for Bot */}
                                    {msg.sender === 'bot' && (
                                        <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
                                            <Bot size={16} className="text-primary" />
                                        </div>
                                    )}

                                    <div className="max-w-[80%]">
                                        <div className={`p-3 rounded-2xl text-sm leading-relaxed shadow-sm ${
                                            msg.sender === 'user' 
                                            ? 'bg-primary text-white rounded-tr-none' 
                                            : 'bg-white text-gray-700 rounded-tl-none border border-gray-100'
                                        }`}>
                                            {msg.sender === 'bot' ? (
                                                msg.text === '' ? (
                                                    // Show typing dots when message is empty (streaming not started yet)
                                                    <div className="flex items-center gap-1.5 py-2 px-1">
                                                        <div className="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                                        <div className="w-1.5 h-1.5 bg-primary/60 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                                                        <div className="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></div>
                                                        <span className="text-[10px] text-gray-400 ml-1 font-medium italic">AI sedang mengetik...</span>
                                                    </div>
                                                ) : (
                                                    <ReactMarkdown 
                                                        remarkPlugins={[remarkGfm]}
                                                        rehypePlugins={[rehypeRaw]}
                                                        components={{
                                                            h1: ({node, ...props}) => <h1 className="text-lg font-bold mb-2 text-gray-900" {...props} />,
                                                            h2: ({node, ...props}) => <h2 className="text-base font-bold mb-2 text-gray-900" {...props} />,
                                                            h3: ({node, ...props}) => <h3 className="text-sm font-semibold mb-1 text-gray-800" {...props} />,
                                                            p: ({node, ...props}) => <p className="mb-2 last:mb-0" {...props} />,
                                                            ul: ({node, ...props}) => <ul className="list-disc list-inside mb-2 space-y-1" {...props} />,
                                                            ol: ({node, ...props}) => <ol className="list-decimal list-inside mb-2 space-y-1" {...props} />,
                                                            li: ({node, ...props}) => <li className="ml-2" {...props} />,
                                                            a: ({node, ...props}) => <a className="text-primary underline hover:text-primary-darker" target="_blank" rel="noopener noreferrer" {...props} />,
                                                            code: ({node, inline, ...props}) => 
                                                                inline ? (
                                                                    <code className="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono text-gray-800" {...props} />
                                                                ) : (
                                                                    <code className="block bg-gray-100 p-2 rounded text-xs font-mono overflow-x-auto mb-2" {...props} />
                                                                ),
                                                            strong: ({node, ...props}) => <strong className="font-bold text-gray-900" {...props} />,
                                                            em: ({node, ...props}) => <em className="italic" {...props} />,
                                                            blockquote: ({node, ...props}) => <blockquote className="border-l-4 border-gray-300 pl-3 italic text-gray-600 mb-2" {...props} />,
                                                        }}
                                                    >
                                                        {msg.text}
                                                    </ReactMarkdown>
                                                )
                                            ) : (
                                                msg.text
                                            )}
                                            
                                            {/* WhatsApp Button for WA redirect messages */}
                                            {msg.sender === 'bot' && msg.id && String(msg.id).startsWith('wa-') && (
                                                <button
                                                    onClick={openWhatsApp}
                                                    className="mt-3 w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-xl flex items-center justify-center gap-2 transition-colors shadow-sm"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.099-.307-.058-.468.075-.643.127-.161.297-.297.466-.466.594-.173.127-.347.223-.644.15-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.099-.307-.058-.468.075-.643.127-.161.297-.297.466-.466.594-.173.127-.347.223-.644.15-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.099-.307-.058-.468.075-.643.127-.161.297-.297.466-.466.594-.173.127-.347.223-.644.15-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.099-.307-.058-.468.075-.643.127-.161.297-.297.466-.466.594-.173.127-.347.223-.644.15-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.099-.307-.058-.468.075-.643.127-.161.297-.297.466-.466.594-.173.127-.347.223-.644.15-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059zm-5.472 8.617c-5.947 0-10.774-4.827-10.774-10.774 0-5.947 4.827-10.774 10.774-10.774 5.947 0 10.774 4.827 10.774 10.774 0 5.947-4.827 10.774-10.774 10.774zm-18.472 0c0-10.188 8.284-18.472 18.472-18.472 10.188 0 18.472 8.284 18.472 18.472 0 10.188-8.284 18.472-18.472 18.472-10.188 0-18.472-8.284-18.472-18.472z"/>
                                                    </svg>
                                                    Chat WhatsApp
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {/* Typing Indicator */}
                            {isTyping && (
                                <div className="flex justify-start">
                                    <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-2">
                                        <Bot size={16} className="text-primary" />
                                    </div>
                                    <div className="bg-white border border-gray-100 p-3 rounded-2xl rounded-tl-none shadow-sm flex items-center gap-1.5">
                                        <div className="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                        <div className="w-1.5 h-1.5 bg-primary/60 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                                        <div className="w-1.5 h-1.5 bg-primary rounded-full animate-bounce"></div>
                                        <span className="text-[10px] text-gray-400 ml-1 font-medium italic">
                                            {isSearching ? 'AI sedang mencari dokumen...' : 'AI sedang menyiapkan jawaban...'}
                                        </span>
                                    </div>
                                </div>
                            )}
                            <div ref={messagesEndRef} />
                        </div>
                    </div>

                    {/* Footer Input */}
                    <div className="bg-white border-t border-gray-100">
                        {/* Suggestion Chips */}
                        {showSuggestions && (
                            <div className="p-3 pb-0">
                                <div className="flex flex-wrap gap-2">
                                    {suggestionChips.map((chip, index) => (
                                        <button
                                            key={index}
                                            onClick={() => handleChipClick(chip)}
                                            disabled={isTyping}
                                            className="px-3 py-1.5 text-xs font-medium text-primary bg-primary/5 hover:bg-primary/10 border border-primary/20 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            {chip}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                        
                        <div className="p-3">
                            <form onSubmit={handleSend} className="flex gap-2">
                                <input
                                    type="text"
                                    value={inputValue}
                                    onChange={(e) => setInputValue(e.target.value)}
                                    placeholder={countdown > 0 ? `Tunggu ${countdown} detik...` : "Tanya apa saja..."}
                                    disabled={isTyping || countdown > 0}
                                    className="flex-1 bg-gray-100 border-0 rounded-full px-4 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:ring-2 focus:ring-primary focus:bg-white transition-all disabled:opacity-50"
                                />
                                <button 
                                    type="submit"
                                    disabled={!inputValue.trim() || isTyping || countdown > 0}
                                    className="bg-primary text-white p-2 rounded-full hover:bg-primary-darker disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-md"
                                >
                                    <Send size={18} className={inputValue.trim() ? "ml-0.5" : ""} />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Floating Action Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`${
                    isOpen ? 'bg-red-500 rotate-90' : 'bg-primary hover:bg-primary-darker'
                } text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 z-[9999]`}
            >
                {isOpen ? <X size={24} /> : <MessageCircle size={28} />}
                
                {/* Notification Badge */}
                {!isOpen && (
                    <span className="absolute top-0 right-0 flex h-3 w-3">
                        <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span className="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                    </span>
                )}
            </button>
        </div>
    );
}
