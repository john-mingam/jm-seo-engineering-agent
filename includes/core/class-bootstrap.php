<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Bootstrap {

    public static function init(): void {
        JM_SEO_Database::install();
        JM_SEO_Permissions::register();
        JM_SEO_Hooks::register();
        JM_SEO_Crawler::schedule();

        // Register scheduled crawl hooks
        add_action(JM_SEO_Crawler::START_HOOK, ['JM_SEO_Crawler', 'run_scheduled_scan']);
        add_action(JM_SEO_Crawler::BATCH_HOOK, ['JM_SEO_Crawler', 'run_batch']);

        // Initialize dashboard and admin
        JM_SEO_Dashboard::init();
        JM_SEO_Admin_Manager::init();
        JM_SEO_Settings_Page::instance();
        JM_SEO_Issues_Browser::init();
        JM_SEO_Scan_History::init();
        JM_SEO_Advanced_Reporting::init();
        JM_SEO_Health_Dashboard::init();
        JM_SEO_Aller_Plus_Loin::init();
        JM_SEO_Documentation::init();

        // Initialize REST API
        JM_SEO_REST_API::register();
    }
}
