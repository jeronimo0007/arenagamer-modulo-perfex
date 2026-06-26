<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Add module options
// API quebrada em 4 microserviços independentes, cada um com domínio próprio.
add_option('arenagamer_api_url_auth', 'https://auth.omnyarena.com');
add_option('arenagamer_api_url_common', 'https://common.omnyarena.com');
add_option('arenagamer_api_url_admin', 'https://admin.omnyarena.com');
add_option('arenagamer_api_url_public', 'https://public.omnyarena.com');
// URL legada (monólito) — mantida como fallback para instalações antigas.
add_option('arenagamer_api_url', '');
add_option('arenagamer_api_token', '');
add_option('arenagamer_refresh_token', '');
add_option('arenagamer_admin_email', '');
add_option('arenagamer_admin_password', '');
add_option('arenagamer_auto_sync', '1');
add_option('arenagamer_sync_interval', '300'); // 5 minutes
add_option('arenagamer_tournament_base_price', '5.00');
add_option('arenagamer_extra_participant_price', '1.00');
add_option('arenagamer_included_participants_default', '8');

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

if (!$CI->db->table_exists(db_prefix() . 'arenagamer_plan_invoices')) {
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "arenagamer_plan_invoices` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `client_userid` INT(11) NOT NULL,
        `api_plan_id` BIGINT NOT NULL,
        `plan_name` VARCHAR(191) NOT NULL DEFAULT '',
        `invoice_id` INT(11) NOT NULL,
        `recurring_root_invoice_id` INT(11) NULL,
        `action` VARCHAR(20) NOT NULL DEFAULT 'subscribe',
        `is_recurring_root` TINYINT(1) NOT NULL DEFAULT 0,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `billing_period_months` INT(11) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `paid_at` DATETIME NULL,
        `applied_at` DATETIME NULL,
        `apply_error` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `client_userid` (`client_userid`),
        KEY `invoice_id` (`invoice_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'arenagamer_credit_invoices')) {
    $CI->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "arenagamer_credit_invoices` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `client_userid` INT(11) NOT NULL,
        `contact_id` INT(11) NOT NULL DEFAULT 0,
        `invoice_id` INT(11) NOT NULL,
        `credits_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `paid_at` DATETIME NULL,
        `applied_at` DATETIME NULL,
        `apply_error` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `client_userid` (`client_userid`),
        KEY `invoice_id` (`invoice_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}
