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

require_once __DIR__ . '/helpers/arenagamer_helper.php';

hooks()->add_action('admin_init', 'arenagamer_module_init_menu_items');
hooks()->add_action('admin_init', 'arenagamer_permissions');
hooks()->add_action('admin_init', 'arenagamer_init_customer_profile_tabs');
hooks()->add_action('admin_init', 'arenagamer_ensure_module_options');
hooks()->add_action('app_admin_head', 'arenagamer_admin_head');
hooks()->add_action('app_admin_footer', 'arenagamer_admin_footer');
hooks()->add_action('clients_init', 'arenagamer_clients_init');
hooks()->add_action('after_customers_area_sub_menu_end', 'arenagamer_clients_submenu_credits');
hooks()->add_action('after_contact_login', 'arenagamer_sync_contact_login');
hooks()->add_action('before_contact_logout', 'arenagamer_sync_contact_logout');
hooks()->add_action('app_customers_head', 'arenagamer_clients_head');
hooks()->add_action('app_customers_footer', 'arenagamer_clients_footer');
hooks()->add_action('invoice_status_changed', 'arenagamer_on_invoice_status_changed');
hooks()->add_action('after_payment_added', 'arenagamer_on_payment_added');
hooks()->add_action('after_recurring_invoice_created', 'arenagamer_on_recurring_invoice_created');
hooks()->add_action('invoice_marked_as_cancelled', 'arenagamer_on_invoice_cancelled');

/**
 * Register module activation hook
 */
register_activation_hook(ARENAGAMER_MODULE_NAME, 'arenagamer_module_activate');

function arenagamer_module_activate()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

function arenagamer_ensure_module_options()
{
    $defaults = [
        'arenagamer_tournament_base_price'        => '5.00',
        'arenagamer_extra_participant_price'      => '1.00',
        'arenagamer_included_participants_default'=> '8',
        'arenagamer_max_owned_teams'              => '1',
        'arenagamer_max_participated_teams'       => '3',
        'arenagamer_max_tournaments_per_team'     => '',
    ];

    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
}

function arenagamer_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'                  => _l('permission_view') . ' (' . _l('permission_global') . ')',
        'create'                => _l('permission_create'),
        'edit'                  => _l('permission_edit'),
        'delete'                => _l('permission_delete'),
        'manage_client_credits' => 'Gerenciar créditos de clientes',
    ];

    register_staff_capabilities('arenagamer', $capabilities, _l('arenagamer'));
}

function arenagamer_init_customer_profile_tabs()
{
    if (!staff_can('view', 'arenagamer')) {
        return;
    }

    $CI = &get_instance();
    $CI->app_tabs->add_customer_profile_tab('arenagamer_credits', [
        'name'     => 'Créditos ArenaGamer',
        'icon'     => 'fa fa-money',
        'view'     => '../../modules/arenagamer/views/admin/clients/groups/credits',
        'position' => 46,
        'visible'  => staff_can('view', 'arenagamer'),
    ]);
}

/**
 * Register menu items in admin sidebar
 */
function arenagamer_module_init_menu_items()
{
    $CI = &get_instance();

    if (staff_can('view', 'arenagamer')) {
        $CI->app_menu->add_sidebar_menu_item('arenagamer', [
            'slug'     => 'arenagamer',
            'name'     => 'ArenaGamer',
            'icon'     => 'fa fa-gamepad',
            'position' => 30,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-dashboard',
            'name'     => 'Dashboard',
            'href'     => arenagamer_admin_url(),
            'position' => 1,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-tournaments',
            'name'     => 'Torneios',
            'href'     => arenagamer_admin_url('tournaments'),
            'position' => 2,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-plans',
            'name'     => 'Planos',
            'href'     => arenagamer_admin_url('plans'),
            'position' => 3,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-subscriptions',
            'name'     => 'Assinaturas',
            'href'     => arenagamer_admin_url('subscriptions'),
            'position' => 4,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-presets',
            'name'     => 'Presets/Jogos',
            'href'     => arenagamer_admin_url('presets'),
            'position' => 5,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-users',
            'name'     => 'Usuários',
            'href'     => arenagamer_admin_url('users'),
            'position' => 6,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-contacts',
            'name'     => 'Contatos',
            'href'     => arenagamer_admin_url('contacts'),
            'position' => 8,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-audits',
            'name'     => 'Auditoria',
            'href'     => arenagamer_admin_url('audits'),
            'position' => 9,
        ]);

        $CI->app_menu->add_sidebar_children_item('arenagamer', [
            'slug'     => 'arenagamer-settings',
            'name'     => 'Configurações',
            'href'     => arenagamer_admin_url('settings'),
            'position' => 10,
        ]);
    }
}

/**
 * Add CSS to admin head
 */
function arenagamer_admin_head()
{
    $CI = &get_instance();
    if ($CI->uri->segment(2) === 'arenagamer') {
        echo '<link rel="stylesheet" href="' . module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/css/arenagamer.css') . '">';
    }
}

/**
 * Add JS to admin footer (after jQuery)
 */
function arenagamer_admin_footer()
{
    $CI = &get_instance();
    if ($CI->uri->segment(2) === 'arenagamer') {
        echo '<script src="' . module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/js/arenagamer.js') . '"></script>';
    }
}

function arenagamer_clients_init()
{
    try {
        if (!is_client_logged_in()) {
            return;
        }

        $CI = &get_instance();

        add_theme_menu_item('arenagamer', [
            'name'     => 'ArenaGamer',
            'href'     => arenagamer_client_url(),
            'position' => 25,
        ]);
    } catch (Throwable $e) {
        if (function_exists('log_activity')) {
            log_activity('ArenaGamer clients_init error: ' . $e->getMessage());
        }
    }
}

function arenagamer_clients_head()
{
    $CI = &get_instance();
    if ($CI->router->fetch_module() === 'arenagamer' && $CI->router->fetch_class() === 'client') {
        echo '<link rel="stylesheet" href="' . module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/css/arenagamer.css') . '">';
    }
}

function arenagamer_clients_submenu_credits()
{
    if (!function_exists('is_client_logged_in') || !is_client_logged_in()) {
        return;
    }

    $CI = &get_instance();
    if ($CI->router->fetch_module() !== 'arenagamer') {
        return;
    }

    $wallet = arenagamer_client_wallet_balance();
    if ($wallet === null) {
        return;
    }

    $available = (float) ($wallet['availableBalance'] ?? 0);
    ?>
    <li class="customers-top-submenu-arenagamer-credits">
        <span class="tw-inline-flex tw-items-center arenagamer-submenu-credits-balance">
            <i class="fa fa-money tw-mr-1"></i>
            <span><?php echo arenagamer_format_credits($available); ?></span>
        </span>
    </li>
    <?php
}

function arenagamer_clients_footer()
{
    $CI = &get_instance();
    if ($CI->router->fetch_module() !== 'arenagamer' || $CI->router->fetch_class() !== 'client') {
        return;
    }

    if (!function_exists('is_client_logged_in') || !is_client_logged_in()) {
        return;
    }

    if (!arenagamer_contact_can_buy_credits()) {
        return;
    }

    $CI->load->view('../../modules/arenagamer/views/client/partials/buy_credits_modal');
    echo '<script src="' . module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/js/arenagamer.js') . '"></script>';
}

function arenagamer_on_invoice_status_changed($data)
{
    if (!is_array($data) || (int) ($data['status'] ?? 0) !== 2) {
        return;
    }

    $invoiceId = (int) ($data['invoice_id'] ?? 0);
    arenagamer_process_plan_invoice_paid($invoiceId);
    arenagamer_process_credit_invoice_paid($invoiceId);
}

function arenagamer_on_payment_added($paymentId)
{
    $CI = &get_instance();
    $CI->load->model('payments_model');
    $payment = $CI->payments_model->get((int) $paymentId);

    if (!$payment || empty($payment->invoiceid)) {
        return;
    }

    arenagamer_process_plan_invoice_paid((int) $payment->invoiceid);
    arenagamer_process_credit_invoice_paid((int) $payment->invoiceid);
}

function arenagamer_on_recurring_invoice_created($data)
{
    if (!is_array($data) || empty($data['original_invoice']) || empty($data['new_invoice_id'])) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_plan_invoices_model');

    $originalId = is_object($data['original_invoice'])
        ? (int) $data['original_invoice']->id
        : (int) ($data['original_invoice']['id'] ?? 0);

    $rootLink = $CI->arenagamer_plan_invoices_model->resolve_recurring_root_link($originalId);
    if (!$rootLink) {
        return;
    }

    $CI->arenagamer_plan_invoices_model->link_recurring_child($rootLink, (int) $data['new_invoice_id']);
}

function arenagamer_on_invoice_cancelled($invoiceId)
{
    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_plan_invoices_model');
    $CI->load->model('arenagamer/arenagamer_credit_invoices_model');
    $CI->arenagamer_plan_invoices_model->mark_cancelled_by_invoice((int) $invoiceId);
    $CI->arenagamer_credit_invoices_model->mark_cancelled_by_invoice((int) $invoiceId);
}

function arenagamer_process_plan_invoice_paid($invoiceId)
{
    if ($invoiceId <= 0) {
        return;
    }

    if (!arenagamer_invoice_is_fully_paid($invoiceId)) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_plan_invoices_model');
    $CI->load->model('arenagamer/arenagamer_model');

    $link = $CI->arenagamer_plan_invoices_model->get_by_invoice_id($invoiceId);
    if (!$link) {
        return;
    }

    $status = (string) ($link['status'] ?? '');
    if ($status === 'applied') {
        return;
    }

    if ($status === 'paid' && empty($link['apply_error'])) {
        return;
    }

    if (!in_array($status, ['pending', 'paid'], true)) {
        return;
    }

    if ($status === 'pending') {
        $CI->arenagamer_plan_invoices_model->mark_paid((int) $link['id']);
    }

    $api = arenagamer_api_for_client((int) $link['client_userid']);
    if (!$api) {
        $CI->arenagamer_plan_invoices_model->mark_applied((int) $link['id'], 'Não foi possível autenticar o contato principal na API ArenaGamer.');
        if (function_exists('log_activity')) {
            log_activity('ArenaGamer plan invoice #' . $invoiceId . ': falha de autenticação API');
        }

        return;
    }

    $billingMonths = (int) ($link['billing_period_months'] ?? 1);
    if ($billingMonths <= 0) {
        $billingMonths = 1;
    }

    $result = $api->subscribe_plan((int) $link['api_plan_id'], $billingMonths);
    if (!arenagamer_api_is_success($result)) {
        $error = $api->get_last_error() ?: 'Falha ao ativar plano na API.';
        $CI->arenagamer_plan_invoices_model->mark_applied((int) $link['id'], $error);
        $CI->arenagamer_model->log_sync('plan_invoice', (int) $link['id'], 'apply', 'error', $error);

        return;
    }

    $CI->arenagamer_plan_invoices_model->mark_applied((int) $link['id']);
    $CI->arenagamer_model->log_sync(
        'plan_invoice',
        (int) $link['id'],
        (string) ($link['action'] ?? 'subscribe'),
        'success',
        'Plano ' . ($link['plan_name'] ?? '') . ' ativado via fatura #' . $invoiceId
    );

    if (in_array($link['action'] ?? '', ['subscribe', 'upgrade'], true)) {
        $CI->arenagamer_plan_invoices_model->retire_previous_recurring_roots(
            (int) $link['client_userid'],
            (int) $invoiceId
        );
        $CI->arenagamer_plan_invoices_model->sync_invoice_recurring_schedule(
            (int) $invoiceId,
            $billingMonths,
            (float) ($link['amount'] ?? 0)
        );
    }
}
