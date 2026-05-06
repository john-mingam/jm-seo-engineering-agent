<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Export reads custom plugin tables.
class JM_SEO_PDF_Generator {
    public function generate(int $scan_id): string {
        global $wpdb;
        if(!is_dir(JM_SEO_AGENT_REPORTS_PATH)) wp_mkdir_p(JM_SEO_AGENT_REPORTS_PATH);
        $scan=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id=%d",$scan_id));
        $pages=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id=%d ORDER BY page_score ASC LIMIT 20",$scan_id));
        $issues=$wpdb->get_results($wpdb->prepare("SELECT issue_type, issue_message, severity FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id=%d ORDER BY id DESC LIMIT 20",$scan_id));
        $file=JM_SEO_AGENT_REPORTS_PATH.'jm-seo-report-'.$scan_id.'.pdf';
        $lines = [];
        $lines[] = 'JM SEO Engineering Agent - Rapport';
        $lines[] = 'Client: '.JM_SEO_Settings::client_name();
        $lines[] = 'Site: '.home_url('/');
        $lines[] = 'Score global: '.(int) $scan->site_score.'/100';
        $lines[] = 'SFT (Structure): '.(int) $scan->sft_score.'/100';
        $lines[] = 'Bloquants: '.(int) $scan->blocking_issues.'  Warnings: '.(int) $scan->warnings.'  Infos: '.(int) $scan->infos;
        $lines[] = 'Pages critiques';
        foreach ($pages as $page) {
            $lines[] = $page->url.' | HTTP '.$page->http_code.' | Score '.$page->page_score.' | S '.$page->structure_score.' F '.$page->flow_score.' T '.$page->trust_score;
        }
        $lines[] = 'Derniers problemes';
        foreach ($issues as $issue) {
            $lines[] = '['.$issue->severity.'] '.$issue->issue_type.' - '.$issue->issue_message;
        }
        file_put_contents($file, $this->build_pdf($lines));
        return $file;
    }

    private function build_pdf(array $lines): string {
        $content = "BT /F1 12 Tf 40 800 Td";
        $first = true;
        foreach ($lines as $line) {
            $escaped = $this->escape((string) $line);
            $content .= $first ? ' (' . $escaped . ') Tj' : ' T* (' . $escaped . ') Tj';
            $first = false;
        }
        $content .= ' ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= sprintf("%010d %05d f \n", 0, 65535);
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d %05d n \n", $offsets[$i], 0);
        }
        $pdf .= 'trailer' . "\n<< /Size " . (count($objects) + 1) . ' /Root 1 0 R >>' . "\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function escape(string $value): string {
        $value = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
        return preg_replace('/[^\x20-\x7E]/', '', $value) ?: '';
    }
}
