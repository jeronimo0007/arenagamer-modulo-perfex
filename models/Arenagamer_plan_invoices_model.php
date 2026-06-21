<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Arenagamer_plan_invoices_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_table();
    }

    public function ensure_table()
    {
        if (!$this->db->table_exists(db_prefix() . 'arenagamer_plan_invoices')) {
            $this->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "arenagamer_plan_invoices` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $this->db->char_set . ';');
        }

        $this->ensure_billing_period_column();
    }

    private function ensure_billing_period_column()
    {
        if (!$this->db->table_exists(db_prefix() . 'arenagamer_plan_invoices')) {
            return;
        }

        if ($this->db->field_exists('billing_period_months', db_prefix() . 'arenagamer_plan_invoices')) {
            return;
        }

        $this->db->query('ALTER TABLE `' . db_prefix() . 'arenagamer_plan_invoices`
            ADD COLUMN `billing_period_months` INT(11) NOT NULL DEFAULT 1 AFTER `amount`');
    }

    public function get_by_invoice_id($invoiceId)
    {
        return $this->db->where('invoice_id', (int) $invoiceId)
            ->get(db_prefix() . 'arenagamer_plan_invoices')
            ->row_array();
    }

    /**
     * Localiza o vínculo raiz da recorrência (mensal, semestral ou anual)
     */
    public function resolve_recurring_root_link($invoiceId)
    {
        $link = $this->get_by_invoice_id((int) $invoiceId);
        if (!$link) {
            return null;
        }

        if (!empty($link['is_recurring_root'])) {
            return $link;
        }

        $rootInvoiceId = (int) ($link['recurring_root_invoice_id'] ?? 0);
        if ($rootInvoiceId <= 0) {
            return null;
        }

        $rootLink = $this->get_by_invoice_id($rootInvoiceId);

        return ($rootLink && !empty($rootLink['is_recurring_root'])) ? $rootLink : null;
    }

    /**
     * Desativa recorrência Perfex das faturas-raiz anteriores do client
     */
    public function retire_previous_recurring_roots($clientUserId, $keepInvoiceId = 0)
    {
        $clientUserId = (int) $clientUserId;
        $keepInvoiceId = (int) $keepInvoiceId;

        $roots = $this->db->where('client_userid', $clientUserId)
            ->where('is_recurring_root', 1)
            ->where_in('status', ['paid', 'applied'])
            ->get(db_prefix() . 'arenagamer_plan_invoices')
            ->result_array();

        foreach ($roots as $root) {
            $invoiceId = (int) ($root['invoice_id'] ?? 0);
            if ($invoiceId <= 0 || ($keepInvoiceId > 0 && $invoiceId === $keepInvoiceId)) {
                continue;
            }

            $this->disable_invoice_recurring($invoiceId);
        }

        return true;
    }

    /**
     * Interrompe recorrência Perfex do plano (admin remove ou cliente cancela).
     */
    public function stop_recurring_for_client($clientUserId)
    {
        $clientUserId = (int) $clientUserId;
        if ($clientUserId <= 0) {
            return false;
        }

        $stopped = false;
        $pending = $this->get_pending_for_client($clientUserId);

        if ($pending) {
            $linkId = (int) ($pending['id'] ?? 0);
            $invoiceId = (int) ($pending['invoice_id'] ?? 0);

            if ($linkId > 0) {
                $this->mark_link_cancelled($linkId);
            }

            if ($invoiceId > 0) {
                $this->load->model('invoices_model');
                $invoice = $this->invoices_model->get($invoiceId);

                if ($invoice && in_array((int) $invoice->status, [
                    Invoices_model::STATUS_UNPAID,
                    Invoices_model::STATUS_OVERDUE,
                    Invoices_model::STATUS_DRAFT,
                ], true)) {
                    $this->invoices_model->mark_as_cancelled($invoiceId);
                }
            }

            $stopped = true;
        }

        $roots = $this->db->where('client_userid', $clientUserId)
            ->where('is_recurring_root', 1)
            ->where_in('status', ['paid', 'applied'])
            ->get(db_prefix() . 'arenagamer_plan_invoices')
            ->result_array();

        foreach ($roots as $root) {
            $invoiceId = (int) ($root['invoice_id'] ?? 0);
            $linkId = (int) ($root['id'] ?? 0);

            if ($invoiceId > 0) {
                $this->disable_invoice_recurring($invoiceId);

                $this->db->where('recurring_root_invoice_id', $invoiceId)
                    ->where('status', 'pending')
                    ->update(db_prefix() . 'arenagamer_plan_invoices', [
                        'status' => 'cancelled',
                    ]);
            }

            if ($linkId > 0) {
                $this->db->where('id', $linkId)->update(db_prefix() . 'arenagamer_plan_invoices', [
                    'status' => 'cancelled',
                ]);
            }

            $stopped = true;
        }

        return $stopped;
    }

    private function disable_invoice_recurring($invoiceId)
    {
        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0) {
            return false;
        }

        $this->db->where('id', $invoiceId)->update(db_prefix() . 'invoices', [
            'recurring'            => 0,
            'recurring_type'       => null,
            'custom_recurring'     => 0,
            'last_recurring_date'  => null,
        ]);

        return true;
    }

    public function sync_invoice_recurring_schedule($invoiceId, $billingPeriodMonths, $amount = null)
    {
        $invoiceId = (int) $invoiceId;
        $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
            ? arenagamer_normalize_billing_period_months($billingPeriodMonths)
            : max(1, (int) $billingPeriodMonths);

        $update = function_exists('arenagamer_invoice_recurring_data')
            ? arenagamer_invoice_recurring_data($billingPeriodMonths)
            : [
                'recurring'        => $billingPeriodMonths,
                'recurring_type'   => 'month',
                'cycles'           => 0,
                'custom_recurring' => 0,
            ];

        if ($amount !== null) {
            $decimals = function_exists('get_decimal_places') ? get_decimal_places() : 2;
            $formatted = number_format((float) $amount, $decimals, '.', '');
            $update['subtotal'] = $formatted;
            $update['total'] = $formatted;
        }

        $this->db->where('id', $invoiceId)->update(db_prefix() . 'invoices', $update);

        return true;
    }

    public function get_pending_for_client($clientUserId)
    {
        return $this->db->where('client_userid', (int) $clientUserId)
            ->where('status', 'pending')
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'arenagamer_plan_invoices')
            ->row_array();
    }

    /**
     * Retorna fatura pendente válida; repara totais quebrados ou descarta vínculos obsoletos
     */
    public function resolve_pending_for_client($clientUserId)
    {
        $pending = $this->get_pending_for_client($clientUserId);
        if (!$pending) {
            return null;
        }

        $linkId = (int) ($pending['id'] ?? 0);
        $invoiceId = (int) ($pending['invoice_id'] ?? 0);

        if ($invoiceId <= 0) {
            $this->mark_link_cancelled($linkId);

            return null;
        }

        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($invoiceId);

        if (!$invoice) {
            $this->mark_link_cancelled($linkId);

            return null;
        }

        if ((int) ($invoice->status ?? 0) === Invoices_model::STATUS_CANCELLED) {
            $this->mark_link_cancelled($linkId);

            return null;
        }

        if (function_exists('arenagamer_invoice_is_fully_paid') && arenagamer_invoice_is_fully_paid($invoiceId)) {
            if (function_exists('arenagamer_process_plan_invoice_paid')) {
                arenagamer_process_plan_invoice_paid($invoiceId);
            }

            return null;
        }

        $needsSync = (float) ($invoice->total ?? 0) <= 0
            || (function_exists('arenagamer_invoice_is_false_paid') && arenagamer_invoice_is_false_paid($invoiceId));

        if ($needsSync) {
            $synced = function_exists('arenagamer_sync_invoice_totals')
                && arenagamer_sync_invoice_totals($invoiceId);

            if (!$synced) {
                $this->mark_link_cancelled($linkId);

                return null;
            }

            $invoice = $this->invoices_model->get($invoiceId);
            if (!$invoice || (float) ($invoice->total ?? 0) <= 0) {
                $this->mark_link_cancelled($linkId);

                return null;
            }
        }

        if ((int) ($invoice->status ?? 0) === Invoices_model::STATUS_PAID) {
            $this->mark_link_cancelled($linkId);

            return null;
        }

        return $pending;
    }

    public function mark_link_cancelled($linkId)
    {
        if ((int) $linkId <= 0) {
            return false;
        }

        return $this->db->where('id', (int) $linkId)
            ->where('status', 'pending')
            ->update(db_prefix() . 'arenagamer_plan_invoices', [
                'status' => 'cancelled',
            ]);
    }

    public function get_recurring_root_for_client($clientUserId)
    {
        return $this->db->where('client_userid', (int) $clientUserId)
            ->where('is_recurring_root', 1)
            ->where_in('status', ['paid', 'applied'])
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'arenagamer_plan_invoices')
            ->row_array();
    }

    public function create_plan_invoice($clientUserId, array $plan, $action = 'subscribe', $billingPeriodMonths = 1)
    {
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('payment_modes_model');

        $client = $this->clients_model->get((int) $clientUserId);
        if (!$client) {
            return ['success' => false, 'message' => 'Cliente não encontrado no Perfex.'];
        }

        $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
            ? arenagamer_normalize_billing_period_months($billingPeriodMonths)
            : 1;

        $monthlyPrice = (float) ($plan['monthlyPrice'] ?? 0);
        if ($monthlyPrice <= 0) {
            return ['success' => false, 'message' => 'Plano sem valor para faturamento.'];
        }

        $amount = function_exists('arenagamer_plan_billing_amount')
            ? arenagamer_plan_billing_amount($monthlyPrice, $billingPeriodMonths)
            : $monthlyPrice;

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Valor da fatura inválido.'];
        }

        $planName = trim((string) ($plan['name'] ?? 'Plano ArenaGamer'));
        $planId = (int) ($plan['id'] ?? 0);
        $isSubscribe = $action === 'subscribe';
        $isUpgrade = $action === 'upgrade';

        $allowedModes = [];
        foreach ($this->payment_modes_model->get('', [], false) as $mode) {
            if (!empty($mode['active'])) {
                $allowedModes[] = $mode['id'];
            }
        }

        $dueDays = (int) get_option('invoice_due_after');
        $decimals = function_exists('get_decimal_places') ? get_decimal_places() : 2;
        $currencyId = (int) ($client->default_currency ?? 0);
        if ($currencyId <= 0 && function_exists('get_base_currency')) {
            $baseCurrency = get_base_currency();
            $currencyId = (int) ($baseCurrency->id ?? 0);
        }

        $invoiceData = [
            'clientid'              => (int) $clientUserId,
            'number'                => get_option('next_invoice_number'),
            'date'                  => _d(date('Y-m-d')),
            'duedate'               => $dueDays > 0 ? _d(date('Y-m-d', strtotime('+' . $dueDays . ' DAY'))) : _d(date('Y-m-d')),
            'currency'              => $currencyId,
            'status'                => Invoices_model::STATUS_UNPAID,
            'subtotal'              => number_format($amount, $decimals, '.', ''),
            'total'                 => number_format($amount, $decimals, '.', ''),
            'adjustment'            => 0,
            'discount_percent'      => 0,
            'discount_total'        => 0,
            'discount_type'         => '',
            'billing_street'        => clear_textarea_breaks($client->billing_street ?? ''),
            'billing_city'          => $client->billing_city ?? '',
            'billing_state'         => $client->billing_state ?? '',
            'billing_zip'           => $client->billing_zip ?? '',
            'billing_country'       => (int) ($client->billing_country ?? 0),
            'allowed_payment_modes' => $allowedModes,
            'adminnote'             => 'ArenaGamer plan_id=' . $planId
                . ' action=' . $action
                . ' billing_months=' . $billingPeriodMonths,
            'newitems'              => [
                1 => [
                    'description'      => function_exists('arenagamer_plan_invoice_item_label')
                        ? arenagamer_plan_invoice_item_label($plan, $billingPeriodMonths, $isUpgrade)
                        : 'ArenaGamer — ' . $planName,
                    'long_description' => trim((string) ($plan['description'] ?? '')),
                    'qty'              => 1,
                    'rate'             => $amount,
                    'unit'             => function_exists('arenagamer_billing_periods')
                        ? (arenagamer_billing_periods()[$billingPeriodMonths]['short'] ?? 'mês')
                        : 'mês',
                    'taxname'          => [],
                    'order'            => 1,
                ],
            ],
        ];

        if (function_exists('arenagamer_invoice_recurring_data')) {
            $invoiceData = array_merge($invoiceData, arenagamer_invoice_recurring_data($billingPeriodMonths));
        }

        $invoiceId = $this->invoices_model->add($invoiceData);
        if (!$invoiceId) {
            return ['success' => false, 'message' => 'Não foi possível criar a fatura no Perfex.'];
        }

        // Perfex chama update_invoice_status antes dos itens; recalcula totais e força Unpaid
        if (!function_exists('arenagamer_sync_invoice_totals') || !arenagamer_sync_invoice_totals((int) $invoiceId)) {
            $this->invoices_model->mark_as_cancelled((int) $invoiceId);

            return ['success' => false, 'message' => 'Não foi possível calcular o total da fatura.'];
        }

        $invoice = $this->invoices_model->get($invoiceId);

        $this->db->insert(db_prefix() . 'arenagamer_plan_invoices', [
            'client_userid'             => (int) $clientUserId,
            'api_plan_id'               => $planId,
            'plan_name'                 => $planName,
            'invoice_id'                => (int) $invoiceId,
            'recurring_root_invoice_id' => null,
            'action'                    => $action,
            'is_recurring_root'         => in_array($action, ['subscribe', 'upgrade'], true) ? 1 : 0,
            'status'                    => 'pending',
            'amount'                    => $amount,
            'billing_period_months'     => $billingPeriodMonths,
            'created_at'                => date('Y-m-d H:i:s'),
        ]);

        return [
            'success'               => true,
            'invoice_id'            => (int) $invoiceId,
            'hash'                  => $invoice->hash ?? '',
            'url'                   => site_url('invoice/' . (int) $invoiceId . '/' . ($invoice->hash ?? '')),
            'action'                => $action,
            'billing_period_months' => $billingPeriodMonths,
            'amount'                => $amount,
        ];
    }

    public function link_recurring_child($rootLink, $newInvoiceId)
    {
        if (!is_array($rootLink) || empty($rootLink['api_plan_id'])) {
            return false;
        }

        $billingMonths = (int) ($rootLink['billing_period_months'] ?? 1);
        if ($billingMonths <= 0) {
            $billingMonths = 1;
        }

        $this->db->insert(db_prefix() . 'arenagamer_plan_invoices', [
            'client_userid'             => (int) $rootLink['client_userid'],
            'api_plan_id'               => (int) $rootLink['api_plan_id'],
            'plan_name'                 => (string) ($rootLink['plan_name'] ?? ''),
            'invoice_id'                => (int) $newInvoiceId,
            'recurring_root_invoice_id' => (int) $rootLink['invoice_id'],
            'action'                    => 'renewal',
            'is_recurring_root'         => 0,
            'status'                    => 'pending',
            'amount'                    => (float) ($rootLink['amount'] ?? 0),
            'billing_period_months'     => $billingMonths,
            'created_at'                => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function mark_paid($id)
    {
        return $this->db->where('id', (int) $id)->update(db_prefix() . 'arenagamer_plan_invoices', [
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function mark_applied($id, $error = null)
    {
        $data = [
            'applied_at' => date('Y-m-d H:i:s'),
        ];

        if ($error) {
            $data['status'] = 'paid';
            $data['apply_error'] = $error;
        } else {
            $data['status'] = 'applied';
            $data['apply_error'] = null;
        }

        return $this->db->where('id', (int) $id)->update(db_prefix() . 'arenagamer_plan_invoices', $data);
    }

    public function mark_cancelled_by_invoice($invoiceId)
    {
        return $this->db->where('invoice_id', (int) $invoiceId)
            ->where('status', 'pending')
            ->update(db_prefix() . 'arenagamer_plan_invoices', [
                'status' => 'cancelled',
            ]);
    }

    public function update_recurring_root_plan($clientUserId, array $plan)
    {
        $root = $this->get_recurring_root_for_client($clientUserId);
        if (!$root) {
            return false;
        }

        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get((int) $root['invoice_id']);
        if (!$invoice || empty($invoice->items[0]['id'])) {
            return false;
        }

        $itemId = (int) $invoice->items[0]['id'];
        $amount = (float) ($plan['monthlyPrice'] ?? 0);
        $billingMonths = (int) ($root['billing_period_months'] ?? 1);
        if ($billingMonths <= 0) {
            $billingMonths = 1;
        }
        if (function_exists('arenagamer_plan_billing_amount')) {
            $amount = arenagamer_plan_billing_amount($amount, $billingMonths);
        }

        $planName = trim((string) ($plan['name'] ?? 'Plano ArenaGamer'));

        $this->db->where('id', $itemId)->update(db_prefix() . 'itemable', [
            'description'      => function_exists('arenagamer_plan_invoice_item_label')
                ? arenagamer_plan_invoice_item_label($plan, $billingMonths, false)
                : 'ArenaGamer — ' . $planName . ' (mensal)',
            'long_description' => trim((string) ($plan['description'] ?? '')),
            'rate'             => $amount,
        ]);

        $this->invoices_model->update([
            'subtotal'  => $amount,
            'total'     => $amount,
            'adminnote' => 'ArenaGamer plan_id=' . (int) ($plan['id'] ?? 0) . ' action=subscribe',
        ], (int) $root['invoice_id']);

        $this->sync_invoice_recurring_schedule((int) $root['invoice_id'], $billingMonths, $amount);

        $this->db->where('id', (int) $root['id'])->update(db_prefix() . 'arenagamer_plan_invoices', [
            'api_plan_id' => (int) ($plan['id'] ?? 0),
            'plan_name'   => $planName,
            'amount'      => $amount,
        ]);

        return true;
    }
}
