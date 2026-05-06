<?php
/**
 * SFT Weighting System - Domain-Specific Score Components
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_SFT_Weighting {

    const COMPONENT_STRUCTURE = 'structure';
    const COMPONENT_FLOW = 'flow';
    const COMPONENT_TRUST = 'trust';

    /**
     * Issue type to component mapping with weights
     *
     * @return array Mapping with format: 'issue_type' => ['component' => weight]
     */
    public static function get_issue_weights(): array {
        return [
            // Structure issues (30% of total score)
            'missing_title' => [self::COMPONENT_STRUCTURE => 25],
            'empty_title' => [self::COMPONENT_STRUCTURE => 15],
            'missing_meta_description' => [self::COMPONENT_STRUCTURE => 20],
            'empty_meta_description' => [self::COMPONENT_STRUCTURE => 10],
            'missing_h1' => [self::COMPONENT_STRUCTURE => 20],
            'multiple_h1' => [self::COMPONENT_STRUCTURE => 15],
            'missing_canonical' => [self::COMPONENT_STRUCTURE => 20],
            'external_canonical' => [self::COMPONENT_STRUCTURE => 25],
            'multiple_canonicals' => [self::COMPONENT_STRUCTURE => 25],
            'canonical_target_error' => [self::COMPONENT_STRUCTURE => 15],
            'missing_json_ld' => [self::COMPONENT_STRUCTURE => 20],
            'invalid_json_ld' => [self::COMPONENT_STRUCTURE => 18],
            'json_ld_raw_text' => [self::COMPONENT_STRUCTURE => 15],
            'http_error' => [self::COMPONENT_STRUCTURE => 30],

            // Flow issues (30% of total score)
            'missing_images_alt' => [self::COMPONENT_FLOW => 10],
            'broken_internal_links' => [self::COMPONENT_FLOW => 20],
            'sitemap_missing' => [self::COMPONENT_FLOW => 15],
            'sitemap_error' => [self::COMPONENT_FLOW => 18],
            'robots_txt_blocking' => [self::COMPONENT_FLOW => 20],
            'weak_maillage' => [self::COMPONENT_FLOW => 15],
            'too_deep_crawl' => [self::COMPONENT_FLOW => 12],

            // Trust issues (40% of total score)
            'missing_author' => [self::COMPONENT_TRUST => 15],
            'missing_organization' => [self::COMPONENT_TRUST => 15],
            'missing_sameas' => [self::COMPONENT_TRUST => 12],
            'missing_date' => [self::COMPONENT_TRUST => 10],
            'missing_breadcrumb' => [self::COMPONENT_TRUST => 10],
            'seo_plugin_conflict' => [self::COMPONENT_TRUST => 20],
            'meta_tag_duplication' => [self::COMPONENT_TRUST => 12],
            'noindex_detected' => [self::COMPONENT_TRUST => 25],

            // Mixed (all components affected)
            'schema_advanced_error' => [
                self::COMPONENT_STRUCTURE => 12,
                self::COMPONENT_TRUST => 12,
            ],
        ];
    }

    /**
     * Get component weights (relative importance)
     *
     * @return array Component weights: structure=>0.3, flow=>0.3, trust=>0.4
     */
    public static function get_component_weights(): array {
        return [
            self::COMPONENT_STRUCTURE => 0.30,
            self::COMPONENT_FLOW => 0.30,
            self::COMPONENT_TRUST => 0.40,
        ];
    }

    /**
     * Calculate component scores from issues
     *
     * @param array $issues Array of issue arrays with 'severity' and 'type' keys
     * @return array Component scores: ['structure' => 85, 'flow' => 78, 'trust' => 92]
     */
    public static function calculate_component_scores(array $issues): array {
        $weights = self::get_issue_weights();
        $component_scores = [
            self::COMPONENT_STRUCTURE => 100,
            self::COMPONENT_FLOW => 100,
            self::COMPONENT_TRUST => 100,
        ];

        foreach ($issues as $issue) {
            $issue_type = $issue['type'] ?? 'unknown';
            $severity = $issue['severity'] ?? 'info';

            // Severity multiplier
            $multiplier = self::get_severity_multiplier($severity);

            // Get weights for this issue type
            $issue_weights = $weights[$issue_type] ?? [];

            foreach ($issue_weights as $component => $base_weight) {
                $penalty = $base_weight * $multiplier;
                $component_scores[$component] = max(0, $component_scores[$component] - $penalty);
            }
        }

        return [
            'structure' => intval($component_scores[self::COMPONENT_STRUCTURE]),
            'flow' => intval($component_scores[self::COMPONENT_FLOW]),
            'trust' => intval($component_scores[self::COMPONENT_TRUST]),
        ];
    }

    /**
     * Calculate global score from component scores
     *
     * @param array $component_scores Component scores with keys: structure, flow, trust
     * @return int Global score (0-100)
     */
    public static function calculate_global_score(array $component_scores): int {
        $weights = self::get_component_weights();

        $global_score =
            ($component_scores['structure'] ?? 100) * $weights[self::COMPONENT_STRUCTURE] +
            ($component_scores['flow'] ?? 100) * $weights[self::COMPONENT_FLOW] +
            ($component_scores['trust'] ?? 100) * $weights[self::COMPONENT_TRUST];

        return intval($global_score);
    }

    /**
     * Get severity multiplier
     *
     * @param string $severity Severity level: bloquant|warning|info
     * @return float Multiplier
     */
    private static function get_severity_multiplier(string $severity): float {
        return match ($severity) {
            'bloquant' => 1.5, // 150% weight
            'warning' => 1.0,  // 100% weight
            'info' => 0.3,     // 30% weight
            default => 1.0,
        };
    }
}
