<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Regression detector reads custom plugin tables.
class JM_SEO_Regression_Detector {
    public function compare_last_two(): array {
        global $wpdb;
        $scans=$wpdb->get_results("SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE status='finished' ORDER BY id DESC LIMIT 2");
        if(count($scans)<2) return [];
        $current=$scans[0]; $previous=$scans[1];
        $delta=(int)$current->site_score - (int)$previous->site_score;
        if($delta <= -10) return ['severity'=>'bloquant','message'=>'Régression score global : '.$delta.' points'];
        if($delta <= -5) return ['severity'=>'warning','message'=>'Baisse score global : '.$delta.' points'];
        return [];
    }
}
