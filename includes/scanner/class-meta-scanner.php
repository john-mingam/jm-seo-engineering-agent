<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Meta_Scanner {
    public function scan(DOMDocument $dom, DOMXPath $xpath, string $url, string $html, $headers = null): array {
        $issues = [];
        $titles = $dom->getElementsByTagName('title');
        if ($titles->length === 0 || trim($titles->item(0)->textContent) === '') {
            $issues[] = ['severity' => 'warning', 'type' => 'missing_title', 'message' => 'Balise title manquante ou vide.', 'suggestion' => 'Vérifier wp_head, thème ou plugin SEO.'];
        }

        $description = $xpath->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]');
        if ($description->length === 0 || trim($description->item(0)->getAttribute('content')) === '') {
            $issues[] = ['severity' => 'warning', 'type' => 'missing_meta_description', 'message' => 'Meta description manquante ou vide.', 'suggestion' => 'Ajouter une meta description unique.'];
        }

        $h1 = $dom->getElementsByTagName('h1');
        if ($h1->length === 0) {
            $issues[] = ['severity' => 'warning', 'type' => 'missing_h1', 'message' => 'Aucun H1 détecté.', 'suggestion' => 'Ajouter un H1 unique.'];
        } elseif ($h1->length > 1) {
            $issues[] = ['severity' => 'info', 'type' => 'multiple_h1', 'message' => $h1->length . ' H1 détectés.', 'suggestion' => 'Vérifier la hiérarchie éditoriale.'];
        }

        $canonical = $xpath->query('//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]');
        if ($canonical->length === 0) {
            $issues[] = ['severity' => 'warning', 'type' => 'missing_canonical', 'message' => 'Canonical manquante.', 'suggestion' => 'Ajouter une canonical.'];
        } else {
            $canonical_href = trim($canonical->item(0)->getAttribute('href'));
            if ($canonical_href === '') {
                $issues[] = ['severity' => 'warning', 'type' => 'empty_canonical', 'message' => 'Canonical vide.', 'suggestion' => 'Corriger la balise canonical.'];
            } elseif (!$this->same_host($canonical_href, $url)) {
                $issues[] = ['severity' => 'warning', 'type' => 'external_canonical', 'message' => 'Canonical externe détectée : ' . $canonical_href, 'suggestion' => 'Vérifier si la canonical doit pointer vers un autre domaine.'];
            } elseif (!$this->equivalent_url($canonical_href, $url)) {
                $issues[] = ['severity' => 'warning', 'type' => 'self_contradictory_canonical', 'message' => 'Canonical potentiellement contradictoire : ' . $canonical_href, 'suggestion' => 'Aligner canonical, URL courante et variantes trailing slash.'];
            }
        }

        $robots = $xpath->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]');
        foreach ($robots as $robot) {
            $content = strtolower((string) $robot->getAttribute('content'));
            if (str_contains($content, 'noindex')) {
                $issues[] = ['severity' => 'bloquant', 'type' => 'noindex_detected', 'message' => 'Meta robots noindex détectée.', 'suggestion' => 'Vérifier si cette page doit être indexable.'];
            }
            if (str_contains($content, 'nofollow')) {
                $issues[] = ['severity' => 'warning', 'type' => 'nofollow_detected', 'message' => 'Meta robots nofollow détectée.', 'suggestion' => 'Vérifier le besoin de nofollow global.'];
            }
        }

        if ($headers && method_exists($headers, 'getAll')) {
            $x_robots = implode(',', $headers->getAll('x-robots-tag'));
            if ($x_robots !== '' && str_contains(strtolower($x_robots), 'noindex')) {
                $issues[] = ['severity' => 'bloquant', 'type' => 'header_noindex_detected', 'message' => 'X-Robots-Tag noindex détecté.', 'suggestion' => 'Retirer le noindex côté en-tête si la page doit être indexée.'];
            }
        }

        $rb = new JM_SEO_Robots_Parser();
        if (!$rb->is_allowed($url)) {
            $issues[] = ['severity' => 'bloquant', 'type' => 'robots_blocked', 'message' => 'URL bloquée par robots.txt.', 'suggestion' => 'Vérifier robots.txt.'];
        }

        $date_markers = ['31 Déc 1969', '31 Dec 1969', '01 Jan 1970', '1970'];
        foreach ($date_markers as $marker) {
            if (stripos($html, $marker) !== false) {
                $issues[] = ['severity' => 'bloquant', 'type' => 'invalid_date', 'message' => 'Date invalide détectée : ' . $marker, 'suggestion' => 'Probable timestamp vide ou bug template.'];
                break;
            }
        }

        $missing_alt = 0;
        foreach ($dom->getElementsByTagName('img') as $img) {
            if (!$img->hasAttribute('alt') || trim($img->getAttribute('alt')) === '') {
                $missing_alt++;
            }
        }
        if ($missing_alt > 0) {
            $issues[] = ['severity' => 'info', 'type' => 'missing_alt', 'message' => $missing_alt . ' image(s) sans alt.', 'suggestion' => 'Ajouter des alt descriptifs aux images importantes.'];
        }

        if (stripos($html, 'yoast') !== false || stripos($html, 'rank math') !== false || stripos($html, 'aioseo') !== false) {
            $issues[] = ['severity' => 'warning', 'type' => 'seo_plugin_conflict', 'message' => 'Marqueur de conflit SEO détecté.', 'suggestion' => 'Vérifier les doublons de balises meta et les plugins SEO actifs.'];
        }

        return $issues;
    }

    private function same_host(string $left, string $right): bool {
        return wp_parse_url($left, PHP_URL_HOST) === wp_parse_url($right, PHP_URL_HOST);
    }

    private function equivalent_url(string $left, string $right): bool {
        return untrailingslashit($left) === untrailingslashit($right);
    }
}
