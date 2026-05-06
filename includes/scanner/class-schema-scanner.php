<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Schema_Scanner {
    public function scan(DOMXPath $xpath, string $html, string $url): array {
        $issues = [];
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        if ($scripts->length === 0) {
            $issues[] = ['severity' => 'warning', 'type' => 'missing_json_ld', 'message' => 'Aucun JSON-LD détecté.', 'suggestion' => 'Ajouter un Schema.org adapté.'];
        }

        $has_author = false;
        $has_org = false;
        $has_same_as = false;
        $has_breadcrumb = false;
        $has_faq = false;
        $has_article = false;
        $types = [];

        foreach ($scripts as $script) {
            $json = trim($script->textContent);
            if ($json === '') {
                $issues[] = ['severity' => 'bloquant', 'type' => 'empty_json_ld', 'message' => 'JSON-LD vide.', 'suggestion' => 'Corriger la génération Schema.'];
                continue;
            }

            json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $issues[] = ['severity' => 'bloquant', 'type' => 'invalid_json_ld', 'message' => 'JSON-LD invalide : ' . json_last_error_msg(), 'suggestion' => 'Corriger guillemets, virgules ou échappements.'];
                continue;
            }

            if (!str_contains($json, '@type')) {
                $issues[] = ['severity' => 'warning', 'type' => 'schema_type_missing', 'message' => 'JSON-LD sans @type détecté.', 'suggestion' => 'Définir le type Schema principal.'];
            }

            if (preg_match_all('/"@type"\s*:\s*"([^"]+)"/i', $json, $matches)) {
                $types = array_merge($types, $matches[1]);
            }

            $has_author = $has_author || stripos($json, 'author') !== false;
            $has_org = $has_org || stripos($json, 'organization') !== false;
            $has_same_as = $has_same_as || stripos($json, 'sameAs') !== false;
            $has_breadcrumb = $has_breadcrumb || stripos($json, 'breadcrumb') !== false;
            $has_faq = $has_faq || stripos($json, 'faqpage') !== false || stripos($json, 'faq') !== false;
            $has_article = $has_article || stripos($json, 'article') !== false;
        }

        if (str_contains($html, '&quot;@context&quot;')) {
            $issues[] = ['severity' => 'bloquant', 'type' => 'json_ld_raw', 'message' => 'JSON-LD probablement affiché en texte brut.', 'suggestion' => 'Rendre le JSON-LD dans une vraie balise script.'];
        }

        if (!$has_author) {
            $issues[] = ['severity' => 'warning', 'type' => 'schema_author_missing', 'message' => 'Auteur absent du Schema.', 'suggestion' => 'Ajouter author pour renforcer la confiance.'];
        }
        if (!$has_org) {
            $issues[] = ['severity' => 'warning', 'type' => 'schema_organization_missing', 'message' => 'Organization absente du Schema.', 'suggestion' => 'Déclarer Organization dans le JSON-LD.'];
        }
        if (!$has_same_as) {
            $issues[] = ['severity' => 'info', 'type' => 'schema_sameas_missing', 'message' => 'sameAs absent.', 'suggestion' => 'Ajouter les profils officiels.'];
        }
        if (!$has_breadcrumb) {
            $issues[] = ['severity' => 'info', 'type' => 'schema_breadcrumb_missing', 'message' => 'Breadcrumb manquant.', 'suggestion' => 'Ajouter BreadcrumbList si pertinent.'];
        }
        if ($has_faq && !str_contains($html, 'FAQPage')) {
            $issues[] = ['severity' => 'warning', 'type' => 'schema_faq_invalid', 'message' => 'FAQ détectée mais structure Schema ambiguë.', 'suggestion' => 'Valider FAQPage et les paires question/réponse.'];
        }
        if (str_contains($html, '/article') && !$has_article) {
            $issues[] = ['severity' => 'info', 'type' => 'schema_article_missing', 'message' => 'Article mal structuré ou absent.', 'suggestion' => 'Ajouter Article/BlogPosting si la page est un contenu éditorial.'];
        }
        if (!$types) {
            $issues[] = ['severity' => 'info', 'type' => 'schema_type_absent', 'message' => 'Type Schema absent.', 'suggestion' => 'Déclarer un type principal explicite.'];
        }

        return $issues;
    }
}
