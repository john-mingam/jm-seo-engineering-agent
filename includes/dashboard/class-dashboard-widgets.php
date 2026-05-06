<?php
/**
 * Dashboard Widgets Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin tables are queried for admin dashboard summaries.

class JM_SEO_Dashboard_Widgets {

    public static function init(): void {
        add_action('wp_dashboard_setup', [self::class, 'register_widgets']);
    }

    /**
     * Register dashboard widgets
     */
    public static function register_widgets(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }

        wp_add_dashboard_widget(
            'jm_seo_score_widget',
            'JM SEO - Score Global',
            [self::class, 'render_score_widget']
        );

        wp_add_dashboard_widget(
            'jm_seo_last_scans_widget',
            'JM SEO - Derniers scans',
            [self::class, 'render_last_scans_widget']
        );

        wp_add_dashboard_widget(
            'jm_seo_issues_widget',
            'JM SEO - Problèmes détectés',
            [self::class, 'render_issues_widget']
        );

        wp_add_dashboard_widget(
            'jm_seo_queue_widget',
            'JM SEO - File d\'attente',
            [self::class, 'render_queue_widget']
        );
    }

    /**
     * Render score widget
     */
    public static function render_score_widget(): void {
        global $wpdb;

        $latest = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY finished_at DESC LIMIT 1"
        );

        if (!$latest) {
            echo '<p>Aucun scan effectué.</p>';
            return;
        }

        $sft = json_decode($latest->sft_score, true);
        $structure = $sft['structure'] ?? 0;
        $flow = $sft['flow'] ?? 0;
        $trust = $sft['trust'] ?? 0;

        echo '<div style="text-align: center; padding: 20px;">';
        echo '<h2 style="font-size: 48px; color: ' . esc_attr(self::get_score_color((int) $latest->site_score)) . ';">';
        echo intval($latest->site_score) . '/100';
        echo '</h2>';

        echo '<div style="display: flex; justify-content: center; gap: 15px; margin: 20px 0;">';
        echo '<div style="background: #f0f0f0; padding: 10px 20px; border-radius: 5px;">';
        echo '<strong style="color: #2c5aa0;">Structure</strong><br>';
        echo '<span style="font-size: 24px; color: ' . esc_attr(self::get_score_color((int) $structure)) . ';">' . esc_html((string) $structure) . '</span>';
        echo '</div>';

        echo '<div style="background: #f0f0f0; padding: 10px 20px; border-radius: 5px;">';
        echo '<strong style="color: #2c5aa0;">Flow</strong><br>';
        echo '<span style="font-size: 24px; color: ' . esc_attr(self::get_score_color((int) $flow)) . ';">' . esc_html((string) $flow) . '</span>';
        echo '</div>';

        echo '<div style="background: #f0f0f0; padding: 10px 20px; border-radius: 5px;">';
        echo '<strong style="color: #2c5aa0;">Trust</strong><br>';
        echo '<span style="font-size: 24px; color: ' . esc_attr(self::get_score_color((int) $trust)) . ';">' . esc_html((string) $trust) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<p style="color: #666; font-size: 12px;">';
        echo esc_html(sprintf('Dernière analyse: %s ago', human_time_diff(strtotime($latest->finished_at), current_time('timestamp'))));
        echo '</p>';
        echo '</div>';
    }

    /**
     * Render last scans widget
     */
    public static function render_last_scans_widget(): void {
        global $wpdb;

        $scans = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY finished_at DESC LIMIT 10"
        );

        if (empty($scans)) {
            echo '<p>Aucun scan effectué.</p>';
            return;
        }

        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr style="background: #f0f0f0;"><th>Date</th><th>Pages</th><th>Score</th><th>Status</th></tr>';

        foreach ($scans as $scan) {
            $date = date_i18n('d/m/Y H:i', strtotime($scan->finished_at));
            $status_color = $scan->status === 'finished' ? 'green' : 'orange';

            echo '<tr style="border-bottom: 1px solid #ddd;">';
            echo '<td style="padding: 8px;">' . esc_html($date) . '</td>';
            echo '<td style="padding: 8px;">' . intval($scan->pages_scanned) . '</td>';
            echo '<td style="padding: 8px; color: ' . esc_attr(self::get_score_color((int) $scan->site_score)) . '; font-weight: bold;">' . intval($scan->site_score) . '</td>';
            echo '<td style="padding: 8px; color: ' . esc_attr($status_color) . ';">' . esc_html(ucfirst($scan->status)) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Render issues widget
     */
    public static function render_issues_widget(): void {
        global $wpdb;

        $latest_scan = $wpdb->get_row(
            "SELECT id FROM {$wpdb->prefix}jm_seo_scans ORDER BY finished_at DESC LIMIT 1"
        );

        if (!$latest_scan) {
            echo '<p>Aucun scan effectué.</p>';
            return;
        }

        $issues = $wpdb->get_results($wpdb->prepare(
            "SELECT severity, issue_type, COUNT(*) as count FROM {$wpdb->prefix}jm_seo_scan_issues 
             WHERE scan_id = %d GROUP BY severity, issue_type ORDER BY severity DESC",
            $latest_scan->id
        ));

        if (empty($issues)) {
            echo '<p style="color: green;"><strong>Aucun problème détecté!</strong></p>';
            return;
        }

        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr style="background: #f0f0f0;"><th>Type</th><th>Sévérité</th><th>Nombre</th></tr>';

        foreach ($issues as $issue) {
            $color = match ($issue->severity) {
                'bloquant' => '#d32f2f',
                'warning' => '#f57c00',
                default => '#1976d2',
            };

            echo '<tr style="border-bottom: 1px solid #ddd;">';
            echo '<td style="padding: 8px;">' . esc_html($issue->issue_type) . '</td>';
            echo '<td style="padding: 8px;"><span style="color: ' . esc_attr($color) . '; font-weight: bold;">' . esc_html(ucfirst($issue->severity)) . '</span></td>';
            echo '<td style="padding: 8px; text-align: right;">' . intval($issue->count) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Render queue widget
     */
    public static function render_queue_widget(): void {
        global $wpdb;

        $active_scan = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE status = 'running' ORDER BY started_at DESC LIMIT 1"
        );

        if (!$active_scan) {
            echo '<p>Aucun crawl en cours.</p>';
            echo '<button class="button button-primary" onclick="jmSeoStartScan()">Lancer un scan</button>';
            return;
        }

        $queue_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id = %d",
            $active_scan->id
        ));

        $pages_scanned = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d",
            $active_scan->id
        ));

        $progress = intval(($pages_scanned / ($pages_scanned + $queue_count)) * 100);

        echo '<p><strong>Scan #' . intval($active_scan->id) . ' en cours</strong></p>';
        echo '<div style="background: #f0f0f0; border-radius: 5px; overflow: hidden; height: 20px;">';
        echo '<div style="background: #4caf50; width: ' . esc_attr((string) $progress) . '%; height: 100%; transition: width 0.3s;"></div>';
        echo '</div>';
        echo '<p style="font-size: 12px; margin-top: 5px;">';
        echo intval($pages_scanned) . ' analysées / ' . intval($queue_count) . ' en attente';
        echo '</p>';
    }

    /**
     * Get color based on score
     *
     * @param int $score Score (0-100)
     * @return string Color code
     */
    private static function get_score_color(int $score): string {
        if ($score >= 80) {
            return '#4caf50'; // Green
        } elseif ($score >= 60) {
            return '#ff9800'; // Orange
        }
        return '#d32f2f'; // Red
    }
}

add_action('plugins_loaded', [JM_SEO_Dashboard_Widgets::class, 'init']);
