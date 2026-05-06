<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Database {
    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $scans = $wpdb->prefix . 'jm_seo_scans';
        $pages = $wpdb->prefix . 'jm_seo_scan_pages';
        $issues = $wpdb->prefix . 'jm_seo_scan_issues';
        $logs = $wpdb->prefix . 'jm_seo_logs';
        $queue = $wpdb->prefix . 'jm_seo_queue';

        dbDelta("CREATE TABLE $scans (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            site_url TEXT NULL,
            started_at DATETIME NULL,
            finished_at DATETIME NULL,
            scan_type VARCHAR(50) DEFAULT 'full',
            pages_scanned INT DEFAULT 0,
            site_score INT DEFAULT 0,
            sft_score INT DEFAULT 0,
            blocking_issues INT DEFAULT 0,
            warnings INT DEFAULT 0,
            infos INT DEFAULT 0,
            status VARCHAR(50) DEFAULT 'running',
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE $pages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id BIGINT UNSIGNED NOT NULL,
            url TEXT NOT NULL,
            http_code INT DEFAULT 0,
            page_score INT DEFAULT 100,
            structure_score INT DEFAULT 100,
            flow_score INT DEFAULT 100,
            trust_score INT DEFAULT 100,
            crawl_depth INT DEFAULT 0,
            scanned_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY scan_id (scan_id)
        ) $charset;");

        dbDelta("CREATE TABLE $issues (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id BIGINT UNSIGNED NOT NULL,
            page_id BIGINT UNSIGNED NOT NULL,
            severity VARCHAR(20) NOT NULL,
            issue_type VARCHAR(255) NOT NULL,
            issue_message TEXT NULL,
            suggestion TEXT NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY scan_id (scan_id),
            KEY page_id (page_id)
        ) $charset;");

        dbDelta("CREATE TABLE $logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            level VARCHAR(20) DEFAULT 'info',
            context VARCHAR(255) NULL,
            message LONGTEXT NULL,
            created_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset;");

        dbDelta("CREATE TABLE $queue (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id BIGINT UNSIGNED NOT NULL,
            url TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            priority INT DEFAULT 10,
            depth INT DEFAULT 0,
            source VARCHAR(50) DEFAULT 'sitemap',
            created_at DATETIME NULL,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY scan_status (scan_id, status)
        ) $charset;");
    }
}
