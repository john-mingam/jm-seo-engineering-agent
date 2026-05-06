<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Robots_Parser {
    private array $disallows = [];
    private array $allows = [];
    private ?int $crawl_delay = null;
    public function __construct() {
        $res = wp_remote_get(home_url('/robots.txt'), ['timeout' => 8]);
        if (!is_wp_error($res)) {
            $body = wp_remote_retrieve_body($res);
            foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
                $line = trim($line);
                if (stripos($line, 'Disallow:') === 0) {
                    $path = trim(substr($line, 9));
                    if ($path !== '') $this->disallows[] = $path;
                } elseif (stripos($line, 'Allow:') === 0) {
                    $path = trim(substr($line, 6));
                    if ($path !== '') $this->allows[] = $path;
                } elseif (stripos($line, 'Crawl-delay:') === 0) {
                    $delay = trim(substr($line, 12));
                    if ($delay !== '') $this->crawl_delay = (int) $delay;
                }
            }
        }
    }
    public function is_allowed(string $url): bool {
        $path = wp_parse_url($url, PHP_URL_PATH) ?: '/';
        foreach ($this->allows as $rule) {
            if ($rule !== '' && str_starts_with($path, $rule)) return true;
        }
        foreach ($this->disallows as $rule) {
            if ($rule !== '/' && str_starts_with($path, $rule)) return false;
        }
        return true;
    }

    public function crawl_delay(): ?int {
        return $this->crawl_delay;
    }
}
