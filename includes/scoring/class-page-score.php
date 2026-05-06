<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Page_Score {
    public function from_issues(array $issues): array {
        $page = 100;
        $structure = 100;
        $flow = 100;
        $trust = 100;

        foreach ($issues as $issue) {
            $severity = $issue['severity'] ?? 'info';
            $type = strtolower((string) ($issue['type'] ?? ''));
            $penalty = match ($severity) {
                'bloquant' => 20,
                'warning' => 8,
                default => 2,
            };

            $page -= $penalty;

            if (str_contains($type, 'schema') || str_contains($type, 'json') || str_contains($type, 'title') || str_contains($type, 'canonical') || str_contains($type, 'robots') || str_contains($type, 'h1') || str_contains($type, 'meta')) {
                $structure -= $penalty;
            } elseif (str_contains($type, 'link') || str_contains($type, 'sitemap') || str_contains($type, 'crawl')) {
                $flow -= $penalty;
            } else {
                $trust -= $penalty;
            }
        }

        return [
            'page_score' => max(0, min(100, $page)),
            'structure_score' => max(0, min(100, $structure)),
            'flow_score' => max(0, min(100, $flow)),
            'trust_score' => max(0, min(100, $trust)),
        ];
    }
}
