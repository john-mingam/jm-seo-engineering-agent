<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_AI_Visibility_Scanner {
    public function scan(DOMDocument $dom, DOMXPath $xpath, string $html, string $url): array {
        $issues = [];
        $title = '';
        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            $title = trim((string) $titles->item(0)->textContent);
        }

        if ($title === '' || strlen($title) < 10) {
            $issues[] = ['severity' => 'info', 'type' => 'entity_ambiguous', 'message' => 'Sujet potentiellement ambigu.', 'suggestion' => 'Rendre le title et le H1 plus explicites.'];
        }

        if (!str_contains(strtolower($html), 'organization') && !str_contains(strtolower($html), 'author')) {
            $issues[] = ['severity' => 'info', 'type' => 'entity_main_absent', 'message' => 'Entité principale ou signal d auteur absent.', 'suggestion' => 'Ajouter auteur, organisation et contexte éditorial.'];
        }

        if (!str_contains(strtolower($html), 'sameas')) {
            $issues[] = ['severity' => 'info', 'type' => 'semantic_linking_weak', 'message' => 'Relations d entités faibles.', 'suggestion' => 'Renforcer les liens sameAs, author et organization.'];
        }

        return $issues;
    }
}
