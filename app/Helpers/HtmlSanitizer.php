<?php

namespace App\Helpers;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    protected static ?HTMLPurifier $purifier = null;

    protected const SECTION_HTML_PATHS = [
        'about_lp' => [
            'description_html',
        ],
        'kepsek_welcome_lp' => [
            'welcome_text_html',
        ],
        'pengaturan_umum' => [
            'description_html',
            'announcement_text',
            'contact_info',
        ],
        'persyaratan' => [
            'additional_notes',
            'items.*.description',
        ],
        'prosedur' => [
            'items.*.description',
            'contact_info',
        ],
        'faq' => [
            'items.*.answer',
        ],
        'history' => [
            'description_html',
            'timeline.*.description',
        ],
    ];

    public static function sanitize(string $value): string
    {
        return self::getPurifier()->purify($value);
    }

    public static function sanitizeSection(string $sectionKey, array $content): array
    {
        $paths = self::SECTION_HTML_PATHS[$sectionKey] ?? [];
        if (empty($paths)) {
            return $content;
        }

        foreach ($paths as $path) {
            self::sanitizePath($content, explode('.', $path));
        }

        return $content;
    }

    protected static function sanitizePath(array &$data, array $segments): void
    {
        if (empty($segments)) {
            return;
        }

        $segment = array_shift($segments);

        if ($segment === '*') {
            foreach ($data as &$value) {
                if (is_array($value)) {
                    self::sanitizePath($value, $segments);
                } elseif (empty($segments) && is_string($value)) {
                    $value = self::sanitize($value);
                }
            }
            unset($value);

            return;
        }

        if (!array_key_exists($segment, $data)) {
            return;
        }

        if (empty($segments)) {
            if (is_string($data[$segment])) {
                $data[$segment] = self::sanitize($data[$segment]);
            } elseif (is_array($data[$segment])) {
                foreach ($data[$segment] as &$value) {
                    if (is_string($value)) {
                        $value = self::sanitize($value);
                    }
                }
                unset($value);
            }

            return;
        }

        if (!is_array($data[$segment])) {
            return;
        }

        self::sanitizePath($data[$segment], $segments);
    }

    protected static function getPurifier(): HTMLPurifier
    {
        if (!self::$purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(?:www\.youtube\.com/embed/|player\.vimeo\.com/video/)%');
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.DefinitionID', 'html_sanitizer_unique');
            $config->set('HTML.DefinitionRev', 1);
            $def = $config->maybeGetRawHTMLDefinition();
            if ($def) {
                $def->addAttribute('a', 'rel', 'CDATA');
                $def->addAttribute('img', 'loading', 'CDATA');
            }
            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}
