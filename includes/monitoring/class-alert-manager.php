<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Alert_Manager {
    public static function maybe_send(int $scan_id, int $page_id, array $issues): void {
        $critical = array_filter($issues, static fn(array $issue): bool => ($issue['severity'] ?? '') === 'bloquant');
        if (!$critical) {
            return;
        }

        $subject = '[JM SEO Agent] Alerte critique';
        $body = "Un blocant a ete detecte sur le scan #{$scan_id} / page #{$page_id}\n\n";
        foreach ($critical as $issue) {
            $body .= '- ' . ($issue['type'] ?? 'issue') . ' : ' . ($issue['message'] ?? '') . "\n";
        }

        wp_mail(JM_SEO_Settings::report_email(), $subject, $body);
    }
}
