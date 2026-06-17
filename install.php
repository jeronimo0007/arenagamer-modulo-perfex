<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Add module options
add_option('arenagamer_api_url', 'http://localhost:8080/api/v1');
add_option('arenagamer_api_token', '');
add_option('arenagamer_admin_email', '');
add_option('arenagamer_admin_password', '');
add_option('arenagamer_auto_sync', '1');
add_option('arenagamer_sync_interval', '300'); // 5 minutes

// Create permissions
if (!$CI->db->table_exists(db_prefix() . 'arenagamer_sync_log')) {
    $CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "arenagamer_sync_log` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `entity_type` VARCHAR(50) NOT NULL,
        `entity_id` INT(11) NOT NULL,
        `action` VARCHAR(20) NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'success',
        `message` TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `entity_type_idx` (`entity_type`),
        KEY `created_at_idx` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

// Register permissions
$capabilities = [];
$capabilities['capabilities'] = [
    'view'   => 'View ArenaGamer',
    'create' => 'Create ArenaGamer',
    'edit'   => 'Edit ArenaGamer',
    'delete' => 'Delete ArenaGamer',
];

register_staff_capabilities('arenagamer', $capabilities, _l('arenagamer'));
