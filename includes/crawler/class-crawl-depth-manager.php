<?php
/**
 * Crawl Depth Limiting System
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Crawl depth manager reads custom plugin tables.

class JM_SEO_Crawl_Depth_Manager {

    const MAX_DEPTH = 3; // Limiter à profondeur 3
    const SITEMAP_DEPTH = 1;
    const RECENT_POSTS_DEPTH = 2;
    const DISCOVERED_DEPTH = 3;

    /**
     * Calculate URL depth
     *
     * @param string $url URL to analyze
     * @return int Depth level
     */
    public static function calculate_depth(string $url): int {
        $home = home_url();
        $relative = str_replace($home, '', $url);
        $relative = trim($relative, '/');

        if (empty($relative)) {
            return 0; // Homepage
        }

        $segments = explode('/', $relative);
        // Remove query string if present
        $last = array_pop($segments);
        if (strpos($last, '?') !== false) {
            list($last) = explode('?', $last);
        }

        $depth = count($segments);
        if (!empty($last)) {
            $depth += 1;
        }

        return $depth;
    }

    /**
     * Assign depth based on discovery source
     *
     * @param string $source Discovery source (sitemap|recent_posts|discovered_links)
     * @return int Depth to assign
     */
    public static function get_source_depth(string $source): int {
        return match ($source) {
            'sitemap' => self::SITEMAP_DEPTH,
            'recent_posts' => self::RECENT_POSTS_DEPTH,
            'discovered_links' => self::DISCOVERED_DEPTH,
            default => self::DISCOVERED_DEPTH,
        };
    }

    /**
     * Check if URL should be crawled based on depth
     *
     * @param int $depth URL depth
     * @param int $max_depth Maximum allowed depth
     * @return bool Should crawl
     */
    public static function should_crawl(int $depth, int $max_depth = self::MAX_DEPTH): bool {
        return $depth <= $max_depth;
    }

    /**
     * Get depth distribution stats
     *
     * @param int $scan_id Scan ID
     * @return array Depth distribution
     */
    public static function get_depth_stats(int $scan_id): array {
        global $wpdb;

        $depths = $wpdb->get_results($wpdb->prepare(
            "SELECT crawl_depth, COUNT(*) as count FROM {$wpdb->prefix}jm_seo_scan_pages 
             WHERE scan_id = %d GROUP BY crawl_depth ORDER BY crawl_depth ASC",
            $scan_id
        ));

        $stats = [
            'by_depth' => [],
            'total_depth_0' => 0,
            'total_depth_1' => 0,
            'total_depth_2' => 0,
            'total_depth_3_plus' => 0,
            'average_depth' => 0,
            'max_depth' => 0,
        ];

        $total_urls = 0;
        $sum_depths = 0;

        foreach ($depths as $depth_stat) {
            $d = intval($depth_stat->crawl_depth);
            $c = intval($depth_stat->count);

            $stats['by_depth'][$d] = $c;
            $total_urls += $c;
            $sum_depths += $d * $c;

            if ($d === 0) {
                $stats['total_depth_0'] = $c;
            } elseif ($d === 1) {
                $stats['total_depth_1'] = $c;
            } elseif ($d === 2) {
                $stats['total_depth_2'] = $c;
            } elseif ($d >= 3) {
                $stats['total_depth_3_plus'] += $c;
            }

            $stats['max_depth'] = max($stats['max_depth'], $d);
        }

        if ($total_urls > 0) {
            $stats['average_depth'] = round($sum_depths / $total_urls, 2);
        }

        return $stats;
    }

    /**
     * Flag pages that are too deep
     *
     * @param int $scan_id Scan ID
     * @param int $max_depth Maximum allowed depth
     * @return int Number of deep pages
     */
    public static function flag_deep_pages(int $scan_id, int $max_depth = self::MAX_DEPTH): int {
        global $wpdb;

        // Get pages deeper than max_depth
        $deep_pages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, url, crawl_depth FROM {$wpdb->prefix}jm_seo_scan_pages 
             WHERE scan_id = %d AND crawl_depth > %d",
            $scan_id,
            $max_depth
        ));

        $count = 0;
        foreach ($deep_pages as $page) {
            // Insert issue for deep page
            $wpdb->insert(
                "{$wpdb->prefix}jm_seo_scan_issues",
                [
                    'scan_id' => $scan_id,
                    'page_id' => $page->id,
                    'severity' => 'info',
                    'issue_type' => 'too_deep_crawl',
                    'issue_message' => sprintf(
                        'Page trop profonde: profondeur %d (max: %d)',
                        $page->crawl_depth,
                        $max_depth
                    ),
                    'suggestion' => 'Améliorez le maillage interne pour rapprocher cette page de la surface.',
                    'created_at' => current_time('mysql'),
                ]
            );
            $count++;
        }

        return $count;
    }
}
