<?php
/**
 * SEO Plugin Conflict Detector
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Plugin_Conflict_Detector {

    /**
     * Detect SEO plugin conflicts
     *
     * @return array Issues detected
     */
    public static function detect(): array {
        $issues = [];

        // Active plugins check
        $active_plugins = get_option('active_plugins', []);

        // Yoast SEO
        $yoast_active = in_array('wordpress-seo/wp-seo.php', $active_plugins, true);
        $yoast_premium = in_array('wordpress-seo-premium/wp-seo-premium.php', $active_plugins, true);

        // RankMath
        $rankmath_active = in_array('seo-by-rank-math/rank-math.php', $active_plugins, true);
        $rankmath_pro = in_array('seo-by-rank-math-pro/rank-math.php', $active_plugins, true);

        // AIOSEO
        $aioseo_active = in_array('all-in-one-seo/all-in-one-seo.php', $active_plugins, true);

        // Semrush
        $semrush_active = in_array('semrush-seo-writing-assistant/semrush-seo-writing-assistant.php', $active_plugins, true);

        // Conflict detection
        $seo_plugins = [
            'Yoast SEO' => ($yoast_active || $yoast_premium),
            'Rank Math' => ($rankmath_active || $rankmath_pro),
            'AIOSEO' => $aioseo_active,
            'Semrush' => $semrush_active,
        ];

        $active_count = array_reduce($seo_plugins, function ($count, $active) {
            return $active ? $count + 1 : $count;
        }, 0);

        if ($active_count > 1) {
            $active_names = array_keys(array_filter($seo_plugins));
            $issues[] = [
                'severity' => 'bloquant',
                'type' => 'seo_plugin_conflict',
                'message' => sprintf(
                    'Conflits détectés: %s sont actifs simultanément',
                    implode(', ', $active_names)
                ),
                'suggestion' => 'Désactivez tous les plugins SEO sauf un. Les doublons de métadonnées nuisent au crawl Google.',
            ];
        }

        // Meta tag duplication check
        $meta_duplicates = self::check_meta_duplication();
        if (!empty($meta_duplicates)) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'meta_tag_duplication',
                'message' => 'Tags meta dupliqués détectés: ' . implode(', ', $meta_duplicates),
                'suggestion' => 'Vérifiez qu\'un seul plugin génère les métadonnées.',
            ];
        }

        return $issues;
    }

    /**
     * Check for meta tag duplication in database
     *
     * @return array Duplicate meta keys found
     */
    private static function check_meta_duplication(): array {
        global $wpdb;

        $duplicate_metas = [
            'yoast_wpseo_title',
            'yoast_wpseo_metadesc',
            'rank_math_title',
            'rank_math_description',
            '_aioseo_title',
            '_aioseo_description',
        ];

        $duplicates = [];

        // Check sample posts for duplicate meta
        $posts = get_posts([
            'posts_per_page' => 5,
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        foreach ($posts as $post) {
            $found_metas = [];
            foreach ($duplicate_metas as $meta_key) {
                $meta_value = get_post_meta($post->ID, $meta_key, true);
                if (!empty($meta_value)) {
                    $found_metas[] = $meta_key;
                }
            }

            if (count($found_metas) > 1) {
                $duplicates = array_merge($duplicates, $found_metas);
            }
        }

        return array_unique($duplicates);
    }
}
