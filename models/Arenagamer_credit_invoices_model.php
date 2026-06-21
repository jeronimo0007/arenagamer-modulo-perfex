<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Arenagamer_credit_invoices_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_table();
    }

    public function ensure_table()
    {
        if ($this->db->table_exists(db_prefix() . 'arenagamer_credit_invoices')) {
            return;
        }

        $this->db->query('CREATE TABLE IF NOT EXISTS `' . db_prefix() . "arenagamer_credit_invoices` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $this->db->char_set . ';');
    }

    public function get_by_invoice_id($invoiceId)
    {
        return $this->db->where('invoice_id', (int) $invoiceId)
            ->get(db_prefix() . 'arenagamer_credit_invoices')
            ->row_array();
    }

    public function get_pending_for_client($clientUserId)
    {
        return $this->db->where('client_userid', (int) $clientUserId)
            ->where('status', 'pending')
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'arenagamer_credit_invoices')
            ->row_array();
    }

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
            if (function_exists('arenagamer_process_credit_invoice_paid')) {
                arenagamer_process_credit_invoice_paid($invoiceId);
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
            ->update(db_prefix() . 'arenagamer_credit_invoices', [
                'status' => 'cancelled',
            ]);
    }

    public function mark_paid($id)
    {
        return $this->db->where('id', (int) $id)->update(db_prefix() . 'arenagamer_credit_invoices', [
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

        return $this->db->where('id', (int) $id)->update(db_prefix() . 'arenagamer_credit_invoices', $data);
    }

    public function mark_cancelled_by_invoice($invoiceId)
    {
        return $this->db->where('invoice_id', (int) $invoiceId)
            ->where('status', 'pending')
            ->update(db_prefix() . 'arenagamer_credit_invoices', [
                'status' => 'cancelled',
            ]);
    }

    /**
     * Gera fatura Perfex para compra de créditos (1 crédito = R$ 1,00).
     */
    public function create_credit_invoice($clientUserId, $contactId, $creditsAmount)
    {
        $this->load->model('invoices_model');
        $this->load->model('clients_model');
        $this->load->model('payment_modes_model');

        $clientUserId = (int) $clientUserId;
        $contactId = (int) $contactId;
        $creditsAmount = round((float) $creditsAmount, 2);

        if ($clientUserId <= 0) {
            return ['success' => false, 'message' => 'Cliente inválido.'];
        }

        if ($creditsAmount <= 0) {
            return ['success' => false, 'message' => 'Informe uma quantidade válida de créditos.'];
        }

        $client = $this->clients_model->get($clientUserId);
        if (!$client) {
            return ['success' => false, 'message' => 'Cliente não encontrado no Perfex.'];
        }

        $amount = $creditsAmount;
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

        $itemLabel = 'ArenaGamer — Compra de ' . number_format($creditsAmount, $decimals, ',', '.') . ' créditos';

        $invoiceData = [
            'clientid'              => $clientUserId,
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
            'adminnote'             => 'ArenaGamer credit_purchase credits=' . $creditsAmount,
            'newitems'              => [
                1 => [
                    'description'      => $itemLabel,
                    'long_description' => 'Créditos ArenaGamer (1 crédito = R$ 1,00). Após o pagamento, o saldo será creditado automaticamente.',
                    'qty'              => 1,
                    'rate'             => $amount,
                    'unit'             => 'pacote',
                    'taxname'          => [],
                    'order'            => 1,
                ],
            ],
        ];

        $invoiceId = $this->invoices_model->add($invoiceData);
        if (!$invoiceId) {
            return ['success' => false, 'message' => 'Não foi possível criar a fatura no Perfex.'];
        }

        if (!function_exists('arenagamer_sync_invoice_totals') || !arenagamer_sync_invoice_totals((int) $invoiceId)) {
            $this->invoices_model->mark_as_cancelled((int) $invoiceId);

            return ['success' => false, 'message' => 'Não foi possível calcular o total da fatura.'];
        }

        $invoice = $this->invoices_model->get($invoiceId);

        $this->db->insert(db_prefix() . 'arenagamer_credit_invoices', [
            'client_userid'   => $clientUserId,
            'contact_id'      => $contactId,
            'invoice_id'      => (int) $invoiceId,
            'credits_amount'  => $creditsAmount,
            'amount'          => $amount,
            'status'          => 'pending',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        return [
            'success'        => true,
            'invoice_id'     => (int) $invoiceId,
            'hash'           => $invoice->hash ?? '',
            'url'            => site_url('invoice/' . (int) $invoiceId . '/' . ($invoice->hash ?? '')),
            'credits_amount' => $creditsAmount,
            'amount'         => $amount,
        ];
    }
}
