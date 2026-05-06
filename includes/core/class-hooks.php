<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Hooks {
    public static function register(): void {
        add_action('admin_init', [JM_SEO_Permissions::class, 'register']);
    }
}
