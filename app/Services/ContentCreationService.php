<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Log;

/**
 * Service for AI-powered content creation (news articles, social media posts, etc.)
 * This service uses Groq with the content model for better content quality
 */
class ContentCreationService
{
    protected GroqService $groq;

    public function __construct(GroqService $groq)
    {
        $this->groq = $groq;
    }

    /**
     * Generate news article from Instagram post or other source
     * Uses Groq content model for better quality
     * 
     * @param string $caption Instagram caption or source content
     * @param array $metadata Additional metadata (hashtags, images, date, etc.)
     * @param array $options Generation options
     * @return array Result with success status and generated content
     */
    public function generateNewsArticle(string $caption, array $metadata = [], array $options = []): array
    {
        // Build comprehensive school news system prompt
        $systemPrompt = "Anda adalah jurnalis dan penulis berita profesional untuk website resmi SMA Negeri 1 Baleendah, Kabupaten Bandung, Jawa Barat. "
            . "Tugas Anda adalah menulis artikel berita sekolah yang:\n\n"
            . "**GAYA PENULISAN:**\n"
            . "- Menggunakan Bahasa Indonesia baku, formal, dan profesional\n"
            . "- Objektif, informatif, dan faktual\n"
            . "- Tidak berlebihan atau bombastis, tetapi tetap menarik\n"
            . "- Menghindari kata-kata klise seperti 'luar biasa', 'menggemparkan', 'memukau'\n"
            . "- Menggunakan kalimat aktif dan efektif\n\n"
            . "**STRUKTUR ARTIKEL:**\n"
            . "- Judul: Singkat, jelas, to-the-point (maks 100 karakter). Judul HARUS merepresentasikan inti berita DAN konteks visual jika tersedia.\n"
            . "- Lead paragraph: 5W+1H (What, When, Where, Who, Why, How)\n"
            . "- Isi: Elaborasi detail dengan kutipan jika ada\n"
            . "- Penutup: Kesimpulan atau harapan/call-to-action\n\n"
            . "**PEMANFAATAN KONTEKS VISUAL (SANGAT PENTING):**\n"
            . "- Jika ada keterangan 'KONTEKS VISUAL', gunakan informasi tersebut untuk memperkaya narasi.\n"
            . "- Jika gambar menampilkan 'penyerahan piala', pastikan judul dan isi menyebutkan momen seremoni tersebut.\n"
            . "- Jika gambar menampilkan 'upacara', 'rapat', atau 'lomba', sesuaikan diksi berita dengan suasana tersebut.\n\n"
            . "**SEO OPTIMIZATION:**\n"
            . "- Judul mengandung keyword utama di awal\n"
            . "- Gunakan variasi kata kunci secara natural\n"
            . "- Struktur heading yang jelas (H2, H3)\n"
            . "- Meta description ringkas dan menarik\n\n"
            . "**KONTEKS SEKOLAH:**\n"
            . "- Nama lengkap: SMA Negeri 1 Baleendah (bisa disingkat SMAN 1 Baleendah/Smansa Baleendah)\n"
            . "- Lokasi: Kabupaten Bandung, Jawa Barat\n"
            . "- Gunakan istilah yang tepat: siswa/siswi (bukan murid), guru (bukan pengajar), "
            . "kepala sekolah, wakil kepala sekolah, ekstrakurikuler, OSIS, dll.\n\n"
            . "PENTING: Judul harus langsung menggambarkan inti berita, bukan sapaan atau pembuka.";

        $userPrompt = "Buatkan artikel berita sekolah dari konten Instagram berikut:\n\n";
        $userPrompt .= "**CAPTION:**\n{$caption}\n\n";

        // Add image context if available
        if (!empty($metadata['image_count'])) {
            $userPrompt .= "**KONTEKS VISUAL:**\n";
            $userPrompt .= "- Post ini memiliki {$metadata['image_count']} gambar\n";
            if (!empty($metadata['image_description'])) {
                $userPrompt .= "- Deskripsi gambar: {$metadata['image_description']}\n";
            }
            $userPrompt .= "- Sesuaikan narasi dengan konteks visual (kegiatan, suasana, peserta)\n\n";
        }

        if (!empty($metadata['hashtags'])) {
            $userPrompt .= "**HASHTAGS:** " . implode(', ', $metadata['hashtags']) . "\n\n";
        }

        if (!empty($metadata['date'])) {
            $userPrompt .= "**TANGGAL POSTING:** {$metadata['date']}\n\n";
        }

        if (!empty($metadata['engagement'])) {
            $userPrompt .= "**ENGAGEMENT:** {$metadata['engagement']} likes\n\n";
        }

        $userPrompt .= "Format output dalam JSON dengan struktur:\n";
        $userPrompt .= "{\n";
        $userPrompt .= '  "title": "Judul berita SEO-friendly (keyword di awal, maks 100 karakter)",' . "\n";
        $userPrompt .= '  "excerpt": "Ringkasan 2-3 kalimat untuk preview (maks 200 karakter)",' . "\n";
        $userPrompt .= '  "content": "Isi artikel lengkap dalam format HTML dengan <p>, <h3>, <ul><li> jika perlu",' . "\n";
        $userPrompt .= '  "meta_description": "Meta description untuk SEO (maks 160 karakter)",' . "\n";
        $userPrompt .= '  "keywords": ["keyword1", "keyword2", "keyword3", "keyword4", "keyword5"],' . "\n";
        $userPrompt .= '  "category": "Kategori (Berita/Prestasi/Kegiatan/Pengumuman/Akademik)"' . "\n";
        $userPrompt .= "}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt], 
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // Use Groq content model for article generation
        $completionOptions = array_merge($options, [
            'context' => 'content',
            'max_tokens' => 2000,
            'temperature' => 0.6,
        ]);

        Log::info('[ContentCreationService] Generating news article', [
            'source' => 'instagram',
            'caption_length' => strlen($caption),
            'has_images' => !empty($metadata['image_count']),
        ]);

        $result = $this->groq->contentCompletion($messages, $completionOptions);

        if (!$result['success']) {
            Log::error('[ContentCreationService] Failed to generate article');
            return [
                'success' => false,
                'error' => 'Gagal membuat artikel. Silakan coba lagi.',
            ];
        }

        // Try to parse JSON response
        $content = $result['message'];
        
        // 1. Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        // 2. Clean up common JSON issues
        $content = trim($content);
        
        // 3. Attempt decoding
        $articleData = json_decode($content, true);

        // 4. If failed, try more aggressive fixes
        if (!$articleData) {
            // Fix: Replace physical newlines inside JSON string values with \n
            // This is common in LLM outputs
            $fixedContent = preg_replace_callback('/"(.*?)":\s*"(.*?)"/s', function($m) {
                return '"' . $m[1] . '": "' . str_replace(["\n", "\r"], ["\\n", ""], $m[2]) . '"';
            }, $content);
            
            $articleData = json_decode($fixedContent, true);
        }

        // 5. If still failed, try to find JSON object structure manually
        if (!$articleData) {
            // Find first { and last }
            $start = strpos($content, '{');
            $end = strrpos($content, '}');
            
            if ($start !== false && $end !== false) {
                $jsonCandidate = substr($content, $start, $end - $start + 1);
                // Apply the same newline fix
                $jsonCandidate = preg_replace_callback('/"(.*?)":\s*"(.*?)"/s', function($m) {
                    return '"' . $m[1] . '": "' . str_replace(["\n", "\r"], ["\\n", ""], $m[2]) . '"';
                }, $jsonCandidate);
                
                $articleData = json_decode($jsonCandidate, true);
            }
        }

        if (!$articleData) {
            // Fallback: return raw content but with better title
            Log::warning('[ContentCreationService] Could not parse JSON, returning raw content', [
                'content_preview' => substr($content, 0, 100)
            ]);
            
            // Generate title from original caption if possible, or from content
            $sourceText = !empty($caption) ? $caption : strip_tags($content);
            $lines = explode("\n", $sourceText);
            
            $fallbackTitle = 'Berita Sekolah Terbaru';
            
            // Phrases to skip as title
            $skipPhrases = [
                'seluruh civitas', 'kami mengucapkan', 'assalamualaikum', 'selamat pagi', 
                'selamat siang', 'selamat malam', 'sampurasun', 'halo wargi', 'informasi sekolah'
            ];

            foreach ($lines as $line) {
                // Remove emojis and extra formatting markers if needed
                $cleanLine = trim($line);
                // Remove all hashtags at start of line
                $cleanLine = preg_replace('/^#\w+\s*/', '', $cleanLine);
                // Clean punctuation at start
                $cleanLine = ltrim($cleanLine, ".- \t\n\r\0\x0B");
                
                // Check if line contains skip phrases
                $lowerLine = strtolower($cleanLine);
                $shouldSkip = false;
                foreach ($skipPhrases as $phrase) {
                    if (str_contains($lowerLine, $phrase)) {
                        $shouldSkip = true;
                        break;
                    }
                }

                if ($shouldSkip) continue;
                
                // If line is not empty and NOT a hashtag-only line (in case Regex didn't catch all)
                // and has reasonable length
                if (!empty($cleanLine) && !str_starts_with($cleanLine, '#') && strlen($cleanLine) > 10) {
                    $fallbackTitle = \Illuminate\Support\Str::limit($cleanLine, 100);
                    break;
                }
            }

            return [
                'success' => true,
                'provider' => $result['provider'],
                'article' => [
                    'title' => $fallbackTitle, // Use the better fallback title
                    'excerpt' => substr(strip_tags($content), 0, 200),
                    'content' => $this->formatCaptionAsHtml($caption), 
                    'meta_description' => '',
                    'keywords' => ['Berita', 'Sekolah', 'Update'],
                    'category' => 'Berita Sekolah',
                ],
                'raw_content' => $content,
            ];
        }

        // Keep the AI-generated content (don't overwrite with formatted caption)
        // Only format the content if it's missing proper HTML structure
        if (!empty($articleData['content'])) {
            // Ensure proper HTML formatting and capitalization
            $articleData['content'] = $this->ensureProperHtmlFormat($articleData['content']);
        } else {
            // Fallback: use formatted caption if AI didn't generate content
            $articleData['content'] = $this->formatCaptionAsHtml($caption);
        }
        
        // Validation for title from AI
        if (str_starts_with($articleData['title'], '#') || strlen($articleData['title']) < 5) {
             $sourceText = !empty($caption) ? $caption : '';
             $lines = explode("\n", $sourceText);
             foreach ($lines as $line) {
                $cleanLine = trim($line);
                $cleanLine = preg_replace('/^#\w+\s*/', '', $cleanLine);
                $cleanLine = ltrim($cleanLine, ".- \t\n\r\0\x0B");
                if (!empty($cleanLine) && !str_starts_with($cleanLine, '#') && strlen($cleanLine) > 5) {
                    $articleData['title'] = \Illuminate\Support\Str::limit($cleanLine, 100);
                    break;
                }
            }
        }

        if (!isset($articleData['meta_description'])) {
             $articleData['meta_description'] = $articleData['excerpt'] ?? '';
        }

        return [
            'success' => true,
            'provider' => $result['provider'],
            'article' => $articleData,
        ];
    }

    /**
     * Format Caption as HTML Content
     * 
     * @param string $caption
     * @return string
     */
    protected function formatCaptionAsHtml(string $caption): string 
    {
        // 1. Escape HTML entities to prevent XSS (if caption has raw html) - but we want to allow newlines
        $text = htmlspecialchars($caption, ENT_QUOTES, 'UTF-8');

        // 2. Capitalize first letter of sentences
        $text = $this->capitalizeSentences($text);

        // 3. Convert newlines to paragraphs or line breaks
        // Split by double newlines for paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $html = '';
        
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            
            // Convert single newlines inside paragraph to <br>
            $p = nl2br($p);
            
            // Linkify URLs
            $p = preg_replace(
                '/(https?:\/\/[^\s]+)/', 
                '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">$1</a>', 
                $p
            );
            
            // Linkify Mentions (@username)
            $p = preg_replace(
                '/@(\w+)/', 
                '<a href="https://instagram.com/$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">@$1</a>', 
                $p
            );

             // Linkify Hashtags (#tag)
             // $p = preg_replace(
             //    '/#(\w+)/', 
             //    '<span class="text-blue-500">#$1</span>', 
             //    $p
             // );

            $html .= "<p class=\"mb-4 text-gray-700 leading-relaxed\">{$p}</p>";
        }

        return $html;
    }

    /**
     * Capitalize first letter of each sentence
     * Also handles common Indonesian text patterns
     * 
     * @param string $text
     * @return string
     */
    protected function capitalizeSentences(string $text): string
    {
        // First, capitalize the very first character of the text
        $text = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');
        
        // Capitalize after sentence-ending punctuation followed by space/newline
        $text = preg_replace_callback(
            '/([.!?])\s+([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/u',
            function ($matches) {
                return $matches[1] . ' ' . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );
        
        // Capitalize after newlines
        $text = preg_replace_callback(
            '/(\n\s*)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/u',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );
        
        // Capitalize after colon followed by newline or double space (common in announcements)
        $text = preg_replace_callback(
            '/(:\s*\n\s*)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/u',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );

        // Capitalize after numbered list items (1. 2. 3. etc.)
        $text = preg_replace_callback(
            '/(\d+\.\s*)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/u',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );
        
        // Capitalize after bullet points (- or *)
        $text = preg_replace_callback(
            '/([\-\*]\s+)([a-záàâäãåæçéèêëíìîïñóòôöõøúùûüýÿ])/u',
            function ($matches) {
                return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
            },
            $text
        );

        return $text;
    }

    /**
     * Ensure AI-generated content has proper HTML formatting
     * Fixes common issues with AI HTML output
     * 
     * @param string $content
     * @return string
     */
    protected function ensureProperHtmlFormat(string $content): string
    {
        // If content already has HTML tags, just clean it up
        if (preg_match('/<[^>]+>/', $content)) {
            // Ensure paragraph tags have proper classes
            $content = preg_replace(
                '/<p>/',
                '<p class="mb-4 text-gray-700 leading-relaxed">',
                $content
            );
            
            // Ensure h3 tags have proper classes
            $content = preg_replace(
                '/<h3>/',
                '<h3 class="text-lg font-semibold text-gray-800 mt-6 mb-3">',
                $content
            );
            
            // Ensure ul tags have proper classes
            $content = preg_replace(
                '/<ul>/',
                '<ul class="list-disc pl-6 mb-4 space-y-2">',
                $content
            );
            
            // Ensure li tags have proper classes
            $content = preg_replace(
                '/<li>/',
                '<li class="text-gray-700">',
                $content
            );
            
            return $content;
        }
        
        // Content is plain text - convert to HTML paragraphs
        return $this->convertTextToHtml($content);
    }

    /**
     * Convert plain text to proper HTML paragraphs
     * 
     * @param string $text
     * @return string
     */
    protected function convertTextToHtml(string $text): string
    {
        // Apply capitalization
        $text = $this->capitalizeSentences($text);
        
        // Split by double newlines for paragraphs
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $html = '';
        
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (empty($p)) continue;
            
            // Convert single newlines to <br>
            $p = nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8'));
            
            $html .= "<p class=\"mb-4 text-gray-700 leading-relaxed\">{$p}</p>";
        }
        
        return $html;
    }

    /**
     * Improve or rewrite existing content
     */
    public function improveContent(string $content, string $type = 'general', array $options = []): array
    {
        $systemPrompt = "Anda adalah editor konten profesional. Tugas Anda memperbaiki dan meningkatkan kualitas konten.";
        
        $improvements = [
            'grammar' => 'Perbaiki tata bahasa dan ejaan',
            'seo' => 'Optimalkan untuk SEO dengan keyword natural',
            'readability' => 'Tingkatkan keterbacaan dengan struktur yang lebih baik',
            'formal' => 'Buat lebih formal dan profesional',
            'engaging' => 'Buat lebih menarik dan engaging',
        ];

        $requestedImprovements = $options['improvements'] ?? ['grammar', 'readability'];
        $improvementList = array_intersect_key($improvements, array_flip($requestedImprovements));

        $userPrompt = "Perbaiki konten berikut dengan fokus pada:\n";
        foreach ($improvementList as $improvement) {
            $userPrompt .= "- {$improvement}\n";
        }
        $userPrompt .= "\nKonten:\n{$content}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $completionOptions = array_merge($options, [
            'context' => 'content',
            'max_tokens' => 2500,
        ]);

        return $this->groq->contentCompletion($messages, $completionOptions);
    }

    /**
     * Generate social media caption from article
     */
    public function generateSocialMediaCaption(string $articleTitle, string $articleExcerpt, string $platform = 'instagram'): array
    {
        $platformSpecs = [
            'instagram' => ['max_length' => 2200, 'style' => 'casual, emoji-friendly'],
            'facebook' => ['max_length' => 5000, 'style' => 'informative, semi-formal'],
            'twitter' => ['max_length' => 280, 'style' => 'concise, hashtag-heavy'],
        ];

        $spec = $platformSpecs[$platform] ?? $platformSpecs['instagram'];

        $systemPrompt = "Anda adalah social media specialist untuk SMAN 1 Baleendah.";
        $userPrompt = "Buatkan caption {$platform} menarik dari artikel berikut:\n\n"
            . "Judul: {$articleTitle}\n"
            . "Ringkasan: {$articleExcerpt}\n\n"
            . "Style: {$spec['style']}\n"
            . "Max length: {$spec['max_length']} karakter\n"
            . "Sertakan hashtags relevan dan call-to-action.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        return $this->groq->contentCompletion($messages, [
            'max_tokens' => 500,
            'temperature' => 0.8,
        ]);
    }
}
