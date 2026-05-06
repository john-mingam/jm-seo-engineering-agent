<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Link_Scanner {
    public function scan(DOMDocument $dom, string $base_url): array {
        $issues = [];
        $count = 0;
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            if ($count >= 30) {
                break;
            }
            $href = trim($anchor->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $url = $this->normalize($href, $base_url);
            if (!$url || !$this->internal($url)) {
                continue;
            }
            $head = wp_remote_head($url, ['timeout' => 7, 'redirection' => 5]);
            if (!is_wp_error($head) && (int) wp_remote_retrieve_response_code($head) >= 400) {
                $issues[] = ['severity' => 'warning', 'type' => 'broken_internal_link', 'message' => 'Lien interne cassé : ' . $url, 'suggestion' => 'Corriger ou rediriger ce lien.'];
            }
            $count++;
        }

        return $issues;
    }

    private function normalize(string $href, string $base): string {
        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return esc_url_raw($href);
        }
        if (str_starts_with($href, '/')) {
            return esc_url_raw(home_url($href));
        }
        return esc_url_raw(trailingslashit($base) . ltrim($href, '/'));
    }

    private function internal(string $url): bool {
        return wp_parse_url(home_url(), PHP_URL_HOST) === wp_parse_url($url, PHP_URL_HOST);
    }
}
