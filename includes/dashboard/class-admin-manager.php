<?php
/**
 * Admin Menu & Assets Enqueue Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Admin_Manager {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_submenus'], 9);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Register submenu pages (after main menu exists)
     */
    public static function register_submenus(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }

        // Settings submenu
        add_submenu_page(
            'jm-seo-agent',
            'Paramètres SEO Agent',
            'Paramètres',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-settings',
            [self::class, 'render_settings_page']
        );
    }

    /**
     * Render settings page wrapper
     */
    public static function render_settings_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }

        $settings = JM_SEO_Settings_Page::instance();
        $settings->render_page();
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_assets(string $hook): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }

        // Only on JM SEO pages
        if (strpos($hook, 'jm-seo') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'jm-seo-dashboard-css',
            JM_SEO_AGENT_URL . 'assets/dashboard.css',
            [],
            JM_SEO_AGENT_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'jm-seo-dashboard-js',
            JM_SEO_AGENT_URL . 'assets/dashboard.js',
            [],
            JM_SEO_AGENT_VERSION,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('jm-seo-dashboard-js', 'jmSeoAjax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jm_seo_nonce'),
        ]);
    }
}
