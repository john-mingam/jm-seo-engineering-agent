<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Link_Extractor {
    public function extract(string $url): array {
        $response = wp_remote_get($url, ['timeout' => 10, 'redirection' => 3]);
        if (is_wp_error($response)) {
            return [];
        }
        $html = (string) wp_remote_retrieve_body($response);
        if ($html === '') {
            return [];
        }
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        $links = [];
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $normalized = $this->normalize($href, $url);
            if ($normalized && $this->internal($normalized)) {
                $links[] = $normalized;
            }
        }
        return array_values(array_unique($links));
    }

    public function priority(string $url, int $depth): int {
        $priority = 10 + ($depth * 3);
        if (str_contains($url, '/blog/') || str_contains($url, '/article')) {
            $priority -= 3;
        }
        return max(1, $priority);
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
