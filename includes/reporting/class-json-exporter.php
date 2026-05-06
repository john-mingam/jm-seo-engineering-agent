<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Export reads custom plugin tables.

class JM_SEO_JSON_Exporter {
    public function generate(int $scan_id): string {
        global $wpdb;
        if (!is_dir(JM_SEO_AGENT_REPORTS_PATH)) {
            wp_mkdir_p(JM_SEO_AGENT_REPORTS_PATH);
        }

        $scan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id=%d", $scan_id), ARRAY_A);
        $pages = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id=%d ORDER BY page_score ASC", $scan_id), ARRAY_A);
        $issues = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id=%d ORDER BY id ASC", $scan_id), ARRAY_A);

        $payload = [
            'scan' => $scan,
            'pages' => $pages,
            'issues' => $issues,
        ];

        $file = JM_SEO_AGENT_REPORTS_PATH . 'jm-seo-report-' . $scan_id . '.json';
        file_put_contents($file, wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $file;
    }
}
