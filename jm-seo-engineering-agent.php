<?php
/**
 * Plugin Name: JM SEO Engineering Agent
 * Description: SEO Engineering monitoring agent with crawl, history, scoring, exports and email reporting.
 * Author: John Mingam
 * Version: 2.0.0
 * Requires PHP: 8.0
 * Text Domain: jm-seo-engineering-agent
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('JM_SEO_AGENT_VERSION', '2.0.0');
define('JM_SEO_AGENT_PATH', plugin_dir_path(__FILE__));
define('JM_SEO_AGENT_URL', plugin_dir_url(__FILE__));
define('JM_SEO_AGENT_REPORTS_PATH', JM_SEO_AGENT_PATH . 'reports/');
define('JM_SEO_AGENT_LOGS_PATH', JM_SEO_AGENT_PATH . 'logs/');

require_once JM_SEO_AGENT_PATH . 'includes/core/class-container.php';
require_once JM_SEO_AGENT_PATH . 'includes/core/class-hooks.php';
require_once JM_SEO_AGENT_PATH . 'includes/storage/class-database.php';
require_once JM_SEO_AGENT_PATH . 'includes/storage/class-logs.php';
require_once JM_SEO_AGENT_PATH . 'includes/storage/class-history.php';
require_once JM_SEO_AGENT_PATH . 'includes/storage/class-cache.php';
require_once JM_SEO_AGENT_PATH . 'includes/config/class-settings.php';
require_once JM_SEO_AGENT_PATH . 'includes/config/class-client-config.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-url-discovery.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-sitemap-parser.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-robots-parser.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-link-extractor.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-crawl-depth-manager.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-queue.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-scanner.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-meta-scanner.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-schema-scanner.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-link-scanner.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-performance-scanner.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-ai-visibility-scanner.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-plugin-conflict-detector.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-canonical-validator.php';
require_once JM_SEO_AGENT_PATH . 'includes/scanner/class-noindex-detector.php';
require_once JM_SEO_AGENT_PATH . 'includes/scoring/class-score-engine.php';
require_once JM_SEO_AGENT_PATH . 'includes/scoring/class-sft-weighting.php';
require_once JM_SEO_AGENT_PATH . 'includes/scoring/class-sft-score.php';
require_once JM_SEO_AGENT_PATH . 'includes/scoring/class-page-score.php';
require_once JM_SEO_AGENT_PATH . 'includes/scoring/class-site-score.php';
require_once JM_SEO_AGENT_PATH . 'includes/reporting/class-csv-generator.php';
require_once JM_SEO_AGENT_PATH . 'includes/reporting/class-pdf-generator.php';
require_once JM_SEO_AGENT_PATH . 'includes/reporting/class-email-reporter.php';
require_once JM_SEO_AGENT_PATH . 'includes/reporting/class-report-generator.php';
require_once JM_SEO_AGENT_PATH . 'includes/reporting/class-json-exporter.php';
require_once JM_SEO_AGENT_PATH . 'includes/monitoring/class-regression-detector.php';
require_once JM_SEO_AGENT_PATH . 'includes/monitoring/class-advanced-regression-detector.php';
require_once JM_SEO_AGENT_PATH . 'includes/monitoring/class-alert-manager.php';
require_once JM_SEO_AGENT_PATH . 'includes/monitoring/class-health-monitor.php';
require_once JM_SEO_AGENT_PATH . 'includes/crawler/class-crawler.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-dashboard.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-admin-manager.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-dashboard-widgets.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-settings-page.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-manual-scan.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-permissions.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-issues-browser.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-scan-history.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-advanced-reporting.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-health-dashboard.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-aller-plus-loin.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-documentation.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-rest-api.php';
require_once JM_SEO_AGENT_PATH . 'includes/dashboard/class-rest-handlers.php';
require_once JM_SEO_AGENT_PATH . 'includes/core/class-bootstrap.php';

register_activation_hook(__FILE__, ['JM_SEO_Database', 'install']);
register_activation_hook(__FILE__, ['JM_SEO_Permissions', 'activate']);
register_deactivation_hook(__FILE__, ['JM_SEO_Crawler', 'unschedule']);
add_action('plugins_loaded', ['JM_SEO_Bootstrap', 'init']);

if (defined('WPMU_PLUGIN_DIR')) {
    add_action('muplugins_loaded', ['JM_SEO_Database', 'install']);
}
