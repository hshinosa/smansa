<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI services including Groq API, embedding service,
    | and RAG (Retrieval-Augmented Generation) settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Groq API Configuration
    |--------------------------------------------------------------------------
    */
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 2048),
        'temperature' => (float) env('GROQ_TEMPERATURE', 0.7),
        'timeout' => (int) env('GROQ_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Service Configuration
    |--------------------------------------------------------------------------
    */
    'embedding' => [
        'provider' => env('EMBEDDING_PROVIDER', 'tei'),
        'base_url' => env('EMBEDDING_BASE_URL', 'http://embedding:8080'),
        'model' => env('EMBEDDING_MODEL', 'intfloat/multilingual-e5-small'),
        'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 384),
        'timeout' => (int) env('EMBEDDING_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG Configuration
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'chunk_size' => (int) env('RAG_CHUNK_SIZE', 500),
        'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 100),
        'top_k' => (int) env('RAG_TOP_K', 5),
        'similarity_threshold' => (float) env('RAG_SIMILARITY_THRESHOLD', 0.5),
        'max_context_length' => (int) env('RAG_MAX_CONTEXT_LENGTH', 4000),
    ],

    /*
    |--------------------------------------------------------------------------
    | System Prompts
    |--------------------------------------------------------------------------
    |
    | Default system prompts for the AI chatbot. These can be overridden
    | via database settings in the admin panel.
    |
    */
    'prompts' => [
        'system' => env('AI_SYSTEM_PROMPT', 'Kamu adalah asisten AI untuk SMA Negeri 1 Baleendah. Tugasmu adalah membantu menjawab pertanyaan seputar sekolah dengan ramah dan informatif. Berikan jawaban yang akurat berdasarkan informasi yang tersedia. Jika tidak tahu, katakan jujur bahwa kamu tidak memiliki informasi tersebut.'),

        'rag_context' => 'Berikut adalah informasi relevan dari database sekolah yang dapat membantu menjawab pertanyaan:',

        'no_context' => 'Maaf, saya tidak memiliki informasi yang cukup untuk menjawab pertanyaan tersebut. Silakan hubungi pihak sekolah untuk informasi lebih lanjut.',

        'greeting' => 'Halo! Saya asisten AI SMAN 1 Baleendah. Ada yang bisa saya bantu?',

        'fallback' => 'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi dalam beberapa saat.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Configuration
    |--------------------------------------------------------------------------
    */
    'chat' => [
        'max_history' => (int) env('CHAT_MAX_HISTORY', 10),
        'session_ttl' => (int) env('CHAT_SESSION_TTL', 3600),
        'rate_limit_per_minute' => (int) env('CHAT_RATE_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Generation Prompts
    |--------------------------------------------------------------------------
    */
    'content_generation' => [
        'instagram_caption' => 'Buatkan caption menarik untuk postingan Instagram sekolah dengan informasi berikut:',

        'news_summary' => 'Ringkas konten berikut menjadi paragraf singkat untuk preview berita:',

        'title_suggestion' => 'Buatkan judul menarik untuk konten berikut:',
    ],
];
