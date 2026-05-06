<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Client_Config {
    public static function agency_roles(): array {
        $roles = defined('JM_SEO_AGENT_AGENCY_ROLES') ? JM_SEO_AGENT_AGENCY_ROLES : 'administrator,agency_admin,seo_manager';
        $roles = array_map('trim', explode(',', (string) $roles));
        $roles = array_values(array_filter($roles));
        return apply_filters('jm_seo_agent_agency_roles', $roles);
    }

    public static function strategic_urls(): array {
        $stored = get_option('jm_seo_agent_strategic_urls', '');
        $urls = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $stored)));
        if (!in_array(home_url('/'), $urls, true)) {
            array_unshift($urls, home_url('/'));
        }
        return array_values(array_unique(array_map('esc_url_raw', $urls)));
    }

    public static function report_recipients(): array {
        $stored = get_option('jm_seo_agent_report_recipients', '');
        $emails = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $stored)));
        if (!$emails) {
            $emails = [JM_SEO_Settings::report_email()];
        }
        return array_values(array_unique(array_map('sanitize_email', $emails)));
    }
}
