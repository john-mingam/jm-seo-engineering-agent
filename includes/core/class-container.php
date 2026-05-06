<?php
if (!defined('ABSPATH')) exit;

class JM_SEO_Container {
    private static array $services = [];

    public static function set(string $id, mixed $service): void {
        self::$services[$id] = $service;
    }

    public static function get(string $id): mixed {
        return self::$services[$id] ?? null;
    }
}
