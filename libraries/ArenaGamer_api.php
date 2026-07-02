<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ArenaGamer API Client Library
 * Alinhado com OpenAPI 3.1 - ArenaGamer API v1.0.0
 *
 * Áreas:
 * - /public/*  — auth sem token; catálogo com HTTP Basic
 * - /common/*  — operações autenticadas (JWT Bearer)
 * - /admin/*   — painel staff (JWT Bearer)
 */
class ArenaGamer_api
{
    private const API_PREFIX = '/api/v1';

    /** @var false Autenticação desabilitada (ex.: login/register) */
    private const AUTH_NONE = false;

    /** @var true JWT Bearer (staff ou cliente) */
    private const AUTH_BEARER = true;

    /** @var string HTTP Basic (email + senha) para catálogo público */
    private const AUTH_BASIC = 'basic';

    /** Defaults dos 4 microserviços (cada um com domínio próprio) */
    private const DEFAULT_URLS = [
        'auth'   => 'https://auth.omnyarena.com',
        'common' => 'https://common.omnyarena.com',
        'admin'  => 'https://admin.omnyarena.com',
        'public' => 'https://public.omnyarena.com',
    ];

    private $CI;
    /** @var array<string,string> URL base de cada microserviço (auth/common/admin/public) */
    private $service_urls = [];
    private $auth_context;
    private $token;
    private $refresh_token;
    private $auth_user;
    private $last_error;
    private $last_message;
    private $contact_id = 0;

    public function __construct($config = [])
    {
        if (!is_array($config)) {
            $config = [];
        }

        $this->CI = &get_instance();
        $this->CI->load->helper('arenagamer/arenagamer');
        $this->load_service_urls();
        $this->auth_context = ($config['context'] ?? 'staff') === 'contact' ? 'contact' : 'staff';
        $this->contact_id = (int) ($config['contact_id'] ?? 0);
        $this->load_auth_state();
    }

    public function set_contact_id($contactId)
    {
        $this->contact_id = (int) $contactId;
        $this->load_auth_state();

        return $this;
    }

    public function get_context()
    {
        return $this->auth_context;
    }

    public function is_contact_context()
    {
        return $this->auth_context === 'contact';
    }

    // --- Auth ---

    public function authenticate($email = null, $password = null, $staff = true)
    {
        if ($this->auth_context === 'contact') {
            if ($email !== null && $password !== null) {
                return $this->login($email, $password, false);
            }

            return $this->ensure_contact_session();
        }

        $email    = $email ?? get_option('arenagamer_admin_email');
        $password = $password ?? get_option('arenagamer_admin_password');

        if (empty($email) || empty($password)) {
            $this->last_error = 'Email e senha do admin não configurados';
            return false;
        }

        return $this->login($email, $password, $staff);
    }

    public function login($email, $password, $staff = true)
    {
        $payload = [
            'email'    => $email,
            'password' => $password,
        ];

        if ($staff) {
            $payload['staff'] = true;
        }

        $response = $this->request('POST', '/public/auth/login', $payload, self::AUTH_NONE);

        return $this->store_auth_tokens($response, false);
    }

    /** @deprecated Use login() */
    public function authenticate_as_contact($email, $password)
    {
        return $this->login($email, $password, false);
    }

    public function refresh_token()
    {
        if (empty($this->refresh_token)) {
            $this->load_auth_state();
        }

        if (empty($this->refresh_token)) {
            return false;
        }

        $response = $this->request('POST', '/public/auth/refresh', [
            'refreshToken' => $this->refresh_token,
        ], self::AUTH_NONE);

        return $this->store_auth_tokens($response, true);
    }

    public function logout()
    {
        if (!empty($this->token)) {
            $this->request('POST', '/common/auth/logout');
        }

        $this->clear_auth_state(true);

        return true;
    }

    public function has_access_token()
    {
        if (empty($this->token)) {
            $this->load_auth_state();
        }

        return !empty($this->token);
    }

    public function ensure_contact_session()
    {
        if ($this->auth_context !== 'contact') {
            return $this->authenticate();
        }

        if ($this->has_access_token()) {
            return true;
        }

        return $this->restore_contact_session();
    }

    public function update_profile(array $data)
    {
        $params = array_filter([
            'firstName'     => $data['firstName'] ?? null,
            'lastName'      => $data['lastName'] ?? null,
            'phoneNumber'   => $data['phoneNumber'] ?? null,
            'avatarUrl'     => array_key_exists('avatarUrl', $data) ? $data['avatarUrl'] : null,
            'instagramUrl'  => array_key_exists('instagramUrl', $data) ? $data['instagramUrl'] : null,
            'youtubeUrl'    => array_key_exists('youtubeUrl', $data) ? $data['youtubeUrl'] : null,
            'twitchUrl'     => array_key_exists('twitchUrl', $data) ? $data['twitchUrl'] : null,
        ], function ($value, $key) {
            if (in_array($key, ['avatarUrl', 'instagramUrl', 'youtubeUrl', 'twitchUrl'], true)) {
                return true;
            }

            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        return $this->request('PUT', '/common/users/me', null, true, $params);
    }

    public function get_me($refresh = false)
    {
        if (!$refresh && $this->has_access_token() && is_array($this->auth_user) && !empty($this->auth_user)) {
            return ['success' => true, 'data' => $this->auth_user];
        }

        $response = $this->request('GET', '/common/users/me');
        if (arenagamer_api_is_success($response)) {
            $this->store_auth_user(arenagamer_api_data($response));
        }

        return $response;
    }

    public function get_auth_user()
    {
        if (!$this->has_access_token()) {
            return null;
        }

        if (is_array($this->auth_user) && !empty($this->auth_user)) {
            return $this->auth_user;
        }

        $response = $this->get_me(true);
        if (arenagamer_api_is_success($response)) {
            return arenagamer_api_data($response);
        }

        return null;
    }

    public function is_staff_user()
    {
        $user = $this->get_auth_user();

        return is_array($user) && ($user['userType'] ?? '') === 'STAFF';
    }

    public function is_contact_user()
    {
        $user = $this->get_auth_user();

        return is_array($user) && ($user['userType'] ?? '') === 'CONTACT';
    }

    // --- Admin (staff only) ---

    private function require_staff_context()
    {
        if ($this->auth_context === 'contact') {
            $this->last_error = 'Este recurso não está disponível para clientes';

            return false;
        }

        return true;
    }

    // --- Admin: Plans ---
    // GET    /api/v1/admin/plans
    // POST   /api/v1/admin/plans
    // GET    /api/v1/admin/plans/{id}
    // PUT    /api/v1/admin/plans/{id}
    // DELETE /api/v1/admin/plans/{id}

    public function get_plans()
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/plans');
    }

    public function get_plan($id)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/plans/' . (int) $id);
    }

    public function create_plan(array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('POST', '/admin/plans', $this->normalize_plan_payload($data));
    }

    public function update_plan($id, array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('PUT', '/admin/plans/' . (int) $id, $this->normalize_plan_payload($data));
    }

    public function delete_plan($id)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('DELETE', '/admin/plans/' . (int) $id);
    }

    // --- Admin: Subscriptions ---
    // GET    /api/v1/admin/subscriptions
    // GET    /api/v1/admin/subscriptions/plan/{planId}
    // GET    /api/v1/admin/subscriptions/client/{clientUserId}
    // PUT    /api/v1/admin/subscriptions/client/{clientUserId}
    // DELETE /api/v1/admin/subscriptions/client/{clientUserId}
    // POST   /api/v1/admin/subscriptions/client/{clientUserId}/reset-usage

    public function get_admin_subscriptions($page = 0, $size = 20, $search = '')
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $query = $this->pageable($page, $size);
        if (trim((string) $search) !== '') {
            $query['search'] = trim((string) $search);
        }

        return $this->request('GET', '/admin/subscriptions', null, self::AUTH_BEARER, $query);
    }

    public function get_admin_subscriptions_by_plan($planId, $page = 0, $size = 20, $search = '')
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $planId = (int) $planId;
        if ($planId <= 0) {
            $this->last_error = 'ID do plano inválido';

            return null;
        }

        $query = $this->pageable($page, $size);
        if (trim((string) $search) !== '') {
            $query['search'] = trim((string) $search);
        }

        $response = $this->request('GET', '/admin/subscriptions/plan/' . $planId, null, self::AUTH_BEARER, $query);

        if ($response !== null && arenagamer_api_is_success($response)) {
            return $response;
        }

        $fallbackSearch = trim((string) $search);
        $allResponse = $this->get_admin_subscriptions(0, 500, $fallbackSearch);
        if (!$this->has_paginated_content($allResponse)) {
            return $response ?? $allResponse;
        }

        $filtered = array_values(array_filter(
            $allResponse['data']['content'],
            function ($item) use ($planId) {
                $plan = is_array($item['plan'] ?? null) ? $item['plan'] : [];
                $currentPlanId = (int) ($plan['id'] ?? 0);
                $pendingPlanId = (int) ($plan['pendingPlanId'] ?? 0);

                return $currentPlanId === $planId || $pendingPlanId === $planId;
            }
        ));

        return $this->build_paginated_response($filtered, $page, $size);
    }

    public function admin_assign_subscription($clientUserId, $planId, $billingPeriodMonths = 1)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
            ? arenagamer_normalize_billing_period_months($billingPeriodMonths)
            : max(1, (int) $billingPeriodMonths);

        return $this->request('PUT', '/admin/subscriptions/client/' . (int) $clientUserId, [
            'planId'              => (int) $planId,
            'billingPeriodMonths' => $billingPeriodMonths,
        ]);
    }

    public function admin_remove_subscription($clientUserId)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('DELETE', '/admin/subscriptions/client/' . (int) $clientUserId);
    }

    public function admin_reset_subscription_usage($clientUserId)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('POST', '/admin/subscriptions/client/' . (int) $clientUserId . '/reset-usage');
    }

    private function normalize_plan_payload(array $data)
    {
        $maxTournamentsPerMonth = (int) ($data['freeTournamentsPerMonth'] ?? $data['maxTournamentsPerMonth'] ?? 0);

        return [
            'name'                    => trim((string) ($data['name'] ?? '')),
            'description'             => trim((string) ($data['description'] ?? '')),
            'freeTournamentsPerMonth' => $maxTournamentsPerMonth,
            'freeMaxParticipants'     => (int) ($data['freeMaxParticipants'] ?? 0),
            'allowsEntryFee'          => (bool) ($data['allowsEntryFee'] ?? false),
            'maxTournamentsPerMonth'  => $maxTournamentsPerMonth,
            'monthlyPrice'            => (float) ($data['monthlyPrice'] ?? 0),
            'hidden'                  => (bool) ($data['hidden'] ?? false),
            'active'                  => (bool) ($data['active'] ?? true),
            'sortOrder'               => (int) ($data['sortOrder'] ?? 0),
        ];
    }

    // --- Admin: Tournament pricing ---
    // GET /api/v1/admin/tournament-pricing
    // PUT /api/v1/admin/tournament-pricing

    public function get_tournament_pricing()
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/tournament-pricing');
    }

    public function update_tournament_pricing(array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('PUT', '/admin/tournament-pricing', $this->normalize_tournament_pricing_payload($data));
    }

    // --- Admin: Team settings ---
    // GET /api/v1/admin/team-settings
    // PUT /api/v1/admin/team-settings

    public function get_team_settings()
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/team-settings');
    }

    public function update_team_settings(array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $payload = [
            'maxOwnedTeamsPerClient'        => max(1, (int) ($data['maxOwnedTeamsPerClient'] ?? $data['maxOwnedTeamsPerContact'] ?? 1)),
            'maxParticipatedTeamsPerClient' => max(1, (int) ($data['maxParticipatedTeamsPerClient'] ?? $data['maxParticipatedTeamsPerContact'] ?? 3)),
        ];

        if (array_key_exists('maxTournamentsPerTeam', $data)) {
            $payload['maxTournamentsPerTeam'] = $data['maxTournamentsPerTeam'] === null || $data['maxTournamentsPerTeam'] === ''
                ? null
                : max(1, (int) $data['maxTournamentsPerTeam']);
        }

        if (array_key_exists('maxTournamentsPerClient', $data)) {
            $payload['maxTournamentsPerClient'] = $data['maxTournamentsPerClient'] === null || $data['maxTournamentsPerClient'] === ''
                ? null
                : max(1, (int) $data['maxTournamentsPerClient']);
        }

        return $this->request('PUT', '/admin/team-settings', $payload);
    }

    public function get_public_team_settings()
    {
        return $this->request('GET', '/public/team-settings', null, self::AUTH_BASIC);
    }

    // GET /api/v1/public/tournament-pricing (catálogo — HTTP Basic)
    public function get_public_tournament_pricing()
    {
        return $this->request('GET', '/public/tournament-pricing', null, self::AUTH_BASIC);
    }

    // GET /api/v1/admin/tournament-systems
    // PUT /api/v1/admin/tournament-systems
    // PATCH /api/v1/admin/tournament-systems/{type}
    public function get_tournament_systems()
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/tournament-systems');
    }

    public function update_tournament_systems(array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('PUT', '/admin/tournament-systems', $this->normalize_tournament_systems_payload($data));
    }

    public function patch_tournament_system($type, array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $type = strtoupper(trim((string) $type));
        if ($type === '') {
            $this->last_error = 'Tipo de torneio inválido.';

            return null;
        }

        return $this->request('PATCH', '/admin/tournament-systems/' . rawurlencode($type), [
            'enabled' => !empty($data['enabled']),
        ]);
    }

    public function get_public_tournament_systems()
    {
        return $this->request('GET', '/public/tournament-systems', null, self::AUTH_BASIC);
    }

    private function normalize_tournament_systems_payload(array $data)
    {
        $systems = [];

        foreach ($data['systems'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = strtoupper(trim((string) ($item['type'] ?? '')));
            if ($type === '') {
                continue;
            }

            $systems[] = [
                'type'    => $type,
                'enabled' => !empty($item['enabled']),
            ];
        }

        return ['systems' => $systems];
    }

    private function normalize_tournament_pricing_payload(array $data)
    {
        return [
            'baseTournamentPrice'   => (float) ($data['baseTournamentPrice'] ?? 0),
            'extraParticipantPrice' => (float) ($data['extraParticipantPrice'] ?? 0),
            'includedParticipants'  => (int) ($data['includedParticipants'] ?? 8),
        ];
    }

    public function get_presets()
    {
        if ($this->auth_context === 'contact') {
            return $this->get_public_presets();
        }

        return $this->request('GET', '/admin/presets');
    }

    /**
     * GET /common/presets ou GET /admin/presets — listar ou pesquisar jogos (presets).
     *
     * @param string|null $query Texto de busca (nome do jogo ou plataforma)
     * @param bool        $activeOnly Apenas ativos (staff; clientes sempre recebem só ativos)
     */
    public function search_presets($query = null, $activeOnly = true)
    {
        $queryParams = [];

        if ($query !== null && trim((string) $query) !== '') {
            $queryParams['q'] = trim((string) $query);
        }

        if ($this->auth_context === 'contact') {
            return $this->request('GET', '/common/presets', null, self::AUTH_BEARER, $queryParams);
        }

        if ($activeOnly) {
            $queryParams['activeOnly'] = 'true';
        }

        return $this->request('GET', '/admin/presets', null, self::AUTH_BEARER, $queryParams);
    }

    public function get_preset($id)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/presets/' . (int) $id);
    }

    public function create_preset(array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('POST', '/admin/presets', $this->normalize_preset_payload($data));
    }

    public function update_preset($id, array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('PUT', '/admin/presets/' . (int) $id, $this->normalize_preset_payload($data));
    }

    private function normalize_preset_payload(array $data)
    {
        return [
            'gameName'           => trim((string) ($data['gameName'] ?? '')),
            'platform'           => trim((string) ($data['platform'] ?? '')),
            'teamSize'           => max(1, (int) ($data['teamSize'] ?? 1)),
            'minPlayersPerTeam'  => max(1, (int) ($data['minPlayersPerTeam'] ?? 1)),
            'maxPlayersPerTeam'  => max(1, (int) ($data['maxPlayersPerTeam'] ?? 1)),
            'iconUrl'            => trim((string) ($data['iconUrl'] ?? '')),
            'gameImageUrl'       => trim((string) ($data['gameImageUrl'] ?? '')),
            'rulesTemplate'      => trim((string) ($data['rulesTemplate'] ?? '')),
            'scoringScript'      => trim((string) ($data['scoringScript'] ?? '')),
            'active'             => !array_key_exists('active', $data) || !empty($data['active']),
        ];
    }

    // GET /api/v1/public/presets (catálogo — HTTP Basic)
    public function get_public_presets()
    {
        return $this->request('GET', '/public/presets', null, self::AUTH_BASIC);
    }

    // GET /api/v1/public/plans (catálogo — HTTP Basic)
    public function get_public_plans()
    {
        return $this->request('GET', '/public/plans', null, self::AUTH_BASIC);
    }

    // POST /api/v1/common/subscriptions (contratar, upgrade ou downgrade)
    public function subscribe_plan($planId, $billingPeriodMonths = 1)
    {
        $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
            ? arenagamer_normalize_billing_period_months($billingPeriodMonths)
            : max(1, (int) $billingPeriodMonths);

        $response = $this->request('POST', '/common/subscriptions', [
            'planId'              => (int) $planId,
            'billingPeriodMonths' => $billingPeriodMonths,
        ]);

        if (arenagamer_api_is_success($response)) {
            $this->get_me(true);
        }

        return $response;
    }

    public function subscribe_plan_with_credits($planId, $billingPeriodMonths = 1)
    {
        $billingPeriodMonths = function_exists('arenagamer_normalize_billing_period_months')
            ? arenagamer_normalize_billing_period_months($billingPeriodMonths)
            : max(1, (int) $billingPeriodMonths);

        $response = $this->request('POST', '/common/subscriptions/with-credits', [
            'planId'              => (int) $planId,
            'billingPeriodMonths' => $billingPeriodMonths,
        ]);

        if (arenagamer_api_is_success($response)) {
            $this->get_me(true);
        }

        return $response;
    }

    // DELETE /api/v1/common/subscriptions (cancelar plano pago — não Free)
    public function cancel_plan()
    {
        $response = $this->request('DELETE', '/common/subscriptions');

        if (arenagamer_api_is_success($response)) {
            $this->get_me(true);
        }

        return $response;
    }

    public function get_users($page = 0, $size = 20)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/users', null, true, $this->pageable($page, $size));
    }

    public function get_contacts($page = 0, $size = 20)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $response = $this->request('GET', '/admin/contacts', null, true, $this->pageable($page, $size));

        if ($this->has_paginated_content($response)) {
            return $response;
        }

        // Fallback: /admin/users também retorna contatos (userType CONTACT)
        $usersResponse = $this->request('GET', '/admin/users', null, true, [
            'page' => 0,
            'size' => 200,
        ]);

        if ($this->has_paginated_content($usersResponse)) {
            $contacts = array_values(array_filter(
                $usersResponse['data']['content'],
                function ($item) {
                    return ($item['userType'] ?? '') === 'CONTACT';
                }
            ));

            if (!empty($contacts)) {
                return $this->build_paginated_response($contacts, $page, $size);
            }
        }

        return $response ?? $usersResponse;
    }

    public function get_admin_tournaments($page = 0, $size = 20, array $sort = [])
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/tournaments', null, true, $this->pageable($page, $size, $sort));
    }

    public function get_audits($page = 0, $size = 50)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/audits', null, true, $this->pageable($page, $size));
    }

    /**
     * Registra auditoria manual (ações locais do Perfex ou complementares).
     *
     * @param string      $action
     * @param string      $entityType
     * @param int|null    $entityId
     * @param string|null $oldValue
     * @param string|null $newValue
     */
    public function create_audit_log($action, $entityType, $entityId = null, $oldValue = null, $newValue = null)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        $payload = [
            'action'     => (string) $action,
            'entityType' => (string) $entityType,
        ];

        if ($entityId !== null && $entityId !== '') {
            $payload['entityId'] = (int) $entityId;
        }

        if ($oldValue !== null && $oldValue !== '') {
            $payload['oldValue'] = (string) $oldValue;
        }

        if ($newValue !== null && $newValue !== '') {
            $payload['newValue'] = (string) $newValue;
        }

        return $this->request('POST', '/admin/audits', $payload, true);
    }

    // --- Tournaments (Common) ---
    // GET    /api/v1/common/tournaments/{slug}
    // GET    /api/v1/common/tournaments/{slug}/matches?page&size&finished&scheduled&sort
    // GET    /api/v1/common/tournaments/my-joined
    // GET    /api/v1/common/tournaments/my-created
    // POST   /api/v1/common/tournaments
    // PUT    /api/v1/common/tournaments/{slug}/status
    // POST   /api/v1/common/tournaments/{slug}/schedule
    // POST   /api/v1/common/tournaments/{slug}/generate-bracket
    // DELETE /api/v1/common/tournaments/{slug}
    // DELETE /api/v1/common/tournaments/{slug}/participants/{participantId}
    // PUT    /api/v1/common/tournaments/matches/{matchId}/reschedule
    //
    // --- Tournaments (Public catálogo) ---
    // GET    /api/v1/public/tournaments — HTTP Basic

    public function list_tournaments($page = 0, $size = 20, array $sort = [], $filter = null)
    {
        $query = $this->pageable($page, $size, $sort);
        if ($filter !== null && trim((string) $filter) !== '') {
            $query['filter'] = strtoupper(trim((string) $filter));
        }

        return $this->request(
            'GET',
            '/public/tournaments',
            null,
            self::AUTH_BASIC,
            $query
        );
    }

    /** @deprecated Use get_admin_tournaments() no painel admin */
    public function get_tournaments($page = 0, $size = 20, array $sort = [])
    {
        return $this->get_admin_tournaments($page, $size, $sort);
    }

    public function get_my_joined_tournaments($page = 0, $size = 20, array $sort = [])
    {
        return $this->request('GET', '/common/tournaments/my-joined', null, true, $this->pageable($page, $size, $sort));
    }

    public function get_my_created_tournaments($page = 0, $size = 20, array $sort = [])
    {
        return $this->request('GET', '/common/tournaments/my-created', null, true, $this->pageable($page, $size, $sort));
    }

    public function get_my_managed_tournaments($page = 0, $size = 20, array $sort = [])
    {
        return $this->request('GET', '/common/tournaments/my-managed', null, true, $this->pageable($page, $size, $sort));
    }

    public function can_manage_tournament($slug)
    {
        $slug = (string) $slug;
        if ($slug === '') {
            return false;
        }

        $user = $this->get_auth_user();
        if (
            $this->auth_context === 'staff'
            && is_array($user)
            && ($user['userType'] ?? '') === 'STAFF'
            && ($user['role'] ?? '') === 'ADMIN'
        ) {
            return true;
        }

        $page = 0;
        $size = 100;

        do {
            $response = $this->get_my_managed_tournaments($page, $size);
            if (!arenagamer_api_is_success($response)) {
                return false;
            }

            foreach (arenagamer_paginated_content($response) as $tournament) {
                if (($tournament['slug'] ?? '') === $slug) {
                    return true;
                }
            }

            $meta = arenagamer_pagination_meta($response);
            $page++;
        } while ($page < ($meta['totalPages'] ?? 1));

        return false;
    }

    public function get_tournament_managers($slug)
    {
        return $this->request('GET', '/common/tournaments/' . rawurlencode($slug) . '/managers');
    }

    public function grant_tournament_manager($slug, $contactId)
    {
        return $this->request(
            'POST',
            '/common/tournaments/' . rawurlencode($slug) . '/managers/' . (int) $contactId
        );
    }

    public function revoke_tournament_manager($slug, $contactId)
    {
        return $this->request(
            'DELETE',
            '/common/tournaments/' . rawurlencode($slug) . '/managers/' . (int) $contactId
        );
    }

    public function get_tournament($slug)
    {
        return $this->request('GET', '/common/tournaments/' . rawurlencode($slug));
    }

    /**
     * @param array{page?:int,size?:int,finished?:bool,scheduled?:bool,sort?:string|array} $options
     */
    public function get_tournament_matches($slug, array $options = [])
    {
        $query = [];

        if (array_key_exists('page', $options)) {
            $query['page'] = max(0, (int) $options['page']);
        }
        if (array_key_exists('size', $options)) {
            $query['size'] = max(1, (int) $options['size']);
        }
        if (array_key_exists('finished', $options)) {
            $query['finished'] = $options['finished'] ? 'true' : 'false';
        }
        if (array_key_exists('scheduled', $options)) {
            $query['scheduled'] = $options['scheduled'] ? 'true' : 'false';
        }
        if (!empty($options['sort'])) {
            $sort = $options['sort'];
            $query['sort'] = is_array($sort) ? array_values($sort) : [(string) $sort];
        }

        return $this->request(
            'GET',
            '/common/tournaments/' . rawurlencode($slug) . '/matches',
            null,
            true,
            $query
        );
    }

    public function get_tournament_standings($slug)
    {
        return $this->request('GET', '/common/tournaments/' . rawurlencode($slug) . '/standings');
    }

    public function finalize_tournament($slug)
    {
        return $this->request('POST', '/common/tournaments/' . rawurlencode($slug) . '/finalize');
    }

    public function create_tournament(array $data)
    {
        $payload = $this->normalize_tournament_payload($data);

        if ($this->auth_context === 'contact') {
            unset($payload['clientUserId']);
        }

        $response = $this->request('POST', '/common/tournaments', $payload);

        if ($this->auth_context === 'contact' && arenagamer_api_is_success($response)) {
            $this->get_me(true);
        }

        return $response;
    }

    public function update_tournament($slug, array $data)
    {
        $payload = $this->normalize_tournament_payload($data, true);

        if ($this->auth_context === 'contact') {
            unset($payload['clientUserId']);
        }

        return $this->request('PUT', '/common/tournaments/' . rawurlencode($slug), $payload);
    }

    public function join_tournament_solo($slug, array $data = [])
    {
        return $this->request(
            'POST',
            '/common/tournaments/' . rawurlencode($slug) . '/participants',
            $this->normalize_join_payload($data)
        );
    }

    public function join_tournament_team($slug, array $data)
    {
        return $this->request(
            'POST',
            '/common/tournaments/' . rawurlencode($slug) . '/participants/team',
            $this->normalize_join_payload($data)
        );
    }

    public function update_tournament_status($slug, $status)
    {
        return $this->request(
            'PUT',
            '/common/tournaments/' . rawurlencode($slug) . '/status',
            null,
            true,
            ['status' => $status]
        );
    }

    public function generate_bracket($slug)
    {
        return $this->request('POST', '/common/tournaments/' . rawurlencode($slug) . '/generate-bracket');
    }

    public function schedule_matches($slug)
    {
        return $this->request('POST', '/common/tournaments/' . rawurlencode($slug) . '/schedule');
    }

    public function advance_round($slug)
    {
        return $this->request('POST', '/common/tournaments/' . rawurlencode($slug) . '/advance-round');
    }

    public function generate_knockout($slug)
    {
        return $this->request('POST', '/common/tournaments/' . rawurlencode($slug) . '/generate-knockout');
    }

    /**
     * DELETE /api/v1/admin/tournaments/{slug}/matches — remove partidas, rodadas, seeds e classificação.
     * Requer JWT staff (ADMIN ou MANAGER). Reseta status IN_PROGRESS → REGISTRATION_CLOSED.
     */
    public function clear_tournament_matches($slug)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request(
            'DELETE',
            '/admin/tournaments/' . rawurlencode($slug) . '/matches'
        );
    }

    public function cancel_tournament($slug)
    {
        return $this->request('DELETE', '/common/tournaments/' . rawurlencode($slug));
    }

    public function expel_participant($slug, $participantId)
    {
        return $this->request(
            'DELETE',
            '/common/tournaments/' . rawurlencode($slug) . '/participants/' . (int) $participantId
        );
    }

    public function reschedule_match($matchId, array $data)
    {
        return $this->request(
            'PUT',
            '/common/tournaments/matches/' . (int) $matchId . '/reschedule',
            null,
            true,
            ['newTime' => (string) ($data['newTime'] ?? $data['scheduledAt'] ?? '')]
        );
    }

    public function record_match_result($matchId, array $data)
    {
        $query = [];

        if (!empty($data['winnerParticipantId'])) {
            $query['winnerParticipantId'] = (int) $data['winnerParticipantId'];
        }

        if (isset($data['homeScore']) && $data['homeScore'] !== '') {
            $query['homeScore'] = (int) $data['homeScore'];
        }
        if (isset($data['awayScore']) && $data['awayScore'] !== '') {
            $query['awayScore'] = (int) $data['awayScore'];
        }
        if (isset($data['proofUrl']) && $data['proofUrl'] !== '') {
            $query['proofUrl'] = (string) $data['proofUrl'];
        }

        return $this->request(
            'POST',
            '/common/tournaments/matches/' . (int) $matchId . '/result',
            null,
            true,
            $query
        );
    }

    private function normalize_tournament_payload(array $data, $isUpdate = false)
    {
        $payload = [
            'name'                 => trim((string) ($data['name'] ?? '')),
            'description'          => trim((string) ($data['description'] ?? '')),
            'type'                 => (string) ($data['type'] ?? 'SINGLE_ELIMINATION'),
            'format'               => (string) ($data['format'] ?? 'SOLO'),
            'visibility'           => (string) ($data['visibility'] ?? 'PUBLIC'),
            'minParticipants'      => max(arenagamer_tournament_min_participants(), (int) ($data['minParticipants'] ?? arenagamer_tournament_min_participants())),
            'entryFeeCredits'      => (float) ($data['entryFeeCredits'] ?? 0),
            'feePercentage'      => (float) ($data['feePercentage'] ?? 0),
            'prizeType'            => (string) ($data['prizeType'] ?? 'AUTOMATIC'),
            'rules'                => trim((string) ($data['rules'] ?? '')),
            'tiebreakerRules'      => trim((string) ($data['tiebreakerRules'] ?? '')),
        ];

        if (!$isUpdate) {
            $payload['participantsLimit'] = max(2, (int) ($data['participantsLimit'] ?? 20));
        }

        if (array_key_exists('presetId', $data)) {
            if ($data['presetId'] !== '' && $data['presetId'] !== null) {
                $payload['presetId'] = (int) $data['presetId'];
            } else {
                $payload['presetId'] = null;
            }
        } elseif (!$isUpdate) {
            $payload['presetId'] = null;
        }

        if (array_key_exists('gameName', $data)) {
            $value = trim((string) ($data['gameName'] ?? ''));
            if ($value !== '') {
                $payload['gameName'] = $value;
            }
        }

        foreach (['prizeType', 'prizeFunding'] as $field) {
            if (array_key_exists($field, $data) && ($data[$field] ?? '') !== '') {
                $payload[$field] = (string) $data[$field];
            }
        }

        if (array_key_exists('prizePool', $data)) {
            $payload['prizePool'] = max(0, (float) ($data['prizePool'] ?? 0));
        }

        if (array_key_exists('entryFeeCredits', $data)) {
            $payload['entryFeeCredits'] = max(0, (float) ($data['entryFeeCredits'] ?? 0));
        }

        if (array_key_exists('feePercentage', $data)) {
            $payload['feePercentage'] = max(0, (float) ($data['feePercentage'] ?? 0));
        }

        foreach (['groupsCount', 'teamsPerGroup', 'advancePerGroup', 'advanceToKnockout', 'bestOf'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if ($data[$field] === '' || $data[$field] === null) {
                if ($isUpdate) {
                    $payload[$field] = null;
                }
                continue;
            }
            $payload[$field] = (int) $data[$field];
        }

        $format = (string) ($payload['format'] ?? 'SOLO');
        if ($format === 'TEAM') {
            if (array_key_exists('minPlayersPerTeam', $data)) {
                $minPlayers = (int) ($data['minPlayersPerTeam'] ?? 0);
                if ($minPlayers > 0) {
                    $payload['minPlayersPerTeam'] = $minPlayers;
                }
            }
            if (array_key_exists('maxPlayersPerTeam', $data)) {
                $maxPlayers = (int) ($data['maxPlayersPerTeam'] ?? 0);
                if ($maxPlayers > 0) {
                    $payload['maxPlayersPerTeam'] = $maxPlayers;
                }
            }
        }

        foreach ([
            'startDate',
            'registrationDeadline',
            'registrationOpensAt',
            'expectedEndDate',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                if ($data[$field] === '' || $data[$field] === null) {
                    if ($isUpdate) {
                        $payload[$field] = null;
                    }
                    continue;
                }
                $iso = function_exists('arenagamer_iso8601_z')
                    ? arenagamer_iso8601_z($data[$field])
                    : (string) $data[$field];
                if ($iso !== null && $iso !== '') {
                    $payload[$field] = $iso;
                }
            }
        }

        foreach (['gameImageUrl', 'coverImageUrl', 'logoImageUrl', 'youtubeUrl', 'twitchUrl'] as $field) {
            if (array_key_exists($field, $data)) {
                $value = trim((string) ($data[$field] ?? ''));
                $payload[$field] = $value !== '' ? $value : null;
            }
        }

        if (array_key_exists('clientUserId', $data) && $data['clientUserId'] !== '' && $data['clientUserId'] !== null) {
            $payload['clientUserId'] = (int) $data['clientUserId'];
        }

        return $payload;
    }

    private function normalize_join_payload(array $data)
    {
        $payload = [];

        if (!empty($data['teamId'])) {
            $payload['teamId'] = (int) $data['teamId'];
        }

        if (!empty($data['availableWindows']) && is_array($data['availableWindows'])) {
            $payload['availableWindows'] = array_values($data['availableWindows']);
        }

        if (array_key_exists('preferWeekends', $data)) {
            $payload['preferWeekends'] = (bool) $data['preferWeekends'];
        }

        return $payload;
    }

    // --- Teams (Common) ---

    public function get_my_teams()
    {
        return $this->request('GET', '/common/teams/my');
    }

    public function get_team($id)
    {
        return $this->request('GET', '/common/teams/' . (int) $id);
    }

    public function create_team(array $data)
    {
        return $this->request('POST', '/common/teams', $this->normalize_team_payload($data));
    }

    public function update_team($id, array $data)
    {
        return $this->request('PUT', '/common/teams/' . (int) $id, $this->normalize_team_payload($data));
    }

    private function normalize_team_payload(array $data)
    {
        $payload = [
            'name'           => trim((string) ($data['name'] ?? '')),
            'tag'            => trim((string) ($data['tag'] ?? '')),
            'logoUrl'        => trim((string) ($data['logoUrl'] ?? '')),
            'youtubeUrl'     => trim((string) ($data['youtubeUrl'] ?? '')),
            'instagramUrl'   => trim((string) ($data['instagramUrl'] ?? '')),
            'twitchUrl'      => trim((string) ($data['twitchUrl'] ?? '')),
            'otherSocialUrl' => trim((string) ($data['otherSocialUrl'] ?? '')),
            'rulesChange'    => trim((string) ($data['rulesChange'] ?? '')),
        ];

        foreach (['logoUrl', 'youtubeUrl', 'instagramUrl', 'twitchUrl', 'otherSocialUrl', 'tag', 'rulesChange'] as $field) {
            if ($payload[$field] === '') {
                $payload[$field] = null;
            }
        }

        return $payload;
    }

    public function add_team_member($teamId, $contactId)
    {
        return $this->request(
            'POST',
            '/common/teams/' . (int) $teamId . '/members/' . (int) $contactId
        );
    }

    public function remove_team_member($teamId, $contactId)
    {
        return $this->request(
            'DELETE',
            '/common/teams/' . (int) $teamId . '/members/' . (int) $contactId
        );
    }

    public function transfer_team_ownership($teamId, $newOwnerClientUserId)
    {
        return $this->request(
            'POST',
            '/common/teams/' . (int) $teamId . '/transfer/clients/' . (int) $newOwnerClientUserId
        );
    }

    public function set_team_captain($teamId, $clientUserId)
    {
        return $this->request(
            'POST',
            '/common/teams/' . (int) $teamId . '/members/clients/' . (int) $clientUserId . '/captain'
        );
    }

    // --- Wallet (Common) ---

    public function get_wallet_balance()
    {
        return $this->request('GET', '/common/wallet/balance');
    }

    public function get_wallet_transactions($page = 0, $size = 20)
    {
        return $this->request('GET', '/common/wallet/transactions', null, true, $this->pageable($page, $size));
    }

    /**
     * Compra de créditos: gera uma fatura no Perfex (não credita direto).
     * O saldo é creditado após o pagamento da fatura.
     */
    public function purchase_credits(array $data)
    {
        return $this->request('POST', '/common/wallet/credits/purchase', [
            'amount' => (float) ($data['amount'] ?? 0),
        ]);
    }

    public function wallet_withdraw(array $data)
    {
        return $this->request('POST', '/common/wallet/withdraw', [
            'amount'      => (float) ($data['amount'] ?? 0),
            'description' => trim((string) ($data['description'] ?? '')),
        ]);
    }

    // --- Admin: Wallet ---

    public function get_admin_client_wallets($clientUserId)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request('GET', '/admin/wallet/client/' . (int) $clientUserId);
    }

    public function get_admin_client_wallet($clientUserId)
    {
        return $this->get_admin_client_wallets($clientUserId);
    }

    public function get_admin_client_wallet_transactions($clientUserId, $page = 0, $size = 20)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request(
            'GET',
            '/admin/wallet/client/' . (int) $clientUserId . '/transactions',
            null,
            true,
            $this->pageable($page, $size)
        );
    }

    public function admin_wallet_deposit($clientUserId, array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request(
            'POST',
            '/admin/wallet/client/' . (int) $clientUserId . '/deposit',
            [
                'amount'      => (float) ($data['amount'] ?? 0),
                'description' => trim((string) ($data['description'] ?? '')),
            ]
        );
    }

    public function admin_wallet_withdraw($clientUserId, array $data)
    {
        if (!$this->require_staff_context()) {
            return null;
        }

        return $this->request(
            'POST',
            '/admin/wallet/client/' . (int) $clientUserId . '/withdraw',
            [
                'amount'      => (float) ($data['amount'] ?? 0),
                'description' => trim((string) ($data['description'] ?? '')),
            ]
        );
    }

    public function get_wallet_permissions()
    {
        return $this->request('GET', '/common/wallet/permissions');
    }

    public function update_wallet_permission($contactId, array $data)
    {
        return $this->request(
            'PUT',
            '/common/wallet/permissions/' . (int) $contactId,
            [
                'walletViewAllowed' => (bool) ($data['walletViewAllowed'] ?? false),
                'walletUseAllowed'  => (bool) ($data['walletUseAllowed'] ?? false),
            ]
        );
    }

    // --- HTTP ---

    private function request($method, $endpoint, $data = null, $auth = self::AUTH_BEARER, array $query = [], $retry_auth = false)
    {
        $useBearer = ($auth === self::AUTH_BEARER || $auth === true);
        $useBasic  = ($auth === self::AUTH_BASIC);

        if ($useBearer && empty($this->token)) {
            $this->authenticate();
        }

        $url = $this->build_url($endpoint, $query);

        $headers = ['Content-Type: application/json', 'Accept: */*'];

        if ($useBasic) {
            $basicHeader = $this->basic_auth_header();
            if ($basicHeader === null) {
                return null;
            }
            $headers[] = $basicHeader;
        } elseif ($useBearer && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        if ($useBearer && empty($this->token)) {
            $this->last_error = $this->contact_auth_error_message();
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data !== null) {
                    $body = json_encode($data, JSON_UNESCAPED_UNICODE);
                    if ($body === false) {
                        $this->last_error = 'Erro ao serializar JSON: ' . json_last_error_msg();
                        curl_close($ch);

                        return null;
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data !== null) {
                    $body = json_encode($data, JSON_UNESCAPED_UNICODE);
                    if ($body === false) {
                        $this->last_error = 'Erro ao serializar JSON: ' . json_last_error_msg();
                        curl_close($ch);

                        return null;
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if ($data !== null) {
                    $body = json_encode($data, JSON_UNESCAPED_UNICODE);
                    if ($body === false) {
                        $this->last_error = 'Erro ao serializar JSON: ' . json_last_error_msg();
                        curl_close($ch);

                        return null;
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        $response   = curl_exec($ch);
        $http_code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->last_error = 'Erro de conexão: ' . $curl_error;
            log_activity('ArenaGamer API Error: ' . $curl_error);
            return null;
        }

        $decoded = json_decode($response, true);
        $status  = $this->response_status($decoded, $http_code);

        if ($this->is_error_response($decoded, $http_code)) {
            if ($useBearer && !$retry_auth && $this->should_retry_auth($decoded, $status)) {
                if ($this->refresh_token()) {
                    return $this->request($method, $endpoint, $data, self::AUTH_BEARER, $query, true);
                }

                $this->invalidate_auth_tokens();

                if ($this->auth_context === 'contact' ? $this->restore_contact_session() : $this->authenticate()) {
                    return $this->request($method, $endpoint, $data, self::AUTH_BEARER, $query, true);
                }
            }

            $this->last_error = $this->parse_error_message($decoded, $status);

            return null;
        }

        if ($decoded === null && $status >= 200 && $status < 300) {
            return ['success' => true, 'data' => null];
        }

        if (arenagamer_api_is_success($decoded)) {
            $this->last_message = arenagamer_api_message($decoded);
        }

        return $decoded;
    }

    private function response_status($decoded, $http_code)
    {
        if (is_array($decoded) && isset($decoded['status'])) {
            return (int) $decoded['status'];
        }

        return (int) $http_code;
    }

    private function is_error_response($decoded, $http_code)
    {
        if ($http_code >= 400) {
            return true;
        }

        return is_array($decoded)
            && array_key_exists('success', $decoded)
            && $decoded['success'] === false;
    }

    private function should_retry_auth($decoded, $status)
    {
        if ($status !== 401) {
            return false;
        }

        $code = is_array($decoded) ? strtoupper((string) ($decoded['code'] ?? '')) : '';

        return $code === '' || in_array($code, ['UNAUTHORIZED', 'TOKEN_EXPIRED', 'INVALID_TOKEN'], true);
    }

    private function parse_error_message($decoded, $http_code)
    {
        if (!is_array($decoded)) {
            return "HTTP {$http_code}";
        }

        $message = trim((string) ($decoded['message'] ?? $decoded['error'] ?? "HTTP {$http_code}"));
        $code    = trim((string) ($decoded['code'] ?? ''));

        if ($code !== '') {
            $message = '[' . $code . '] ' . $message;
        }

        if ($http_code === 403 || $code === 'FORBIDDEN') {
            if (stripos($message, 'clientes') !== false) {
                $message .= ' — use uma conta de cliente/contato na API para ver torneios inscritos';
            } else {
                $message .= ' — os endpoints /admin/* exigem usuário com role ADMIN na API';
            }
        }

        return $message;
    }

    private function has_paginated_content($response)
    {
        return is_array($response)
            && isset($response['data']['content'])
            && is_array($response['data']['content'])
            && count($response['data']['content']) > 0;
    }

    private function build_paginated_response(array $items, $page, $size)
    {
        $page = max(0, (int) $page);
        $size = max(1, (int) $size);
        $total = count($items);
        $offset = $page * $size;
        $content = array_slice($items, $offset, $size);
        $totalPages = (int) ceil($total / $size);

        return [
            'success' => true,
            'data'    => [
                'content'          => $content,
                'totalElements'    => $total,
                'totalPages'       => max(1, $totalPages),
                'size'             => $size,
                'number'           => $page,
                'numberOfElements' => count($content),
                'first'            => $page === 0,
                'last'             => $page >= ($totalPages - 1),
                'empty'            => empty($content),
            ],
        ];
    }

    private function basic_auth_header()
    {
        if ($this->auth_context === 'contact') {
            $email = $this->CI->session->userdata('arenagamer_contact_basic_email');
            $password = $this->CI->session->userdata('arenagamer_contact_basic_password');

            if (empty($email) || empty($password)) {
                $cached = arenagamer_contact_get_cached_presets();
                if (!empty($cached)) {
                    $this->last_error = 'Catálogo em cache indisponível. Faça login novamente.';
                } else {
                    $this->last_error = 'Credenciais indisponíveis para catálogo. Faça login novamente.';
                }

                return null;
            }

            return 'Authorization: Basic ' . base64_encode($email . ':' . $password);
        }

        $email    = get_option('arenagamer_admin_email');
        $password = get_option('arenagamer_admin_password');

        if (empty($email) || empty($password)) {
            $this->last_error = 'Email e senha não configurados para autenticação Basic do catálogo';
            return null;
        }

        return 'Authorization: Basic ' . base64_encode($email . ':' . $password);
    }

    public function cache_public_catalog($email, $password)
    {
        $presets = $this->request_with_basic_credentials('GET', '/public/presets', $email, $password);
        if (arenagamer_api_is_success($presets)) {
            $this->persist_contact_meta('arenagamer_presets_cache', json_encode(arenagamer_api_data($presets, [])));
        }

        $plans = $this->request_with_basic_credentials('GET', '/public/plans', $email, $password);
        if (arenagamer_api_is_success($plans)) {
            $this->persist_contact_meta('arenagamer_plans_cache', json_encode(arenagamer_api_data($plans, [])));
        }

        return arenagamer_api_is_success($presets);
    }

    public function get_cached_presets()
    {
        if ($this->auth_context !== 'contact') {
            return $this->get_presets();
        }

        $raw = $this->read_contact_meta('arenagamer_presets_cache');
        if ($raw) {
            $decoded = json_decode($raw, true);

            return ['success' => true, 'data' => is_array($decoded) ? $decoded : []];
        }

        return $this->get_public_presets();
    }

    private function request_with_basic_credentials($method, $endpoint, $email, $password, array $query = [])
    {
        $url = $this->build_url($endpoint, $query);
        $headers = [
            'Content-Type: application/json',
            'Accept: */*',
            'Authorization: Basic ' . base64_encode($email . ':' . $password),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function build_url($endpoint, array $query = [])
    {
        $url = $this->resolve_service_url($endpoint) . self::API_PREFIX . $endpoint;
        if (empty($query)) {
            return $url;
        }

        $parts = [];
        foreach ($query as $key => $value) {
            if ($key === 'sort' && is_array($value)) {
                foreach ($value as $sortItem) {
                    if ($sortItem !== '' && $sortItem !== null) {
                        $parts[] = 'sort=' . rawurlencode((string) $sortItem);
                    }
                }
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        if (!empty($parts)) {
            $url .= '?' . implode('&', $parts);
        }

        return $url;
    }

    private function normalize_api_url($url)
    {
        $url = rtrim((string) $url, '/');
        if (substr($url, -7) === '/api/v1') {
            $url = substr($url, 0, -7);
        }

        return rtrim($url, '/');
    }

    /**
     * Carrega a URL base de cada microserviço a partir das options.
     * Cada serviço tem domínio próprio; se a option não estiver configurada,
     * usa o domínio padrão *.omnyarena.com.
     */
    private function load_service_urls()
    {
        foreach (self::DEFAULT_URLS as $service => $default) {
            $configured = $this->normalize_api_url(get_option('arenagamer_api_url_' . $service));

            $this->service_urls[$service] = $configured !== ''
                ? $configured
                : rtrim($default, '/');
        }
    }

    /**
     * Determina qual microserviço atende o endpoint.
     *
     * - auth:   /public/auth/*, /common/auth/*, /common/users/*
     * - admin:  /admin/*
     * - public: demais /public/*
     * - common: demais /common/*
     */
    private function resolve_service_url($endpoint)
    {
        $endpoint = '/' . ltrim((string) $endpoint, '/');

        if (
            strpos($endpoint, '/public/auth') === 0
            || strpos($endpoint, '/common/auth') === 0
            || strpos($endpoint, '/common/users') === 0
        ) {
            return $this->service_url('auth');
        }

        if (strpos($endpoint, '/admin') === 0) {
            return $this->service_url('admin');
        }

        if (strpos($endpoint, '/public') === 0) {
            return $this->service_url('public');
        }

        return $this->service_url('common');
    }

    private function service_url($service)
    {
        if (!empty($this->service_urls[$service])) {
            return $this->service_urls[$service];
        }

        return rtrim(self::DEFAULT_URLS[$service] ?? '', '/');
    }

    private function pageable($page, $size, array $sort = [])
    {
        $query = [
            'page' => max(0, (int) $page),
            'size' => max(1, (int) $size),
        ];

        if (!empty($sort)) {
            $query['sort'] = array_values(array_filter($sort, function ($item) {
                return $item !== '' && $item !== null;
            }));
        }

        return $query;
    }

    private function store_auth_tokens($response, $invalidate_on_failure = false)
    {
        if ($response && isset($response['data']['accessToken'])) {
            $this->token = $response['data']['accessToken'];
            $this->refresh_token = $response['data']['refreshToken'] ?? $this->refresh_token;
            $this->persist_auth_tokens();

            if (!empty($response['data']['user']) && is_array($response['data']['user'])) {
                $this->store_auth_user($response['data']['user']);
            }

            return true;
        }

        if (!$this->last_error) {
            $this->last_error = '[AUTH_ERROR] Resposta de autenticação inválida';
        }

        if ($invalidate_on_failure) {
            $this->invalidate_auth_tokens();
        }

        return false;
    }

    private function restore_contact_session()
    {
        if ($this->refresh_token()) {
            return true;
        }

        $email = $this->CI->session->userdata('arenagamer_pending_sync_email');
        $password = $this->CI->session->userdata('arenagamer_pending_sync_password');
        if (!empty($email) && $password !== null && $password !== '') {
            if ($this->login($email, $password, false)) {
                if (function_exists('arenagamer_finalize_contact_login')) {
                    arenagamer_finalize_contact_login($email, $password, $this);
                }

                return true;
            }

            return false;
        }

        $email = $this->CI->session->userdata('arenagamer_contact_basic_email');
        $password = $this->CI->session->userdata('arenagamer_contact_basic_password');
        if (!empty($email) && $password !== null && $password !== '') {
            if ($this->login($email, $password, false)) {
                if (function_exists('arenagamer_finalize_contact_login')) {
                    arenagamer_finalize_contact_login($email, $password, $this);
                }

                return true;
            }

            return false;
        }

        if (function_exists('arenagamer_get_logged_contact_email')) {
            $email = arenagamer_get_logged_contact_email();
            if ($email !== '') {
                $this->CI->session->set_userdata('arenagamer_pending_sync_email', $email);
            }
        }

        return $this->load_auth_state_from_storage();
    }

    private function contact_auth_error_message()
    {
        if ($this->auth_context !== 'contact') {
            return 'Não foi possível autenticar na API. Verifique email e senha em Configurações.';
        }

        if ($this->CI->session->userdata('arenagamer_pending_sync_email')
            || $this->CI->session->userdata('arenagamer_contact_basic_email')) {
            if (function_exists('is_client_logged_in') && is_client_logged_in()) {
                return 'Não foi possível conectar à API ArenaGamer. Confirme sua senha abaixo — ela deve ser a mesma do portal e existir na plataforma ArenaGamer.';
            }

            return 'Não foi possível conectar à API ArenaGamer. Verifique se a API está online e se suas credenciais existem na plataforma.';
        }

        if (function_exists('is_client_logged_in') && is_client_logged_in()) {
            return 'Conecte sua conta ArenaGamer informando a mesma senha do portal abaixo.';
        }

        return 'Sessão ArenaGamer expirada. Faça login novamente no portal.';
    }

    private function invalidate_auth_tokens()
    {
        $this->token = '';
        $this->refresh_token = '';
        $this->auth_user = null;

        if ($this->auth_context === 'contact') {
            $this->CI->session->unset_userdata([
                'arenagamer_contact_token',
                'arenagamer_contact_refresh_token',
                'arenagamer_contact_auth_user',
            ]);

            if (function_exists('get_contact_user_id') && get_contact_user_id()) {
                delete_contact_meta(get_contact_user_id(), 'arenagamer_api_token');
                delete_contact_meta(get_contact_user_id(), 'arenagamer_refresh_token');
                delete_contact_meta(get_contact_user_id(), 'arenagamer_auth_user');
            }

            return;
        }

        update_option('arenagamer_api_token', '');
        update_option('arenagamer_refresh_token', '');
        update_option('arenagamer_auth_user', '');
    }

    private function store_auth_user($user)
    {
        if (!is_array($user)) {
            return;
        }

        $this->auth_user = $user;
        $this->persist_auth_user($user);
    }

    private function load_auth_state()
    {
        if ($this->auth_context === 'contact') {
            $contactId = $this->resolved_contact_id();
            if (!$contactId) {
                $this->token = (string) $this->CI->session->userdata('arenagamer_contact_token');
                $this->refresh_token = (string) $this->CI->session->userdata('arenagamer_contact_refresh_token');
                $this->auth_user = $this->decode_json($this->CI->session->userdata('arenagamer_contact_auth_user'));

                return;
            }

            $this->token = (string) ($this->read_contact_meta('arenagamer_api_token') ?: $this->CI->session->userdata('arenagamer_contact_token'));
            $this->refresh_token = (string) ($this->read_contact_meta('arenagamer_refresh_token') ?: $this->CI->session->userdata('arenagamer_contact_refresh_token'));
            $this->auth_user = $this->decode_json($this->read_contact_meta('arenagamer_auth_user'))
                ?: $this->decode_json($this->CI->session->userdata('arenagamer_contact_auth_user'));

            return;
        }

        $this->token = get_option('arenagamer_api_token');
        $this->refresh_token = get_option('arenagamer_refresh_token');
        $this->auth_user = $this->decode_stored_auth_user();
    }

    private function load_auth_state_from_storage()
    {
        $this->load_auth_state();

        return !empty($this->token);
    }

    private function persist_auth_tokens()
    {
        if ($this->auth_context === 'contact') {
            $this->CI->session->set_userdata([
                'arenagamer_contact_token'         => $this->token,
                'arenagamer_contact_refresh_token' => $this->refresh_token,
            ]);
            $this->persist_contact_meta('arenagamer_api_token', $this->token);
            $this->persist_contact_meta('arenagamer_refresh_token', $this->refresh_token);

            return;
        }

        update_option('arenagamer_api_token', $this->token);
        update_option('arenagamer_refresh_token', $this->refresh_token);
    }

    private function persist_auth_user($user)
    {
        $encoded = json_encode($user, JSON_UNESCAPED_UNICODE);

        if ($this->auth_context === 'contact') {
            $this->CI->session->set_userdata('arenagamer_contact_auth_user', $encoded);
            $this->persist_contact_meta('arenagamer_auth_user', $encoded);

            return;
        }

        update_option('arenagamer_auth_user', $encoded);
    }

    private function clear_auth_state($full = false)
    {
        $this->token = '';
        $this->refresh_token = '';
        $this->auth_user = null;

        if ($this->auth_context === 'contact') {
            $sessionKeys = [
                'arenagamer_contact_token',
                'arenagamer_contact_refresh_token',
                'arenagamer_contact_auth_user',
            ];

            if ($full) {
                $sessionKeys = array_merge($sessionKeys, [
                    'arenagamer_contact_basic_email',
                    'arenagamer_contact_basic_password',
                    'arenagamer_pending_sync_email',
                    'arenagamer_pending_sync_password',
                ]);
            }

            $this->CI->session->unset_userdata($sessionKeys);

            if (function_exists('get_contact_user_id') && get_contact_user_id()) {
                delete_contact_meta(get_contact_user_id(), 'arenagamer_api_token');
                delete_contact_meta(get_contact_user_id(), 'arenagamer_refresh_token');
                delete_contact_meta(get_contact_user_id(), 'arenagamer_auth_user');
            }

            return;
        }

        update_option('arenagamer_api_token', '');
        update_option('arenagamer_refresh_token', '');
        update_option('arenagamer_auth_user', '');
    }

    private function persist_contact_meta($key, $value)
    {
        $contactId = $this->resolved_contact_id();
        if (!$contactId || !function_exists('get_contact_meta')) {
            return;
        }
        $existing = get_contact_meta($contactId, $key);

        if ($existing !== '' && $existing !== false && $existing !== null) {
            update_contact_meta($contactId, $key, $value);
        } else {
            add_contact_meta($contactId, $key, $value);
        }
    }

    private function read_contact_meta($key)
    {
        $contactId = $this->resolved_contact_id();
        if (!$contactId || !function_exists('get_contact_meta')) {
            return '';
        }

        return (string) get_contact_meta($contactId, $key);
    }

    private function resolved_contact_id()
    {
        if ($this->contact_id > 0) {
            return $this->contact_id;
        }

        return function_exists('get_contact_user_id') ? (int) get_contact_user_id() : 0;
    }

    private function decode_json($raw)
    {
        if ($raw === '' || $raw === null) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function decode_stored_auth_user()
    {
        return $this->decode_json(get_option('arenagamer_auth_user'));
    }

    private function is_success($response)
    {
        return arenagamer_api_is_success($response);
    }

    public function get_last_message()
    {
        return $this->last_message ?? '';
    }

    public function get_last_error()
    {
        return $this->last_error;
    }

    public function test_connection()
    {
        if (!$this->require_staff_context()) {
            return ['success' => false, 'message' => $this->last_error];
        }

        if (!$this->authenticate()) {
            return ['success' => false, 'message' => $this->get_last_error()];
        }

        $probe = $this->request('GET', '/admin/users', null, true, $this->pageable(0, 1));
        if (!arenagamer_api_is_success($probe)) {
            return ['success' => false, 'message' => $this->get_last_error()];
        }

        return [
            'success' => true,
            'message' => arenagamer_api_message($probe, 'Conexão estabelecida com sucesso'),
        ];
    }
}
