<?php
/**
 * Cache System - Cache crawl results for performance
 */

if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cache fallback uses a custom plugin table.

class JM_SEO_Cache {
    
    const CACHE_GROUP = 'jm_seo_cache';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Check if external object cache is available
     * 
     * @return bool True if using persistent cache (Redis/Memcached), false for default memory cache
     */
    public static function is_external_cache_active(): bool {
        return defined('WP_CACHE') && WP_CACHE && function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache();
    }

    /**
     * Get cache backend information
     * 
     * @return array Cache backend info
     */
    public static function get_backend_info(): array {
        $external = self::is_external_cache_active();
        
        return [
            'external_cache_active' => $external,
            'backend' => $external ? 'Redis/Memcached (Persistent)' : 'WordPress Memory Cache (Transient)',
            'group' => self::CACHE_GROUP,
            'ttl' => self::CACHE_TTL,
            'status' => $external ? '✅ Active (Persistent)' : '⚠ Memory Cache Only',
        ];
    }

    /**
     * Get cached value
     */
    public static function get(string $key): mixed {
        return wp_cache_get($key, self::CACHE_GROUP);
    }

    /**
     * Set cached value
     */
    public static function set(string $key, mixed $value, int $ttl = self::CACHE_TTL): bool {
        return wp_cache_set($key, $value, self::CACHE_GROUP, $ttl);
    }

    /**
     * Delete cached value
     */
    public static function delete(string $key): bool {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    /**
     * Invalidate all scan cache for a scan
     */
    public static function invalidate_scan(int $scan_id): void {
        global $wpdb;
        
        // Get all pages from scan
        $pages = $wpdb->get_results($wpdb->prepare(
            "SELECT url FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d",
            $scan_id
        ));

        foreach ($pages as $page) {
            self::delete('page_analysis_' . md5($page->url));
            self::delete('page_issues_' . md5($page->url));
        }

        self::delete("scan_{$scan_id}_pages");
        self::delete("scan_{$scan_id}_stats");
    }

    /**
     * Get cached page analysis
     */
    public static function get_page_analysis(string $url): mixed {
        return self::get('page_analysis_' . md5($url));
    }

    /**
     * Cache page analysis
     */
    public static function cache_page_analysis(string $url, array $data): void {
        self::set('page_analysis_' . md5($url), $data);
    }

    /**
     * Get cached page issues
     */
    public static function get_page_issues(string $url): mixed {
        return self::get('page_issues_' . md5($url));
    }

    /**
     * Cache page issues
     */
    public static function cache_page_issues(string $url, array $issues): void {
        self::set('page_issues_' . md5($url), $issues);
    }

    /**
     * Get cached scan pages
     */
    public static function get_scan_pages(int $scan_id): mixed {
        return self::get("scan_{$scan_id}_pages");
    }

    /**
     * Cache scan pages
     */
    public static function cache_scan_pages(int $scan_id, array $pages): void {
        self::set("scan_{$scan_id}_pages", $pages);
    }

    /**
     * Get cached scan statistics
     */
    public static function get_scan_stats(int $scan_id): mixed {
        return self::get("scan_{$scan_id}_stats");
    }

    /**
     * Cache scan statistics
     */
    public static function cache_scan_stats(int $scan_id, array $stats): void {
        self::set("scan_{$scan_id}_stats", $stats);
    }

    /**
     * Clear all JM SEO cache
     */
    public static function clear_all(): bool {
        return wp_cache_flush();
    }

    /**
     * Get cache stats
     */
    public static function get_stats(): array {
        global $wpdb;
        
        // Count cached items (approximate)
        $count = wp_cache_get('jm_seo_cache_count', self::CACHE_GROUP) ?? 0;

        return [
            'cached_items' => $count,
            'cache_group' => self::CACHE_GROUP,
            'ttl' => self::CACHE_TTL,
        ];
    }
}
