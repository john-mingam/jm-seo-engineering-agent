<?php
/**
 * Scan History & Comparison - View and Compare Scan Results
 */

if (!defined('ABSPATH')) exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin tables are queried for admin scan history.

class JM_SEO_Scan_History {
    
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 11);
    }

    public static function register_menu(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }
        
        add_submenu_page(
            'jm-seo-agent',
            'Historique des scans',
            'Historique',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-history',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }

        global $wpdb;
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameters for an admin screen.
        $action = sanitize_text_field(wp_unslash($_GET['action'] ?? ''));
        $scan_id = intval(wp_unslash($_GET['scan_id'] ?? 0));
        $compare_with = intval(wp_unslash($_GET['compare_with'] ?? 0));

        if ($action === 'compare' && $scan_id && $compare_with) {
            self::render_comparison($scan_id, $compare_with);
            return;
        }

        if ($action === 'detail' && $scan_id) {
            self::render_detail($scan_id);
            return;
        }

        // List all scans
        $page = max(1, intval(wp_unslash($_GET['paged'] ?? 1)));
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $total = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scans"));
        $pages = ceil($total / $per_page);

        $scans = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        ?>
        <div class="wrap">
            <h1>Historique des scans</h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="15%">Type</th>
                        <th width="20%">Date</th>
                        <th width="12%">Score</th>
                        <th width="12%">SFT (Structure)</th>
                        <th width="12%">Pages</th>
                        <th width="12%">Bloquants</th>
                        <th width="7%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scans as $scan): ?>
                        <tr>
                            <td><strong>#<?php echo intval($scan->id); ?></strong></td>
                            <td><?php echo esc_html(ucfirst($scan->scan_type)); ?></td>
                            <td><?php echo esc_html($scan->started_at); ?></td>
                            <td>
                                <span style="font-weight:700;font-size:14px;<?php 
                                    echo ($scan->site_score >= 80) ? 'color:#28a745;' : (($scan->site_score >= 60) ? 'color:#ffc107;' : 'color:#dc3545;');
                                ?>">
                                    <?php echo intval($scan->site_score); ?>/100
                                </span>
                            </td>
                            <td><?php echo intval($scan->sft_score); ?></td>
                            <td><?php echo intval($scan->pages_scanned); ?></td>
                            <td>
                                <span style="background:#dc3545;color:#fff;padding:4px 8px;border-radius:3px;font-weight:600;">
                                    <?php echo intval($scan->blocking_issues); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'detail', 'scan_id' => $scan->id], admin_url('admin.php?page=jm-seo-history'))); ?>" class="button button-small">
                                    Détail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

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

    private static function render_detail(int $scan_id): void {
        global $wpdb;
        
        $scan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d",
            $scan_id
        ));

        if (!$scan) {
            wp_die('Scan non trouvé');
        }

        $pages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, url, page_score FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d ORDER BY page_score ASC LIMIT 50",
            $scan_id
        ));

        $issues_by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT issue_type, severity, COUNT(*) as count FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id = %d GROUP BY issue_type, severity ORDER BY count DESC",
            $scan_id
        ));

        ?>
        <div class="wrap">
            <h1>Détail Scan #<?php echo intval($scan->id); ?></h1>

            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin:16px 0;">
                <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;">Score Global</div>
                    <div style="font-size:28px;font-weight:700;color:<?php echo ($scan->site_score >= 80) ? '#28a745' : (($scan->site_score >= 60) ? '#ffc107' : '#dc3545'); ?>;">
                        <?php echo intval($scan->site_score); ?>
                    </div>
                </div>
                <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;">SFT (Structure) Score</div>
                    <div style="font-size:28px;font-weight:700;"><?php echo intval($scan->sft_score); ?></div>
                </div>
                <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;">Pages</div>
                    <div style="font-size:28px;font-weight:700;"><?php echo intval($scan->pages_scanned); ?></div>
                </div>
                <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;">Bloquants</div>
                    <div style="font-size:28px;font-weight:700;color:#dc3545;"><?php echo intval($scan->blocking_issues); ?></div>
                </div>
                <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:4px;">
                    <div style="font-size:12px;color:#646970;text-transform:uppercase;">Date</div>
                    <div style="font-size:12px;font-weight:700;word-break:break-word;"><?php echo esc_html($scan->started_at); ?></div>
                </div>
            </div>

            <h2>Pages analysées (Bottom 10)</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th width="15%">Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><?php echo esc_html($page->url); ?></td>
                            <td>
                                <span style="font-weight:700;<?php echo ($page->page_score >= 80) ? 'color:#28a745;' : (($page->page_score >= 60) ? 'color:#ffc107;' : 'color:#dc3545;'); ?>">
                                    <?php echo intval($page->page_score); ?>/100
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Problèmes par type</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Bloquant</th>
                        <th>Warning</th>
                        <th>Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $by_type = [];
                    foreach ($issues_by_type as $issue) {
                        if (!isset($by_type[$issue->issue_type])) {
                            $by_type[$issue->issue_type] = ['bloquant' => 0, 'warning' => 0, 'info' => 0];
                        }
                        $by_type[$issue->issue_type][$issue->severity] = $issue->count;
                    }
                    foreach ($by_type as $type => $severities):
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($type); ?></code></td>
                            <td><span style="background:#dc3545;color:#fff;padding:4px 8px;border-radius:3px;"><?php echo intval($severities['bloquant']); ?></span></td>
                            <td><span style="background:#ffc107;color:#333;padding:4px 8px;border-radius:3px;"><?php echo intval($severities['warning']); ?></span></td>
                            <td><span style="background:#17a2b8;color:#fff;padding:4px 8px;border-radius:3px;"><?php echo intval($severities['info']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:24px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=jm-seo-history')); ?>" class="button">Retour</a>
            </div>
        </div>
        <?php
    }

    private static function render_comparison(int $scan_id1, int $scan_id2): void {
        global $wpdb;

        $scan1 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d",
            $scan_id1
        ));

        $scan2 = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d",
            $scan_id2
        ));

        if (!$scan1 || !$scan2) {
            wp_die('Scans non trouvés');
        }

        $delta_score = $scan2->site_score - $scan1->site_score;
        $delta_blocking = $scan2->blocking_issues - $scan1->blocking_issues;

        ?>
        <div class="wrap">
            <h1>Comparaison Scan #<?php echo intval($scan_id1); ?> vs #<?php echo intval($scan_id2); ?></h1>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th width="25%">Métrique</th>
                        <th width="25%">Scan #<?php echo intval($scan_id1); ?></th>
                        <th width="25%">Scan #<?php echo intval($scan_id2); ?></th>
                        <th width="25%">Delta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Score global</strong></td>
                        <td><?php echo intval($scan1->site_score); ?></td>
                        <td><?php echo intval($scan2->site_score); ?></td>
                        <td>
                            <?php if ($delta_score > 0): ?>
                                <span style="color:#28a745;">+<?php echo intval($delta_score); ?></span>
                            <?php elseif ($delta_score < 0): ?>
                                <span style="color:#dc3545;"><?php echo intval($delta_score); ?></span>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Pages</strong></td>
                        <td><?php echo intval($scan1->pages_scanned); ?></td>
                        <td><?php echo intval($scan2->pages_scanned); ?></td>
                        <td><?php echo intval($scan2->pages_scanned - $scan1->pages_scanned); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Bloquants</strong></td>
                        <td><?php echo intval($scan1->blocking_issues); ?></td>
                        <td><?php echo intval($scan2->blocking_issues); ?></td>
                        <td>
                            <?php if ($delta_blocking < 0): ?>
                                <span style="color:#28a745;">-<?php echo esc_html((string) abs(intval($delta_blocking))); ?></span>
                            <?php elseif ($delta_blocking > 0): ?>
                                <span style="color:#dc3545;">+<?php echo intval($delta_blocking); ?></span>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top:24px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=jm-seo-history')); ?>" class="button">Retour</a>
            </div>
        </div>
        <?php
    }
}
