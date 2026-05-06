<?php
/**
 * Health & Performance Dashboard
 */

if (!defined('ABSPATH')) exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin tables are queried for admin health metrics.

class JM_SEO_Health_Dashboard {
    
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 13);
    }

    public static function register_menu(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }
        
        add_submenu_page(
            'jm-seo-agent',
            'Santé du site',
            'Santé',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-health',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }

        global $wpdb;

        $recent_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");
        $previous_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1 OFFSET 1");

        $health_score = self::calculate_health_score();
        $status = self::get_status_badge($health_score);

        ?>
        <div class="wrap">
            <h1>Santé du site SEO</h1>

            <!-- Health Status -->
            <div style="background:#fff;padding:24px;margin:16px 0;border:1px solid #dcdcde;border-radius:4px;border-left:6px solid <?php echo esc_attr(self::score_color($health_score)); ?>;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h2 style="margin:0;font-size:24px;">État général</h2>
                        <p style="margin:8px 0;color:#646970;">Basé sur les 30 derniers jours</p>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:48px;font-weight:700;color:<?php echo esc_attr(self::score_color($health_score)); ?>;">
                            <?php echo intval($health_score); ?>
                        </div>
                        <div style="font-size:14px;color:#646970;margin-top:8px;">
                            <?php echo esc_html(self::get_health_label($health_score)); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Components -->
            <h2>Indicateurs de santé</h2>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin:16px 0;">
                <?php self::render_health_metric('Score moyen', self::get_avg_score(), 'Scores globaux (30j)'); ?>
                <?php self::render_health_metric('Problèmes bloquants', intval($wpdb->get_var("SELECT SUM(blocking_issues) FROM {$wpdb->prefix}jm_seo_scans WHERE DATE(started_at) > DATE_SUB(NOW(), INTERVAL 30 DAY)")), 'Problèmes critiques'); ?>
                <?php self::render_health_metric('Pages conformes', self::get_conformance_rate() . '%', 'Pages sans bloquants'); ?>
                <?php self::render_health_metric('Régression', (self::detect_regression($recent_scan, $previous_scan) ? 'OUI' : 'NON'), 'Baisse de score récente'); ?>
            </div>

            <!-- Recent Errors -->
            <h2>Erreurs récentes</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th width="15%">Niveau</th>
                        <th width="25%">Contexte</th>
                        <th width="60%">Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $errors = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}jm_seo_logs WHERE level IN ('error', 'critical') ORDER BY created_at DESC LIMIT 10");
                    
                    if (empty($errors)):
                    ?>
                        <tr>
                            <td colspan="3" style="text-align:center;padding:24px;color:#646970;">Aucune erreur récente</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($errors as $error): ?>
                            <tr>
                                <td>
                                    <span style="background:<?php echo ($error->level === 'critical') ? '#dc3545' : '#ffc107'; ?>;color:#fff;padding:4px 8px;border-radius:3px;font-size:11px;font-weight:600;">
                                        <?php echo esc_html(strtoupper($error->level)); ?>
                                    </span>
                                </td>
                                <td><code><?php echo esc_html($error->context); ?></code></td>
                                <td><?php echo esc_html($error->message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Performance Stats -->
            <h2>Performances</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Métrique</th>
                        <th>Valeur</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $avg_crawl_time = self::get_avg_crawl_time();
                    $urls_per_sec = self::get_urls_per_sec();
                    $cache_hit_rate = self::get_cache_stats();
                    ?>
                    <tr>
                        <td>Temps moyen de crawl</td>
                        <td><?php echo number_format($avg_crawl_time, 2); ?>s</td>
                        <td>
                            <?php if ($avg_crawl_time < 30): ?>
                                <span style="color:#28a745;">✓ Optimal</span>
                            <?php elseif ($avg_crawl_time < 60): ?>
                                <span style="color:#ffc107;">⚠ Acceptable</span>
                            <?php else: ?>
                                <span style="color:#dc3545;">✗ Lent</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>URLs crawlées/sec</td>
                        <td><?php echo number_format($urls_per_sec, 2); ?></td>
                        <td>
                            <?php if ($urls_per_sec > 0.5): ?>
                                <span style="color:#28a745;">✓ Optimal</span>
                            <?php elseif ($urls_per_sec > 0.1): ?>
                                <span style="color:#ffc107;">⚠ Acceptable</span>
                            <?php else: ?>
                                <span style="color:#dc3545;">✗ Lent</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Cache</td>
                        <td><?php echo esc_html($cache_hit_rate['backend']); ?></td>
                        <td><?php echo wp_kses_post($cache_hit_rate['status']); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Recommendations -->
            <h2>Recommandations</h2>
            <div style="background:#fff;padding:16px;margin:16px 0;border-left:4px solid #0073aa;border-radius:3px;">
                <?php self::render_recommendations(); ?>
            </div>
        </div>
        <?php
    }

    private static function render_health_metric(string $label, mixed $value, string $description): void {
        $color = self::score_color(intval($value));
        ?>
        <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
            <div style="font-size:12px;color:#646970;text-transform:uppercase;"><?php echo esc_html($label); ?></div>
            <div style="font-size:28px;font-weight:700;color:<?php echo esc_attr($color); ?>;margin:8px 0;">
                <?php echo esc_html((string)$value); ?>
            </div>
            <div style="font-size:12px;color:#646970;"><?php echo esc_html($description); ?></div>
        </div>
        <?php
    }

    private static function calculate_health_score(): int {
        global $wpdb;

        $avg_score = intval($wpdb->get_var(
            "SELECT AVG(site_score) FROM {$wpdb->prefix}jm_seo_scans WHERE started_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )) ?? 0;

        $blocking_count = intval($wpdb->get_var(
            "SELECT SUM(blocking_issues) FROM {$wpdb->prefix}jm_seo_scans WHERE started_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )) ?? 0;

        $health = $avg_score;
        $health -= min($blocking_count / 10, 20); // Deduct points for blocking issues

        return max(0, min(100, intval($health)));
    }

    private static function get_status_badge(int $score): string {
        if ($score >= 80) {
            return '✓ Excellent';
        } elseif ($score >= 60) {
            return '⚠ Bon';
        } elseif ($score >= 40) {
            return '⚠ Passable';
        } else {
            return '✗ Critique';
        }
    }

    private static function get_health_label(int $score): string {
        if ($score >= 80) {
            return 'Site en excellent état';
        } elseif ($score >= 60) {
            return 'Site en bon état';
        } elseif ($score >= 40) {
            return 'Améliorations recommandées';
        } else {
            return 'Actions urgentes requises';
        }
    }

    private static function score_color(int $score): string {
        if ($score >= 80) {
            return '#28a745';
        } elseif ($score >= 60) {
            return '#ffc107';
        } else {
            return '#dc3545';
        }
    }

    private static function get_avg_score(): int {
        global $wpdb;
        return intval($wpdb->get_var(
            "SELECT AVG(site_score) FROM {$wpdb->prefix}jm_seo_scans WHERE started_at > DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )) ?? 0;
    }

    private static function get_conformance_rate(): int {
        global $wpdb;
        $recent_scan = $wpdb->get_row("SELECT id, pages_scanned FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");
        
        if (!$recent_scan || $recent_scan->pages_scanned === 0) {
            return 100;
        }

        $problematic = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT page_id) FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id = %d AND severity = 'bloquant'",
            $recent_scan->id
        )));

        return max(0, intval((($recent_scan->pages_scanned - $problematic) / $recent_scan->pages_scanned) * 100));
    }

    private static function detect_regression($recent, $previous): bool {
        if (!$recent || !$previous) {
            return false;
        }
        return ($recent->site_score - $previous->site_score) < -10;
    }

    private static function get_avg_crawl_time(): float {
        global $wpdb;
        $scans = $wpdb->get_results(
            "SELECT TIMESTAMPDIFF(SECOND, started_at, finished_at) as duration FROM {$wpdb->prefix}jm_seo_scans 
            WHERE finished_at IS NOT NULL ORDER BY id DESC LIMIT 10"
        );

        if (empty($scans)) {
            return 0;
        }

        $total = 0;
        foreach ($scans as $scan) {
            $total += $scan->duration ?? 0;
        }

        return $total / count($scans);
    }

    private static function get_urls_per_sec(): float {
        global $wpdb;
        $avg_crawl = self::get_avg_crawl_time();
        $avg_pages = intval($wpdb->get_var("SELECT AVG(pages_scanned) FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 10"));

        if ($avg_crawl === 0 || $avg_pages === 0) {
            return 0;
        }

        return $avg_pages / $avg_crawl;
    }

    private static function get_cache_stats(): array {
        $backend_info = JM_SEO_Cache::get_backend_info();
        return [
            'enabled' => $backend_info['external_cache_active'],
            'backend' => $backend_info['backend'],
            'status' => $backend_info['status'],
        ];
    }

    private static function render_recommendations(): void {
        global $wpdb;

        $health_score = self::calculate_health_score();
        $recent_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");

        $recommendations = [];

        if ($health_score < 60) {
            $recommendations[] = ['type' => 'critical', 'text' => 'Score global faible - action urgente requise'];
        }

        if ($recent_scan && $recent_scan->blocking_issues > 10) {
            $recommendations[] = ['type' => 'warning', 'text' => sprintf('%d problèmes bloquants détectés', $recent_scan->blocking_issues)];
        }

        if (!JM_SEO_Cache::is_external_cache_active()) {
            $recommendations[] = ['type' => 'info', 'text' => 'Activez Redis/Memcached pour améliorer les performances - actuellement utilisation du cache mémoire'];
        }

        if (empty($recommendations)) {
            echo '<p style="color:#28a745;"><strong>✓ Aucun problème détecté</strong> - Site en bon état</p>';
        } else {
            foreach ($recommendations as $rec) {
                $colors = [
                    'critical' => '#dc3545',
                    'warning' => '#ffc107',
                    'info' => '#0073aa',
                ];
                $color = $colors[$rec['type']] ?? '#0073aa';
                echo sprintf('<div style="padding:8px;margin:8px 0;background:%s;color:white;border-radius:3px;">%s</div>', esc_attr($color), esc_html($rec['text']));
            }
        }
    }
}
