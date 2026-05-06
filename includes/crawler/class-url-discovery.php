<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_URL_Discovery {
    public function discover(): array {
        $items = [];
        $push = function (string $url, int $priority, int $depth, string $source) use (&$items): void {
            $url = esc_url_raw($url);
            if (!$url) {
                return;
            }
            $items[] = [
                'url' => $url,
                'priority' => $priority,
                'depth' => $depth,
                'source' => $source,
            ];
        };

        foreach ((new JM_SEO_Sitemap_Parser())->get_urls() as $url) {
            $push($url, 1, 0, 'sitemap');
        }

        foreach (JM_SEO_Client_Config::strategic_urls() as $url) {
            $push($url, 2, 0, 'strategic');
        }

        $public_post_types = get_post_types(['public' => true], 'names');
        foreach ($public_post_types as $post_type) {
            $recent_posts = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => 15,
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            foreach ($recent_posts as $post) {
                $permalink = get_permalink($post);
                if ($permalink) {
                    $push($permalink, 3, 0, 'recent');
                }
            }
        }

        foreach ($this->traffic_pages() as $url) {
            $push($url, 4, 0, 'traffic');
        }

        foreach (JM_SEO_History::issue_urls(25) as $url) {
            $push($url, 5, 0, 'historical');
        }

        $push(home_url('/'), 2, 0, 'fallback');

        $items = array_values(array_unique($items, SORT_REGULAR));
        usort($items, static function (array $left, array $right): int {
            $left_key = [$left['priority'], $left['depth'], $left['url']];
            $right_key = [$right['priority'], $right['depth'], $right['url']];
            return $left_key <=> $right_key;
        });

        return array_slice($items, 0, JM_SEO_Settings::crawl_limit());
    }

    private function traffic_pages(): array {
        $urls = [];
        $meta_keys = ['post_views_count', '_views', 'views'];
        $posts = get_posts([
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => 40,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        foreach ($posts as $post) {
            foreach ($meta_keys as $meta_key) {
                $views = (int) get_post_meta($post->ID, $meta_key, true);
                if ($views > 0) {
                    $urls[] = get_permalink($post);
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }
}
