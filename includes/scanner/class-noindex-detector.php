<?php
/**
 * Noindex Strategic Pages Detector
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Noindex_Detector {

    /**
     * Detect noindex on pages
     *
     * @param string $html Page HTML
     * @param string $current_url Current page URL
     * @return array Issues detected
     */
    public static function detect(string $html, string $current_url): array {
        $issues = [];

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);
        } catch (\Exception $e) {
            return $issues;
        }

        // Check meta robots
        $robots_elements = $xpath->query('//meta[@name="robots"]/@content');

        if ($robots_elements->length > 0) {
            $robots_content = strtolower($robots_elements->item(0)->value);

            // Check for noindex
            if (strpos($robots_content, 'noindex') !== false) {
                $is_strategic = self::is_strategic_page($current_url);

                $issues[] = [
                    'severity' => $is_strategic ? 'bloquant' : 'info',
                    'type' => 'noindex_detected',
                    'message' => sprintf(
                        'Noindex détecté%s',
                        $is_strategic ? ' sur page stratégique' : ''
                    ),
                    'suggestion' => $is_strategic
                        ? 'Cette page est considérée stratégique. Vérifiez que noindex est intentionnel.'
                        : 'Cette page n\'est pas crawlée par Google.',
                ];
            }

            // Check for nofollow
            if (strpos($robots_content, 'nofollow') !== false) {
                $issues[] = [
                    'severity' => 'info',
                    'type' => 'nofollow_detected',
                    'message' => 'Nofollow détecté - liens ne seront pas crawlés',
                    'suggestion' => 'Utilisez nofollow seulement si intentionnel.',
                ];
            }
        }

        // Also check X-Robots-Tag header (if available)
        // This would need to be passed from scanner

        return $issues;
    }

    /**
     * Check if URL is considered strategic
     *
     * @param string $url URL to check
     * @return bool True if strategic
     */
    private static function is_strategic_page(string $url): string {
        // Get list from settings
        $strategic = get_option('jm_seo_strategic_pages', '');
        if (empty($strategic)) {
            return false;
        }

        $paths = array_map('trim', explode("\n", $strategic));
        foreach ($paths as $path) {
            if (!empty($path) && strpos($url, $path) !== false) {
                return true;
            }
        }

        return false;
    }
}
