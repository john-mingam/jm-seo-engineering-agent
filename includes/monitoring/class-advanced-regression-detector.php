<?php
/**
 * Advanced Regression Detector - Per-URL Tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Regression detector reads custom plugin tables.

class JM_SEO_Advanced_Regression_Detector {

    /**
     * Detect regressions on specific URL
     *
     * @param string $url URL to check
     * @param int|null $scan_id Current scan ID (optional)
     * @return array Regression details
     */
    public static function detect_url_regression(string $url, ?int $scan_id = null): array {
        global $wpdb;

        // Get last two scans
        $scans = $wpdb->get_results(
            "SELECT id, site_score, finished_at FROM {$wpdb->prefix}jm_seo_scans 
             ORDER BY finished_at DESC LIMIT 2"
        );

        if (count($scans) < 2) {
            return []; // Not enough history
        }

        $current_scan = $scans[0];
        $previous_scan = $scans[1];

        // Get page data
        $current_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d AND url = %s LIMIT 1",
            $current_scan->id,
            $url
        ));

        $previous_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d AND url = %s LIMIT 1",
            $previous_scan->id,
            $url
        ));

        if (!$current_page || !$previous_page) {
            return []; // Page not in both scans
        }

        $regression = [];

        // Overall score regression
        $score_delta = $previous_page->page_score - $current_page->page_score;
        if ($score_delta >= 10) {
            $regression['overall'] = [
                'type' => 'score_drop',
                'severity' => 'bloquant',
                'delta' => $score_delta,
                'previous' => intval($previous_page->page_score),
                'current' => intval($current_page->page_score),
            ];
        }

        // Component regressions
        $structure_delta = $previous_page->structure_score - $current_page->structure_score;
        $flow_delta = $previous_page->flow_score - $current_page->flow_score;
        $trust_delta = $previous_page->trust_score - $current_page->trust_score;

        if ($structure_delta >= 5) {
            $regression['structure'] = [
                'type' => 'structure_regression',
                'delta' => $structure_delta,
                'previous' => intval($previous_page->structure_score),
                'current' => intval($current_page->structure_score),
            ];
        }

        if ($flow_delta >= 5) {
            $regression['flow'] = [
                'type' => 'flow_regression',
                'delta' => $flow_delta,
                'previous' => intval($previous_page->flow_score),
                'current' => intval($current_page->flow_score),
            ];
        }

        if ($trust_delta >= 5) {
            $regression['trust'] = [
                'type' => 'trust_regression',
                'delta' => $trust_delta,
                'previous' => intval($previous_page->trust_score),
                'current' => intval($current_page->trust_score),
            ];
        }

        // Issue type regressions (disappeared or appeared)
        $previous_issues = $wpdb->get_results($wpdb->prepare(
            "SELECT issue_type, severity FROM {$wpdb->prefix}jm_seo_scan_issues WHERE page_id = %d",
            $previous_page->id
        ));

        $current_issues = $wpdb->get_results($wpdb->prepare(
            "SELECT issue_type, severity FROM {$wpdb->prefix}jm_seo_scan_issues WHERE page_id = %d",
            $current_page->id
        ));

        $previous_types = array_column($previous_issues, 'issue_type');
        $current_types = array_column($current_issues, 'issue_type');

        // Critical issues that disappeared (good)
        $resolved = array_diff($previous_types, $current_types);
        if (!empty($resolved)) {
            $regression['resolved_issues'] = [
                'type' => 'positive_regression',
                'issues' => array_values($resolved),
            ];
        }

        // New blocking issues (bad)
        $new_blocking = $wpdb->get_results($wpdb->prepare(
            "SELECT issue_type FROM {$wpdb->prefix}jm_seo_scan_issues 
             WHERE page_id = %d AND severity = 'bloquant'
             AND issue_type NOT IN (
                SELECT issue_type FROM {$wpdb->prefix}jm_seo_scan_issues WHERE page_id = %d
             )",
            $current_page->id,
            $previous_page->id
        ));

        if (!empty($new_blocking)) {
            $regression['new_critical_issues'] = [
                'type' => 'negative_regression',
                'severity' => 'bloquant',
                'issues' => array_column($new_blocking, 'issue_type'),
            ];
        }

        return $regression;
    }

    /**
     * Get all regressed URLs in last scan
     *
     * @return array List of regressed URLs with details
     */
    public static function detect_all_regressions(): array {
        global $wpdb;

        $scans = $wpdb->get_results(
            "SELECT id FROM {$wpdb->prefix}jm_seo_scans ORDER BY finished_at DESC LIMIT 2"
        );

        if (count($scans) < 2) {
            return [];
        }

        // Compare pages from last two scans
        $regressions = $wpdb->get_results(
            "SELECT 
                cp.url, 
                cp.page_score - pp.page_score as score_delta
            FROM {$wpdb->prefix}jm_seo_scan_pages cp
            JOIN {$wpdb->prefix}jm_seo_scan_pages pp ON cp.url = pp.url
            WHERE cp.scan_id = %d AND pp.scan_id = %d
            AND (cp.page_score - pp.page_score) >= 10
            ORDER BY score_delta DESC",
            $scans[0]->id,
            $scans[1]->id
        );

        return array_map(function ($regression) {
            return [
                'url' => $regression->url,
                'score_drop' => $regression->score_delta,
            ];
        }, $regressions);
    }

    /**
     * Detect specific issue type regression (e.g., all JSON-LD disappeared)
     *
     * @param string $issue_type Issue type to track
     * @return array Issue regression report
     */
    public static function detect_issue_type_regression(string $issue_type): array {
        global $wpdb;

        $scans = $wpdb->get_results(
            "SELECT id, finished_at FROM {$wpdb->prefix}jm_seo_scans ORDER BY finished_at DESC LIMIT 2"
        );

        if (count($scans) < 2) {
            return [];
        }

        $previous_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues 
             WHERE scan_id = %d AND issue_type = %s",
            $scans[1]->id,
            $issue_type
        ));

        $current_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues 
             WHERE scan_id = %d AND issue_type = %s",
            $scans[0]->id,
            $issue_type
        ));

        return [
            'issue_type' => $issue_type,
            'previous_count' => intval($previous_count),
            'current_count' => intval($current_count),
            'delta' => intval($current_count - $previous_count),
            'change_percent' => $previous_count > 0 ? round((($current_count - $previous_count) / $previous_count) * 100, 1) : 0,
            'regressed' => $current_count > $previous_count,
        ];
    }
}
