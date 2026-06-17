<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: ArenaGamer
Description: Módulo de integração ArenaGamer - Gerenciamento de torneios de e-sports
Version: 1.0.0
Requires at least: 3.0
Author: ArenaGamer
Author URI: https://arenagamer.com
*/

define('ARENAGAMER_MODULE_NAME', 'arenagamer');
define('ARENAGAMER_MODULE_PATH', __DIR__ . '/');

hooks()->add_action('admin_init', 'arenagamer_module_init_menu_items');
hooks()->add_action('app_admin_head', 'arenagamer_admin_head');

/**
 * Register module activation hook
 */
register_activation_hook(ARENAGAMER_MODULE_NAME, 'arenagamer_module_activate');

function arenagamer_module_activate()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register menu items in admin sidebar
 */
function arenagamer_module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('arenagamer', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('arenagamer', [
            'slug'     => 'arenagamer',
            'name'     => 'ArenaGamer',
            'icon'     => 'fa fa-gamepad',
            'position' => 30,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-dashboard',
            'name'     => 'Dashboard',
            'href'     => admin_url('arenagamer'),
            'position' => 1,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-tournaments',
            'name'     => 'Torneios',
            'href'     => admin_url('arenagamer/tournaments'),
            'position' => 2,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-plans',
            'name'     => 'Planos',
            'href'     => admin_url('arenagamer/plans'),
            'position' => 3,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-presets',
            'name'     => 'Presets/Jogos',
            'href'     => admin_url('arenagamer/presets'),
            'position' => 4,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-credit-tiers',
            'name'     => 'Tiers de Créditos',
            'href'     => admin_url('arenagamer/credit_tiers'),
            'position' => 5,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-users',
            'name'     => 'Usuários',
            'href'     => admin_url('arenagamer/users'),
            'position' => 6,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-audits',
            'name'     => 'Auditoria',
            'href'     => admin_url('arenagamer/audits'),
            'position' => 7,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-webhooks',
            'name'     => 'Webhooks',
            'href'     => admin_url('arenagamer/webhooks'),
            'position' => 8,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-settings',
            'name'     => 'Configurações',
            'href'     => admin_url('arenagamer/settings'),
            'position' => 9,
        ]);
    }
}

/**
 * Add CSS/JS to admin head
 */
function arenagamer_admin_head()
{
    $CI = &get_instance();
    if ($CI->uri->segment(2) === 'arenagamer') {
        echo '<link rel="stylesheet" href="' . module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/css/arenagamer.css') . '">';
        echo '<script src="' . module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/js/arenagamer.js') . '"></script>';
    }
}
