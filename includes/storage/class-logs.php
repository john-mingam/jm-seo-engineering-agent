<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Log storage uses custom plugin tables.

class JM_SEO_Logs {
    public static function add(string $level, string $context, string $message): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'jm_seo_logs', [
            'level' => $level,
            'context' => $context,
            'message' => $message,
            'created_at' => current_time('mysql'),
        ]);
    }

    public static function tail(int $limit = 50): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_logs ORDER BY id DESC LIMIT %d",
            $limit
        ), ARRAY_A) ?: [];
    }
}
