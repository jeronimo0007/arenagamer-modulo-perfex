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
}
