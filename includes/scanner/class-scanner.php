<?php
/**
 * Main Scanner Class - Enhanced with all detectors
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Scanner {

    /**
     * Scan URL for SEO issues
     *
     * @param string $url URL to scan
     * @return array Scan result with http_code, html, issues
     */
    public function scan(string $url): array {
        $issues = [];

        // HTTP request
        $res = wp_remote_get($url, [
            'timeout' => JM_SEO_Settings::request_timeout(),
            'redirection' => 5,
            'headers' => ['User-Agent' => 'JM SEO Engineering Agent/2.0'],
            'sslverify' => apply_filters('https_local_ssl_verify', false), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core HTTP API filter.
        ]);

        // Handle errors
        if (is_wp_error($res)) {
            return [
                'http_code' => 0,
                'html' => '',
                'issues' => [[
                    'severity' => 'bloquant',
                    'type' => 'crawl_error',
                    'message' => $res->get_error_message(),
                    'suggestion' => 'Vérifier l\'accessibilité serveur.',
                ]]
            ];
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $html = (string) wp_remote_retrieve_body($res);
        $headers = wp_remote_retrieve_headers($res);

        // HTTP error
        if ($code >= 400) {
            $issues[] = [
                'severity' => 'bloquant',
                'type' => 'http_error',
                'message' => 'Code HTTP ' . $code,
                'suggestion' => 'Corriger la page ou la redirection.',
            ];
        }

        // Empty HTML
        if (!$html) {
            $issues[] = [
                'severity' => 'bloquant',
                'type' => 'empty_html',
                'message' => 'HTML vide.',
                'suggestion' => 'Vérifier cache, firewall ou erreur PHP.',
            ];
            return ['http_code' => $code, 'html' => '', 'issues' => $issues];
        }

        // Parse DOM
        $dom = $this->parse_html($html);
        if (!$dom) {
            return ['http_code' => $code, 'html' => $html, 'issues' => $issues];
        }

        $xp = new DOMXPath($dom);

        // Run all specialized scanners
        $issues = array_merge(
            $issues,
            (new JM_SEO_Meta_Scanner())->scan($dom, $xp, $url, $html, $headers),
            (new JM_SEO_Schema_Scanner())->scan($xp, $html, $url),
            (new JM_SEO_Link_Scanner())->scan($dom, $url),
            (new JM_SEO_Performance_Scanner())->scan($html, $headers),
            (new JM_SEO_AI_Visibility_Scanner())->scan($dom, $xp, $html, $url),
            JM_SEO_Canonical_Validator::validate($url, $html),
            JM_SEO_Noindex_Detector::detect($html, $url),
            JM_SEO_Plugin_Conflict_Detector::detect()
        );

        return ['http_code' => $code, 'html' => $html, 'issues' => $issues];
    }

    /**
     * Parse HTML to DOM
     *
     * @param string $html HTML content
     * @return DOMDocument|false
     */
    private function parse_html(string $html) {
        try {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="UTF-8"?>' . $html);
            libxml_clear_errors();
            return $dom;
        } catch (Exception $e) {
            return false;
        }
    }
}
