<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Arenagamer extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('arenagamer/arenagamer');
        $this->load->library('arenagamer/ArenaGamer_api', [], 'arenagamer_api');
        $this->load->model('arenagamer/arenagamer_model');
    }

    /**
     * Dashboard
     */
    public function index()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Dashboard';
        $data['tournaments'] = $this->arenagamer_api->get_my_managed_tournaments(0, 5);
        $data['users'] = $this->arenagamer_api->get_users(0, 5);
        $data['wallet'] = $this->arenagamer_api->get_wallet_balance();
        $data['auth_user'] = $this->arenagamer_api->get_auth_user();

        $this->load->view('admin/dashboard', $data);
    }

    /**
     * Tournaments
     */
    public function tournaments()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
        $view = $this->input->get('view') ?: 'all';
        $sort = $this->input->get('sort') ? [(string) $this->input->get('sort')] : arenagamer_tournament_list_sort();

        switch ($view) {
            case 'my-managed':
                $response = $this->arenagamer_api->get_my_managed_tournaments($page, 20, $sort);
                break;
            case 'my-created':
                $response = $this->arenagamer_api->get_my_created_tournaments($page, 20, $sort);
                break;
            case 'my-joined':
                $response = $this->arenagamer_api->get_my_joined_tournaments($page, 20, $sort);
                break;
            default:
                $view = 'all';
                $response = $this->arenagamer_api->get_admin_tournaments($page, 20, $sort);
                break;
        }

        $data['title'] = 'ArenaGamer - Torneios';
        $data['view'] = $view;
        $data['response'] = $response;
        $data['client_names'] = $this->build_client_names_map(arenagamer_paginated_content($response));
        $data['api_error'] = ($response === null || !arenagamer_api_is_success($response))
            ? $this->arenagamer_api->get_last_error()
            : '';

        $this->load->view('admin/tournaments/index', $data);
    }

    public function tournament($id = '')
    {
        if (!staff_can('create', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Novo Torneio';
        $data['tournament'] = null;
        $data['presets'] = $this->arenagamer_api->get_presets();
        $data['auth_user'] = $this->arenagamer_api->get_auth_user();
        $data['clients'] = $this->arenagamer_api->is_staff_user()
            ? $this->arenagamer_model->get_clients_for_select()
            : [];
        $data['api_error'] = '';

        if ($this->input->post()) {
            $payload = arenagamer_tournament_payload_from_input($this->input, $data['auth_user'], $data['presets'] ?? null, [
                'api' => $this->arenagamer_api,
            ]);
            $payloadError = arenagamer_tournament_payload_error($payload);

            if ($payloadError !== null) {
                set_alert('danger', $payloadError);
                $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
                $data['api_error'] = $payloadError;
                $this->load->view('admin/tournaments/form', $data);
                return;
            }
            $payload = arenagamer_tournament_sanitize_payload($payload);

            if (empty($payload['name'])) {
                set_alert('danger', 'O nome do torneio é obrigatório');
                $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
                $this->load->view('admin/tournaments/form', $data);
                return;
            }

            $result = $this->arenagamer_api->create_tournament($payload);

            if (arenagamer_api_is_success($result)) {
                $created = arenagamer_api_data($result);
                $slug = $created['slug'] ?? '';
                set_alert('success', arenagamer_api_message($result, 'Torneio criado com sucesso'));
                $this->arenagamer_model->log_sync('tournament', 0, 'create', 'success', $payload['name']);

                if ($slug !== '') {
                    redirect(admin_url('arenagamer/tournament_detail/' . $slug));
                }

                redirect(admin_url('arenagamer/tournaments?view=my-managed'));
            }

            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', 0, 'create', 'error', $this->arenagamer_api->get_last_error());
            $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
            $data['api_error'] = $this->arenagamer_api->get_last_error();
        }

        $this->load->view('admin/tournaments/form', $data);
    }

    public function tournament_edit($slug = '')
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $slug = trim((string) $slug);
        if ($slug === '') {
            redirect(admin_url('arenagamer/tournaments'));
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para editar este torneio.');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        $data['title'] = 'ArenaGamer - Editar Torneio';
        $data['is_edit'] = true;
        $data['presets'] = $this->arenagamer_api->get_presets();
        $data['auth_user'] = $this->arenagamer_api->get_auth_user();
        $data['clients'] = $this->arenagamer_api->is_staff_user()
            ? $this->arenagamer_model->get_clients_for_select()
            : [];
        $data['api_error'] = '';

        $existingResponse = $this->arenagamer_api->get_tournament($slug);
        if (!arenagamer_api_is_success($existingResponse)) {
            set_alert('danger', 'Torneio não encontrado: ' . $this->arenagamer_api->get_last_error());
            redirect(admin_url('arenagamer/tournaments'));
        }
        $data['tournament'] = arenagamer_api_data($existingResponse);

        if (!arenagamer_tournament_is_editable($data['tournament'])) {
            set_alert('warning', arenagamer_tournament_not_editable_message());
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        if ($this->input->post()) {
            $updateOptions = ['is_update' => true, 'api' => $this->arenagamer_api, 'existing_tournament' => $data['tournament']];
            $payload = arenagamer_tournament_payload_from_input($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions);
            $payloadError = arenagamer_tournament_payload_error($payload);

            if ($payloadError !== null) {
                set_alert('danger', $payloadError);
                $data['tournament'] = array_merge($data['tournament'], arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions));
                $data['api_error'] = $payloadError;
                $this->load->view('admin/tournaments/form', $data);
                return;
            }
            $payload = arenagamer_tournament_sanitize_payload($payload);

            if (empty($payload['name'])) {
                set_alert('danger', 'O nome do torneio é obrigatório');
                $data['tournament'] = array_merge($data['tournament'], arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions));
                $this->load->view('admin/tournaments/form', $data);
                return;
            }

            $result = $this->arenagamer_api->update_tournament($slug, $payload);

            if (arenagamer_api_is_success($result)) {
                set_alert('success', arenagamer_api_message($result, 'Torneio atualizado com sucesso'));
                $this->arenagamer_model->log_sync('tournament', 0, 'update', 'success', $payload['name']);
                redirect(admin_url('arenagamer/tournament_detail/' . $slug));
            }

            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', 0, 'update', 'error', $this->arenagamer_api->get_last_error());
            $data['tournament'] = array_merge($data['tournament'], arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions));
            $data['api_error'] = $this->arenagamer_api->get_last_error();
        }

        $this->load->view('admin/tournaments/form', $data);
    }

    public function tournament_detail($slug)
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Torneio';
        $data['tournament'] = $this->arenagamer_api->get_tournament($slug);
        $data['matches'] = $this->arenagamer_api->get_tournament_matches($slug);
        $data['standings'] = $this->arenagamer_api->get_tournament_standings($slug);
        $data['can_manage'] = $this->arenagamer_api->can_manage_tournament($slug);

        $tournamentData = arenagamer_api_data($data['tournament']);
        $clientUserId = is_array($tournamentData) ? ($tournamentData['clientUserId'] ?? null) : null;
        $data['client_name'] = $clientUserId
            ? $this->arenagamer_model->get_client_company_name($clientUserId)
            : null;
        $data['managers'] = [];
        $data['grantable_contacts'] = [];
        $data['primary_contact_ids'] = [];

        if ($data['can_manage'] && $clientUserId) {
            $managersResponse = $this->arenagamer_api->get_tournament_managers($slug);
            $data['managers'] = arenagamer_api_data($managersResponse, []);
            if (!is_array($data['managers'])) {
                $data['managers'] = [];
            }

            $data['primary_contact_ids'] = $this->arenagamer_model->get_primary_contact_ids($clientUserId);
            $existingManagerIds = array_column($data['managers'], 'contactId');
            $allContacts = arenagamer_paginated_content($this->arenagamer_api->get_contacts(0, 200));
            $data['grantable_contacts'] = $this->arenagamer_model->get_grantable_contacts(
                $allContacts,
                $clientUserId,
                $existingManagerIds,
                $data['primary_contact_ids']
            );
        }

        $this->load->view('admin/tournaments/detail', $data);
    }

    public function grant_tournament_manager($slug, $contactId)
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if (!$slug || !$contactId) {
            redirect(admin_url('arenagamer/tournaments'));
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        $result = $this->arenagamer_api->grant_tournament_manager($slug, $contactId);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Permissão concedida com sucesso'));
            $this->arenagamer_model->log_sync('tournament', (int) $contactId, 'grant_manager', 'success', "Slug: {$slug}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', (int) $contactId, 'grant_manager', 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url('arenagamer/tournament_detail/' . $slug));
    }

    public function revoke_tournament_manager($slug, $contactId)
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if (!$slug || !$contactId) {
            redirect(admin_url('arenagamer/tournaments'));
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        $result = $this->arenagamer_api->revoke_tournament_manager($slug, $contactId);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Permissão revogada com sucesso'));
            $this->arenagamer_model->log_sync('tournament', (int) $contactId, 'revoke_manager', 'success', "Slug: {$slug}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', (int) $contactId, 'revoke_manager', 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url('arenagamer/tournament_detail/' . $slug));
    }

    public function tournament_action($slug, $action)
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
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
            case 'advance_round':
                $result = $this->arenagamer_api->advance_round($slug);
                break;
            case 'generate_knockout':
                $result = $this->arenagamer_api->generate_knockout($slug);
                break;
            case 'finalize':
                $result = $this->arenagamer_api->finalize_tournament($slug);
                break;
            case 'cancel':
                $result = $this->arenagamer_api->cancel_tournament($slug);
                break;
            default:
                set_alert('warning', 'Ação não reconhecida');
                redirect(admin_url('arenagamer/tournaments'));
                return;
        }

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Ação executada com sucesso'));
            $this->arenagamer_model->log_sync('tournament', 0, $action, 'success', "Slug: {$slug}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', 0, $action, 'error', $this->arenagamer_api->get_last_error());
        }

        if ($action === 'cancel') {
            redirect(admin_url('arenagamer/tournaments'));
            return;
        }

        redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
    }

    public function expel_participant($slug, $participantId)
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if (!$slug || !$participantId) {
            redirect(admin_url('arenagamer/tournaments'));
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        $result = $this->arenagamer_api->expel_participant($slug, $participantId);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Participante expulso com sucesso'));
            $this->arenagamer_model->log_sync('tournament', 0, 'expel_participant', 'success', "Slug: {$slug}, ID: {$participantId}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', 0, 'expel_participant', 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
    }

    public function reschedule_match($matchId)
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $slug = $this->input->post('slug');
        $scheduledAt = trim((string) $this->input->post('scheduled_at'));

        if (!$matchId || !$slug || $scheduledAt === '') {
            set_alert('danger', 'Dados inválidos para reagendar a partida');
            redirect($slug ? admin_url("arenagamer/tournament_detail/{$slug}") : admin_url('arenagamer/tournaments'));
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        $result = $this->arenagamer_api->reschedule_match($matchId, [
            'scheduledAt' => date('c', strtotime($scheduledAt)),
        ]);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Partida reagendada com sucesso'));
            $this->arenagamer_model->log_sync('tournament', (int) $matchId, 'reschedule_match', 'success', "Slug: {$slug}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', (int) $matchId, 'reschedule_match', 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
    }

    public function record_match_result($matchId)
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $slug = $this->input->post('slug');
        $winnerParticipantId = (int) $this->input->post('winner_participant_id');
        $homeScore = $this->input->post('home_score');
        $awayScore = $this->input->post('away_score');

        if (!$matchId || !$slug) {
            set_alert('danger', 'Dados inválidos para registrar o resultado');
            redirect($slug ? admin_url("arenagamer/tournament_detail/{$slug}") : admin_url('arenagamer/tournaments'));
        }

        if ($homeScore === '' || $homeScore === null || $awayScore === '' || $awayScore === null) {
            set_alert('danger', 'Informe o placar da partida');
            redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
        }

        if (!$this->arenagamer_api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(admin_url('arenagamer/tournament_detail/' . $slug));
        }

        $proofUrl = '';
        $proofUpload = arenagamer_handle_match_proof_upload('proof_file');
        if (is_array($proofUpload) && isset($proofUpload['error'])) {
            set_alert('danger', 'Erro no upload do comprovante: ' . $proofUpload['error']);
            redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
        }
        if (is_string($proofUpload)) {
            $proofUrl = $proofUpload;
        }

        $result = $this->arenagamer_api->record_match_result($matchId, [
            'winnerParticipantId' => $winnerParticipantId,
            'homeScore'           => $homeScore,
            'awayScore'           => $awayScore,
            'proofUrl'            => $proofUrl,
        ]);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Resultado registrado com sucesso'));
            $this->arenagamer_model->log_sync('tournament', (int) $matchId, 'record_match_result', 'success', "Slug: {$slug}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('tournament', (int) $matchId, 'record_match_result', 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url("arenagamer/tournament_detail/{$slug}"));
    }

    /**
     * Plans catalog
     */
    public function plans()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if ($this->input->get('tab') === 'subscriptions') {
            $redirectQuery = [];
            if (trim((string) $this->input->get('search')) !== '') {
                $redirectQuery['search'] = trim((string) $this->input->get('search'));
            }
            if (trim((string) $this->input->get('client_search')) !== '') {
                $redirectQuery['client_search'] = trim((string) $this->input->get('client_search'));
            }
            if ($this->input->get('page')) {
                $redirectQuery['page'] = (int) $this->input->get('page');
            }

            $suffix = !empty($redirectQuery) ? '?' . http_build_query($redirectQuery) : '';
            redirect(admin_url('arenagamer/subscriptions' . $suffix));
        }

        $data['title'] = 'ArenaGamer - Planos';
        $data['plans'] = $this->arenagamer_api->get_plans();
        $plansData = arenagamer_api_data($data['plans'], []);
        if (is_array($plansData)) {
            usort($plansData, function ($a, $b) {
                return (int) ($a['sortOrder'] ?? 0) <=> (int) ($b['sortOrder'] ?? 0);
            });
            if (is_array($data['plans']) && array_key_exists('data', $data['plans'])) {
                $data['plans']['data'] = $plansData;
            }
        }
        $data['plans_list'] = $plansData;
        $data['api_error'] = $data['plans'] === null ? $this->arenagamer_api->get_last_error() : '';

        $this->load->view('admin/plans/index', $data);
    }

    /**
     * Client subscriptions (admin)
     */
    public function subscriptions()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if ($this->input->post('assign_subscription')) {
            if (!staff_can('edit', 'arenagamer')) {
                access_denied('arenagamer');
            }

            $clientUserId = (int) $this->input->post('client_userid');
            $planId = (int) $this->input->post('plan_id');
            $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
                ? arenagamer_normalize_billing_period_months($this->input->post('billing_period'))
                : 1;

            if ($clientUserId <= 0 || $planId <= 0) {
                set_alert('danger', 'Selecione um cliente e um plano válidos.');
                redirect(admin_url('arenagamer/subscriptions'));
            }

            $result = $this->arenagamer_api->admin_assign_subscription($clientUserId, $planId, $billingPeriodMonths);
            if (arenagamer_api_is_success($result)) {
                $company = $this->arenagamer_model->get_client_company_name($clientUserId);
                set_alert('success', 'Plano atribuído a ' . ($company ?: ('cliente #' . $clientUserId)) . '.');
                $this->arenagamer_model->log_sync('subscription', $clientUserId, 'assign', 'success', 'plan_id=' . $planId);
            } else {
                set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
                $this->arenagamer_model->log_sync('subscription', $clientUserId, 'assign', 'error', $this->arenagamer_api->get_last_error());
            }

            redirect(admin_url('arenagamer/subscriptions'));
        }

        if ($this->input->post('remove_subscription')) {
            if (!staff_can('edit', 'arenagamer')) {
                access_denied('arenagamer');
            }

            $clientUserId = (int) $this->input->post('client_userid');
            if ($clientUserId <= 0) {
                set_alert('danger', 'Cliente inválido.');
                redirect(admin_url('arenagamer/subscriptions'));
            }

            $result = $this->arenagamer_api->admin_remove_subscription($clientUserId);
            if (arenagamer_api_is_success($result)) {
                if (function_exists('arenagamer_stop_plan_recurring')) {
                    arenagamer_stop_plan_recurring($clientUserId);
                }
                $company = $this->arenagamer_model->get_client_company_name($clientUserId);
                set_alert('success', 'Plano removido de ' . ($company ?: ('cliente #' . $clientUserId)) . '.');
                $this->arenagamer_model->log_sync('subscription', $clientUserId, 'remove', 'success', 'Plano removido pelo staff');
            } else {
                set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
                $this->arenagamer_model->log_sync('subscription', $clientUserId, 'remove', 'error', $this->arenagamer_api->get_last_error());
            }

            redirect(admin_url('arenagamer/subscriptions'));
        }

        if ($this->input->post('reset_subscription_usage')) {
            if (!staff_can('edit', 'arenagamer')) {
                access_denied('arenagamer');
            }

            $clientUserId = (int) $this->input->post('client_userid');
            if ($clientUserId <= 0) {
                set_alert('danger', 'Cliente inválido.');
                redirect(admin_url('arenagamer/subscriptions'));
            }

            $result = $this->arenagamer_api->admin_reset_subscription_usage($clientUserId);
            if (arenagamer_api_is_success($result)) {
                $company = $this->arenagamer_model->get_client_company_name($clientUserId);
                set_alert('success', 'Uso mensal do plano resetado para ' . ($company ?: ('cliente #' . $clientUserId)) . '.');
                $this->arenagamer_model->log_sync('subscription', $clientUserId, 'reset_usage', 'success', 'Contagem mensal zerada');
            } else {
                set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
                $this->arenagamer_model->log_sync('subscription', $clientUserId, 'reset_usage', 'error', $this->arenagamer_api->get_last_error());
            }

            redirect(admin_url('arenagamer/subscriptions'));
        }

        $search = trim((string) $this->input->get('search'));
        $clientSearch = trim((string) $this->input->get('client_search'));
        $pageSize = function_exists('arenagamer_admin_page_size') ? arenagamer_admin_page_size() : 25;
        $page = function_exists('arenagamer_spring_page_index') ? arenagamer_spring_page_index() : 0;

        $data['title'] = 'ArenaGamer - Assinaturas de clientes';
        $data['search'] = $search;
        $data['client_search'] = $clientSearch;
        $data['can_manage_subscriptions'] = staff_can('edit', 'arenagamer');
        $data['plans'] = $this->arenagamer_api->get_plans();
        $plansData = arenagamer_api_data($data['plans'], []);
        if (is_array($plansData)) {
            usort($plansData, function ($a, $b) {
                return (int) ($a['sortOrder'] ?? 0) <=> (int) ($b['sortOrder'] ?? 0);
            });
        }
        $data['plans_list'] = is_array($plansData) ? $plansData : [];
        $data['billing_periods'] = function_exists('arenagamer_billing_periods') ? arenagamer_billing_periods() : [];

        $subscriptionsResponse = $this->arenagamer_api->get_admin_subscriptions($page, $pageSize, $search);
        $data['subscriptions_response'] = $subscriptionsResponse;
        $data['subscriptions'] = arenagamer_paginated_content($subscriptionsResponse);
        $data['subscriptions_pagination'] = arenagamer_pagination_meta($subscriptionsResponse);
        $data['client_options'] = $this->arenagamer_model->search_clients($clientSearch !== '' ? $clientSearch : $search, 50);

        $apiErrors = [];
        if ($data['plans'] === null) {
            $apiErrors[] = $this->arenagamer_api->get_last_error();
        }
        if ($subscriptionsResponse === null) {
            $apiErrors[] = $this->arenagamer_api->get_last_error();
        }
        $data['api_error'] = implode(' | ', array_filter($apiErrors));

        $this->load->view('admin/subscriptions/index', $data);
    }

    public function plan_detail($id)
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if (!$id) {
            redirect(admin_url('arenagamer/plans'));
        }

        $response = $this->arenagamer_api->get_plan($id);
        $plan = arenagamer_api_data($response);

        if (!arenagamer_api_is_success($response) || empty($plan)) {
            set_alert('danger', 'Plano não encontrado: ' . $this->arenagamer_api->get_last_error());
            redirect(admin_url('arenagamer/plans'));
        }

        $data['title'] = 'ArenaGamer - ' . ($plan['name'] ?? 'Plano');
        $data['plan'] = $plan;

        $pageSize = function_exists('arenagamer_admin_page_size') ? arenagamer_admin_page_size() : 25;
        $page = function_exists('arenagamer_spring_page_index') ? arenagamer_spring_page_index() : 0;
        $search = trim((string) $this->input->get('search'));
        $subscriptionsResponse = $this->arenagamer_api->get_admin_subscriptions_by_plan($id, $page, $pageSize, $search);
        $data['subscriptions'] = arenagamer_paginated_content($subscriptionsResponse);
        $data['subscriptions_pagination'] = arenagamer_pagination_meta($subscriptionsResponse);
        $data['subscriptions_search'] = $search;
        $data['can_manage_subscriptions'] = staff_can('edit', 'arenagamer');
        $data['subscriptions_error'] = $subscriptionsResponse === null ? $this->arenagamer_api->get_last_error() : '';

        $this->load->view('admin/plans/detail', $data);
    }

    public function plan($id = '')
    {
        $isCreate = ($id === '' || $id === '0');

        if ($isCreate) {
            if (!staff_can('create', 'arenagamer')) {
                access_denied('arenagamer');
            }
            $data['title'] = 'ArenaGamer - Novo Plano';
            $data['plan'] = null;
        } else {
            if (!staff_can('edit', 'arenagamer')) {
                access_denied('arenagamer');
            }
            $response = $this->arenagamer_api->get_plan($id);
            if (!arenagamer_api_is_success($response) || !arenagamer_api_data($response)) {
                set_alert('danger', 'Plano não encontrado: ' . $this->arenagamer_api->get_last_error());
                redirect(admin_url('arenagamer/plans'));
            }
            $data['title'] = 'ArenaGamer - Editar Plano';
            $data['plan'] = arenagamer_api_data($response);
        }

        $data['api_error'] = '';

        if ($this->input->post()) {
            $payload = arenagamer_plan_payload_from_input($this->input);

            if (empty($payload['name'])) {
                set_alert('danger', 'O nome do plano é obrigatório');
                $data['plan'] = arenagamer_plan_from_post($this->input);
                $this->load->view('admin/plans/form', $data);
                return;
            }

            if ($isCreate) {
                $result = $this->arenagamer_api->create_plan($payload);
                $action = 'create';
            } else {
                $result = $this->arenagamer_api->update_plan($id, $payload);
                $action = 'update';
            }

            if (arenagamer_api_is_success($result)) {
                $entityId = (int) $id;
                if ($action === 'create') {
                    $created = arenagamer_api_data($result);
                    $entityId = (int) ($created['id'] ?? 0);
                }
                set_alert('success', arenagamer_api_message(
                    $result,
                    $isCreate ? 'Plano criado com sucesso' : 'Plano atualizado com sucesso'
                ));
                $this->arenagamer_model->log_sync('plan', $entityId, $action, 'success', $payload['name']);

                if ($entityId > 0) {
                    redirect(admin_url('arenagamer/plan_detail/' . $entityId));
                }

                redirect(admin_url('arenagamer/plans'));
            }

            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('plan', (int) $id, $action, 'error', $this->arenagamer_api->get_last_error());
            $data['plan'] = arenagamer_plan_from_post($this->input);
            $data['api_error'] = $this->arenagamer_api->get_last_error();
        }

        $this->load->view('admin/plans/form', $data);
    }

    public function delete_plan($id)
    {
        if (!staff_can('delete', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if (!$id) {
            redirect(admin_url('arenagamer/plans'));
        }

        $result = $this->arenagamer_api->delete_plan($id);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Plano removido com sucesso'));
            $this->arenagamer_model->log_sync('plan', (int) $id, 'delete', 'success', "ID: {$id}");
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('plan', (int) $id, 'delete', 'error', $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url('arenagamer/plans'));
    }

    /**
     * Presets
     */
    public function presets()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $data['title'] = 'ArenaGamer - Presets';
        $data['presets'] = $this->arenagamer_api->get_presets();

        $this->load->view('admin/presets/index', $data);
    }

    public function preset($id = '')
    {
        $isCreate = ($id === '' || $id === '0');

        if ($isCreate) {
            if (!staff_can('create', 'arenagamer')) {
                access_denied('arenagamer');
            }
            $data['title'] = 'ArenaGamer - Novo Preset';
            $data['preset'] = null;
        } else {
            if (!staff_can('edit', 'arenagamer')) {
                access_denied('arenagamer');
            }
            $response = $this->arenagamer_api->get_preset($id);
            if (!arenagamer_api_is_success($response) || !arenagamer_api_data($response)) {
                set_alert('danger', 'Preset não encontrado: ' . $this->arenagamer_api->get_last_error());
                redirect(admin_url('arenagamer/presets'));
            }
            $data['title'] = 'ArenaGamer - Editar Preset';
            $data['preset'] = arenagamer_api_data($response);
        }

        $data['api_error'] = '';

        if ($this->input->post()) {
            $payload = arenagamer_preset_payload_from_input($this->input);

            if (empty($payload['gameName'])) {
                set_alert('danger', 'O nome do jogo é obrigatório');
                $data['preset'] = arenagamer_preset_from_post($this->input);
                $this->load->view('admin/presets/form', $data);
                return;
            }

            if (!empty($payload['_upload_error'])) {
                $uploadError = $payload['_upload_error'];
                unset($payload['_upload_error']);
                set_alert('danger', 'Erro no upload: ' . $uploadError);
                $data['preset'] = arenagamer_preset_from_post($this->input);
                $data['api_error'] = $uploadError;
                $this->load->view('admin/presets/form', $data);
                return;
            }
            unset($payload['_upload_error']);

            if ($isCreate) {
                $result = $this->arenagamer_api->create_preset($payload);
                $action = 'create';
            } else {
                $result = $this->arenagamer_api->update_preset($id, $payload);
                $action = 'update';
            }

            if (arenagamer_api_is_success($result)) {
                $entityId = (int) $id;
                if ($action === 'create') {
                    $created = arenagamer_api_data($result);
                    $entityId = (int) ($created['id'] ?? 0);
                }
                set_alert('success', arenagamer_api_message(
                    $result,
                    $isCreate ? 'Preset criado com sucesso' : 'Preset atualizado com sucesso'
                ));
                $this->arenagamer_model->log_sync('preset', $entityId, $action, 'success', $payload['gameName']);
                redirect(admin_url('arenagamer/presets'));
            }

            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
            $this->arenagamer_model->log_sync('preset', (int) $id, $action, 'error', $this->arenagamer_api->get_last_error());
            $data['preset'] = arenagamer_preset_from_post($this->input);
            $data['api_error'] = $this->arenagamer_api->get_last_error();
        }

        $this->load->view('admin/presets/form', $data);
    }

    /**
     * Users
     */
    public function users()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;
        $data['title'] = 'ArenaGamer - Usuários';
        $data['response'] = $this->arenagamer_api->get_users($page, 20);
        $data['api_error'] = empty(arenagamer_paginated_content($data['response']))
            ? $this->arenagamer_api->get_last_error()
            : '';

        $this->load->view('admin/users/index', $data);
    }

    /**
     * Contacts (client contacts from Perfex)
     */
    public function contacts()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
        $data['title'] = 'ArenaGamer - Contatos';
        $data['response'] = $this->arenagamer_api->get_contacts($page, 20);
        $data['api_error'] = empty(arenagamer_paginated_content($data['response']))
            ? $this->arenagamer_api->get_last_error()
            : '';

        $this->load->view('admin/contacts/index', $data);
    }

    /**
     * Audits
     */
    public function audits()
    {
        if (!staff_can('view', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $page = $this->input->get('page') ? (int)$this->input->get('page') : 0;
        $data['title'] = 'ArenaGamer - Auditoria';
        $data['response'] = $this->arenagamer_api->get_audits($page, 50);

        $this->load->view('admin/audits/index', $data);
    }

    /**
     * Ajuste manual de créditos (aba do cliente no admin)
     */
    public function client_wallet($clientId)
    {
        if (!staff_can('manage_client_credits', 'arenagamer')) {
            access_denied('arenagamer');
        }

        $clientId = (int) $clientId;
        if ($clientId <= 0) {
            redirect(admin_url('clients'));
        }

        if (!$this->input->post()) {
            redirect(admin_url('clients/client/' . $clientId . '?group=arenagamer_credits'));
        }

        $contactId = (int) $this->input->post('contact_id');
        $action = (string) $this->input->post('wallet_action');
        $amount = (float) str_replace(',', '.', (string) $this->input->post('amount'));
        $description = trim((string) $this->input->post('description'));

        if ($amount <= 0) {
            set_alert('danger', 'Informe um valor válido.');
            redirect(admin_url('clients/client/' . $clientId . '?group=arenagamer_credits'));
        }

        $payload = [
            'amount'      => $amount,
            'description' => $description,
        ];

        if ($action === 'withdraw') {
            $result = $this->arenagamer_api->admin_wallet_withdraw($clientId, $payload);
            $successMessage = 'Créditos removidos com sucesso';
            $auditAction = 'WITHDRAW';
        } else {
            $result = $this->arenagamer_api->admin_wallet_deposit($clientId, $payload);
            $successMessage = 'Créditos adicionados com sucesso';
            $auditAction = 'DEPOSIT';
        }

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, $successMessage));
            arenagamer_audit_message(
                $auditAction,
                'wallet',
                $clientId,
                $description !== '' ? $description : null,
                (string) $amount
            );
        } else {
            set_alert('danger', 'Erro: ' . $this->arenagamer_api->get_last_error());
        }

        redirect(admin_url('clients/client/' . $clientId . '?group=arenagamer_credits'));
    }

    /**
     * Settings
     */
    public function settings()
    {
        if (!staff_can('edit', 'arenagamer')) {
            access_denied('arenagamer');
        }

        if ($this->input->post()) {
            $settings = [
                'arenagamer_api_url_auth'    => rtrim((string) $this->input->post('api_url_auth'), '/'),
                'arenagamer_api_url_common'  => rtrim((string) $this->input->post('api_url_common'), '/'),
                'arenagamer_api_url_admin'   => rtrim((string) $this->input->post('api_url_admin'), '/'),
                'arenagamer_api_url_public'  => rtrim((string) $this->input->post('api_url_public'), '/'),
                'arenagamer_admin_email'     => $this->input->post('admin_email'),
                'arenagamer_auto_sync'       => $this->input->post('auto_sync') ? '1' : '0',
                'arenagamer_sync_interval'   => $this->input->post('sync_interval'),
                'arenagamer_tournament_base_price'         => str_replace(',', '.', (string) $this->input->post('tournament_base_price')),
                'arenagamer_extra_participant_price'       => str_replace(',', '.', (string) $this->input->post('extra_participant_price')),
                'arenagamer_included_participants_default' => (int) $this->input->post('included_participants'),
                'arenagamer_max_owned_teams'               => max(1, (int) $this->input->post('max_owned_teams')),
                'arenagamer_max_participated_teams'        => max(1, (int) $this->input->post('max_participated_teams')),
                'arenagamer_max_tournaments_per_team'      => $this->input->post('unlimited_tournaments_per_team')
                    ? ''
                    : (string) max(1, (int) $this->input->post('max_tournaments_per_team')),
                'arenagamer_max_tournaments_per_client'    => $this->input->post('unlimited_tournaments_per_client')
                    ? ''
                    : (string) max(1, (int) $this->input->post('max_tournaments_per_client')),
            ];

            $password = $this->input->post('admin_password');
            if (!empty($password)) {
                $settings['arenagamer_admin_password'] = $password;
            }

            if ($this->input->post('regenerate_internal_secret')) {
                $settings['arenagamer_internal_secret'] = arenagamer_generate_internal_secret();
            }

            $pricingPayload = [
                'baseTournamentPrice'   => (float) $settings['arenagamer_tournament_base_price'],
                'extraParticipantPrice' => (float) $settings['arenagamer_extra_participant_price'],
                'includedParticipants'  => (int) $settings['arenagamer_included_participants_default'],
            ];
            $pricingValidation = arenagamer_validate_tournament_pricing_payload($pricingPayload);

            if ($pricingValidation !== null) {
                set_alert('danger', $pricingValidation);
                redirect(admin_url('arenagamer/settings'));
            }

            foreach ($settings as $key => $val) {
                update_option($key, $val);
            }

            $pricingResult = $this->arenagamer_api->update_tournament_pricing($pricingPayload);
            if (!arenagamer_api_is_success($pricingResult)) {
                set_alert('warning', 'Configurações locais salvas, mas falha ao sincronizar preços com a API: '
                    . $this->arenagamer_api->get_last_error());
            }

            $teamSettingsPayload = [
                'maxOwnedTeamsPerClient'        => (int) $settings['arenagamer_max_owned_teams'],
                'maxParticipatedTeamsPerClient' => (int) $settings['arenagamer_max_participated_teams'],
                'maxTournamentsPerTeam'          => $settings['arenagamer_max_tournaments_per_team'] === ''
                    ? null
                    : (int) $settings['arenagamer_max_tournaments_per_team'],
                'maxTournamentsPerClient'        => $settings['arenagamer_max_tournaments_per_client'] === ''
                    ? null
                    : (int) $settings['arenagamer_max_tournaments_per_client'],
            ];
            $teamSettingsResult = $this->arenagamer_api->update_team_settings($teamSettingsPayload);
            if (!arenagamer_api_is_success($teamSettingsResult)) {
                set_alert('warning', 'Configurações locais salvas, mas falha ao sincronizar regras de times com a API: '
                    . $this->arenagamer_api->get_last_error());
            }

            $auditNote = 'Configurações do módulo ArenaGamer atualizadas';
            if (!empty($password)) {
                $auditNote .= ' (senha da API alterada)';
            }
            $auditNote .= sprintf(
                ' | Torneio padrão: %s créditos, incluídos: %d, avulso: %s créditos',
                $settings['arenagamer_tournament_base_price'],
                (int) $settings['arenagamer_included_participants_default'],
                $settings['arenagamer_extra_participant_price']
            );
            arenagamer_audit_message('UPDATE', 'settings', 0, $auditNote);

            set_alert('success', 'Configurações salvas com sucesso');
            redirect(admin_url('arenagamer/settings'));
        }

        $pricingResponse = $this->arenagamer_api->get_tournament_pricing();
        $pricing = arenagamer_api_data($pricingResponse);
        if (!is_array($pricing) || empty($pricing)) {
            $pricing = arenagamer_tournament_pricing_local();
        }

        $data['title'] = 'ArenaGamer - Configurações';
        $data['tournament_pricing'] = $pricing;

        $teamSettingsResponse = $this->arenagamer_api->get_team_settings();
        $data['team_settings'] = arenagamer_api_is_success($teamSettingsResponse)
            ? arenagamer_api_data($teamSettingsResponse)
            : arenagamer_team_settings_local();

        $this->load->view('admin/settings/index', $data);
    }

    /**
     * Pesquisa jogos (presets) para autocomplete no formulário de torneio.
     */
    public function search_presets()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!staff_can('view', 'arenagamer')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $query = arenagamer_preset_search_term_from_input($this->input);
        if (mb_strlen($query) < 3) {
            echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $response = $this->arenagamer_api->search_presets($query, true);
        if (!arenagamer_api_is_success($response)) {
            echo json_encode([
                'success' => false,
                'message' => $this->arenagamer_api->get_last_error(),
                'data'    => [],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = arenagamer_filter_presets_by_search(arenagamer_api_data($response, []), $query);
        echo json_encode([
            'success' => true,
            'data'    => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Test API Connection (AJAX)
     */
    public function test_connection()
    {
        if (!staff_can('edit', 'arenagamer')) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }

        $result = $this->arenagamer_api->test_connection();
        echo json_encode($result);
    }

    /**
     * Mapa clientUserId => nome da empresa para listagens
     */
    private function build_client_names_map(array $tournaments)
    {
        $map = [];

        foreach ($tournaments as $tournament) {
            $clientUserId = $tournament['clientUserId'] ?? null;
            if ($clientUserId === null || $clientUserId === '' || isset($map[$clientUserId])) {
                continue;
            }

            $map[$clientUserId] = $this->arenagamer_model->get_client_company_name($clientUserId);
        }

        return $map;
    }
}
