<?php
if (!defined('ABSPATH')) exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Crawler persists scan data in custom plugin tables.

class JM_SEO_Crawler {
    const START_HOOK = 'jm_seo_agent_start_scan';
    const BATCH_HOOK = 'jm_seo_agent_process_batch';

    public static function schedule(): void {
        if (!wp_next_scheduled(self::START_HOOK)) wp_schedule_event(time()+300, 'daily', self::START_HOOK);
    }

    public static function unschedule(): void {
        $timestamp = wp_next_scheduled(self::START_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::START_HOOK);
        }
        $batch = wp_next_scheduled(self::BATCH_HOOK);
        if ($batch) {
            wp_unschedule_event($batch, self::BATCH_HOOK);
        }
    }

    public static function run_scheduled_scan(): void {
        (new self())->start('full');
    }

    public static function run_batch(): void {
        (new self())->process_batch();
    }

    public function start(string $type='full'): int {
        global $wpdb;
        JM_SEO_Database::install();
        $wpdb->insert($wpdb->prefix.'jm_seo_scans', ['site_url'=>home_url('/'),'started_at'=>current_time('mysql'),'scan_type'=>$type,'status'=>'running']);
        $scan_id=(int)$wpdb->insert_id;
        $urls=(new JM_SEO_URL_Discovery())->discover();
        $queue = new JM_SEO_Queue();
        foreach($urls as $entry){
            if(!is_array($entry) || empty($entry['url'])) continue;
            if($this->excluded($entry['url'])) continue;
            $queue->enqueue($scan_id, $entry['url'], (int) ($entry['priority'] ?? 10), (int) ($entry['depth'] ?? 0), (string) ($entry['source'] ?? 'sitemap'));
        }
        JM_SEO_Logs::add('info','crawler','Scan '.$scan_id.' started with '.count($urls).' discovered URLs');
        if (!wp_next_scheduled(self::BATCH_HOOK)) wp_schedule_single_event(time()+30,self::BATCH_HOOK);

        // Manual/API scans should begin processing immediately instead of waiting for cron.
        if (in_array($type, ['manual', 'api'], true)) {
            $this->process_batch($scan_id, 3);
        }

        return $scan_id;
    }

    public function process_batch(?int $scan_id = null, int $max_batches = 1): void {
        global $wpdb;
        $queue = new JM_SEO_Queue();
        $scanner = new JM_SEO_Scanner();
        $scorer = new JM_SEO_Score_Engine();

        for ($batch = 0; $batch < $max_batches; $batch++) {
            $scan = $scan_id
                ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE id = %d", $scan_id))
                : $wpdb->get_row("SELECT * FROM {$wpdb->prefix}jm_seo_scans WHERE status='running' ORDER BY id DESC LIMIT 1");

            if (!$scan) {
                return;
            }

            $rows = $queue->next_batch((int) $scan->id, JM_SEO_Settings::batch_size());
            if (!$rows) {
                $this->finish((int) $scan->id);
                return;
            }

            foreach ($rows as $row) {
                $scan_result = $scanner->scan($row->url);
                $scores = $scorer->score_page($scan_result['issues']);
                $wpdb->insert($wpdb->prefix.'jm_seo_scan_pages', [
                    'scan_id' => $scan->id,
                    'url' => $row->url,
                    'http_code' => $scan_result['http_code'],
                    'page_score' => $scores['global'],
                    'structure_score' => $scores['structure'],
                    'flow_score' => $scores['flow'],
                    'trust_score' => $scores['trust'],
                    'crawl_depth' => $row->depth,
                    'scanned_at' => current_time('mysql'),
                ]);
                $page_id = (int) $wpdb->insert_id;

                foreach ($scan_result['issues'] as $issue) {
                    $wpdb->insert($wpdb->prefix.'jm_seo_scan_issues', [
                        'scan_id' => $scan->id,
                        'page_id' => $page_id,
                        'severity' => $issue['severity'],
                        'issue_type' => $issue['type'],
                        'issue_message' => $issue['message'],
                        'suggestion' => $issue['suggestion'] ?? '',
                        'created_at' => current_time('mysql'),
                    ]);
                }

                $queue->mark_done((int) $row->id);

                if ((int) $row->depth < JM_SEO_Settings::max_depth()) {
                    $queue->enqueue_discovered((int) $scan->id, (string) $row->url, (int) $row->depth);
                }

                JM_SEO_Alert_Manager::maybe_send((int) $scan->id, $page_id, $scan_result['issues']);
            }

            $pending = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_queue WHERE scan_id = %d AND status = 'pending'",
                $scan->id
            ));

            if ($pending <= 0) {
                $this->finish((int) $scan->id);
                return;
            }

            if (!wp_next_scheduled(self::BATCH_HOOK)) {
                wp_schedule_single_event(time() + 90, self::BATCH_HOOK);
            }
        }
    }

    private function finish(int $scan_id): void {
        global $wpdb;
        $pages=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id=%d",$scan_id));
        $avg=(int)$wpdb->get_var($wpdb->prepare("SELECT AVG(page_score) FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id=%d",$scan_id));
        $sft=(int)$wpdb->get_var($wpdb->prepare("SELECT AVG((structure_score+flow_score+trust_score)/3) FROM {$wpdb->prefix}jm_seo_scan_pages WHERE scan_id=%d",$scan_id));
        $b=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id=%d AND severity='bloquant'",$scan_id));
        $w=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id=%d AND severity='warning'",$scan_id));
        $i=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}jm_seo_scan_issues WHERE scan_id=%d AND severity='info'",$scan_id));
        $wpdb->update($wpdb->prefix.'jm_seo_scans',['finished_at'=>current_time('mysql'),'pages_scanned'=>$pages,'site_score'=>$avg,'sft_score'=>$sft,'blocking_issues'=>$b,'warnings'=>$w,'infos'=>$i,'status'=>'finished'],['id'=>$scan_id]);
        JM_SEO_Logs::add('info','crawler','Scan '.$scan_id.' finished');
        (new JM_SEO_Email_Reporter())->send($scan_id);
    }

    public function excluded(string $url): bool {
        $patterns=['/wp-admin/','/wp-login.php','/cart/','/checkout/','/my-account/','?utm_','?replytocom='];
        foreach($patterns as $p){ if(str_contains($url,$p)) return true; }
        return false;
    }
}
