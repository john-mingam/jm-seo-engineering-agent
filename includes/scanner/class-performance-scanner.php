<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Performance_Scanner {
    public function scan(string $html, $headers = null): array {
        $issues = [];
        $size = strlen($html);
        if ($size > 250000) {
            $issues[] = ['severity' => 'info', 'type' => 'large_html_payload', 'message' => 'HTML volumineux détecté.', 'suggestion' => 'Réduire le poids des templates et blocs.'];
        }

        if (preg_match_all('/<script\b/i', $html, $matches) && count($matches[0]) > 20) {
            $issues[] = ['severity' => 'info', 'type' => 'too_many_scripts', 'message' => 'Nombre élevé de scripts détecté.', 'suggestion' => 'Limiter les scripts tiers et les doublons.'];
        }

        return $issues;
    }
}
