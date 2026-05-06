<?php
/**
 * Admin Settings Page for JM SEO Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class JM_SEO_Settings_Page {

    private static ?JM_SEO_Settings_Page $instance = null;

    public static function instance(): JM_SEO_Settings_Page {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'render_initial_setup_notice']);
    }

    /**
     * Register settings sections and fields
     */
    public function register_settings(): void {
        // Crawl Settings Group
        register_setting('jm_seo_settings_group', 'jm_seo_crawl_limit', [
            'type' => 'integer',
            'default' => 500,
            'sanitize_callback' => 'intval',
        ]);
        register_setting('jm_seo_settings_group', 'jm_seo_batch_size', [
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => 'intval',
        ]);
        register_setting('jm_seo_settings_group', 'jm_seo_request_timeout', [
            'type' => 'integer',
            'default' => 12,
            'sanitize_callback' => 'intval',
        ]);
        register_setting('jm_seo_settings_group', 'jm_seo_crawl_schedule', [
            'type' => 'string',
            'default' => 'weekly',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Report Settings
        register_setting('jm_seo_settings_group', 'jm_seo_john_mingam_client', [
            'type' => 'string',
            'default' => 'no',
            'sanitize_callback' => [$this, 'sanitize_client_status'],
        ]);
        register_setting('jm_seo_settings_group', 'jm_seo_report_email', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_email',
        ]);
        register_setting('jm_seo_settings_group', 'jm_seo_report_cc', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Strategic Pages
        register_setting('jm_seo_settings_group', 'jm_seo_strategic_pages', [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ]);

        // Alert Settings
        register_setting('jm_seo_settings_group', 'jm_seo_alert_enabled', [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => fn($val) => $val ? 1 : 0,
        ]);
        register_setting('jm_seo_settings_group', 'jm_seo_alert_threshold', [
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => 'intval',
        ]);

        // Add sections
        add_settings_section(
            'jm_seo_crawl_section',
            'Paramètres de crawl',
            [$this, 'render_crawl_section'],
            'jm_seo_settings_page'
        );

        add_settings_section(
            'jm_seo_report_section',
            'Paramètres de rapport',
            [$this, 'render_report_section'],
            'jm_seo_settings_page'
        );

        add_settings_section(
            'jm_seo_strategic_section',
            'Pages stratégiques',
            [$this, 'render_strategic_section'],
            'jm_seo_settings_page'
        );

        add_settings_section(
            'jm_seo_alert_section',
            'Alertes critiques',
            [$this, 'render_alert_section'],
            'jm_seo_settings_page'
        );

        // Crawl fields
        add_settings_field(
            'jm_seo_crawl_limit_field',
            'Limite de crawl (URLs max)',
            [$this, 'render_crawl_limit_field'],
            'jm_seo_settings_page',
            'jm_seo_crawl_section'
        );

        add_settings_field(
            'jm_seo_batch_size_field',
            'Taille batch (URLs/batch)',
            [$this, 'render_batch_size_field'],
            'jm_seo_settings_page',
            'jm_seo_crawl_section'
        );

        add_settings_field(
            'jm_seo_request_timeout_field',
            'Timeout requête (secondes)',
            [$this, 'render_request_timeout_field'],
            'jm_seo_settings_page',
            'jm_seo_crawl_section'
        );

        add_settings_field(
            'jm_seo_crawl_schedule_field',
            'Schedule crawl',
            [$this, 'render_crawl_schedule_field'],
            'jm_seo_settings_page',
            'jm_seo_crawl_section'
        );

        // Report fields
        add_settings_field(
            'jm_seo_john_mingam_client_field',
            'Profil client',
            [$this, 'render_john_mingam_client_field'],
            'jm_seo_settings_page',
            'jm_seo_report_section'
        );

        add_settings_field(
            'jm_seo_report_email_field',
            'Email rapport',
            [$this, 'render_report_email_field'],
            'jm_seo_settings_page',
            'jm_seo_report_section'
        );

        add_settings_field(
            'jm_seo_report_cc_field',
            'CC (optionnel)',
            [$this, 'render_report_cc_field'],
            'jm_seo_settings_page',
            'jm_seo_report_section'
        );

        // Strategic pages field
        add_settings_field(
            'jm_seo_strategic_pages_field',
            'URLs prioritaires',
            [$this, 'render_strategic_pages_field'],
            'jm_seo_settings_page',
            'jm_seo_strategic_section'
        );

        // Alert fields
        add_settings_field(
            'jm_seo_alert_enabled_field',
            'Alertes activées',
            [$this, 'render_alert_enabled_field'],
            'jm_seo_settings_page',
            'jm_seo_alert_section'
        );

        add_settings_field(
            'jm_seo_alert_threshold_field',
            'Seuil alerte (score baisse)',
            [$this, 'render_alert_threshold_field'],
            'jm_seo_settings_page',
            'jm_seo_alert_section'
        );
    }

    /**
     * Render settings page
     */
    public function render_page(): void {
        if (!JM_SEO_Permissions::can_access()) {
            wp_die('Accès refusé');
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('JM SEO Agent - Paramètres', 'jm-seo-engineering-agent'); ?></h1>
            
            <form method="post" action="options.php" class="jm-seo-settings-form">
                <?php settings_fields('jm_seo_settings_group'); ?>
                <?php do_settings_sections('jm_seo_settings_page'); ?>
                <?php submit_button('Enregistrer les modifications'); ?>
            </form>
        </div>
        <?php
    }

    public function render_initial_setup_notice(): void {
        if (!JM_SEO_Permissions::can_access()) {
            return;
        }

        $client_status = get_option('jm_seo_john_mingam_client', null);
        if (in_array($client_status, ['yes', 'no'], true)) {
            return;
        }

        $settings_url = add_query_arg('page', 'jm-seo-settings', admin_url('admin.php'));
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('JM SEO Agent : configuration requise.', 'jm-seo-engineering-agent'); ?></strong>
                <?php esc_html_e('Indique si ce site est client John Mingam ou non, puis renseigne l’email qui doit recevoir les rapports.', 'jm-seo-engineering-agent'); ?>
                <a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configurer les rapports', 'jm-seo-engineering-agent'); ?></a>
            </p>
        </div>
        <?php
    }

    public function render_crawl_section(): void {
        echo '<p>Configurez le comportement du crawl.</p>';
    }

    public function render_report_section(): void {
        echo '<p>Choisissez qui doit recevoir les rapports après installation.</p>';
    }

    public function sanitize_client_status(string $value): string {
        return in_array($value, ['yes', 'no'], true) ? $value : 'no';
    }

    public function render_strategic_section(): void {
        echo '<p>Listez les URLs prioritaires à crawler en premier. Une par ligne.</p>';
    }

    public function render_alert_section(): void {
        echo '<p>Configurez les alertes critiques.</p>';
    }

    public function render_crawl_limit_field(): void {
        $value = get_option('jm_seo_crawl_limit', 500);
        printf(
            '<input type="number" name="jm_seo_crawl_limit" value="%d" min="10" max="5000" /> URLs',
            intval($value)
        );
    }

    public function render_batch_size_field(): void {
        $value = get_option('jm_seo_batch_size', 10);
        printf(
            '<input type="number" name="jm_seo_batch_size" value="%d" min="1" max="50" /> URLs par lot',
            intval($value)
        );
    }

    public function render_request_timeout_field(): void {
        $value = get_option('jm_seo_request_timeout', 12);
        printf(
            '<input type="number" name="jm_seo_request_timeout" value="%d" min="5" max="60" /> secondes',
            intval($value)
        );
    }

    public function render_crawl_schedule_field(): void {
        $value = get_option('jm_seo_crawl_schedule', 'weekly');
        ?>
        <select name="jm_seo_crawl_schedule">
            <option value="disabled" <?php selected($value, 'disabled'); ?>>Désactivé</option>
            <option value="daily" <?php selected($value, 'daily'); ?>>Quotidien</option>
            <option value="weekly" <?php selected($value, 'weekly'); ?>>Hebdomadaire</option>
            <option value="monthly" <?php selected($value, 'monthly'); ?>>Mensuel</option>
        </select>
        <?php
    }

    public function render_report_email_field(): void {
        $is_john_mingam_client = JM_SEO_Settings::is_john_mingam_client();
        $value = get_option('jm_seo_report_email', '');
        $fallback = get_option('admin_email');

        if (!$value && !$is_john_mingam_client) {
            $value = $fallback;
        }
        ?>
        <div id="jm-seo-john-mingam-report-email" <?php echo $is_john_mingam_client ? '' : 'style="display:none;"'; ?>>
            <input type="email" value="<?php echo esc_attr(JM_SEO_Settings::john_mingam_report_email()); ?>" class="regular-text" disabled="disabled" />
            <p class="description">Site déclaré client John Mingam : les rapports sont envoyés à l’adresse de suivi John Mingam.</p>
        </div>
        <div id="jm-seo-client-report-email" <?php echo $is_john_mingam_client ? 'style="display:none;"' : ''; ?>>
            <input type="email" name="jm_seo_report_email" value="<?php echo esc_attr($value); ?>" class="regular-text" />
            <p class="description">Site non client John Mingam : indique ton adresse mail pour recevoir les rapports. John Mingam ne recevera aucun de ces emails.</p>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="jm_seo_john_mingam_client"]');
            const jmEmail = document.getElementById('jm-seo-john-mingam-report-email');
            const clientEmail = document.getElementById('jm-seo-client-report-email');
            const sync = () => {
                const selected = document.querySelector('input[name="jm_seo_john_mingam_client"]:checked');
                const isJohnMingamClient = selected && selected.value === 'yes';
                if (jmEmail) {
                    jmEmail.style.display = isJohnMingamClient ? '' : 'none';
                }
                if (clientEmail) {
                    clientEmail.style.display = isJohnMingamClient ? 'none' : '';
                }
            };
            radios.forEach((radio) => radio.addEventListener('change', sync));
            sync();
        });
        </script>
        <?php
    }

    public function render_john_mingam_client_field(): void {
        $value = get_option('jm_seo_john_mingam_client', 'no');
        ?>
        <fieldset>
            <label>
                <input type="radio" name="jm_seo_john_mingam_client" value="yes" <?php checked($value, 'yes'); ?> />
                Client John Mingam
            </label>
            <br />
            <label>
                <input type="radio" name="jm_seo_john_mingam_client" value="no" <?php checked($value, 'no'); ?> />
                Non client John Mingam
            </label>
            <p class="description">Pour un non client, les rapports doivent partir uniquement vers l’email du client renseigné ci-dessous.</p>
        </fieldset>
        <?php
    }

    public function render_report_cc_field(): void {
        $value = get_option('jm_seo_report_cc', '');
        printf(
            '<input type="text" name="jm_seo_report_cc" value="%s" class="regular-text" placeholder="email1@example.com, email2@example.com" />',
            esc_attr($value)
        );
    }

    public function render_strategic_pages_field(): void {
        $value = get_option('jm_seo_strategic_pages', '');
        printf(
            '<textarea name="jm_seo_strategic_pages" rows="6" class="large-text" placeholder="/accueil&#10;/a-propos&#10;/contact">%s</textarea>',
            esc_textarea($value)
        );
    }

    public function render_alert_enabled_field(): void {
        $value = get_option('jm_seo_alert_enabled', 1);
        printf(
            '<input type="checkbox" name="jm_seo_alert_enabled" value="1" %s /> Envoyer alertes par email',
            checked($value, 1, false)
        );
    }

    public function render_alert_threshold_field(): void {
        $value = get_option('jm_seo_alert_threshold', 10);
        printf(
            '<input type="number" name="jm_seo_alert_threshold" value="%d" min="1" max="50" /> points',
            intval($value)
        );
    }
}
