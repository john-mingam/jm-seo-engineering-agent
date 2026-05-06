<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_SFT_Score {
    public function normalize(array $page_scores): array {
        $defaults = ['structure_score' => 100, 'flow_score' => 100, 'trust_score' => 100];
        return array_merge($defaults, $page_scores);
    }
}
