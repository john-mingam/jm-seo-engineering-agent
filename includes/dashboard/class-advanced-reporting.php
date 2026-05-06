<?php
/**
 * Advanced Reporting - Detailed reports with trends and insights
 */

if (!defined('ABSPATH')) exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin tables are queried for admin reports.

class JM_SEO_Advanced_Reporting {
    
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 12);
    }

    public static function register_menu(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }
        
        add_submenu_page(
            'jm-seo-agent',
            'Rapports avancés',
            'Rapports',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-reports',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only report filters for an admin screen.
        $report_type = sanitize_text_field(wp_unslash($_GET['report'] ?? 'overview'));
        $days = intval(wp_unslash($_GET['days'] ?? 30));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        ?>
        <div class="wrap">
            <h1>Rapports avancés</h1>

            <!-- Report Type Selector -->
            <div style="background:#fff;padding:16px;margin:16px 0;border-radius:4px;border:1px solid #dcdcde;">
                <strong>Type de rapport:</strong>
                <a href="<?php echo esc_url(add_query_arg('report', 'overview')); ?>" class="button <?php echo ($report_type === 'overview') ? 'active' : ''; ?>" style="<?php echo ($report_type === 'overview') ? 'background:#0073aa;color:#fff;' : ''; ?>">Vue d'ensemble</a>
                <a href="<?php echo esc_url(add_query_arg('report', 'issues')); ?>" class="button <?php echo ($report_type === 'issues') ? 'active' : ''; ?>" style="<?php echo ($report_type === 'issues') ? 'background:#0073aa;color:#fff;' : ''; ?>">Problèmes</a>
                <a href="<?php echo esc_url(add_query_arg('report', 'trends')); ?>" class="button <?php echo ($report_type === 'trends') ? 'active' : ''; ?>" style="<?php echo ($report_type === 'trends') ? 'background:#0073aa;color:#fff;' : ''; ?>">Tendances</a>
                <a href="<?php echo esc_url(add_query_arg('report', 'compliance')); ?>" class="button <?php echo ($report_type === 'compliance') ? 'active' : ''; ?>" style="<?php echo ($report_type === 'compliance') ? 'background:#0073aa;color:#fff;' : ''; ?>">Conformité</a>
            </div>

            <?php
            match ($report_type) {
                'overview' => self::render_overview(),
                'issues' => self::render_issues(),
                'trends' => self::render_trends($days),
                'compliance' => self::render_compliance(),
                default => self::render_overview(),
            };
            ?>
        </div>
        <?php
    }

    private static function render_overview(): void {
        global $wpdb;

        $recent_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");
        $avg_score = $wpdb->get_var("SELECT AVG(site_score) FROM {$wpdb->prefix}jm_seo_scans WHERE id > IFNULL((SELECT MAX(id)-30 FROM {$wpdb->prefix}jm_seo_scans), 0)");
        $total_scans = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scans");
        $avg_pages = $wpdb->get_var("SELECT AVG(pages_scanned) FROM {$wpdb->prefix}jm_seo_scans");

        ?>
        <h2>Vue d'ensemble</h2>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:16px 0;">
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Dernier score</div>
                <div style="font-size:32px;font-weight:700;color:<?php echo ($recent_scan->site_score >= 80) ? '#28a745' : (($recent_scan->site_score >= 60) ? '#ffc107' : '#dc3545'); ?>;">
                    <?php echo intval($recent_scan->site_score); ?>
                </div>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Score moyen (30j)</div>
                <div style="font-size:32px;font-weight:700;">
                    <?php echo intval($avg_score); ?>
                </div>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Total scans</div>
                <div style="font-size:32px;font-weight:700;"><?php echo intval($total_scans); ?></div>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Pages/scan</div>
                <div style="font-size:32px;font-weight:700;"><?php echo intval($avg_pages); ?></div>
            </div>
        </div>

        <h2>Statistiques globales</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Métrique</th>
                    <th>Valeur</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total problèmes bloquants</td>
                    <td><?php echo intval($wpdb->get_var("SELECT SUM(blocking_issues) FROM {$wpdb->prefix}jm_seo_scans")); ?></td>
                </tr>
                <tr>
                    <td>Total problèmes avertissement</td>
                    <td><?php echo intval($wpdb->get_var("SELECT SUM(warning_issues) FROM {$wpdb->prefix}jm_seo_scans")); ?></td>
                </tr>
                <tr>
                    <td>Pages uniques scannées</td>
                    <td><?php echo intval($wpdb->get_var("SELECT COUNT(DISTINCT url) FROM {$wpdb->prefix}jm_seo_scan_pages")); ?></td>
                </tr>
                <tr>
                    <td>Type problèmes</td>
                    <td><?php echo intval($wpdb->get_var("SELECT COUNT(DISTINCT issue_type) FROM {$wpdb->prefix}jm_seo_scan_issues")); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    private static function render_issues(): void {
        global $wpdb;

        $issues_by_type = $wpdb->get_results(
            "SELECT issue_type, severity, COUNT(*) as count FROM {$wpdb->prefix}jm_seo_scan_issues 
            GROUP BY issue_type, severity ORDER BY count DESC LIMIT 50"
        );

        $by_type = [];
        foreach ($issues_by_type as $issue) {
            if (!isset($by_type[$issue->issue_type])) {
                $by_type[$issue->issue_type] = ['bloquant' => 0, 'warning' => 0, 'info' => 0];
            }
            $by_type[$issue->issue_type][$issue->severity] = $issue->count;
        }

        ?>
        <h2>Distribution des problèmes</h2>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th width="30%">Type</th>
                    <th width="15%">Bloquant</th>
                    <th width="15%">Warning</th>
                    <th width="15%">Info</th>
                    <th width="25%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($by_type as $type => $severities): ?>
                    <?php $total = array_sum($severities); ?>
                    <tr>
                        <td><code><?php echo esc_html($type); ?></code></td>
                        <td><span style="background:#dc3545;color:#fff;padding:4px 8px;border-radius:3px;"><?php echo intval($severities['bloquant']); ?></span></td>
                        <td><span style="background:#ffc107;color:#333;padding:4px 8px;border-radius:3px;"><?php echo intval($severities['warning']); ?></span></td>
                        <td><span style="background:#17a2b8;color:#fff;padding:4px 8px;border-radius:3px;"><?php echo intval($severities['info']); ?></span></td>
                        <td><strong><?php echo intval($total); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_trends(int $days): void {
        global $wpdb;

        $scans = $wpdb->get_results($wpdb->prepare(
            "SELECT id, site_score, sft_score, blocking_issues, started_at FROM {$wpdb->prefix}jm_seo_scans 
            WHERE started_at > DATE_SUB(NOW(), INTERVAL %d DAY) 
            ORDER BY id ASC",
            $days
        ));

        ?>
        <h2>Tendances (<?php echo intval($days); ?> derniers jours)</h2>

        <div style="background:#fff;padding:16px;margin:16px 0;border:1px solid #dcdcde;border-radius:4px;overflow-x:auto;">
            <canvas id="scoreChart" width="900" height="280" style="max-width:100%;width:100%;height:auto;"></canvas>
        </div>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Score</th>
                    <th>SFT (Structure)</th>
                    <th>Bloquants</th>
                    <th>Tendance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $prev_score = null;
                foreach ($scans as $scan): 
                    $trend = '';
                    if ($prev_score !== null) {
                        if ($scan->site_score > $prev_score) {
                            $trend = '<span style="color:#28a745;">↗ +' . ($scan->site_score - $prev_score) . '</span>';
                        } elseif ($scan->site_score < $prev_score) {
                            $trend = '<span style="color:#dc3545;">↘ ' . ($scan->site_score - $prev_score) . '</span>';
                        } else {
                            $trend = '→ Stable';
                        }
                    }
                    $prev_score = $scan->site_score;
                ?>
                    <tr>
                        <td><?php echo esc_html($scan->started_at); ?></td>
                        <td><?php echo intval($scan->site_score); ?></td>
                        <td><?php echo intval($scan->sft_score); ?></td>
                        <td><?php echo intval($scan->blocking_issues); ?></td>
                        <td><?php echo wp_kses($trend, ['span' => ['style' => true]]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const data = <?php echo wp_json_encode(array_map(static fn($s) => [
                'date' => substr((string) $s->started_at, 0, 10),
                'score' => (int) $s->site_score,
                'sft' => (int) $s->sft_score,
            ], $scans)); ?>;

            const canvas = document.getElementById('scoreChart');
            if (!canvas || !canvas.getContext || !data.length) {
                return;
            }

            const ctx = canvas.getContext('2d');
            const ratio = window.devicePixelRatio || 1;
            const width = canvas.width;
            const height = canvas.height;
            canvas.style.width = '100%';
            canvas.style.height = 'auto';
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            ctx.scale(ratio, ratio);

            const pad = 40;
            const chartW = width - pad * 2;
            const chartH = height - pad * 2;
            const max = 100;

            const points = (series) => data.map((item, index) => ({
                x: pad + (data.length === 1 ? chartW / 2 : (index / (data.length - 1)) * chartW),
                y: pad + chartH - ((series(item) / max) * chartH),
            }));

            const scorePoints = points((item) => item.score);
            const sftPoints = points((item) => item.sft);

            const drawLine = (pts, color) => {
                ctx.beginPath();
                ctx.strokeStyle = color;
                ctx.lineWidth = 3;
                pts.forEach((point, index) => {
                    if (index === 0) {
                        ctx.moveTo(point.x, point.y);
                    } else {
                        ctx.lineTo(point.x, point.y);
                    }
                });
                ctx.stroke();

                pts.forEach((point) => {
                    ctx.beginPath();
                    ctx.fillStyle = color;
                    ctx.arc(point.x, point.y, 4, 0, Math.PI * 2);
                    ctx.fill();
                });
            };

            ctx.clearRect(0, 0, width, height);
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, 0, width, height);

            ctx.strokeStyle = '#dcdcde';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 5; i++) {
                const y = pad + (chartH / 5) * i;
                ctx.beginPath();
                ctx.moveTo(pad, y);
                ctx.lineTo(width - pad, y);
                ctx.stroke();
                ctx.fillStyle = '#646970';
                ctx.font = '12px sans-serif';
                ctx.fillText(String(max - i * 20), 8, y + 4);
            }

            drawLine(scorePoints, '#0073aa');
            drawLine(sftPoints, '#28a745');

            ctx.fillStyle = '#0073aa';
            ctx.fillRect(width - 180, 18, 12, 12);
            ctx.fillStyle = '#333';
            ctx.fillText('Score global', width - 160, 28);
            ctx.fillStyle = '#28a745';
            ctx.fillRect(width - 60, 18, 12, 12);
            ctx.fillStyle = '#333';
            ctx.fillText('SFT (Structure)', width - 40, 28);

            ctx.fillStyle = '#646970';
            ctx.font = '11px sans-serif';
            data.forEach((item, index) => {
                const x = pad + (data.length === 1 ? chartW / 2 : (index / (data.length - 1)) * chartW);
                ctx.save();
                ctx.translate(x, height - 10);
                ctx.rotate(-Math.PI / 6);
                ctx.fillText(item.date, -15, 0);
                ctx.restore();
            });
        });
        </script>
        <?php
    }

    private static function render_compliance(): void {
        global $wpdb;

        $recent_scan = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 1");
        
        if (!$recent_scan) {
            echo '<p>Aucun scan disponible</p>';
            return;
        }

        $pages_with_blocking = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT page_id) FROM {$wpdb->prefix}jm_seo_scan_issues 
            WHERE scan_id = %d AND severity = 'bloquant'",
            $recent_scan->id
        ));

        $total_pages = $recent_scan->pages_scanned;
        $compliance_rate = ($total_pages - $pages_with_blocking) / $total_pages * 100;

        ?>
        <h2>Conformité SEO</h2>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:16px 0;">
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Taux de conformité</div>
                <div style="font-size:32px;font-weight:700;color:<?php echo ($compliance_rate >= 80) ? '#28a745' : (($compliance_rate >= 60) ? '#ffc107' : '#dc3545'); ?>;">
                    <?php echo number_format($compliance_rate, 1); ?>%
                </div>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Pages conformes</div>
                <div style="font-size:32px;font-weight:700;color:#28a745;"><?php echo intval($total_pages - $pages_with_blocking); ?></div>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                <div style="font-size:12px;color:#646970;text-transform:uppercase;">Pages non conformes</div>
                <div style="font-size:32px;font-weight:700;color:#dc3545;"><?php echo intval($pages_with_blocking); ?></div>
            </div>
        </div>

        <h2>Recommandations</h2>
        <ul style="list-style:none;padding:0;">
            <?php
            $blocking_issues = $wpdb->get_results($wpdb->prepare(
                "SELECT issue_type, COUNT(*) as count FROM {$wpdb->prefix}jm_seo_scan_issues 
                WHERE scan_id = %d AND severity = 'bloquant'
                GROUP BY issue_type ORDER BY count DESC LIMIT 5",
                $recent_scan->id
            ));

            foreach ($blocking_issues as $issue):
            ?>
                <li style="background:#fff3cd;padding:12px;margin:8px 0;border-left:4px solid #ffc107;border-radius:3px;">
                    <strong>Priorité:</strong> <?php echo esc_html($issue->issue_type); ?> (<?php echo intval($issue->count); ?> occurrences)
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}
