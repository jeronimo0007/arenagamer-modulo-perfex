<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Arenagamer_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Log a sync operation
     */
    public function log_sync($entity_type, $entity_id, $action, $status = 'success', $message = '')
    {
        return $this->db->insert(db_prefix() . 'arenagamer_sync_log', [
            'entity_type' => $entity_type,
            'entity_id'   => $entity_id,
            'action'      => $action,
            'status'      => $status,
            'message'     => $message,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get sync logs
     */
    public function get_sync_logs($limit = 50, $offset = 0)
    {
        return $this->db->order_by('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get(db_prefix() . 'arenagamer_sync_log')
            ->result_array();
    }

    /**
     * Get sync stats
     */
    public function get_sync_stats()
    {
        $total = $this->db->count_all(db_prefix() . 'arenagamer_sync_log');
        $errors = $this->db->where('status', 'error')->count_all_results(db_prefix() . 'arenagamer_sync_log');
        $last = $this->db->order_by('created_at', 'DESC')
            ->limit(1)
            ->get(db_prefix() . 'arenagamer_sync_log')
            ->row_array();

        return [
            'total'       => $total,
            'errors'      => $errors,
            'last_sync'   => $last ? $last['created_at'] : null,
            'last_status' => $last ? $last['status'] : null,
        ];
    }

    /**
     * Clientes Perfex para select (clientUserId = tblclients.userid)
     */
    public function get_clients_for_select()
    {
        $this->load->model('clients_model');

        return $this->clients_model->get('', [
            db_prefix() . 'clients.active' => 1,
        ]);
    }

    /**
     * Nome da empresa pelo userid do cliente
     */
    public function get_client_company_name($clientUserId)
    {
        if (!$clientUserId) {
            return null;
        }

        $this->load->model('clients_model');
        $client = $this->clients_model->get((int) $clientUserId);

        if (!$client) {
            return null;
        }

        return $client->company ?: ('Cliente #' . (int) $clientUserId);
    }

    /**
     * Busca clientes Perfex por empresa ou ID (para gestão admin de planos)
     */
    public function search_clients($term = '', $limit = 25)
    {
        $limit = max(1, min(100, (int) $limit));
        $term = trim((string) $term);

        $this->db->select('userid, company');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where(db_prefix() . 'clients.active', 1);

        if ($term !== '') {
            $this->db->group_start();
            $this->db->like('company', $term);
            if (ctype_digit($term)) {
                $this->db->or_where('userid', (int) $term);
            }
            $this->db->group_end();
        }

        return $this->db->order_by('company', 'ASC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * IDs de contatos principais (is_primary) de um cliente Perfex
     */
    public function get_primary_contact_ids($clientUserId)
    {
        if (!$clientUserId) {
            return [];
        }

        $rows = $this->db->select('id')
            ->where('userid', (int) $clientUserId)
            ->where('is_primary', 1)
            ->get(db_prefix() . 'contacts')
            ->result_array();

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Contatos principais de um cliente (dados para exibição)
     */
    public function get_primary_contacts($clientUserId)
    {
        if (!$clientUserId) {
            return [];
        }

        return $this->db->select('id, firstname, lastname, email')
            ->where('userid', (int) $clientUserId)
            ->where('is_primary', 1)
            ->where('active', 1)
            ->order_by('id', 'ASC')
            ->get(db_prefix() . 'contacts')
            ->result_array();
    }

    /**
     * Verifica se o contato logado no portal é o principal (is_primary)
     */
    public function is_logged_contact_primary()
    {
        if (!function_exists('get_contact_user_id') || !get_contact_user_id()) {
            return false;
        }

        $row = $this->db->select('is_primary')
            ->where('id', (int) get_contact_user_id())
            ->get(db_prefix() . 'contacts')
            ->row();

        return $row && (int) $row->is_primary === 1;
    }

    /**
     * ID do contato principal de um cliente Perfex
     */
    public function get_primary_contact_id($clientUserId)
    {
        if (!$clientUserId) {
            return 0;
        }

        $row = $this->db->select('id')
            ->where('userid', (int) $clientUserId)
            ->where('is_primary', 1)
            ->where('active', 1)
            ->order_by('id', 'ASC')
            ->get(db_prefix() . 'contacts')
            ->row();

        return $row ? (int) $row->id : 0;
    }

    /**
     * Contatos elegíveis para permissão de gestão (mesmo clientUserId, exceto principal e já concedidos)
     */
    public function get_grantable_contacts(array $apiContacts, $clientUserId, array $existingManagerIds = [], array $primaryContactIds = [])
    {
        $existingManagerIds = array_map('intval', $existingManagerIds);
        $primaryContactIds = array_map('intval', $primaryContactIds);
        $eligible = [];

        foreach ($apiContacts as $contact) {
            if (($contact['userType'] ?? '') !== 'CONTACT') {
                continue;
            }

            if ((int) ($contact['clientUserId'] ?? 0) !== (int) $clientUserId) {
                continue;
            }

            $contactId = (int) ($contact['id'] ?? 0);
            if ($contactId <= 0) {
                continue;
            }

            if (in_array($contactId, $primaryContactIds, true)) {
                continue;
            }

            if (in_array($contactId, $existingManagerIds, true)) {
                continue;
            }

            $eligible[] = $contact;
        }

        usort($eligible, function ($a, $b) {
            $nameA = trim(($a['firstName'] ?? '') . ' ' . ($a['lastName'] ?? ''));
            $nameB = trim(($b['firstName'] ?? '') . ' ' . ($b['lastName'] ?? ''));

            return strcasecmp($nameA, $nameB);
        });

        return $eligible;
    }

    /**
     * Contatos elegíveis para permissão via Perfex (mesma empresa)
     */
    public function get_grantable_contacts_from_perfex($clientUserId, array $existingManagerIds = [], array $primaryContactIds = [])
    {
        if (!$clientUserId) {
            return [];
        }

        $this->load->model('clients_model');
        $contacts = $this->clients_model->get_contacts((int) $clientUserId, ['active' => 1]);
        $existingManagerIds = array_map('intval', $existingManagerIds);
        $primaryContactIds = array_map('intval', $primaryContactIds);
        $eligible = [];

        foreach ($contacts as $contact) {
            $contactId = (int) ($contact['id'] ?? 0);
            if ($contactId <= 0) {
                continue;
            }

            if (in_array($contactId, $primaryContactIds, true)) {
                continue;
            }

            if (in_array($contactId, $existingManagerIds, true)) {
                continue;
            }

            $eligible[] = [
                'id'        => $contactId,
                'firstName' => $contact['firstname'] ?? '',
                'lastName'  => $contact['lastname'] ?? '',
                'email'     => $contact['email'] ?? '',
            ];
        }

        return $eligible;
    }
}
