<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dashboard reads custom plugin tables.
class JM_SEO_Dashboard {
    public static function init(): void {
        if (JM_SEO_Permissions::can_access()) {
            add_action('admin_menu', [__CLASS__, 'menu'], 8);
            add_action('admin_post_jm_seo_manual_scan', [__CLASS__, 'handle_manual_scan']);
        }
    }
    public static function menu(): void {
        add_menu_page('JM SEO Agent','JM SEO Agent',JM_SEO_Permissions::menu_capability(),'jm-seo-agent',[__CLASS__,'render'],'dashicons-chart-area',58);
        
        // Add dashboard as first submenu for clarity
        add_submenu_page(
            'jm-seo-agent',
            'Tableau de bord',
            'Tableau de bord',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-agent'
        );
    }
    public static function render(): void {
        global $wpdb;
        $scan=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");
        $history = JM_SEO_History::latest_scans(5);
        $delta = JM_SEO_History::compare_last_two();
        echo '<div class="wrap"><h1>JM SEO Engineering Agent</h1>';
        echo '<p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=jm_seo_manual_scan'), 'jm_seo_manual_scan')).'">Lancer un scan manuel</a></p>';
        if(!$scan){ echo '<p>Aucun scan.</p></div>'; return; }
        echo '<div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:24px 0;">';
        self::metric('Score global', (int) $scan->site_score);
        self::metric('SFT', (int) $scan->sft_score);
        self::metric('Bloquants', (int) $scan->blocking_issues);
        self::metric('Pages', (int) $scan->pages_scanned);
        echo '</div>';
        if (!empty($delta)) {
            echo '<p><strong>Régression récente :</strong> '.esc_html($delta['message']).'</p>';
        }
        echo '<h2>Historique comparatif</h2><table class="widefat striped"><thead><tr><th>Date</th><th>Score</th><th>SFT (Structure)</th><th>Bloquants</th><th>Warnings</th><th>Infos</th></tr></thead><tbody>';
        foreach ($history as $row) {
            echo '<tr><td>'.esc_html($row['started_at']).'</td><td>'.esc_html($row['site_score']).'</td><td>'.esc_html($row['sft_score']).'</td><td>'.esc_html($row['blocking_issues']).'</td><td>'.esc_html($row['warnings']).'</td><td>'.esc_html($row['infos']).'</td></tr>';
        }
        echo '</tbody></table>';
        echo '<h2>Logs techniques</h2><table class="widefat striped"><thead><tr><th>Niveau</th><th>Contexte</th><th>Message</th></tr></thead><tbody>';
        foreach (JM_SEO_Logs::tail(12) as $row) {
            echo '<tr><td>'.esc_html($row['level']).'</td><td>'.esc_html($row['context']).'</td><td>'.esc_html($row['message']).'</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public static function handle_manual_scan(): void {
        if (!current_user_can(JM_SEO_Permissions::capability())) {
            wp_die('Forbidden');
        }
        check_admin_referer('jm_seo_manual_scan');
        $scan_id = (new JM_SEO_Crawler())->start('manual');
        wp_safe_redirect(add_query_arg(['page' => 'jm-seo-agent', 'scan_id' => $scan_id, 'started' => 1], admin_url('admin.php')));
        exit;
    }

    private static function metric(string $label, int $value): void {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;"><div style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#646970;">'.esc_html($label).'</div><div style="font-size:32px;font-weight:700;margin-top:6px;">'.esc_html((string) $value).'</div></div>';
    }
}
