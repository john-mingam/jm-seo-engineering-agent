<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Queue storage uses custom plugin tables.

class JM_SEO_Queue {
    public function enqueue(int $scan_id, string $url, int $priority = 10, int $depth = 0, string $source = 'sitemap'): void {
        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id=%d",
            $scan_id
        ));
        if ($count >= JM_SEO_Settings::crawl_limit()) {
            return;
        }
        $exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id=%d AND url=%s AND status IN ('pending', 'done')",
            $scan_id,
            $url
        ));
        if ($exists > 0) {
            return;
        }
        $wpdb->insert($wpdb->prefix . 'jm_seo_queue', [
            'scan_id' => $scan_id,
            'url' => $url,
            'status' => 'pending',
            'priority' => $priority,
            'depth' => $depth,
            'source' => $source,
            'created_at' => current_time('mysql'),
        ]);
    }

    public function next_batch(int $scan_id, int $limit): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id=%d AND status='pending' ORDER BY priority ASC, depth ASC, id ASC LIMIT %d",
            $scan_id,
            $limit
        ));
    }

    public function mark_done(int $queue_id): void {
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'jm_seo_queue', [
            'status' => 'done',
            'processed_at' => current_time('mysql'),
        ], ['id' => $queue_id]);
    }

    public function enqueue_discovered(int $scan_id, string $source_url, int $current_depth): void {
        $extractor = new JM_SEO_Link_Extractor();
        $links = $extractor->extract($source_url);
        $crawler = new JM_SEO_Crawler();
        foreach ($links as $link) {
            if ($crawler->excluded($link)) {
                continue;
            }
            $priority = $extractor->priority($link, $current_depth + 1);
            $this->enqueue($scan_id, $link, $priority, $current_depth + 1, 'discovered');
        }
    }
}
