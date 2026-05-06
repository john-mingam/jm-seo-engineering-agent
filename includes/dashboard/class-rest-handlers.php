<?php
/**
 * REST API Handlers for JM SEO Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- REST endpoints read custom plugin tables.

class JM_SEO_REST_Handlers {

    public static function register(): void {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    /**
     * Register REST routes
     */
    public static function register_routes(): void {
        // Start scan
        register_rest_route('jm-seo/v1', '/scan', [
            'methods' => 'POST',
            'callback' => [self::class, 'start_scan'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);

        // Get scans list
        register_rest_route('jm-seo/v1', '/scans', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_scans'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);

        // Get scan details
        register_rest_route('jm-seo/v1', '/scans/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_scan_detail'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);

        // Get scan issues
        register_rest_route('jm-seo/v1', '/scans/(?P<id>\d+)/issues', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_scan_issues'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);

        // Get reports
        register_rest_route('jm-seo/v1', '/reports/(?P<scan_id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_report'],
            'permission_callback' => [self::class, 'check_permission'],
            'args' => [
                'format' => [
                    'type' => 'string',
                    'enum' => ['csv', 'pdf', 'json'],
                    'default' => 'json',
                ],
            ],
        ]);

        // Get scores trend
        register_rest_route('jm-seo/v1', '/scores', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_scores'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);
    }

    /**
     * Permission check
     *
     * @return bool Permission granted
     */
    public static function check_permission(): bool {
        return JM_SEO_Permissions::can_access();
    }

    /**
     * Start new scan (POST /wp-json/jm-seo/v1/scan)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function start_scan(WP_REST_Request $request): WP_REST_Response {
        try {
            $crawler = new JM_SEO_Crawler();
            $scan = $crawler->start('api');

            if (is_wp_error($scan)) {
                return new WP_REST_Response(
                    ['error' => $scan->get_error_message()],
                    400
                );
            }

            return new WP_REST_Response([
                'success' => true,
                'scan_id' => $scan['id'],
                'status' => $scan['status'],
                'created_at' => $scan['started_at'],
            ], 201);
        } catch (\Exception $e) {
            return new WP_REST_Response(
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get scans list (GET /wp-json/jm-seo/v1/scans)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_scans(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $page = intval($request->get_param('page') ?? 1);
        $per_page = intval($request->get_param('per_page') ?? 20);
        $offset = ($page - 1) * $per_page;

        $scans = $wpdb->get_results($wpdb->prepare(
            "SELECT id, site_url, started_at, finished_at, pages_scanned, site_score, status 
             FROM {$wpdb->prefix}jm_seo_scans 
             ORDER BY finished_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scans");

        return new WP_REST_Response([
            'scans' => $scans,
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
        ]);
    }

    /**
     * Get scan details (GET /wp-json/jm-seo/v1/scans/{id})
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_scan_detail(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $scan_id = intval($request->get_param('id'));

        $scan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d",
            $scan_id
        ));

        if (!$scan) {
            return new WP_REST_Response(
                ['error' => 'Scan not found'],
                404
            );
        }

        return new WP_REST_Response([
            'id' => intval($scan->id),
            'site_url' => $scan->site_url,
            'status' => $scan->status,
            'created_at' => $scan->started_at,
            'finished_at' => $scan->finished_at,
            'pages_scanned' => intval($scan->pages_scanned),
            'score' => intval($scan->site_score),
            'sft' => json_decode($scan->sft_score, true),
            'issues_summary' => [
                'blocking' => intval($scan->blocking_issues),
                'warnings' => intval($scan->warnings),
                'infos' => intval($scan->infos),
            ],
        ]);
    }

    /**
     * Get scan issues (GET /wp-json/jm-seo/v1/scans/{id}/issues)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_scan_issues(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $scan_id = intval($request->get_param('id'));
        $severity = sanitize_text_field((string) $request->get_param('severity'));
        $per_page = intval($request->get_param('per_page') ?? 50);

        if ($severity) {
            $issues = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id = %d AND severity = %s LIMIT %d",
                $scan_id,
                $severity,
                $per_page
            ));
        } else {
            $issues = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id = %d LIMIT %d",
                $scan_id,
                $per_page
            ));
        }

        return new WP_REST_Response([
            'scan_id' => $scan_id,
            'issues' => $issues,
            'count' => count($issues),
        ]);
    }

    /**
     * Get report (GET /wp-json/jm-seo/v1/reports/{scan_id})
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_report(WP_REST_Request $request): WP_REST_Response {
        $scan_id = intval($request->get_param('scan_id'));
        $format = $request->get_param('format') ?? 'json';

        global $wpdb;

        $scan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d",
            $scan_id
        ));

        if (!$scan) {
            return new WP_REST_Response(
                ['error' => 'Scan not found'],
                404
            );
        }

        // Get pages and issues
        $pages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id = %d ORDER BY page_score ASC LIMIT 50",
            $scan_id
        ));

        $issues_map = [];
        foreach ($pages as $page) {
            $page_issues = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}jm_seo_scan_issues WHERE page_id = %d",
                $page->id
            ));
            $issues_map[$page->id] = $page_issues;
        }

        if ($format === 'csv') {
            return new WP_REST_Response(
                self::format_csv($scan, $pages, $issues_map),
                200,
                ['Content-Type' => 'text/csv']
            );
        } elseif ($format === 'pdf') {
            return new WP_REST_Response(
                self::format_pdf($scan, $pages, $issues_map),
                200,
                ['Content-Type' => 'application/pdf']
            );
        }

        return new WP_REST_Response(
            self::format_json($scan, $pages, $issues_map)
        );
    }

    /**
     * Format as CSV
     */
    private static function format_csv($scan, $pages, $issues_map): string {
        $csv = "URL,HTTP Code,Score,Structure,Flow,Trust,Severity,Issue Type,Message,Suggestion\n";

        foreach ($pages as $page) {
            $issues = $issues_map[$page->id] ?? [];

            if (empty($issues)) {
                $csv .= sprintf(
                    "\"%s\",%d,%d,%d,%d,%d,\"\",\"\",\"\",\"\"\n",
                    $page->url,
                    $page->http_code,
                    $page->page_score,
                    $page->structure_score,
                    $page->flow_score,
                    $page->trust_score
                );
            } else {
                foreach ($issues as $issue) {
                    $csv .= sprintf(
                        "\"%s\",%d,%d,%d,%d,%d,\"%s\",\"%s\",\"%s\",\"%s\"\n",
                        $page->url,
                        $page->http_code,
                        $page->page_score,
                        $page->structure_score,
                        $page->flow_score,
                        $page->trust_score,
                        $issue->severity,
                        $issue->issue_type,
                        $issue->issue_message,
                        $issue->suggestion
                    );
                }
            }
        }

        return $csv;
    }

    /**
     * Format as PDF (returns HTML for now)
     */
    private static function format_pdf($scan, $pages, $issues_map): string {
        ob_start();
        ?>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>JM SEO Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #2c5aa0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background: #2c5aa0; color: white; padding: 10px; text-align: left; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                tr:nth-child(even) { background: #f9f9f9; }
            </style>
        </head>
        <body>
            <h1>JM SEO Engineering Report</h1>
            <p>Site: <?php echo esc_html($scan->site_url); ?></p>
            <p>Generated: <?php echo esc_html(current_time('d/m/Y H:i')); ?></p>

            <h2>Score Global: <?php echo intval($scan->site_score); ?>/100</h2>

            <table>
                <tr>
                    <th>URL</th>
                    <th>Score</th>
                    <th>Issues</th>
                </tr>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?php echo esc_html($page->url); ?></td>
                        <td><?php echo intval($page->page_score); ?></td>
                        <td><?php echo count($issues_map[$page->id] ?? []); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Format as JSON
     */
    private static function format_json($scan, $pages, $issues_map): array {
        $data = [
            'scan' => [
                'id' => intval($scan->id),
                'url' => $scan->site_url,
                'status' => $scan->status,
                'created_at' => $scan->started_at,
                'score' => intval($scan->site_score),
            ],
            'pages' => [],
        ];

        foreach ($pages as $page) {
            $data['pages'][] = [
                'url' => $page->url,
                'http_code' => intval($page->http_code),
                'score' => intval($page->page_score),
                'sft' => [
                    'structure' => intval($page->structure_score),
                    'flow' => intval($page->flow_score),
                    'trust' => intval($page->trust_score),
                ],
                'issues' => $issues_map[$page->id] ?? [],
            ];
        }

        return $data;
    }

    /**
     * Get scores trend (GET /wp-json/jm-seo/v1/scores)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_scores(WP_REST_Request $request): WP_REST_Response {
        global $wpdb;

        $days = intval($request->get_param('days') ?? 30);

        $scans = $wpdb->get_results($wpdb->prepare(
            "SELECT finished_at, site_score FROM {$wpdb->prefix}jm_seo_scans 
             WHERE finished_at > DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY finished_at ASC",
            $days
        ));

        $trend = [];
        foreach ($scans as $scan) {
            $trend[] = [
                'date' => gmdate('Y-m-d', strtotime($scan->finished_at)),
                'score' => intval($scan->site_score),
            ];
        }

        return new WP_REST_Response([
            'trend' => $trend,
            'days' => $days,
            'total_scans' => count($scans),
        ]);
    }
}

add_action('plugins_loaded', [JM_SEO_REST_Handlers::class, 'register']);
