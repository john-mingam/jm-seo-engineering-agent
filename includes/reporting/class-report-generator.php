<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Report_Generator {
    public function generate_bundle(int $scan_id): array {
        return [
            'csv' => (new JM_SEO_CSV_Generator())->generate($scan_id),
            'pdf' => (new JM_SEO_PDF_Generator())->generate($scan_id),
            'json' => (new JM_SEO_JSON_Exporter())->generate($scan_id),
        ];
    }
}
