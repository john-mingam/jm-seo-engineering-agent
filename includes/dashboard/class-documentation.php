<?php
/**
 * Documentation - Complete Plugin Guide
 */

if (!defined('ABSPATH')) exit;

class JM_SEO_Documentation {
    
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 15);
    }

    public static function register_menu(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }
        
        add_submenu_page(
            'jm-seo-agent',
            'Documentation',
            'Documentation',
            JM_SEO_Permissions::menu_capability(),
            'jm-seo-documentation',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }
        ?>
        <div class="wrap jm-seo-doc-wrapper">
            <h1>📖 Documentation JM SEO Engineering Agent</h1>
            <p style="font-size:16px;color:#666;margin:24px 0;">Guide complet du plugin - Version 2.0.0</p>

            <!-- Table of Contents -->
            <div style="background:#f0f7ff;border:2px solid #0073aa;border-radius:6px;padding:24px;margin:32px 0;">
                <h2 style="margin-top:0;color:#0073aa;">📑 Sommaire</h2>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">
                    <div><a href="#overview" style="color:#0073aa;text-decoration:none;"><strong>1. Vue d'ensemble</strong></a></div>
                    <div><a href="#dashboard" style="color:#0073aa;text-decoration:none;"><strong>2. Tableau de bord</strong></a></div>
                    <div><a href="#settings" style="color:#0073aa;text-decoration:none;"><strong>3. Paramètres</strong></a></div>
                    <div><a href="#scan" style="color:#0073aa;text-decoration:none;"><strong>4. Lancer un scan</strong></a></div>
                    <div><a href="#issues" style="color:#0073aa;text-decoration:none;"><strong>5. Navigateur de problèmes</strong></a></div>
                    <div><a href="#history" style="color:#0073aa;text-decoration:none;"><strong>6. Historique des scans</strong></a></div>
                    <div><a href="#reporting" style="color:#0073aa;text-decoration:none;"><strong>7. Rapports</strong></a></div>
                    <div><a href="#health" style="color:#0073aa;text-decoration:none;"><strong>8. Santé du site</strong></a></div>
                    <div><a href="#sft" style="color:#0073aa;text-decoration:none;"><strong>9. Aller plus loin</strong></a></div>
                    <div><a href="#api" style="color:#0073aa;text-decoration:none;"><strong>10. REST API</strong></a></div>
                </div>
            </div>

            <!-- 1. Overview -->
            <section id="overview" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #0073aa;">
                <h2>1. Vue d'ensemble</h2>
                <p><strong>JM SEO Engineering Agent</strong> est un système de surveillance SEO avancé conçu pour analyser et optimiser votre site selon la méthode <strong>SFT (Structure • Flow • Trust)</strong>.</p>
                
                <h3>Caractéristiques principales :</h3>
                <ul>
                    <li>🔍 <strong>Crawl automatisé</strong> - Exploration complète du site</li>
                    <li>🎯 <strong>9+ détecteurs spécialisés</strong> - Analyse multi-domaines</li>
                    <li>📊 <strong>Scoring SFT</strong> - Évaluation Structure </li>
                    <li>📈 <strong>Rapports stratégiques</strong> - CSV, JSON, PDF, Email</li>
                    <li>⏱️ <strong>Surveillance continue</strong> - Scans programmés</li>
                    <li>🤖 <strong>Détection des régressions</strong> - Alertes automatiques</li>
                    <li>💾 <strong>Cache et performance</strong> - Redis ready</li>
                    <li>🔐 <strong>Contrôle d'accès</strong> - Permissions granulaires</li>
                </ul>

                <h3>Architecture du plugin :</h3>
                <ul>
                    <li><strong>50+ classes PHP</strong> - Code modulaire et maintenable</li>
                    <li><strong>8 domaines fonctionnels</strong> - Core, Crawler, Scanner, Scoring, Reporting, Dashboard, Storage, Monitoring</li>
                    <li><strong>5 tables de base de données</strong> - Scans, pages, problèmes, logs, queue</li>
                    <li><strong>6 endpoints REST API</strong> - Intégration tierce</li>
                    <li><strong>9 pages de dashboard</strong> - Interface complète</li>
                </ul>
            </section>

            <!-- 2. Tableau de bord -->
            <section id="dashboard" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #00aa00;">
                <h2>2. Tableau de bord</h2>
                <p>La page d'accueil affiche un aperçu complet de votre site et des derniers scans.</p>
                
                <h3>Ce que vous voyez :</h3>
                <ul>
                    <li>📊 <strong>Dernier score SFT</strong> - Score Structure/Flow/Trust</li>
                    <li>🔢 <strong>Statistiques</strong> - Pages, problèmes, scans totaux</li>
                    <li>⚠️ <strong>Problèmes récents</strong> - Liste des 10 derniers problèmes</li>
                    <li>📈 <strong>Graphique historique</strong> - Tendance des scores</li>
                    <li>🚀 <strong>Actions rapides</strong> - Boutons pour lancer un scan</li>
                </ul>

                <h3>Interprétation des scores :</h3>
                <div style="background:#f8f9fa;padding:16px;border-radius:4px;margin:16px 0;">
                    <p><strong>Score SFT (0-100) :</strong></p>
                    <ul style="margin:12px 0;">
                        <li>80-100 : Excellent ✅</li>
                        <li>60-79 : Bon</li>
                        <li>40-59 : À améliorer</li>
                        <li>0-39 : Critique ❌</li>
                    </ul>
                </div>
            </section>

            <!-- 3. Paramètres -->
            <section id="settings" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #ffc107;">
                <h2>3. Paramètres</h2>
                <p>Configurez le comportement du crawl et de la surveillance de votre site.</p>
                
                <h3>Configuration disponible :</h3>
                <table class="widefat" style="margin:16px 0;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th width="30%">Paramètre</th>
                            <th width="70%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Limite de crawl</strong></td>
                            <td>Nombre maximum de pages à analyser par scan (défaut: 500)</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td><strong>Taille des lots</strong></td>
                            <td>Nombre de pages traitées par lot (défaut: 10)</td>
                        </tr>
                        <tr>
                            <td><strong>Timeout</strong></td>
                            <td>Délai maximum par page en secondes (défaut: 15s)</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td><strong>Planification</strong></td>
                            <td>Fréquence des scans automatiques (quotidien, hebdo, mensuel)</td>
                        </tr>
                        <tr>
                            <td><strong>Email rapports</strong></td>
                            <td>Adresses pour recevoir les rapports de scan</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td><strong>Pages stratégiques</strong></td>
                            <td>URLs prioritaires à analyser en premier</td>
                        </tr>
                        <tr>
                            <td><strong>Alertes</strong></td>
                            <td>Types de notifications à recevoir</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- 4. Lancer un scan -->
            <section id="scan" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #dc3545;">
                <h2>4. Lancer un scan</h2>
                <p>Déclenchez manuellement un analyse complète de votre site.</p>
                
                <h3>Types de scan :</h3>
                <ul>
                    <li><strong>Scan complet</strong> - Analyse tous les URLs découverts</li>
                    <li><strong>Scan rapide</strong> - Pages stratégiques uniquement</li>
                    <li><strong>Scan de régression</strong> - Comparaison avec le dernier scan</li>
                </ul>

                <h3>Processus de scan :</h3>
                <div style="background:#f8f9fa;padding:16px;border-radius:4px;margin:16px 0;border-left:4px solid #dc3545;">
                    <ol>
                        <li><strong>Découverte d'URLs</strong> - Sitemap, robots.txt, crawl</li>
                        <li><strong>Mise en file d'attente</strong> - Priorités d'analyse</li>
                        <li><strong>Traitement par lots</strong> - Exécution des analyses</li>
                        <li><strong>Détection de problèmes</strong> - 9+ détecteurs spécialisés</li>
                        <li><strong>Calcul des scores</strong> - SFT par page et global</li>
                        <li><strong>Génération de rapport</strong> - Export et notifications</li>
                    </ol>
                </div>

                <h3>Durée estimée :</h3>
                <ul>
                    <li>100 pages : 5-10 minutes</li>
                    <li>500 pages : 30-45 minutes</li>
                    <li>1000+ pages : 1-2 heures (selon paramètres)</li>
                </ul>
            </section>

            <!-- 5. Navigateur de problèmes -->
            <section id="issues" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #667eea;">
                <h2>5. Navigateur de problèmes</h2>
                <p>Explorez et filtrez tous les problèmes SEO détectés.</p>
                
                <h3>Catégories de problèmes :</h3>
                <ul>
                    <li>🏗️ <strong>Structure</strong> - Architecture, données structurées, templates</li>
                    <li>🌊 <strong>Flow</strong> - Circulation sémantique, maillage, hiérarchie</li>
                    <li>✅ <strong>Trust</strong> - Crédibilité, signaux EEAT, cohérence</li>
                </ul>

                <h3>Filtres disponibles :</h3>
                <ul>
                    <li>Par sévérité (critique, avertissement, info)</li>
                    <li>Par type de problème</li>
                    <li>Par URL ou page</li>
                    <li>Par scan</li>
                    <li>Par statut (résolu, en cours, bloqué)</li>
                </ul>

                <h3>Actions :</h3>
                <ul>
                    <li>Voir le détail du problème</li>
                    <li>Visualiser la page</li>
                    <li>Marquer comme résolu</li>
                    <li>Ajouter des notes</li>
                    <li>Exporter la liste</li>
                </ul>
            </section>

            <!-- 6. Historique des scans -->
            <section id="history" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #28a745;">
                <h2>6. Historique des scans</h2>
                <p>Consultez tous les scans effectués et comparez les résultats dans le temps.</p>
                
                <h3>Vue liste :</h3>
                <ul>
                    <li>Date du scan</li>
                    <li>Score SFT (Structure, Flow, Trust)</li>
                    <li>Nombre de pages analysées</li>
                    <li>Problèmes détectés (par sévérité)</li>
                    <li>Durée du scan</li>
                    <li>Statut</li>
                </ul>

                <h3>Vue détail :</h3>
                <ul>
                    <li>Rapport complet du scan</li>
                    <li>Tous les problèmes identifiés</li>
                    <li>Comparaison avec le scan précédent</li>
                    <li>Graphique d'évolution</li>
                    <li>Recommandations d'optimisation</li>
                </ul>

                <h3>Comparaison :</h3>
                <p>Analysez les différences entre deux scans :</p>
                <ul>
                    <li>Score SFT (variation)</li>
                    <li>Nouveaux problèmes</li>
                    <li>Problèmes résolus</li>
                    <li>Problèmes persistants</li>
                </ul>
            </section>

            <!-- 7. Rapports -->
            <section id="reporting" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #17a2b8;">
                <h2>7. Rapports</h2>
                <p>Générez et exportez des rapports détaillés de vos analyses SEO.</p>
                
                <h3>Types de rapports :</h3>
                <ul>
                    <li><strong>Vue d'ensemble</strong> - Résumé complet du site</li>
                    <li><strong>Analyse des problèmes</strong> - Détail de tous les problèmes</li>
                    <li><strong>Tendances</strong> - Évolution SFT sur 30/90 jours</li>
                    <li><strong>Conformité</strong> - Audit technique vs standards</li>
                </ul>

                <h3>Formats d'export :</h3>
                <ul>
                    <li>📊 <strong>CSV</strong> - Données structurées pour Excel</li>
                    <li>📄 <strong>PDF</strong> - Rapport professionnel avec design</li>
                    <li>🔗 <strong>JSON</strong> - Données pour intégration API</li>
                    <li>📧 <strong>Email</strong> - Envoi directement aux adresses configurées</li>
                </ul>

                <h3>Contenu des rapports :</h3>
                <ul>
                    <li>Score SFT par domaine et global</li>
                    <li>Liste des problèmes avec recommandations</li>
                    <li>Pages à priorité optimisation</li>
                    <li>Comparaison avec scans précédents</li>
                    <li>Tableau de bord exécutif</li>
                </ul>
            </section>

            <!-- 8. Santé -->
            <section id="health" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #20c997;">
                <h2>8. Santé du site</h2>
                <p>Suivi de la santé globale de votre site et recommandations d'optimisation.</p>
                
                <h3>Indicateurs de santé :</h3>
                <ul>
                    <li>Score de santé général (0-100)</li>
                    <li>Score moyen (30 derniers jours)</li>
                    <li>Problèmes bloquants</li>
                    <li>Taux de conformité</li>
                    <li>Tendance de régression</li>
                    <li>Vitesse de crawl</li>
                    <li>État du cache</li>
                </ul>

                <h3>Recommandations automatiques :</h3>
                <ul>
                    <li>Actions critiques à court terme</li>
                    <li>Optimisations moyen terme</li>
                    <li>Stratégies long terme</li>
                    <li>Ressources et documentation</li>
                </ul>

                <h3>Tableau de santé du plugin :</h3>
                <ul>
                    <li>État de la base de données</li>
                    <li>Tâches planifiées</li>
                    <li>Système de cache</li>
                    <li>Permissions utilisateur</li>
                    <li>Espace disque</li>
                </ul>
            </section>

            <!-- 9. Aller plus loin -->
            <section id="sft" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #0073aa;">
                <h2>9. Aller plus loin - Services SFT</h2>
                <p>Découvrez les services avancés et l'accompagnement stratégique proposés.</p>
                
                <h3>Audit SFT complet :</h3>
                <ul>
                    <li>Analyse stratégique approfondie</li>
                    <li>Rapport avec recommandations prioritaires</li>
                    <li>Plan d'action concret</li>
                    <li>Identification des opportunités</li>
                </ul>

                <h3>Accompagnement SFT :</h3>
                <ul>
                    <li>Support technique continu</li>
                    <li>Architecture sémantique</li>
                    <li>Optimisation des entités</li>
                    <li>Stratégie de crédibilité algorithmique</li>
                </ul>

                <h3>Rachel IA (Bientôt) :</h3>
                <ul>
                    <li>Moteur d'analyse contextuelle</li>
                    <li>Corrections automatiques intelligentes</li>
                    <li>Scoring de confiance avancé</li>
                    <li>Suggestions pertinentes</li>
                </ul>

                <p style="margin-top:24px;">
                    <a href="https://johnmingam.com" target="_blank" class="button button-primary">
                        Consulter les services →
                    </a>
                </p>
            </section>

            <!-- 10. REST API -->
            <section id="api" style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;border-left:6px solid #e74c3c;">
                <h2>10. REST API</h2>
                <p>Intégrez les données du plugin dans vos applications externes.</p>
                
                <h3>Endpoints disponibles :</h3>
                <table class="widefat" style="margin:16px 0;">
                    <thead>
                        <tr style="background:#f8f9fa;">
                            <th width="35%">Endpoint</th>
                            <th width="65%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>/wp-json/jm-seo/v1/scans</code></td>
                            <td>Liste tous les scans</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td><code>/wp-json/jm-seo/v1/scans/{id}</code></td>
                            <td>Détail d'un scan spécifique</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/jm-seo/v1/issues</code></td>
                            <td>Liste tous les problèmes</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td><code>/wp-json/jm-seo/v1/scores</code></td>
                            <td>Scores SFT globaux et par page</td>
                        </tr>
                        <tr>
                            <td><code>/wp-json/jm-seo/v1/reports</code></td>
                            <td>Génération de rapports</td>
                        </tr>
                        <tr style="background:#f8f9fa;">
                            <td><code>/wp-json/jm-seo/v1/health</code></td>
                            <td>État de santé du site</td>
                        </tr>
                    </tbody>
                </table>

                <h3>Authentification :</h3>
                <p>Les appels API nécessitent une authentification WordPress (nonce ou JWT token).</p>

                <h3>Exemples d'utilisation :</h3>
                <ul>
                    <li>Intégration avec des outils externes</li>
                    <li>Dashboards personnalisés</li>
                    <li>Automation workflows</li>
                    <li>Synchronisation de données</li>
                    <li>Rapports personnalisés</li>
                </ul>
            </section>

            <!-- FAQ -->
            <section style="background:#fff;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #dcdcde;">
                <h2>❓ Questions fréquentes</h2>
                
                <h3>Q: À quelle fréquence dois-je lancer des scans ?</h3>
                <p><strong>A:</strong> Pour la plupart des sites, une fois par semaine est suffisant. Pour les sites avec beaucoup de mises à jour de contenu, deux fois par semaine est recommandé. Configurez les scans automatisés dans Paramètres.</p>

                <h3>Q: Combien de pages peux-je scanner ?</h3>
                <p><strong>A:</strong> Théoriquement illimité. Par défaut, le plugin scan jusqu'à 500 pages. Cela peut être augmenté dans les paramètres en fonction des ressources du serveur.</p>

                <h3>Q: Les scans ralentissent mon site ?</h3>
                <p><strong>A:</strong> Non, les scans s'exécutent en arrière-plan. Le traitement par lots et les limites de ressources minimisent l'impact.</p>

                <h3>Q: Puis-je exporter les données ?</h3>
                <p><strong>A:</strong> Oui, tous les rapports peuvent être exportés en CSV, JSON ou PDF. L'API REST permet aussi l'extraction de données.</p>

                <h3>Q: Comment interpréter le score SFT ?</h3>
                <p><strong>A:</strong> Voir la section "Tableau de bord" ci-dessus. Le score combine trois dimensions : Structure (architecture), Flow (circulation) et Trust (crédibilité).</p>

                <h3>Q: Puis-je utiliser ce plugin sur plusieurs sites ?</h3>
                <p><strong>A:</strong> Oui, chaque installation a sa propre base de données et ses configurations.</p>
            </section>

            <!-- Support -->
            <section style="background:#f0fff4;padding:32px;margin:32px 0;border-radius:6px;border:1px solid #28a745;border-left:6px solid #28a745;">
                <h2>🆘 Besoin d'aide ?</h2>
                <p>Pour toute question ou problème, vous pouvez :</p>
                <ul>
                    <li>📧 Contacter : <a href="mailto:contact@johnmingam.com">contact@johnmingam.com</a></li>
                    <li>🌐 Visiter : <a href="https://johnmingam.com" target="_blank">johnmingam.com</a></li>
                    <li>📖 Consulter la documentation complète</li>
                    <li>🔧 Vérifier les logs (Santé → Erreurs récentes)</li>
                </ul>
            </section>

            <!-- Footer -->
            <section style="background:#f8f9fa;padding:32px;margin:48px 0 0 0;border-radius:6px;border:1px solid #dcdcde;text-align:center;">
                <p style="color:#666;font-size:13px;">
                    <strong>JM SEO Engineering Agent</strong> v2.0.0<br>
                    Système de surveillance SEO avancé basé sur la méthode SFT
                </p>
                <p style="color:#999;font-size:12px;margin:16px 0 0 0;">
                    &copy; 2026 John Mingam - Architecte d'algorithmes & SEO Engineer
                </p>
            </section>
        </div>

        <style>
            .jm-seo-doc-wrapper {
                max-width:1200px;
                font-size:15px;
                line-height:1.7;
            }
            .jm-seo-doc-wrapper h1 {
                color:#0073aa;
                font-size:32px;
                margin-bottom:8px;
            }
            .jm-seo-doc-wrapper h2 {
                color:#0073aa;
                font-size:24px;
                margin-top:32px;
                margin-bottom:16px;
            }
            .jm-seo-doc-wrapper h3 {
                color:#333;
                font-size:18px;
                margin-top:20px;
                margin-bottom:12px;
            }
            .jm-seo-doc-wrapper section {
                scroll-margin-top:80px;
            }
            .jm-seo-doc-wrapper ul li {
                margin-bottom:8px;
            }
            .jm-seo-doc-wrapper table {
                background:#fff !important;
            }
            .jm-seo-doc-wrapper pre {
                font-family:'Courier New', monospace;
                font-size:12px;
                line-height:1.5;
                white-space:pre-wrap;
                word-wrap:break-word;
            }
            .jm-seo-doc-wrapper code {
                background:#f8f9fa;
                padding:2px 6px;
                border-radius:3px;
                font-family:'Courier New', monospace;
            }
            .jm-seo-doc-wrapper .button {
                background:#0073aa;
                color:#fff;
                padding:10px 24px;
                text-decoration:none;
                border-radius:4px;
                display:inline-block;
            }
            .jm-seo-doc-wrapper .button-primary {
                font-weight:600;
            }
            .jm-seo-doc-wrapper .button:hover {
                background:#005a87;
                color:#fff;
            }
        </style>
        <?php
    }
}
