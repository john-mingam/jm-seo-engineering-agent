<?php
/**
 * Advanced Canonical Coherence Validator
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Canonical_Validator {

    /**
     * Validate canonical tag coherence
     *
     * @param string $current_url Current page URL
     * @param string $html Page HTML content
     * @return array Issues detected
     */
    public static function validate(string $current_url, string $html): array {
        $issues = [];

        // Parse canonical
        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);
        } catch (\Exception $e) {
            return $issues; // Can't parse, skip
        }

        $canonical_elements = $xpath->query('//link[@rel="canonical"]/@href');

        // No canonical
        if ($canonical_elements->length === 0) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'missing_canonical',
                'message' => 'Balise canonical manquante',
                'suggestion' => 'Ajoutez <link rel="canonical" href="' . esc_attr($current_url) . '" />',
            ];
            return $issues;
        }

        // Multiple canonicals
        if ($canonical_elements->length > 1) {
            $issues[] = [
                'severity' => 'bloquant',
                'type' => 'multiple_canonicals',
                'message' => sprintf('Plusieurs canonicals détectées (%d)', $canonical_elements->length),
                'suggestion' => 'Ne conservez qu\'une seule balise canonical.',
            ];
            return $issues;
        }

        $canonical_url = $canonical_elements->item(0)?->value;

        if (empty($canonical_url)) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'empty_canonical',
                'message' => 'Balise canonical vide',
                'suggestion' => 'Remplissez l\'attribut href de canonical.',
            ];
            return $issues;
        }

        // Validate canonical domain
        $current_domain = self::get_domain($current_url);
        $canonical_domain = self::get_domain($canonical_url);

        if ($current_domain !== $canonical_domain) {
            $issues[] = [
                'severity' => 'bloquant',
                'type' => 'external_canonical',
                'message' => sprintf(
                    'Canonical pointe vers domaine externe: %s',
                    $canonical_domain
                ),
                'suggestion' => 'La canonical doit pointer vers le même domaine.',
            ];
            return $issues;
        }

        // Check if canonical points to self or valid URL
        if (strtolower($current_url) !== strtolower($canonical_url)) {
            // It's different, check if it exists
            $response = wp_remote_head($canonical_url, [
                'timeout' => 5,
                'sslverify' => apply_filters('https_local_ssl_verify', false), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core HTTP API filter.
            ]);

            if (is_wp_error($response)) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'canonical_target_unreachable',
                    'message' => sprintf(
                        'Canonical pointe vers URL inaccessible: %s',
                        $canonical_url
                    ),
                    'suggestion' => 'Vérifiez que la canonical pointe vers une URL valide et accessible.',
                ];
            } elseif (wp_remote_retrieve_response_code($response) >= 400) {
                $issues[] = [
                    'severity' => 'warning',
                    'type' => 'canonical_target_error',
                    'message' => sprintf(
                        'Canonical pointe vers erreur HTTP %d',
                        wp_remote_retrieve_response_code($response)
                    ),
                    'suggestion' => 'La canonical ne doit pas pointer vers erreur 4xx ou 5xx.',
                ];
            }
        }

        return $issues;
    }

    /**
     * Extract domain from URL
     *
     * @param string $url URL to parse
     * @return string Domain name
     */
    private static function get_domain(string $url): string {
        $parsed = wp_parse_url($url);
        return strtolower($parsed['host'] ?? '');
    }
}
