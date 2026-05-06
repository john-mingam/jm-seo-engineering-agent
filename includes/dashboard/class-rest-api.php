<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- REST API reads custom plugin tables.

class JM_SEO_REST_API {
    public static function register(): void {
        add_action('rest_api_init', [__CLASS__, 'routes']);
    }

    public static function routes(): void {
        register_rest_route('jm-seo/v1', '/scan', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'scan'],
            'permission_callback' => [__CLASS__, 'permission'],
        ]);

        register_rest_route('jm-seo/v1', '/reports', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'reports'],
            'permission_callback' => [__CLASS__, 'permission'],
        ]);

        register_rest_route('jm-seo/v1', '/issues', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'issues'],
            'permission_callback' => [__CLASS__, 'permission'],
        ]);

        register_rest_route('jm-seo/v1', '/scores', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'scores'],
            'permission_callback' => [__CLASS__, 'permission'],
        ]);
    }

    public static function permission(): bool {
        return JM_SEO_Permissions::can_access();
    }

    public static function scan(WP_REST_Request $request): WP_REST_Response {
        $scan_id = (new JM_SEO_Crawler())->start('manual');
        return new WP_REST_Response(['scan_id' => $scan_id, 'status' => 'started'], 200);
    }

    public static function reports(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1", ARRAY_A);
        if (!$scan) {
            return new WP_REST_Response(['reports' => []], 200);
        }
        $bundle = (new JM_SEO_Report_Generator())->generate_bundle((int) $scan['id']);
        return new WP_REST_Response(['scan' => $scan, 'reports' => $bundle], 200);
    }

    public static function issues(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $scan_id = (int) $request->get_param('scan_id');
        if (!$scan_id) {
            $scan_id = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");
        }
        $issues = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id=%d ORDER BY id DESC", $scan_id), ARRAY_A);
        return new WP_REST_Response(['scan_id' => $scan_id, 'issues' => $issues], 200);
    }

    public static function scores(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;
        $scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1", ARRAY_A);
        $history = JM_SEO_History::latest_scans(10);
        return new WP_REST_Response(['scan' => $scan, 'history' => $history], 200);
    }
}
