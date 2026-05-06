<?php
/**
 * Advanced Issues Browser - Display & Filter Issues
 */

if (!defined('ABSPATH')) exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin tables are queried for the admin issues browser.

class JM_SEO_Issues_Browser {
    
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 10);
    }

    public static function register_menu(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }
        
        add_submenu_page(
            'jm-seo-agent',
            'Problèmes détectés',
            'Problèmes',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-issues',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }

        global $wpdb;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters for an admin listing screen.
        $page = max(1, intval(wp_unslash($_GET['paged'] ?? 1)));
        $severity = sanitize_text_field(wp_unslash($_GET['severity'] ?? ''));
        $issue_type = sanitize_text_field(wp_unslash($_GET['issue_type'] ?? ''));
        $scan_id = intval(wp_unslash($_GET['scan_id'] ?? 0));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        // Get total
        $total = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues
            WHERE (%d = 0 OR scan_id = %d)
            AND (%s = '' OR severity = %s)
            AND (%s = '' OR issue_type = %s)",
            $scan_id,
            $scan_id,
            $severity,
            $severity,
            $issue_type,
            $issue_type
        )));
        $pages = ceil($total / $per_page);

        // Get issues
        $issues = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.url FROM {$wpdb->prefix}jm_seo_scan_issues i 
            JOIN {$wpdb->prefix}jm_seo_scan_pages p ON i.page_id = p.id 
            WHERE (%d = 0 OR i.scan_id = %d)
            AND (%s = '' OR i.severity = %s)
            AND (%s = '' OR i.issue_type = %s)
            ORDER BY FIELD(i.severity, 'bloquant', 'warning', 'info'), i.issue_type
            LIMIT %d OFFSET %d",
            $scan_id,
            $scan_id,
            $severity,
            $severity,
            $issue_type,
            $issue_type,
            $per_page,
            $offset
        ));

        // Get unique values for filters
        $severities = $wpdb->get_col("SELECT DISTINCT severity FROM {$wpdb->prefix}jm_seo_scan_issues ORDER BY FIELD(severity, 'bloquant', 'warning', 'info')");
        $types = $wpdb->get_col("SELECT DISTINCT issue_type FROM {$wpdb->prefix}jm_seo_scan_issues ORDER BY issue_type");
        $scans = $wpdb->get_results("SELECT id, started_at FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT 20");

        ?>
        <div class="wrap">
            <h1>Problèmes détectés</h1>

            <!-- Filters -->
            <div class="jm-seo-filters" style="background:#fff;padding:16px;margin:16px 0;border-radius:4px;border:1px solid #dcdcde;">
                <form method="get" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:flex-end;">
                    <input type="hidden" name="page" value="jm-seo-issues" />

                    <div>
                        <label><strong>Scan</strong></label>
                        <select name="scan_id">
                            <option value="">Tous les scans</option>
                            <?php foreach ($scans as $s): ?>
                                <option value="<?php echo intval($s->id); ?>" <?php selected($scan_id, $s->id); ?>>
                                    #<?php echo intval($s->id); ?> - <?php echo esc_html($s->started_at); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><strong>Sévérité</strong></label>
                        <select name="severity">
                            <option value="">Toutes</option>
                            <?php foreach ($severities as $sev): ?>
                                <option value="<?php echo esc_attr($sev); ?>" <?php selected($severity, $sev); ?>>
                                    <?php echo esc_html(ucfirst($sev)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><strong>Type</strong></label>
                        <select name="issue_type">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($issue_type, $type); ?>>
                                    <?php echo esc_html($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="button button-primary">Filtrer</button>
                </form>
            </div>

            <!-- Stats -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:16px 0;">
                <?php 
                $blocking = self::count_issues_by_severity('bloquant', $scan_id);
                $warnings = self::count_issues_by_severity('warning', $scan_id);
                $infos = self::count_issues_by_severity('info', $scan_id);
                ?>
                <div style="background:#fff3cd;padding:12px;border-radius:4px;">
                    <div style="font-size:12px;color:#665d00;text-transform:uppercase;">Bloquants</div>
                    <div style="font-size:24px;font-weight:700;color:#d63638;"><?php echo intval($blocking); ?></div>
                </div>
                <div style="background:#fff;padding:12px;border-radius:4px;border:1px solid #f0ad4e;">
                    <div style="font-size:12px;color:#664d00;text-transform:uppercase;">Avertissements</div>
                    <div style="font-size:24px;font-weight:700;color:#f0ad4e;"><?php echo intval($warnings); ?></div>
                </div>
                <div style="background:#d1ecf1;padding:12px;border-radius:4px;">
                    <div style="font-size:12px;color:#0c5460;text-transform:uppercase;">Infos</div>
                    <div style="font-size:24px;font-weight:700;color:#0c5460;"><?php echo intval($infos); ?></div>
                </div>
            </div>

            <!-- Issues Table -->
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th width="40%">URL</th>
                        <th width="20%">Type</th>
                        <th width="15%">Sévérité</th>
                        <th width="25%">Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($issues)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:24px;">Aucun problème trouvé</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($issues as $issue): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($issue->url); ?>" target="_blank" style="text-decoration:none;word-break:break-all;">
                                        <?php echo esc_html($issue->url); ?>
                                    </a>
                                </td>
                                <td>
                                    <code><?php echo esc_html($issue->issue_type); ?></code>
                                </td>
                                <td>
                                    <?php 
                                    $color = match($issue->severity) {
                                        'bloquant' => '#d63638',
                                        'warning' => '#f0ad4e',
                                        default => '#0c5460'
                                    };
                                    ?>
                                    <span style="background:<?php echo esc_attr($color); ?>;color:#fff;padding:4px 8px;border-radius:3px;font-size:11px;font-weight:600;">
                                        <?php echo esc_html(ucfirst($issue->severity)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($issue->issue_message ?? $issue->message ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo wp_kses_post(paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '« Précédent',
                            'next_text' => 'Suivant »',
                            'total' => $pages,
                            'current' => $page,
                        ]));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function count_issues_by_severity(string $severity, int $scan_id): int {
        global $wpdb;

        if ($scan_id > 0) {
            return intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues WHERE severity = %s AND scan_id = %d",
                $severity,
                $scan_id
            )));
        }

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues WHERE severity = %s",
            $severity
        )));
    }
}
