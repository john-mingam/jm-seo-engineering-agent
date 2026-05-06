<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Export reads custom plugin tables.
class JM_SEO_CSV_Generator {
    public function generate(int $scan_id): string {
        global $wpdb;
        if(!is_dir(JM_SEO_AGENT_REPORTS_PATH)) wp_mkdir_p(JM_SEO_AGENT_REPORTS_PATH);
        $file = JM_SEO_AGENT_REPORTS_PATH.'jm-seo-report-'.$scan_id.'.csv';
        $csv = self::csv_row(['URL','HTTP Code','Score','Severity','Issue Type','Message','Suggestion']);
        $rows=$wpdb->get_results($wpdb->prepare("SELECT p.url,p.http_code,p.page_score,p.structure_score,p.flow_score,p.trust_score,i.severity,i.issue_type,i.issue_message,i.suggestion FROM {$wpdb->prefix}jm_seo_scan_pages p LEFT JOIN {$wpdb->prefix}jm_seo_scan_issues i ON p.id=i.page_id WHERE p.scan_id=%d ORDER BY p.page_score ASC",$scan_id),ARRAY_A);
        foreach($rows as $r) {
            $csv .= self::csv_row([$r['url'],$r['http_code'],$r['page_score'],$r['severity'] ?? '',$r['issue_type'] ?? '',$r['issue_message'] ?? '',$r['suggestion'] ?? '']);
        }

        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        if ($wp_filesystem) {
            $wp_filesystem->put_contents($file, $csv, FS_CHMOD_FILE);
        }
        return $file;
    }

    private static function csv_row(array $fields): string {
        $escaped = array_map(static function ($field): string {
            $field = (string) $field;
            return '"' . str_replace('"', '""', $field) . '"';
        }, $fields);

        return implode(',', $escaped) . "\n";
    }
}
