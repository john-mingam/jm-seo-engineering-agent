<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Sitemap_Parser {
    public function get_urls(): array {
        $candidates = [home_url('/sitemap.xml'), home_url('/wp-sitemap.xml'), home_url('/sitemap_index.xml')];
        $urls = [];
        foreach ($candidates as $sitemap) {
            $body = $this->fetch($sitemap);
            if (!$body) continue;
            $xml = @simplexml_load_string($body);
            if (!$xml) continue;
            foreach ($xml->children() as $child) {
                if (isset($child->loc)) {
                    $loc = esc_url_raw((string) $child->loc);
                    if (str_contains($loc, '.xml')) {
                        $nested = $this->fetch($loc);
                        $nested_xml = $nested ? @simplexml_load_string($nested) : false;
                        if ($nested_xml) {
                            foreach ($nested_xml->children() as $n) {
                                if (isset($n->loc) && !str_contains((string)$n->loc, '.xml')) $urls[] = esc_url_raw((string)$n->loc);
                            }
                        }
                    } else {
                        $urls[] = $loc;
                    }
                }
            }
        }
        return array_values(array_unique(array_filter($urls)));
    }
    private function fetch(string $url): string {
        $res = wp_remote_get($url, ['timeout' => 10, 'redirection' => 3]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) >= 400) return '';
        return (string) wp_remote_retrieve_body($res);
    }

    public function get_sitemap_urls(): array {
        return $this->get_urls();
    }
}
