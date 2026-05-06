<?php
/**
 * Aller Plus Loin - Advanced SEO Strategy Page
 */

if (!defined('ABSPATH')) exit;

class JM_SEO_Aller_Plus_Loin {
    
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 14);
    }

    public static function register_menu(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }
        
        add_submenu_page(
            'jm-seo-agent',
            'Aller plus loin',
            'Aller plus loin',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-aller-plus-loin',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }
        ?>
        <div class="wrap jm-seo-wrapper">
            <h1>Aller plus loin avec JM SEO Engineering</h1>
            
            <!-- Introduction -->
            <div style="background:#fff;padding:32px;margin:24px 0;border-radius:6px;border:1px solid #dcdcde;">
                <h2 style="font-size:28px;margin:0 0 24px 0;">Votre site est-il réellement compris par Google ?</h2>
                <p style="font-size:16px;line-height:1.7;color:#555;margin:0;">
                    La majorité des outils SEO détectent des erreurs. JM SEO Engineering va plus loin :
                </p>
                <ul style="margin:24px 0;font-size:15px;line-height:1.8;color:#555;">
                    <li><strong>Analyse structurelle</strong> du site</li>
                    <li><strong>Détection des signaux faibles</strong></li>
                    <li><strong>Cohérence sémantique</strong></li>
                    <li><strong>Compréhension des entités</strong></li>
                    <li><strong>Qualité du flow SEO</strong></li>
                    <li><strong>Crédibilité algorithmique</strong></li>
                </ul>
                <p style="font-size:15px;color:#666;margin:24px 0 0 0;">
                    Le plugin surveille votre site. Le système SFT aide à comprendre pourquoi certaines pages performent… et pourquoi d'autres restent invisibles.
                </p>
            </div>

            <!-- Audit SFT -->
            <div style="background:#fff;padding:32px;margin:24px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #0073aa;">
                <h2 style="font-size:24px;margin:0 0 24px 0;">📊 Audit SFT</h2>
                <h3 style="font-size:18px;color:#0073aa;margin:16px 0 12px 0;">Analyse stratégique : Structure • Flow • Trust</h3>
                
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    L'audit SFT ne se limite pas à une liste d'erreurs techniques. L'objectif est d'identifier ce qui empêche réellement Google, les moteurs IA et les systèmes de compréhension sémantique de :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>Comprendre votre expertise</li>
                    <li>Faire confiance à votre site</li>
                    <li>Relier vos contenus entre eux</li>
                    <li>Attribuer une crédibilité algorithmique à votre marque</li>
                </ul>

                <h3 style="font-size:18px;color:#0073aa;margin:24px 0 12px 0;">L'audit peut inclure :</h3>
                
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin:24px 0;">
                    <div style="background:#f8f9fa;padding:16px;border-radius:4px;border-left:4px solid #0073aa;">
                        <h4 style="margin:0 0 12px 0;color:#0073aa;">Analyse Structure</h4>
                        <ul style="margin:0;padding:0;list-style:none;font-size:13px;line-height:1.6;color:#555;">
                            <li>Architecture SEO</li>
                            <li>Maillage interne</li>
                            <li>Qualité des templates</li>
                            <li>Cohérence technique</li>
                            <li>Données structurées</li>
                        </ul>
                    </div>
                    <div style="background:#f8f9fa;padding:16px;border-radius:4px;border-left:4px solid #0073aa;">
                        <h4 style="margin:0 0 12px 0;color:#0073aa;">Analyse Flow</h4>
                        <ul style="margin:0;padding:0;list-style:none;font-size:13px;line-height:1.6;color:#555;">
                            <li>Circulation sémantique</li>
                            <li>Liens contextuels</li>
                            <li>Hiérarchie des contenus</li>
                            <li>Compréhension des entités</li>
                        </ul>
                    </div>
                    <div style="background:#f8f9fa;padding:16px;border-radius:4px;border-left:4px solid #0073aa;">
                        <h4 style="margin:0 0 12px 0;color:#0073aa;">Analyse Trust</h4>
                        <ul style="margin:0;padding:0;list-style:none;font-size:13px;line-height:1.6;color:#555;">
                            <li>Signaux de confiance</li>
                            <li>Cohérence auteur/marque</li>
                            <li>Crédibilité algorithmique</li>
                            <li>Cohérence EEAT</li>
                        </ul>
                    </div>
                </div>

                <h3 style="font-size:18px;color:#0073aa;margin:24px 0 12px 0;">Vous recevez :</h3>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>✓ Un rapport stratégique</li>
                    <li>✓ Les priorités réelles</li>
                    <li>✓ Un scoring SFT</li>
                    <li>✓ Un plan d'action concret</li>
                    <li>✓ Les risques SEO identifiés</li>
                    <li>✓ Les opportunités d'amélioration</li>
                </ul>

                <a href="https://johnmingam.com" target="_blank" class="button button-primary" style="font-size:14px;padding:10px 24px;height:auto;">
                    Demander un Audit SFT →
                </a>
            </div>

            <!-- Accompagnement SFT -->
            <div style="background:#fff;padding:32px;margin:24px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #00aa00;">
                <h2 style="font-size:24px;margin:0 0 24px 0;">🚀 Accompagnement SFT</h2>
                <h3 style="font-size:18px;color:#00aa00;margin:16px 0 12px 0;">Un accompagnement stratégique et technique</h3>
                
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Certaines problématiques SEO ne peuvent pas être résolues avec des plugins classiques. L'accompagnement SFT permet d'aller plus loin :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>Stratégie SEO avancée</li>
                    <li>Architecture sémantique</li>
                    <li>Optimisation technique</li>
                    <li>Compréhension des entités</li>
                    <li>Amélioration des signaux de confiance</li>
                    <li>Optimisation IA & moteurs de réponse</li>
                </ul>

                <h3 style="font-size:18px;color:#00aa00;margin:24px 0 12px 0;">Accompagnement possible :</h3>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin:16px 0 24px 0;font-size:14px;">
                    <div style="background:#f8f9fa;padding:12px;border-radius:4px;border-left:4px solid #00aa00;">WordPress</div>
                    <div style="background:#f8f9fa;padding:12px;border-radius:4px;border-left:4px solid #00aa00;">Shopify</div>
                    <div style="background:#f8f9fa;padding:12px;border-radius:4px;border-left:4px solid #00aa00;">Headless</div>
                    <div style="background:#f8f9fa;padding:12px;border-radius:4px;border-left:4px solid #00aa00;">Next.js</div>
                </div>

                <h3 style="font-size:18px;color:#00aa00;margin:24px 0 12px 0;">Domaines d'expertise :</h3>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li><strong>SEO technique</strong> - Architecture et optimisations avancées</li>
                    <li><strong>Entity SEO</strong> - Optimisation des données structurées</li>
                    <li><strong>Crédibilité algorithmique</strong> - Stratégie de confiance moteur</li>
                </ul>

                <h3 style="font-size:18px;color:#00aa00;margin:24px 0 12px 0;">Objectif :</h3>
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Créer un site compréhensible par :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>Google</li>
                    <li>Les IA</li>
                    <li>Les moteurs de réponse</li>
                    <li>Les systèmes de Knowledge Graph</li>
                </ul>

                <a href="https://johnmingam.com" target="_blank" class="button" style="font-size:14px;padding:10px 24px;height:auto;">
                    Découvrir l'accompagnement SFT →
                </a>
            </div>

            <!-- Rachel IA -->
            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:32px;margin:24px 0;border-radius:6px;">
                <h2 style="font-size:24px;margin:0 0 24px 0;color:#fff;">🤖 Rachel IA</h2>
                <p style="background:rgba(0,0,0,0.1);padding:12px;border-radius:4px;margin:0 0 24px 0;font-style:italic;">
                    Bientôt disponible
                </p>
                
                <p style="font-size:15px;line-height:1.7;margin:0 0 24px 0;">
                    Rachel IA est un moteur d'analyse et de correction SEO contextuel actuellement en développement.
                </p>

                <h3 style="font-size:18px;margin:24px 0 12px 0;color:#fff;">Son objectif :</h3>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;">
                    <li>Comprendre le contexte réel du site</li>
                    <li>Interpréter les signaux SEO</li>
                    <li>Prioriser les problèmes</li>
                    <li>Détecter les incohérences</li>
                    <li>Proposer des corrections pertinentes</li>
                    <li>Automatiser certaines optimisations sécurisées</li>
                </ul>

                <p style="font-size:15px;line-height:1.7;margin:0 0 24px 0;">
                    Contrairement aux assistants SEO classiques, Rachel ne se base pas uniquement sur des règles génériques. Le système cherche à comprendre :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;">
                    <li>Le CMS</li>
                    <li>Le thème</li>
                    <li>L'architecture</li>
                    <li>Les extensions</li>
                    <li>Les relations sémantiques</li>
                    <li>Les signaux de confiance</li>
                    <li>La logique structurelle du site</li>
                </ul>

                <h3 style="font-size:18px;margin:24px 0 12px 0;color:#fff;">Fonctionnalités prévues :</h3>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;">
                    <li>✓ Détection intelligente des problèmes</li>
                    <li>✓ Scoring de confiance</li>
                    <li>✓ Suggestions contextualisées</li>
                    <li>✓ Corrections automatiques sécurisées</li>
                    <li>✓ Historique et rollback</li>
                    <li>✓ Analyse Structure • Flow • Trust</li>
                </ul>

                <a href="https://johnmingam.com" target="_blank" class="button" style="background:#fff;color:#667eea;border:none;font-size:14px;padding:10px 24px;height:auto;">
                    Être informé du lancement de Rachel IA →
                </a>
            </div>

            <!-- Pourquoi JM SEO Engineering -->
            <div style="background:#fff;padding:32px;margin:24px 0;border-radius:6px;border:1px solid #dcdcde;">
                <h2 style="font-size:24px;margin:0 0 24px 0;">❓ Pourquoi JM SEO Engineering ?</h2>
                
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    <strong>Parce qu'un site techniquement "propre" n'est pas forcément compris.</strong>
                </p>

                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Aujourd'hui, les moteurs ne se contentent plus d'indexer des pages. Ils tentent de comprendre :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>Les entités</li>
                    <li>Les relations</li>
                    <li>La structure</li>
                    <li>La cohérence</li>
                    <li>La crédibilité</li>
                </ul>

                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    JM SEO Engineering a été conçu pour surveiller ces signaux et aider à construire un web plus compréhensible pour les moteurs modernes et les IA.
                </p>
            </div>

            <!-- À propos de John Mingam -->
            <div style="background:#f8f9fa;padding:32px;margin:24px 0;border-radius:6px;border:1px solid #dcdcde;">
                <h2 style="font-size:24px;margin:0 0 24px 0;">👤 À propos de John Mingam</h2>
                <p style="font-size:13px;color:#666;margin:0 0 24px 0;font-weight:600;text-transform:uppercase;">Architecte d'algorithmes & SEO Engineer</p>
                
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Je m'appelle John Mingam. Depuis plusieurs années, je travaille à l'intersection :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>Du SEO technique</li>
                    <li>Des systèmes de compréhension sémantique</li>
                    <li>Des entités</li>
                    <li>De l'automatisation</li>
                    <li>De l'IA</li>
                    <li>De l'architecture web</li>
                </ul>

                <h3 style="font-size:18px;color:#0073aa;margin:24px 0 12px 0;">Mon approche du SEO</h3>
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Ma conviction est simple : <strong>Google n'analyse plus uniquement des pages. Il tente de comprendre des entités, des relations et des signaux de confiance.</strong>
                </p>

                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    C'est cette vision qui a donné naissance à :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>La méthode SFT</li>
                    <li>JM SEO Engineering</li>
                    <li>Rachel IA</li>
                </ul>

                <h3 style="font-size:18px;color:#0073aa;margin:24px 0 12px 0;">Une approche orientée compréhension algorithmique</h3>
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 12px 0;">
                    La majorité des stratégies SEO se concentrent encore principalement sur :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>Les mots-clés</li>
                    <li>Les backlinks</li>
                    <li>Les optimisations isolées</li>
                </ul>

                <p style="font-size:15px;line-height:1.7;color:#c00;margin:0 0 24px 0;background:#fff5f5;padding:16px;border-radius:4px;">
                    <strong>Le problème :</strong> Un site peut être techniquement optimisé… tout en restant mal compris.
                </p>

                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Mon travail consiste à analyser :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;color:#555;">
                    <li>La structure réelle du site</li>
                    <li>Les relations entre contenus</li>
                    <li>Les signaux de crédibilité</li>
                    <li>La cohérence sémantique</li>
                    <li>La logique des entités</li>
                    <li>La capacité des moteurs à interpréter correctement l'expertise d'une marque</li>
                </ul>
            </div>

            <!-- Méthode SFT -->
            <div style="background:#fff;padding:32px;margin:24px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #ffc107;">
                <h2 style="font-size:24px;margin:0 0 24px 0;">📐 La Méthode SFT</h2>
                
                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    <strong>SFT signifie : Structure • Flow • Trust</strong>
                </p>

                <p style="font-size:15px;line-height:1.7;color:#555;margin:0 0 24px 0;">
                    Trois piliers utilisés pour analyser la qualité SEO globale d'un site.
                </p>

                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin:24px 0;">
                    <div style="background:#fffbf0;padding:20px;border-radius:4px;border-left:4px solid #ffc107;">
                        <h3 style="margin:0 0 12px 0;color:#ffc107;">🏗️ Structure</h3>
                        <ul style="margin:0;padding:0;list-style:none;font-size:13px;line-height:1.8;color:#555;">
                            <li>Architecture</li>
                            <li>Données structurées</li>
                            <li>Templates</li>
                            <li>Hiérarchie</li>
                            <li>Cohérence technique</li>
                        </ul>
                    </div>
                    <div style="background:#f0f8ff;padding:20px;border-radius:4px;border-left:4px solid #0073aa;">
                        <h3 style="margin:0 0 12px 0;color:#0073aa;">🌊 Flow</h3>
                        <ul style="margin:0;padding:0;list-style:none;font-size:13px;line-height:1.8;color:#555;">
                            <li>Circulation sémantique</li>
                            <li>Maillage interne</li>
                            <li>Relations entre contenus</li>
                            <li>Propagation des signaux</li>
                        </ul>
                    </div>
                    <div style="background:#f0fff4;padding:20px;border-radius:4px;border-left:4px solid #00aa00;">
                        <h3 style="margin:0 0 12px 0;color:#00aa00;">✅ Trust</h3>
                        <ul style="margin:0;padding:0;list-style:none;font-size:13px;line-height:1.8;color:#555;">
                            <li>Crédibilité algorithmique</li>
                            <li>Cohérence des signaux</li>
                            <li>Compréhension expertise</li>
                            <li>Signaux EEAT</li>
                            <li>Confiance moteur</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Vision -->
            <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:#fff;padding:32px;margin:24px 0;border-radius:6px;">
                <h2 style="font-size:24px;margin:0 0 24px 0;color:#fff;">🚀 Vision</h2>
                
                <p style="font-size:15px;line-height:1.7;margin:0 0 24px 0;">
                    Le SEO évolue rapidement. Les moteurs deviennent :
                </p>
                <ul style="margin:0 0 24px 0;font-size:14px;line-height:1.8;">
                    <li>Plus contextuels</li>
                    <li>Plus relationnels</li>
                    <li>Plus sémantiques</li>
                    <li>De plus en plus pilotés par l'IA</li>
                </ul>

                <p style="font-size:15px;line-height:1.7;margin:0 0 24px 0;">
                    <strong>Les sites qui survivront ne seront pas seulement les mieux "optimisés".</strong>
                </p>

                <p style="font-size:15px;line-height:1.7;margin:0 0 24px 0;">
                    Ce seront les plus compréhensibles, cohérents et crédibles.
                </p>

                <a href="https://johnmingam.com" target="_blank" class="button button-primary" style="background:#fff;color:#667eea;border:none;font-size:14px;padding:10px 24px;height:auto;font-weight:600;">
                    Découvrir johnmingam.com →
                </a>
            </div>

            <!-- CTA Final -->
            <div style="background:#fff;padding:32px;margin:24px 0 48px 0;border-radius:6px;border:2px solid #0073aa;text-align:center;">
                <h2 style="font-size:22px;margin:0 0 16px 0;color:#0073aa;">Prêt à aller plus loin ?</h2>
                <p style="font-size:15px;color:#555;margin:0 0 24px 0;">
                    Explorez l'accompagnement SFT ou demandez un audit stratégique.
                </p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="https://johnmingam.com" target="_blank" class="button button-primary" style="font-size:14px;padding:10px 24px;height:auto;">
                        Demander un Audit SFT
                    </a>
                    <a href="https://johnmingam.com" target="_blank" class="button" style="font-size:14px;padding:10px 24px;height:auto;">
                        Découvrir l'accompagnement
                    </a>
                </div>
            </div>
        </div>

        <style>
            .jm-seo-wrapper {
                max-width:1200px;
            }
            .jm-seo-wrapper h1 {
                margin-bottom:32px;
                font-size:32px;
                color:#0073aa;
            }
            .jm-seo-wrapper h2 {
                color:#0073aa;
            }
            .jm-seo-wrapper ul li {
                margin-bottom:8px;
            }
            .jm-seo-wrapper .button {
                display:inline-block;
                text-decoration:none;
                color:#fff;
                background:#0073aa;
                border:1px solid #0073aa;
                border-radius:4px;
                padding:8px 20px;
                transition:all 0.2s;
            }
            .jm-seo-wrapper .button:hover {
                background:#005a87;
                border-color:#005a87;
            }
            .jm-seo-wrapper .button-primary {
                background:#0073aa;
                color:#fff;
                font-weight:600;
            }
        </style>
        <?php
    }
}
