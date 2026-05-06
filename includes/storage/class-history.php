<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- History storage uses custom plugin tables.

class JM_SEO_History {
    public static function latest_scans(int $limit = 5): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, started_at, finished_at, site_score, sft_score, blocking_issues, warnings, infos, status FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT %d",
            $limit
        ), ARRAY_A) ?: [];
    }

    public static function compare_last_two(): array {
        global $wpdb;
        $scans = $wpdb->get_results("SELECT id, site_score, sft_score, blocking_issues, warnings, infos, started_at FROM {$wpdb->prefix}jm_seo_scans WHERE status='finished' ORDER BY id DESC LIMIT 2", ARRAY_A);
        if (count($scans) < 2) {
            return [];
        }

        $current = $scans[0];
        $previous = $scans[1];
        $delta = (int) $current['site_score'] - (int) $previous['site_score'];

        return [
            'delta' => $delta,
            'message' => 'Score global : ' . $delta . ' points',
        ];
    }

    public static function issue_urls(int $limit = 25): array {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.url FROM {$wpdb->prefix}jm_seo_scan_issues i INNER JOIN {$wpdb->prefix}jm_seo_scan_pages p ON p.id = i.page_id ORDER BY i.id DESC LIMIT %d",
            $limit
        )) ?: [];
    }
}
