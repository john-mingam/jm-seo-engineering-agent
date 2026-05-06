<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Settings {
    private const JOHN_MINGAM_REPORT_EMAIL = 'wp@johnmingam.com';

    public static function report_email(): string {
        if (defined('JM_SEO_AGENT_REPORT_EMAIL')) {
            return sanitize_email((string) JM_SEO_AGENT_REPORT_EMAIL);
        }

        if (self::is_john_mingam_client()) {
            return self::JOHN_MINGAM_REPORT_EMAIL;
        }

        $stored_email = sanitize_email((string) get_option('jm_seo_report_email', ''));
        return $stored_email ?: sanitize_email((string) get_option('admin_email'));
    }

    public static function is_john_mingam_client(): bool {
        return 'yes' === get_option('jm_seo_john_mingam_client', 'no');
    }

    public static function john_mingam_report_email(): string {
        return self::JOHN_MINGAM_REPORT_EMAIL;
    }

    public static function client_name(): string {
        return defined('JM_SEO_AGENT_CLIENT_NAME') ? JM_SEO_AGENT_CLIENT_NAME : wp_parse_url(home_url(), PHP_URL_HOST);
    }

    public static function crawl_limit(): int {
        return defined('JM_SEO_AGENT_CRAWL_LIMIT') ? (int) JM_SEO_AGENT_CRAWL_LIMIT : 500;
    }

    public static function batch_size(): int {
        return defined('JM_SEO_AGENT_BATCH_SIZE') ? (int) JM_SEO_AGENT_BATCH_SIZE : 10;
    }

    public static function max_depth(): int {
        return defined('JM_SEO_AGENT_MAX_DEPTH') ? (int) JM_SEO_AGENT_MAX_DEPTH : 3;
    }

    public static function agency_roles(): array {
        return JM_SEO_Client_Config::agency_roles();
    }

    public static function request_timeout(): int {
        return defined('JM_SEO_AGENT_TIMEOUT') ? (int) JM_SEO_AGENT_TIMEOUT : 12;
    }
}
