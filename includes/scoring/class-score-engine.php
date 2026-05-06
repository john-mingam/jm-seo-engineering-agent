<?php
/**
 * Enhanced Score Engine using SFT Weighting
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Score_Engine {

    /**
     * Score page from issues with SFT (Structure) (Structure) weighting
     *
     * @param array $issues Array of issues
     * @return array Scores: global, structure, flow, trust
     */
    public function score_page(array $issues): array {
        // Use SFT (Structure) (Structure) Weighting system
        $component_scores = JM_SEO_SFT_Weighting::calculate_component_scores($issues);
        $global_score = JM_SEO_SFT_Weighting::calculate_global_score($component_scores);

        return [
            'global' => $global_score,
            'structure' => $component_scores['structure'],
            'flow' => $component_scores['flow'],
            'trust' => $component_scores['trust'],
            'components' => $component_scores,
        ];
    }

    /**
     * Score site from page scores
     *
     * @param array $page_scores Array of page scores
     * @return array Site-wide scores
     */
    public function score_site(array $page_scores): array {
        if (empty($page_scores)) {
            return [
                'global' => 100,
                'structure' => 100,
                'flow' => 100,
                'trust' => 100,
                'pages_analyzed' => 0,
            ];
        }

        $total_global = 0;
        $total_structure = 0;
        $total_flow = 0;
        $total_trust = 0;

        foreach ($page_scores as $score) {
            $total_global += $score['global'] ?? 100;
            $total_structure += $score['structure'] ?? 100;
            $total_flow += $score['flow'] ?? 100;
            $total_trust += $score['trust'] ?? 100;
        }

        $count = count($page_scores);

        return [
            'global' => intval($total_global / $count),
            'structure' => intval($total_structure / $count),
            'flow' => intval($total_flow / $count),
            'trust' => intval($total_trust / $count),
            'pages_analyzed' => $count,
        ];
    }

    /**
     * Get severity summary from issues
     *
     * @param array $issues Array of issues
     * @return array Summary: bloquant, warning, info counts
     */
    public function count_issues_by_severity(array $issues): array {
        $counts = [
            'bloquant' => 0,
            'warning' => 0,
            'info' => 0,
        ];

        foreach ($issues as $issue) {
            $severity = $issue['severity'] ?? 'info';
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
        }

        return $counts;
    }

    /**
     * Get issue summary by type
     *
     * @param array $issues Array of issues
     * @return array Summary by type
     */
    public function summarize_issues(array $issues): array {
        $summary = [];

        foreach ($issues as $issue) {
            $type = $issue['type'] ?? 'unknown';
            if (!isset($summary[$type])) {
                $summary[$type] = [
                    'count' => 0,
                    'severity' => $issue['severity'] ?? 'info',
                ];
            }
            $summary[$type]['count']++;
        }

        return $summary;
    }
}
