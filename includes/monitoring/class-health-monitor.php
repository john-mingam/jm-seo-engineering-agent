<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Health monitor reads custom plugin tables.

class JM_SEO_Health_Monitor {
    public function queue_status(): array {
        global $wpdb;
        $pending = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE status='pending'");
        $running = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scans WHERE status='running'");
        return ['pending' => $pending, 'running' => $running];
    }
}
