<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Site_Score {
    public function from_pages(array $page_scores): array {
        if (!$page_scores) {
            return ['site_score' => 100, 'sft_score' => 100];
        }

        $count = count($page_scores);
        $score = 0;
        $structure = 0;
        $flow = 0;
        $trust = 0;

        foreach ($page_scores as $page_score) {
            $normalized = (new JM_SEO_SFT_Score())->normalize($page_score);
            $score += (int) ($page_score['page_score'] ?? 100);
            $structure += (int) $normalized['structure_score'];
            $flow += (int) $normalized['flow_score'];
            $trust += (int) $normalized['trust_score'];
        }

        return [
            'site_score' => (int) round($score / $count),
            'sft_score' => (int) round((($structure / $count) + ($flow / $count) + ($trust / $count)) / 3),
        ];
    }
}
