<?php
/**
 * Manual Scan Trigger Handler via AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- AJAX status reads custom plugin tables.

class JM_SEO_Manual_Scan {

    public static function register_ajax(): void {
        add_action('wp_ajax_jm_seo_start_scan', [self::class, 'ajax_start_scan']);
        add_action('wp_ajax_jm_seo_scan_status', [self::class, 'ajax_scan_status']);
    }

    /**
     * AJAX: Start manual scan
     */
    public static function ajax_start_scan(): void {
        // Check nonce
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'jm_seo_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed'], 403);
        }

        if (!JM_SEO_Permissions::can_access()) {
            wp_send_json_error(['message' => 'Accès refusé'], 403);
        }

        try {
            $crawler = new JM_SEO_Crawler();
            $scan_id = $crawler->start('manual');

            if (is_wp_error($scan_id)) {
                wp_send_json_error(['message' => $scan_id->get_error_message()]);
            }

            wp_send_json_success([
                'scan_id' => $scan_id,
                'message' => sprintf('Scan %d commencé', $scan_id),
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Get scan status
     */
    public static function ajax_scan_status(): void {
        // Check nonce
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'jm_seo_nonce')) {
            wp_send_json_error(['message' => 'Nonce verification failed'], 403);
        }

        if (!JM_SEO_Permissions::can_access()) {
            wp_send_json_error(['message' => 'Accès refusé'], 403);
        }

        $scan_id = intval(wp_unslash($_POST['scan_id'] ?? 0));
        if (!$scan_id) {
            wp_send_json_error(['message' => 'Scan ID manquant']);
        }

        global $wpdb;
        $scan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d",
            $scan_id
        ));

        if (!$scan) {
            wp_send_json_error(['message' => 'Scan non trouvé']);
        }

        if ($scan->status === 'running') {
            $pending_before = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id = %d AND status = 'pending'",
                $scan_id
            ));

            if ($pending_before > 0) {
                (new JM_SEO_Crawler())->process_batch($scan_id, 1);
            }
        }

        $queue_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id = %d",
            $scan_id
        ));

        $pages_scanned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d",
            $scan_id
        ));

        wp_send_json_success([
            'status' => $scan->status,
            'queue_remaining' => intval($queue_count),
            'pages_scanned' => intval($pages_scanned),
            'total_pages' => intval($scan->pages_scanned),
            'finished_at' => $scan->finished_at,
        ]);
    }
}

add_action('plugins_loaded', [JM_SEO_Manual_Scan::class, 'register_ajax']);
