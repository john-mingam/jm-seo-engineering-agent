<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Permissions {
    public static function capability(): string {
        return 'jm_seo_agent_access';
    }

    public static function menu_capability(): string {
        return current_user_can('manage_options') ? 'manage_options' : self::capability();
    }

    public static function register(): void {
        foreach (JM_SEO_Settings::agency_roles() as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap(self::capability());
            }
        }
    }

    public static function activate(): void {
        self::register();
    }

    public static function can_access(): bool {
        return current_user_can('manage_options') || current_user_can(self::capability());
    }
}
