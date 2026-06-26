<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Área do cliente — endpoints /common/** como CONTACT
 */
class Client extends ClientsController
{
    /** @var ArenaGamer_api */
    private $api;

    public function __construct()
    {
        parent::__construct();

        if (!is_client_logged_in()) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }

        $this->load->helper('arenagamer/arenagamer');
        $this->load->model('arenagamer/arenagamer_model');
        $this->api = arenagamer_contact_api();

        // Sincroniza JWT apenas ao entrar na área ArenaGamer (não bloqueia login do portal)
        arenagamer_run_pending_contact_sync($this->api);

        $this->data['arenagamer_needs_relink'] = false;
        $this->data['arenagamer_contact_email'] = arenagamer_get_logged_contact_email();

        if (!arenagamer_ensure_contact_api($this->api)) {
            $error = trim((string) $this->api->get_last_error());
            if ($error === '') {
                $error = 'Não foi possível conectar à API ArenaGamer. Verifique se a API está online.';
            }
            $this->session->set_flashdata('arenagamer_api_warning', $error);
            $this->data['arenagamer_needs_relink'] = true;
        }

        $this->enforce_active_plan();
    }

    /**
     * Redireciona para planos quando não há assinatura ativa
     */
    private function enforce_active_plan()
    {
        if (!empty($this->data['arenagamer_needs_relink'])) {
            return;
        }

        $allowed = ['index', 'plans', 'link_account'];
        if (in_array($this->router->fetch_method(), $allowed, true)) {
            return;
        }

        $plan = arenagamer_contact_plan($this->api->get_auth_user());
        if (arenagamer_plan_is_active($plan)) {
            return;
        }

        $authUser = $this->api->get_auth_user();
        if (arenagamer_contact_is_primary($authUser)) {
            set_alert('warning', 'Contrate ou renove um plano para acessar esta área.');
        } else {
            set_alert('warning', arenagamer_plan_subscribe_blocked_message());
        }
        redirect(arenagamer_client_url('plans'));
    }

    public function link_account()
    {
        if (!$this->input->post('password')) {
            set_alert('danger', 'Informe sua senha do portal.');
            redirect(arenagamer_client_url());
        }

        if (arenagamer_relink_contact_api($this->input->post('password', false), $this->api)) {
            set_alert('success', 'Conta ArenaGamer conectada com sucesso.');
            redirect(arenagamer_client_url());
        }

        set_alert('danger', $this->api->get_last_error() ?: 'Não foi possível conectar à API ArenaGamer.');
        redirect(arenagamer_client_url());
    }

    public function index()
    {
        $data['title'] = 'ArenaGamer';
        $data['auth_user'] = arenagamer_refresh_contact_auth_user($this->api) ?: $this->api->get_auth_user();
        $data['current_plan'] = arenagamer_contact_plan($data['auth_user']);
        $data['plan_is_active'] = arenagamer_plan_is_active($data['current_plan']);
        $data['can_subscribe_plan'] = arenagamer_can_subscribe_plan($data['auth_user']);
        $data['client_user_id'] = (int) get_client_user_id();
        $tournamentsResponse = $this->api->get_my_managed_tournaments(0, 5);
        $data['tournaments'] = arenagamer_api_is_success($tournamentsResponse) ? $tournamentsResponse : null;
        $data['can_view_wallet'] = arenagamer_contact_can_view_wallet($data['auth_user']);
        $data['can_use_wallet'] = arenagamer_contact_can_use_wallet($data['auth_user']);
        $data['wallet'] = $data['can_view_wallet'] ? $this->api->get_wallet_balance() : null;
        $apiWarning = trim((string) $this->session->flashdata('arenagamer_api_warning'));
        if ($tournamentsResponse === null && !$this->api->has_access_token()) {
            $this->data['arenagamer_needs_relink'] = true;
            if ($apiWarning === '') {
                $apiWarning = $this->api->get_last_error() ?: 'Conecte sua conta ArenaGamer informando a mesma senha do portal abaixo.';
            }
        }
        $data['api_error'] = $apiWarning !== '' ? $apiWarning : '';
        $data['arenagamer_needs_relink'] = !empty($this->data['arenagamer_needs_relink']);
        $data['arenagamer_contact_email'] = $this->data['arenagamer_contact_email'] ?? arenagamer_get_logged_contact_email();

        $this->data($data);
        $this->view('client/dashboard');
        $this->layout();
    }

    public function tournaments()
    {
        $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
        $view = $this->input->get('view') ?: 'my-managed';
        $sort = arenagamer_tournament_list_sort();
        $size = 20;

        switch ($view) {
            case 'my-created':
                $response = $this->api->get_my_created_tournaments($page, $size, $sort);
                break;
            case 'my-joined':
                $response = $this->api->get_my_joined_tournaments($page, $size, $sort);
                break;
            default:
                $view = 'my-managed';
                $response = $this->api->get_my_managed_tournaments($page, $size, $sort);
                break;
        }

        $data['title'] = 'Meus Torneios';
        $data['view'] = $view;
        $data['response'] = $response;
        $data['client_name'] = $this->arenagamer_model->get_client_company_name(get_client_user_id());
        $data['api_error'] = ($response === null || !arenagamer_api_is_success($response))
            ? $this->api->get_last_error()
            : '';

        $this->data($data);
        $this->view('client/tournaments/index');
        $this->layout();
    }

    public function tournament()
    {
        $data['title'] = 'Novo Torneio';
        $data['tournament'] = null;
        $data['presets'] = $this->api->get_cached_presets();
        $data['auth_user'] = arenagamer_refresh_contact_auth_user($this->api) ?: $this->api->get_auth_user();
        $data['current_plan'] = arenagamer_contact_plan($data['auth_user']);
        $data['api_error'] = '';
        $pricingResponse = $this->api->get_public_tournament_pricing();
        $data['tournament_pricing'] = arenagamer_api_data($pricingResponse, arenagamer_tournament_pricing_local());
        $walletResponse = $this->api->get_wallet_balance();
        $data['wallet'] = arenagamer_api_is_success($walletResponse) ? arenagamer_api_data($walletResponse) : null;

        if ($this->input->post()) {
            $payload = arenagamer_tournament_payload_from_input($this->input, $data['auth_user'], $data['presets'] ?? null, [
                'api' => $this->api,
            ]);
            $payloadError = arenagamer_tournament_payload_error($payload);

            if ($payloadError !== null) {
                set_alert('danger', $payloadError);
                $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
                $data['api_error'] = $payloadError;
                $this->data($data);
                $this->view('client/tournaments/form');
                $this->layout();
                return;
            }
            $payload = arenagamer_tournament_sanitize_payload($payload);

            if (empty($payload['name'])) {
                set_alert('danger', 'O nome do torneio é obrigatório');
                $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
                $this->data($data);
                $this->view('client/tournaments/form');
                $this->layout();
                return;
            }

            $planError = arenagamer_validate_tournament_against_plan(
                $data['current_plan'],
                (int) ($payload['participantsLimit'] ?? 0),
                (float) ($payload['entryFeeCredits'] ?? 0),
                $data['tournament_pricing'],
                $payload['prizeFunding'] ?? 'FIXED',
                [
                    'prizeType' => $payload['prizeType'] ?? 'MANUAL',
                    'prizePool' => $payload['prizePool'] ?? 0,
                ]
            );

            if ($planError !== null) {
                set_alert('danger', $planError);
                $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
                $data['api_error'] = $planError;
                $this->data($data);
                $this->view('client/tournaments/form');
                $this->layout();
                return;
            }

            $walletError = arenagamer_validate_tournament_wallet_balance(
                $data['current_plan'],
                (int) ($payload['participantsLimit'] ?? 0),
                $this->api->get_wallet_balance(),
                $data['tournament_pricing'],
                [
                    'prizeType'    => $payload['prizeType'] ?? 'MANUAL',
                    'prizeFunding' => $payload['prizeFunding'] ?? 'FIXED',
                    'prizePool'    => $payload['prizePool'] ?? 0,
                ]
            );

            if ($walletError !== null) {
                set_alert('danger', $walletError);
                $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
                $data['api_error'] = $walletError;
                $this->data($data);
                $this->view('client/tournaments/form');
                $this->layout();
                return;
            }

            $result = $this->api->create_tournament($payload);

            if (arenagamer_api_is_success($result)) {
                $created = arenagamer_api_data($result);
                set_alert('success', arenagamer_api_message($result, 'Torneio criado com sucesso'));

                if (!empty($created['slug'])) {
                    redirect(arenagamer_client_url('tournament_detail/' . $created['slug']));
                }

                redirect(arenagamer_client_url('tournaments?view=my-managed'));
            }

            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
            $data['tournament'] = arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null);
            $data['api_error'] = $this->api->get_last_error();
        }

        $this->data($data);
        $this->view('client/tournaments/form');
        $this->layout();
    }

    public function tournament_edit($slug = '')
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            redirect(arenagamer_client_url('tournaments'));
        }

        if (!$this->api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para editar este torneio.');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        $data['title'] = 'Editar Torneio';
        $data['is_edit'] = true;
        $data['presets'] = $this->api->get_cached_presets();
        $data['auth_user'] = arenagamer_refresh_contact_auth_user($this->api) ?: $this->api->get_auth_user();
        $data['current_plan'] = arenagamer_contact_plan($data['auth_user']);
        $data['api_error'] = '';
        $pricingResponse = $this->api->get_public_tournament_pricing();
        $data['tournament_pricing'] = arenagamer_api_data($pricingResponse, arenagamer_tournament_pricing_local());
        $walletResponse = $this->api->get_wallet_balance();
        $data['wallet'] = arenagamer_api_is_success($walletResponse) ? arenagamer_api_data($walletResponse) : null;

        $existingResponse = $this->api->get_tournament($slug);
        if (!arenagamer_api_is_success($existingResponse)) {
            set_alert('danger', 'Torneio não encontrado: ' . $this->api->get_last_error());
            redirect(arenagamer_client_url('tournaments'));
        }
        $data['tournament'] = arenagamer_api_data($existingResponse);

        if (!arenagamer_tournament_is_editable($data['tournament'])) {
            set_alert('warning', arenagamer_tournament_not_editable_message());
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        if ($this->input->post()) {
            $updateOptions = ['is_update' => true, 'api' => $this->api, 'existing_tournament' => $data['tournament']];
            $payload = arenagamer_tournament_payload_from_input($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions);
            $payloadError = arenagamer_tournament_payload_error($payload);

            if ($payloadError !== null) {
                set_alert('danger', $payloadError);
                $data['tournament'] = array_merge($data['tournament'], arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions));
                $data['api_error'] = $payloadError;
                $this->data($data);
                $this->view('client/tournaments/form');
                $this->layout();
                return;
            }
            $payload = arenagamer_tournament_sanitize_payload($payload);

            if (empty($payload['name'])) {
                set_alert('danger', 'O nome do torneio é obrigatório');
                $data['tournament'] = array_merge($data['tournament'], arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions));
                $this->data($data);
                $this->view('client/tournaments/form');
                $this->layout();
                return;
            }

            $result = $this->api->update_tournament($slug, $payload);

            if (arenagamer_api_is_success($result)) {
                set_alert('success', arenagamer_api_message($result, 'Torneio atualizado com sucesso'));
                redirect(arenagamer_client_url('tournament_detail/' . $slug));
            }

            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
            $data['tournament'] = array_merge($data['tournament'], arenagamer_tournament_from_post($this->input, $data['auth_user'], $data['presets'] ?? null, $updateOptions));
            $data['api_error'] = $this->api->get_last_error();
        }

        $this->data($data);
        $this->view('client/tournaments/form');
        $this->layout();
    }

    public function public_tournaments()
    {
        $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;
        $filter = strtoupper(trim((string) ($this->input->get('filter') ?? '')));
        $response = $this->api->list_tournaments($page, 20, [], $filter !== '' ? $filter : null);

        $data['title'] = 'Torneios públicos';
        $data['filter'] = $filter;
        $data['filters'] = arenagamer_public_tournament_filters();
        $data['response'] = $response;
        $data['api_error'] = ($response === null || !arenagamer_api_is_success($response))
            ? $this->api->get_last_error()
            : '';

        $this->data($data);
        $this->view('client/public_tournaments');
        $this->layout();
    }

    public function tournament_detail($slug)
    {
        $data['title'] = 'Torneio';
        $data['tournament'] = $this->api->get_tournament($slug);
        $data['matches'] = $this->api->get_tournament_matches($slug);
        $data['standings'] = $this->api->get_tournament_standings($slug);
        $data['can_manage'] = $this->api->can_manage_tournament($slug);
        $data['auth_user'] = $this->api->get_auth_user();

        $tournamentData = arenagamer_api_data($data['tournament']);
        $clientUserId = is_array($tournamentData) ? ($tournamentData['clientUserId'] ?? get_client_user_id()) : get_client_user_id();
        $data['client_name'] = $this->arenagamer_model->get_client_company_name($clientUserId);
        $data['managers'] = [];
        $data['grantable_contacts'] = [];
        $data['primary_contacts'] = [];
        $data['permission_rows'] = [];
        $data['can_grant_permissions'] = arenagamer_contact_is_primary($data['auth_user']);
        $data['my_teams'] = [];

        if (is_array($tournamentData) && ($tournamentData['format'] ?? '') === 'TEAM') {
            $teamsResponse = $this->api->get_my_teams();
            if (arenagamer_api_is_success($teamsResponse)) {
                $data['my_teams'] = arenagamer_api_data($teamsResponse, []);
            }
        }

        if ($data['can_manage'] && !empty($tournamentData['clientUserId'])) {
            $managersResponse = $this->api->get_tournament_managers($slug);
            if (arenagamer_api_is_success($managersResponse)) {
                $data['managers'] = arenagamer_api_data($managersResponse, []);
            }
            if (!is_array($data['managers'])) {
                $data['managers'] = [];
            }

            $data['primary_contacts'] = $this->arenagamer_model->get_primary_contacts($clientUserId);
            $data['permission_rows'] = arenagamer_tournament_permission_rows(
                $data['primary_contacts'],
                $data['managers']
            );

            if ($data['can_grant_permissions']) {
                $primaryIds = $this->arenagamer_model->get_primary_contact_ids($clientUserId);
                $data['grantable_contacts'] = $this->arenagamer_model->get_grantable_contacts_from_perfex(
                    $clientUserId,
                    array_column($data['managers'], 'contactId'),
                    $primaryIds
                );
            }
        }

        $this->data($data);
        $this->view('client/tournaments/detail');
        $this->layout();
    }

    public function tournament_action($slug, $action)
    {
        if (!$this->api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        switch ($action) {
            case 'open_registration':
                $result = $this->api->update_tournament_status($slug, 'REGISTRATION_OPEN');
                break;
            case 'close_registration':
                $result = $this->api->update_tournament_status($slug, 'REGISTRATION_CLOSED');
                break;
            case 'generate_bracket':
                $result = $this->api->generate_bracket($slug);
                break;
            case 'schedule':
                $result = $this->api->schedule_matches($slug);
                break;
            case 'advance_round':
                $result = $this->api->advance_round($slug);
                break;
            case 'generate_knockout':
                $result = $this->api->generate_knockout($slug);
                break;
            case 'finalize':
                $result = $this->api->finalize_tournament($slug);
                break;
            case 'cancel':
                $result = $this->api->cancel_tournament($slug);
                break;
            default:
                set_alert('warning', 'Ação não reconhecida');
                redirect(arenagamer_client_url('tournaments'));
                return;
        }

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Ação executada com sucesso'));
        } else {
            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
        }

        if ($action === 'cancel') {
            redirect(arenagamer_client_url('tournaments'));
            return;
        }

        redirect(arenagamer_client_url('tournament_detail/' . $slug));
    }

    public function record_match_result($matchId)
    {
        $slug = $this->input->post('slug');
        $winnerParticipantId = (int) $this->input->post('winner_participant_id');
        $homeScore = $this->input->post('home_score');
        $awayScore = $this->input->post('away_score');

        if (!$matchId || !$slug) {
            set_alert('danger', 'Dados inválidos para registrar o resultado');
            redirect($slug ? arenagamer_client_url('tournament_detail/' . $slug) : arenagamer_client_url('tournaments'));
            return;
        }

        if ($homeScore === '' || $homeScore === null || $awayScore === '' || $awayScore === null) {
            set_alert('danger', 'Informe o placar da partida');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
            return;
        }

        if (!$this->api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão para gerenciar este torneio');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
            return;
        }

        $proofUrl = '';
        $proofUpload = arenagamer_handle_match_proof_upload('proof_file');
        if (is_array($proofUpload) && isset($proofUpload['error'])) {
            set_alert('danger', 'Erro no upload do comprovante: ' . $proofUpload['error']);
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
            return;
        }
        if (is_string($proofUpload)) {
            $proofUrl = $proofUpload;
        }

        $result = $this->api->record_match_result($matchId, [
            'winnerParticipantId' => $winnerParticipantId,
            'homeScore'           => $homeScore,
            'awayScore'           => $awayScore,
            'proofUrl'            => $proofUrl,
        ]);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Resultado registrado com sucesso'));
        } else {
            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
        }

        redirect(arenagamer_client_url('tournament_detail/' . $slug));
    }

    public function join_tournament($slug)
    {
        $tournament = $this->api->get_tournament($slug);
        $tournamentData = arenagamer_api_data($tournament);
        $isTeam = is_array($tournamentData) && ($tournamentData['format'] ?? '') === 'TEAM';

        if ($isTeam) {
            $teamId = (int) $this->input->post('team_id');
            if ($teamId <= 0) {
                set_alert('danger', 'Selecione um time para se inscrever neste campeonato.');
                redirect(arenagamer_client_url('tournament_detail/' . $slug));
            }

            $result = $this->api->join_tournament_team($slug, [
                'teamId'           => $teamId,
                'availableWindows' => $this->input->post('available_windows') ?: [],
                'preferWeekends'   => (bool) $this->input->post('prefer_weekends'),
            ]);
        } else {
            $result = $this->api->join_tournament_solo($slug, [
                'availableWindows' => $this->input->post('available_windows') ?: [],
                'preferWeekends'     => (bool) $this->input->post('prefer_weekends'),
            ]);
        }

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Inscrição realizada com sucesso'));
        } else {
            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
        }

        redirect(arenagamer_client_url('tournament_detail/' . $slug));
    }

    public function grant_manager($slug, $contactId)
    {
        if (!$this->api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        if (!arenagamer_contact_is_primary($this->api->get_auth_user())) {
            set_alert('danger', 'Apenas o contato principal pode conceder permissões.');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        $result = $this->api->grant_tournament_manager($slug, $contactId);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Permissão concedida'));
        } else {
            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
        }

        redirect(arenagamer_client_url('tournament_detail/' . $slug));
    }

    public function revoke_manager($slug, $contactId)
    {
        if (!$this->api->can_manage_tournament($slug)) {
            set_alert('danger', 'Sem permissão');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        if (!arenagamer_contact_is_primary($this->api->get_auth_user())) {
            set_alert('danger', 'Apenas o contato principal pode revogar permissões.');
            redirect(arenagamer_client_url('tournament_detail/' . $slug));
        }

        $result = $this->api->revoke_tournament_manager($slug, $contactId);

        if (arenagamer_api_is_success($result)) {
            set_alert('success', arenagamer_api_message($result, 'Permissão revogada'));
        } else {
            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
        }

        redirect(arenagamer_client_url('tournament_detail/' . $slug));
    }

    public function wallet()
    {
        $authUser = $this->api->get_auth_user();
        if (!arenagamer_contact_can_view_wallet($authUser)) {
            set_alert('warning', 'Você não tem permissão para visualizar créditos.');
            redirect(arenagamer_client_url());
        }

        $page = $this->input->get('page') ? (int) $this->input->get('page') : 0;

        if ($this->input->post('wallet_action')) {
            if (!arenagamer_contact_can_use_wallet($authUser)) {
                set_alert('danger', 'Você não tem permissão para usar créditos.');
                redirect(arenagamer_client_url('wallet'));
            }

            $amount = (float) str_replace(',', '.', (string) $this->input->post('amount'));
            $description = trim((string) $this->input->post('description'));
            $action = $this->input->post('wallet_action');

            // Adicionar créditos só é permitido via compra com fatura Perfex
            // (botão "Comprar créditos"). Depósito manual direto foi removido.
            if ($action !== 'withdraw') {
                set_alert('info', 'Para adicionar créditos, utilize "Comprar créditos" — uma fatura será gerada no Perfex e o saldo creditado após o pagamento.');
                redirect(arenagamer_client_url('wallet'));
            }

            if ($amount <= 0) {
                set_alert('danger', 'Informe um valor válido');
                redirect(arenagamer_client_url('wallet'));
            }

            $payload = ['amount' => $amount, 'description' => $description];
            $result = $this->api->wallet_withdraw($payload);

            if (arenagamer_api_is_success($result)) {
                set_alert('success', arenagamer_api_message($result, 'Operação realizada com sucesso'));
            } else {
                set_alert('danger', 'Erro: ' . $this->api->get_last_error());
            }

            redirect(arenagamer_client_url('wallet'));
        }

        if ($this->input->post('save_wallet_permissions')) {
            if (!arenagamer_contact_is_primary($authUser)) {
                set_alert('danger', 'Apenas o contato principal pode alterar permissões de créditos.');
                redirect(arenagamer_client_url('wallet'));
            }

            $contactIds = $this->input->post('permission_contact_id') ?? [];
            $viewAllowed = array_map('strval', (array) ($this->input->post('permission_view') ?? []));
            $useAllowed = array_map('strval', (array) ($this->input->post('permission_use') ?? []));

            foreach ($contactIds as $contactId) {
                $contactId = (int) $contactId;
                $canView = in_array((string) $contactId, $viewAllowed, true);
                $canUse = in_array((string) $contactId, $useAllowed, true);
                if ($canUse) {
                    $canView = true;
                }

                $payload = [
                    'walletViewAllowed' => $canView,
                    'walletUseAllowed'  => $canUse,
                ];
                $result = $this->api->update_wallet_permission($contactId, $payload);
                if (!arenagamer_api_is_success($result)) {
                    set_alert('danger', 'Erro ao salvar permissões: ' . $this->api->get_last_error());
                    redirect(arenagamer_client_url('wallet'));
                }
            }

            set_alert('success', 'Permissões de créditos atualizadas');
            redirect(arenagamer_client_url('wallet'));
        }

        $data['title'] = 'Carteira';
        $data['auth_user'] = $authUser;
        $data['can_use_wallet'] = arenagamer_contact_can_use_wallet($authUser);
        $data['is_primary'] = arenagamer_contact_is_primary($authUser);
        $data['balance'] = $this->api->get_wallet_balance();
        $data['transactions'] = $this->api->get_wallet_transactions($page, 20);
        $data['wallet_permissions'] = $data['is_primary'] ? $this->api->get_wallet_permissions() : null;

        $this->data($data);
        $this->view('client/wallet');
        $this->layout();
    }

    public function buy_credits()
    {
        if (!$this->input->post()) {
            redirect(arenagamer_client_url());
        }

        $authUser = $this->api->get_auth_user();
        if (!arenagamer_contact_can_buy_credits($authUser)) {
            set_alert('danger', 'Você não tem permissão para comprar créditos.');
            redirect(arenagamer_client_url());
        }

        $credits = (float) str_replace(',', '.', (string) $this->input->post('credits'));
        if ($credits <= 0) {
            set_alert('danger', 'Informe uma quantidade válida de créditos.');
            redirect(arenagamer_client_url());
        }

        $this->load->model('arenagamer/arenagamer_credit_invoices_model');
        $clientUserId = (int) get_client_user_id();
        $pending = $this->arenagamer_credit_invoices_model->resolve_pending_for_client($clientUserId);

        if ($pending) {
            set_alert('info', 'Você já possui uma fatura pendente de créditos. Conclua o pagamento para receber o saldo.');
            redirect(arenagamer_invoice_payment_url((int) $pending['invoice_id']));
        }

        $result = $this->arenagamer_credit_invoices_model->create_credit_invoice(
            $clientUserId,
            (int) get_contact_user_id(),
            $credits
        );

        if (empty($result['success'])) {
            set_alert('danger', $result['message'] ?? 'Não foi possível gerar a fatura.');
            redirect(arenagamer_client_url());
        }

        set_alert(
            'success',
            'Fatura gerada para ' . number_format($credits, 0, ',', '.') . ' créditos. Após o pagamento, o saldo será creditado automaticamente.'
        );
        redirect($result['url']);
    }

    public function teams()
    {
        $data['title'] = 'Meus Times';
        $data['teams'] = $this->api->get_my_teams();
        $teamSettingsResponse = $this->api->get_public_team_settings();
        $data['team_settings'] = arenagamer_api_is_success($teamSettingsResponse)
            ? arenagamer_api_data($teamSettingsResponse)
            : arenagamer_team_settings_local();
        $data['edit_team'] = null;

        if ($this->input->post()) {
            $payload = arenagamer_team_payload_from_input($this->input);

            if (!empty($payload['_upload_error'])) {
                set_alert('danger', 'Erro no upload: ' . $payload['_upload_error']);
                redirect(arenagamer_client_url('teams'));
            }
            unset($payload['_upload_error']);

            $result = $this->api->create_team($payload);

            if (arenagamer_api_is_success($result)) {
                set_alert('success', arenagamer_api_message($result, 'Time criado com sucesso'));
            } else {
                set_alert('danger', 'Erro: ' . $this->api->get_last_error());
            }

            redirect(arenagamer_client_url('teams'));
        }

        $this->data($data);
        $this->view('client/teams');
        $this->layout();
    }

    public function team_edit($id = '')
    {
        $teamId = (int) $id;
        if ($teamId <= 0) {
            redirect(arenagamer_client_url('teams'));
        }

        $teamResponse = $this->api->get_team($teamId);
        if (!arenagamer_api_is_success($teamResponse)) {
            set_alert('danger', 'Time não encontrado: ' . $this->api->get_last_error());
            redirect(arenagamer_client_url('teams'));
        }

        $data['title'] = 'Editar Time';
        $data['teams'] = $this->api->get_my_teams();
        $teamSettingsResponse = $this->api->get_public_team_settings();
        $data['team_settings'] = arenagamer_api_is_success($teamSettingsResponse)
            ? arenagamer_api_data($teamSettingsResponse)
            : arenagamer_team_settings_local();
        $data['edit_team'] = arenagamer_api_data($teamResponse);

        if ($this->input->post()) {
            $payload = arenagamer_team_payload_from_input($this->input);

            if (!empty($payload['_upload_error'])) {
                set_alert('danger', 'Erro no upload: ' . $payload['_upload_error']);
                redirect(arenagamer_client_url('team_edit/' . $teamId));
            }
            unset($payload['_upload_error']);

            $result = $this->api->update_team($teamId, $payload);

            if (arenagamer_api_is_success($result)) {
                set_alert('success', arenagamer_api_message($result, 'Time atualizado com sucesso'));
                redirect(arenagamer_client_url('teams'));
            }

            set_alert('danger', 'Erro: ' . $this->api->get_last_error());
            $data['edit_team'] = array_merge($data['edit_team'], arenagamer_team_from_post($this->input));
        }

        $this->data($data);
        $this->view('client/teams');
        $this->layout();
    }

    public function profile()
    {
        $data['title'] = 'Meu Perfil';
        $data['profile'] = $this->api->get_me();

        if ($this->input->post()) {
            $payload = arenagamer_profile_payload_from_input($this->input);

            if (!empty($payload['_upload_error'])) {
                set_alert('danger', 'Erro no upload: ' . $payload['_upload_error']);
                redirect(arenagamer_client_url('profile'));
            }
            unset($payload['_upload_error']);

            $result = $this->api->update_profile($payload);

            if (arenagamer_api_is_success($result)) {
                set_alert('success', arenagamer_api_message($result, 'Perfil atualizado'));
                $data['profile'] = $result;
            } else {
                set_alert('danger', 'Erro: ' . $this->api->get_last_error());
            }
        }

        $this->data($data);
        $this->view('client/profile');
        $this->layout();
    }

    public function catalog()
    {
        $data['title'] = 'Catálogo';
        $data['presets'] = $this->api->get_cached_presets();
        $plansRaw = function_exists('get_contact_user_id') && get_contact_user_id()
            ? get_contact_meta(get_contact_user_id(), 'arenagamer_plans_cache')
            : '';
        $plansDecoded = $plansRaw ? json_decode((string) $plansRaw, true) : [];
        $data['plans'] = is_array($plansDecoded) ? $plansDecoded : [];

        $this->data($data);
        $this->view('client/catalog');
        $this->layout();
    }

    public function plans()
    {
        if ($this->input->post('cancel_plan')) {
            $meResponse = $this->api->get_me(true);
            $authUser = arenagamer_api_is_success($meResponse) ? arenagamer_api_data($meResponse) : $this->api->get_auth_user();
            $currentPlan = arenagamer_contact_plan($authUser);

            if (!arenagamer_can_cancel_plan($currentPlan, $authUser)) {
                if (arenagamer_plan_is_free($currentPlan)) {
                    set_alert('warning', 'O plano Free não pode ser cancelado.');
                } elseif (arenagamer_plan_cancel_scheduled($currentPlan)) {
                    set_alert('warning', 'O cancelamento já está agendado para o fim do período.');
                } elseif (!arenagamer_can_subscribe_plan($authUser)) {
                    set_alert('warning', arenagamer_plan_subscribe_blocked_message());
                } else {
                    set_alert('warning', 'Não há plano pago ativo para cancelar.');
                }
                redirect(arenagamer_client_url('plans'));
            }

            $result = $this->api->cancel_plan();

            if (arenagamer_api_is_success($result)) {
                if (function_exists('arenagamer_stop_plan_recurring')) {
                    arenagamer_stop_plan_recurring((int) get_client_user_id());
                }
                $planData = arenagamer_api_data($result);
                $message = arenagamer_api_message($result, 'Cancelamento agendado para o fim do período atual.');
                if (is_array($planData)) {
                    $scheduled = trim(arenagamer_plan_scheduled_message($planData));
                    if ($scheduled !== '') {
                        $message = $scheduled;
                    }
                }
                set_alert('success', $message);
                redirect(arenagamer_client_url('plans'));
            }

            set_alert('danger', 'Erro: ' . ($this->api->get_last_error() ?: 'Não foi possível cancelar o plano.'));
            redirect(arenagamer_client_url('plans'));
        }

        if ($this->input->post('plan_id')) {
            if (!arenagamer_can_subscribe_plan($this->api->get_auth_user())) {
                set_alert('warning', arenagamer_plan_subscribe_blocked_message());
                redirect(arenagamer_client_url('plans'));
            }

            $planId = (int) $this->input->post('plan_id');
            $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
                ? arenagamer_normalize_billing_period_months($this->input->post('billing_period'))
                : 1;

            if ($planId <= 0) {
                set_alert('danger', 'Selecione um plano válido.');
                redirect(arenagamer_client_url('plans'));
            }

            $meResponse = $this->api->get_me(true);
            $authUser = arenagamer_api_is_success($meResponse) ? arenagamer_api_data($meResponse) : $this->api->get_auth_user();
            $currentPlan = arenagamer_contact_plan($authUser);
            $plansResponse = $this->api->get_public_plans();
            $availablePlans = arenagamer_api_data($plansResponse, []);
            $targetPlan = null;

            if (is_array($availablePlans)) {
                foreach ($availablePlans as $plan) {
                    if ((int) ($plan['id'] ?? 0) === $planId) {
                        $targetPlan = $plan;
                        break;
                    }
                }
            }

            if (!$targetPlan) {
                set_alert('danger', 'Plano não encontrado.');
                redirect(arenagamer_client_url('plans'));
            }

            $action = arenagamer_plan_compare_action($currentPlan, $targetPlan);

            if (arenagamer_plan_requires_invoice($action, $targetPlan)) {
                $clientUserId = (int) get_client_user_id();
                $payWithCredits = (int) $this->input->post('pay_with_credits') === 1;

                if ($payWithCredits) {
                    $creditCost = arenagamer_plan_credit_cost($targetPlan, $billingPeriodMonths);
                    $walletResponse = $this->api->get_wallet_balance();
                    $walletData = arenagamer_api_is_success($walletResponse) ? arenagamer_api_data($walletResponse) : [];
                    $available = (float) ($walletData['availableBalance'] ?? 0);

                    if ($creditCost > 0 && $available < $creditCost) {
                        set_alert(
                            'danger',
                            'Saldo insuficiente. Necessário '
                            . arenagamer_format_credits($creditCost)
                            . ', disponível '
                            . arenagamer_format_credits($available)
                            . '.'
                        );
                        redirect(arenagamer_client_url('plans'));
                    }

                    $result = $this->api->subscribe_plan_with_credits($planId, $billingPeriodMonths);

                    if (arenagamer_api_is_success($result)) {
                        if (function_exists('arenagamer_stop_plan_recurring')) {
                            arenagamer_stop_plan_recurring($clientUserId);
                        }
                        $planData = arenagamer_api_data($result);
                        $message = arenagamer_api_message($result, 'Plano contratado com sucesso usando créditos.');
                        if (is_array($planData)) {
                            $scheduled = trim(arenagamer_plan_scheduled_message($planData));
                            if ($scheduled !== '') {
                                $message = $scheduled;
                            }
                        }
                        set_alert('success', $message);
                        redirect(arenagamer_client_url('plans'));
                    }

                    set_alert('danger', 'Erro: ' . ($this->api->get_last_error() ?: 'Não foi possível contratar o plano com créditos.'));
                    redirect(arenagamer_client_url('plans'));
                }

                $this->load->model('arenagamer/arenagamer_plan_invoices_model');
                $pending = $this->arenagamer_plan_invoices_model->resolve_pending_for_client($clientUserId);

                if ($pending) {
                    set_alert('info', 'Você já possui uma fatura pendente para plano. Conclua o pagamento para ativar.');
                    redirect(arenagamer_invoice_payment_url((int) $pending['invoice_id']));
                }

                $invoiceAction = $action === 'upgrade' ? 'upgrade' : 'subscribe';
                $invoiceResult = $this->arenagamer_plan_invoices_model->create_plan_invoice(
                    $clientUserId,
                    $targetPlan,
                    $invoiceAction,
                    $billingPeriodMonths
                );

                if (empty($invoiceResult['success'])) {
                    set_alert('danger', $invoiceResult['message'] ?? 'Não foi possível gerar a fatura.');
                    redirect(arenagamer_client_url('plans'));
                }

                $periodLabel = function_exists('arenagamer_billing_period_label')
                    ? arenagamer_billing_period_label($billingPeriodMonths)
                    : 'Mensal';

                set_alert(
                    'success',
                    $invoiceAction === 'upgrade'
                        ? 'Fatura de upgrade (' . $periodLabel . ') gerada. Após o pagamento, o plano será atualizado.'
                        : 'Fatura gerada (' . $periodLabel . '). Após o pagamento, seu plano será ativado e renovado automaticamente no mesmo período.'
                );
                redirect($invoiceResult['url']);
            }

            $result = $this->api->subscribe_plan($planId, $billingPeriodMonths);

            if (arenagamer_api_is_success($result)) {
                $planData = arenagamer_api_data($result);
                $message = arenagamer_api_message($result, 'Plano contratado com sucesso');
                if (is_array($planData)) {
                    $scheduled = trim(arenagamer_plan_scheduled_message($planData));
                    if ($scheduled !== '') {
                        $message = $scheduled;
                    }
                }
                set_alert('success', $message);
                redirect(arenagamer_client_url('plans'));
            }

            set_alert('danger', 'Erro: ' . ($this->api->get_last_error() ?: 'Não foi possível contratar o plano.'));
            redirect(arenagamer_client_url('plans'));
        }

        $meResponse = $this->api->get_me(true);
        $authUser = arenagamer_api_is_success($meResponse) ? arenagamer_api_data($meResponse) : $this->api->get_auth_user();
        $currentPlan = arenagamer_contact_plan($authUser);
        $plansResponse = $this->api->get_public_plans();
        $availablePlans = arenagamer_api_data($plansResponse, []);

        if (is_array($availablePlans)) {
            usort($availablePlans, function ($a, $b) {
                return ((int) ($a['sortOrder'] ?? 0)) <=> ((int) ($b['sortOrder'] ?? 0));
            });
        } else {
            $availablePlans = [];
        }

        $data['title'] = 'Planos';
        $data['auth_user'] = $authUser;
        $data['current_plan'] = $currentPlan;
        $data['plan_is_active'] = arenagamer_plan_is_active($currentPlan);
        $data['can_subscribe_plan'] = arenagamer_can_subscribe_plan($authUser);
        $data['can_cancel_plan'] = arenagamer_can_cancel_plan($currentPlan, $authUser);
        $this->load->model('arenagamer/arenagamer_plan_invoices_model');
        $data['pending_plan_invoice'] = $this->arenagamer_plan_invoices_model->resolve_pending_for_client((int) get_client_user_id());
        $data['available_plans'] = $availablePlans;
        $data['client_user_id'] = (int) get_client_user_id();
        $walletResponse = $this->api->get_wallet_balance();
        $data['wallet'] = arenagamer_api_is_success($walletResponse) ? arenagamer_api_data($walletResponse) : null;
        $data['api_error'] = !arenagamer_api_is_success($plansResponse) ? $this->api->get_last_error() : '';

        $this->data($data);
        $this->view('client/plans');
        $this->layout();
    }

    /**
     * Pesquisa jogos (presets) para autocomplete no formulário de torneio.
     */
    public function search_presets()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!empty($this->data['arenagamer_needs_relink'])) {
            echo json_encode(['success' => false, 'message' => 'Conta ArenaGamer não conectada.', 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $query = arenagamer_preset_search_term_from_input($this->input);
        if (mb_strlen($query) < 3) {
            echo json_encode(['success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $response = $this->api->search_presets($query);
        if (!arenagamer_api_is_success($response)) {
            echo json_encode([
                'success' => false,
                'message' => $this->api->get_last_error(),
                'data'    => [],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = arenagamer_filter_presets_by_search(arenagamer_api_data($response, []), $query);
        $items = array_values(array_filter($items, function ($preset) {
            return !is_array($preset) || !array_key_exists('active', $preset) || !empty($preset['active']);
        }));

        echo json_encode([
            'success' => true,
            'data'    => $items,
        ], JSON_UNESCAPED_UNICODE);
    }
}
