<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Email report reads custom plugin tables.
class JM_SEO_Email_Reporter {
    public function send(int $scan_id): void {
        global $wpdb;
        $scan=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id=%d",$scan_id));
        if(!$scan) return;
        $bundle=(new JM_SEO_Report_Generator())->generate_bundle($scan_id);
        $body="Rapport JM SEO Engineering Agent\n\nClient : ".JM_SEO_Settings::client_name()."\nSite : ".home_url('/')."\nScore global : {$scan->site_score}/100\nScore SFT (Structure) : {$scan->sft_score}/100\nPages crawlées : {$scan->pages_scanned}\nBloquants : {$scan->blocking_issues}\nWarnings : {$scan->warnings}\nInfos : {$scan->infos}\n";
        $attachments=array_filter([$bundle['pdf'] ?? null,$bundle['csv'] ?? null,$bundle['json'] ?? null]);
        wp_mail(JM_SEO_Settings::report_email(),'[JM SEO Agent] Rapport - '.JM_SEO_Settings::client_name(),$body,[], $attachments);
    }
}
