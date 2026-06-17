<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Arenagamer extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('arenagamer/ArenaGamer_api', null, 'arenagamer_api');
        $this->load->model('arenagamer/arenagamer_model');
    }

    /**
     * Dashboard
     */
    public function index()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Dashboard';
        $data['tournaments'] = $this->arenagamer_api->get_tournaments(0, 5);
        $data['users'] = $this->arenagamer_api->get_users(0, 5);

        $this->load->view('admin/dashboard', $data);
    }

    /**
     * Tournaments
     */
    public function tournaments()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;
        $data['title'] = 'ArenaGamer - Torneios';
        $data['response'] = $this->arenagamer_api->get_tournaments($page, 20);

        $this->load->view('admin/tournaments/index', $data);
    }

    public function tournament_detail($slug)
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Torneio';
        $data['tournament'] = $this->arenagamer_api->get_tournament($slug);
        $data['matches'] = $this->arenagamer_api->get_tournament_matches($slug);

        $this->load->view('admin/tournaments/detail', $data);
    }

    public function tournament_action($slug, $action)
    {
        if (!has_permission('arenagamer', '', 'edit')) {
            access_denied('arenagamer');
        }

        switch ($action) {
            case 'open_registration':
                $result = $this->arenagamer_api->update_tournament_status($slug, 'REGISTRATION_OPEN');
                break;
            case 'close_registration':
                $result = $this->arenagamer_api->update_tournament_status($slug, 'REGISTRATION_CLOSED');
                break;
            case 'generate_bracket':
                $result = $this->arenagamer_api->generate_bracket($slug);
                break;
            case 'schedule':
                $result = $this->arenagamer_api->schedule_matches($slug);
                break;
            case 'cancel':
                $result = $this->arenagamer_api->cancel_tournament($slug);
                break;
            default:
                set_alert('warning', 'Ação não reconhecida');
                redirect(admin_url('arenagamer/tournaments'));
                return;
        }

        if ($result) {
            set_alert('success', 'Ação executada com sucesso');
            $this->arenagamer_model->log_sync('tournament', 0, $action, 'success', "Slug: {$slug}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', 0, $action, 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
    }

    /**
     * Plans
     */
    public function plans()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Planos';
        $data['plans'] = $this->arenagamer_api->get_plans();

        $this->load->view('admin/plans/index', $data);
    }

    /**
     * Presets
     */
    public function presets()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Presets';
        $data['presets'] = $this->arenagamer_api->get_presets();

        $this->load->view('admin/presets/index', $data);
    }

    /**
     * Credit Tiers
     */
    public function credit_tiers()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Tiers de Créditos';
        $data['tiers'] = $this->arenagamer_api->get_credit_tiers();

        $this->load->view('admin/credit_tiers/index', $data);
    }

    /**
     * Users
     */
    public function users()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;
        $data['title'] = 'ArenaGamer - Usuários';
        $data['response'] = $this->arenagamer_api->get_users($page, 20);

        $this->load->view('admin/users/index', $data);
    }

    /**
     * Audits
     */
    public function audits()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;
        $data['title'] = 'ArenaGamer - Auditoria';
        $data['response'] = $this->arenagamer_api->get_audits($page, 50);

        $this->load->view('admin/audits/index', $data);
    }

    /**
     * Webhooks
     */
    public function webhooks()
    {
        if (!has_permission('arenagamer', '', 'view')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Webhooks';
        $this->load->view('admin/webhooks/index', $data);
    }

    /**
     * Settings
     */
    public function settings()
    {
        if (!has_permission('arenagamer', '', 'edit')) {
            access_denied('arenagamer');
        }

        if ($this->input->post()) {
            $settings = [
                'arenagamer_api_url'        => $this->input->post('api_url'),
                'arenagamer_admin_email'     => $this->input->post('admin_email'),
                'arenagamer_auto_sync'       => $this->input->post('auto_sync') ? '1' : '0',
                'arenagamer_sync_interval'   => $this->input->post('sync_interval'),
            ];

            $password = $this->input->post('admin_password');
            if (!empty($password)) {
                $settings['arenagamer_admin_password'] = $password;
            }

            foreach ($settings as $key => $val) {
                update_option($key, $val);
            }

            set_alert('success', 'Configurações salvas com sucesso');
            redirect(admin_url('arenagamer/settings'));
        }

        $data['title'] = 'ArenaGamer - Configurações';
        $this->load->view('admin/settings/index', $data);
    }

    /**
     * Test API Connection (AJAX)
     */
    public function test_connection()
    {
        if (!has_permission('arenagamer', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        $result = $this->arenagamer_api->test_connection();
        echo json_encode($result);
    }
}
