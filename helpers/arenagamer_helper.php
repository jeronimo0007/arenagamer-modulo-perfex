<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Extrai o campo data de uma ApiResponse da ArenaGamer API
 */
function arenagamer_api_data($response, $default = null)
{
    if (!is_array($response) || !array_key_exists('data', $response)) {
        return $default;
    }

    return $response['data'];
}

/**
 * Verifica se a resposta da API indica sucesso
 */
function arenagamer_api_is_success($response)
{
    return is_array($response) && (($response['success'] ?? true) === true);
}

/**
 * Extrai mensagem de sucesso ou erro da ApiResponse
 */
function arenagamer_api_message($response, $default = '')
{
    if (is_array($response) && !empty($response['message'])) {
        return (string) $response['message'];
    }

    return $default;
}

/**
 * Generate a Bootstrap status badge for ArenaGamer statuses
 */
function arenagamer_status_badge($status)
{
    $badges = [
        'DRAFT'               => '<span class="label label-default">Rascunho</span>',
        'REGISTRATION_OPEN'   => '<span class="label label-success">Inscrições Abertas</span>',
        'REGISTRATION_CLOSED' => '<span class="label label-warning">Inscrições Fechadas</span>',
        'IN_PROGRESS'         => '<span class="label label-info">Em Andamento</span>',
        'COMPLETED'           => '<span class="label label-primary">Concluído</span>',
        'CANCELLED'           => '<span class="label label-danger">Cancelado</span>',
        'SCHEDULED'           => '<span class="label label-default">Agendada</span>',
        'WALKOVER'            => '<span class="label label-warning">W.O.</span>',
        'RESCHEDULED'         => '<span class="label label-warning">Reagendada</span>',
        'PENDING'             => '<span class="label label-default">Pendente</span>',
        'FAILED'              => '<span class="label label-danger">Falhou</span>',
        'HELD'                => '<span class="label label-warning">Retido</span>',
        'RELEASED'            => '<span class="label label-info">Liberado</span>',
    ];

    return isset($badges[$status])
        ? $badges[$status]
        : '<span class="label label-default">' . htmlspecialchars((string) $status) . '</span>';
}

/**
 * Opções e rótulos traduzidos dos selects de torneio (valor da API => texto na UI).
 */
function arenagamer_tournament_type_labels()
{
    return [
        'SINGLE_ELIMINATION' => 'Eliminação simples',
        'DOUBLE_ELIMINATION' => 'Eliminação dupla',
        'ROUND_ROBIN'        => 'Pontos corridos',
        'GROUP_STAGE'        => 'Fase de grupos',
        'SWISS'              => 'Sistema suíço',
    ];
}

function arenagamer_tournament_type_options()
{
    return array_keys(arenagamer_tournament_type_labels());
}

function arenagamer_tournament_type_label($type)
{
    $labels = arenagamer_tournament_type_labels();

    return $labels[$type] ?? htmlspecialchars((string) $type);
}

function arenagamer_tournament_format_labels()
{
    return [
        'SOLO' => 'Individual',
        'TEAM' => 'Equipe',
    ];
}

function arenagamer_tournament_format_options()
{
    return array_keys(arenagamer_tournament_format_labels());
}

function arenagamer_tournament_format_label($format)
{
    $labels = arenagamer_tournament_format_labels();

    return $labels[$format] ?? htmlspecialchars((string) $format);
}

function arenagamer_tournament_visibility_labels()
{
    return [
        'PUBLIC'  => 'Público',
        'PRIVATE' => 'Privado',
    ];
}

function arenagamer_tournament_visibility_options()
{
    return array_keys(arenagamer_tournament_visibility_labels());
}

function arenagamer_tournament_visibility_label($visibility)
{
    $labels = arenagamer_tournament_visibility_labels();

    return $labels[$visibility] ?? htmlspecialchars((string) $visibility);
}

function arenagamer_tournament_prize_type_labels()
{
    return [
        'AUTOMATIC' => 'Automático',
        'MANUAL'    => 'Manual',
    ];
}

function arenagamer_tournament_prize_type_options()
{
    return array_keys(arenagamer_tournament_prize_type_labels());
}

function arenagamer_tournament_prize_type_label($prizeType)
{
    $labels = arenagamer_tournament_prize_type_labels();

    return $labels[$prizeType] ?? htmlspecialchars((string) $prizeType);
}

function arenagamer_tournament_prize_funding_labels()
{
    return [
        'FIXED'      => 'Prêmio fixo',
        'ENTRY_FEES' => 'Por arrecadação (taxas de inscrição)',
    ];
}

function arenagamer_tournament_prize_funding_options()
{
    return array_keys(arenagamer_tournament_prize_funding_labels());
}

function arenagamer_tournament_prize_funding_label($funding)
{
    $labels = arenagamer_tournament_prize_funding_labels();

    return $labels[$funding] ?? htmlspecialchars((string) $funding);
}

/**
 * Resolve fonte do prêmio a partir dos dados do torneio (compatível com registros antigos).
 */
function arenagamer_tournament_resolve_prize_funding(array $tournament)
{
    $funding = strtoupper(trim((string) ($tournament['prizeFunding'] ?? '')));
    if ($funding !== '') {
        return $funding;
    }

    return 'FIXED';
}

/**
 * Créditos do prêmio fixo cobrados na criação (automático + prêmio fixo).
 */
function arenagamer_tournament_prize_pool_creation_cost($prizeType, $prizeFunding, $prizePool)
{
    if ($prizeType === 'AUTOMATIC' && $prizeFunding === 'FIXED') {
        return max(0, (float) $prizePool);
    }

    return 0.0;
}

/**
 * Valida e normaliza regras de prêmio/taxa do torneio.
 *
 * @return string|null Mensagem de erro ou null
 */
function arenagamer_validate_tournament_prize_settings(array &$payload)
{
    $prizeType = strtoupper(trim((string) ($payload['prizeType'] ?? 'MANUAL')));
    $prizeFunding = strtoupper(trim((string) ($payload['prizeFunding'] ?? 'FIXED')));
    $prizePool = max(0, (float) ($payload['prizePool'] ?? 0));
    $entryFee = max(0, (float) ($payload['entryFeeCredits'] ?? 0));
    $fee = (float) ($payload['feePercentage'] ?? 0);

    if ($prizeFunding === 'ENTRY_FEES') {
        if ($prizeType !== 'AUTOMATIC') {
            return 'Prêmio por arrecadação só é permitido com distribuição automática.';
        }
        if ($entryFee <= 0) {
            return 'Informe a taxa de inscrição para prêmio por arrecadação.';
        }
        if ($fee < 0 || $fee > 100) {
            return 'A taxa do organizador deve estar entre 0% e 100%.';
        }
        if (fmod($fee, 5) > 0.00001) {
            return 'A taxa do organizador deve ser de 5% em 5% (0%, 5%, 10%...).';
        }
        $payload['prizePool'] = 0;
        $payload['feePercentage'] = round($fee);
        $payload['entryFeeCredits'] = $entryFee;
    } else {
        $payload['feePercentage'] = 0;
        $payload['prizePool'] = $prizePool;
        $payload['entryFeeCredits'] = $entryFee >= 0 ? $entryFee : 0;
        if ($prizeType === 'AUTOMATIC' && $prizePool <= 0) {
            return 'Distribuição automática com prêmio fixo exige valor do prêmio maior que zero.';
        }
    }

    $payload['prizeType'] = $prizeType;
    $payload['prizeFunding'] = $prizeFunding;

    return null;
}

/**
 * Ordenação padrão das listagens de torneio (mais recentes primeiro).
 *
 * @return string[]
 */
function arenagamer_tournament_list_sort()
{
    return ['createdAt,desc'];
}

/**
 * Torneio cancelado não pode ser editado.
 */
function arenagamer_tournament_is_editable($tournament)
{
    if (!is_array($tournament)) {
        return false;
    }

    return strtoupper(trim((string) ($tournament['status'] ?? ''))) !== 'CANCELLED';
}

function arenagamer_tournament_not_editable_message()
{
    return 'Torneios cancelados não podem ser editados.';
}

function arenagamer_user_type_badge($userType)
{
    $badges = [
        'STAFF'   => '<span class="label label-primary">Staff</span>',
        'CONTACT' => '<span class="label label-info">Contato</span>',
    ];

    return $badges[$userType] ?? '<span class="label label-default">' . htmlspecialchars((string) $userType) . '</span>';
}

function arenagamer_role_badge($role)
{
    $classes = [
        'ADMIN'   => 'danger',
        'MANAGER' => 'warning',
        'PLAYER'  => 'info',
    ];
    $class = $classes[$role] ?? 'default';

    return '<span class="label label-' . $class . '">' . htmlspecialchars((string) $role) . '</span>';
}

/**
 * Format ArenaGamer date
 */
function arenagamer_format_date($date, $format = 'd/m/Y H:i')
{
    if (empty($date)) {
        return '—';
    }

    return date($format, strtotime($date));
}

/**
 * Format credits amount
 */
function arenagamer_format_credits($amount)
{
    return number_format((float) $amount, 2, ',', '.') . ' créditos';
}

/**
 * Rótulo amigável para tipos de transação da carteira
 */
function arenagamer_wallet_transaction_type_label($type)
{
    $map = [
        'DEPOSIT'       => 'Crédito adicionado',
        'WITHDRAWAL'    => 'Crédito removido',
        'TOURNAMENT_FEE'=> 'Taxa de torneio',
        'TOURNAMENT_PRIZE'=> 'Prêmio de torneio',
        'ENTRY_FEE'     => 'Taxa de inscrição',
        'REFUND'        => 'Estorno',
        'HOLD'          => 'Reserva',
        'HOLD_RELEASE'  => 'Liberação de reserva',
        'HOLD_CAPTURE'  => 'Uso de reserva',
    ];

    $type = strtoupper((string) $type);

    return $map[$type] ?? $type;
}

/**
 * Indica se a transação representa crédito comprado/adicionado
 */
function arenagamer_wallet_transaction_is_credit_in($type, $amount = null)
{
    $type = strtoupper((string) $type);

    if (in_array($type, ['DEPOSIT', 'REFUND', 'HOLD_RELEASE'], true)) {
        return true;
    }

    if ($type === 'HOLD' || $type === 'HOLD_CAPTURE' || $type === 'WITHDRAWAL') {
        return false;
    }

    return $amount !== null && (float) $amount > 0;
}

/**
 * Formata valor monetário (BRL)
 */
function arenagamer_format_money($amount)
{
    return 'R$ ' . number_format((float) $amount, 2, ',', '.');
}

/**
 * Extrai o plano ativo da conta (cliente) retornado em user.plan pela API
 */
function arenagamer_contact_plan($authUser)
{
    if (!is_array($authUser)) {
        return null;
    }

    $plan = $authUser['plan'] ?? null;

    return is_array($plan) && !empty($plan['id']) ? $plan : null;
}

/**
 * Verifica se o plano está expirado pela data expiresAt
 */
function arenagamer_plan_is_expired($plan)
{
    if (!is_array($plan) || empty($plan['expiresAt'])) {
        return true;
    }

    return strtotime((string) $plan['expiresAt']) <= time();
}

/**
 * Plano vinculado e dentro da validade
 */
function arenagamer_plan_is_active($plan)
{
    return is_array($plan) && !empty($plan['id']) && !arenagamer_plan_is_expired($plan);
}

/**
 * Badge de status do plano (ativo, expirado ou sem plano)
 */
function arenagamer_plan_status_badge($plan)
{
    if (!is_array($plan) || empty($plan['id'])) {
        return '<span class="label label-default">Sem plano</span>';
    }

    if (arenagamer_plan_is_expired($plan)) {
        return '<span class="label label-danger">Expirado</span>';
    }

    if (arenagamer_plan_cancel_scheduled($plan)) {
        return '<span class="label label-warning">Cancelamento agendado</span>';
    }

    if (arenagamer_plan_downgrade_scheduled($plan)) {
        return '<span class="label label-warning">Downgrade agendado</span>';
    }

    return '<span class="label label-success">Ativo</span>';
}

/**
 * Tipo de ação ao escolher um plano do catálogo
 */
function arenagamer_plan_compare_action($currentPlan, array $targetPlan)
{
    $targetId = (int) ($targetPlan['id'] ?? 0);
    $currentId = is_array($currentPlan) ? (int) ($currentPlan['id'] ?? 0) : 0;

    if ($targetId > 0 && $currentId === $targetId && arenagamer_plan_is_active($currentPlan)) {
        return 'current';
    }

    if ($currentId === 0 || !arenagamer_plan_is_active($currentPlan)) {
        return 'subscribe';
    }

    $currentPrice = (float) ($currentPlan['monthlyPrice'] ?? 0);
    $targetPrice = (float) ($targetPlan['monthlyPrice'] ?? 0);

    if ($targetPrice > $currentPrice) {
        return 'upgrade';
    }

    if ($targetPrice < $currentPrice) {
        return 'downgrade';
    }

    return 'change';
}

/**
 * Rótulo do botão de contratação/troca de plano
 */
function arenagamer_plan_action_label($action)
{
    $labels = [
        'current'   => 'Plano atual',
        'subscribe' => 'Contratar',
        'upgrade'   => 'Fazer upgrade',
        'downgrade' => 'Fazer downgrade',
        'change'    => 'Trocar plano',
    ];

    return $labels[$action] ?? 'Contratar';
}

/**
 * Verifica se o plano é o Free (nome ou preço zero)
 */
function arenagamer_plan_is_free($plan)
{
    if (!is_array($plan)) {
        return false;
    }

    $name = strtolower(trim((string) ($plan['name'] ?? '')));
    if ($name === 'free') {
        return true;
    }

    return (float) ($plan['monthlyPrice'] ?? -1) <= 0;
}

/**
 * Cancelamento agendado para o fim do período
 */
function arenagamer_plan_cancel_scheduled($plan)
{
    return is_array($plan) && !empty($plan['cancelAtPeriodEnd']);
}

/**
 * Downgrade agendado para o fim do período
 */
function arenagamer_plan_downgrade_scheduled($plan)
{
    return is_array($plan) && !empty($plan['pendingPlanId']);
}

/**
 * Plano alvo já está agendado como downgrade
 */
function arenagamer_plan_is_pending_target($plan, array $targetPlan)
{
    return arenagamer_plan_downgrade_scheduled($plan)
        && (int) ($plan['pendingPlanId'] ?? 0) === (int) ($targetPlan['id'] ?? 0);
}

/**
 * Mensagem sobre alteração agendada do plano
 */
function arenagamer_plan_scheduled_message($plan)
{
    if (!is_array($plan)) {
        return '';
    }

    $expiresAt = arenagamer_format_date($plan['expiresAt'] ?? '', 'd/m/Y');

    if (arenagamer_plan_cancel_scheduled($plan)) {
        return 'Cancelamento agendado. Você permanece no plano atual até ' . $expiresAt . '.';
    }

    if (arenagamer_plan_downgrade_scheduled($plan)) {
        $targetName = trim((string) ($plan['pendingPlanName'] ?? 'novo plano'));

        return 'Downgrade agendado para ' . $targetName . ' em ' . $expiresAt . '.';
    }

    return '';
}

/**
 * Texto de confirmação ao contratar, fazer downgrade ou cancelar
 */
function arenagamer_plan_action_confirm_message($action, $plan, array $targetPlan = [])
{
    $expiresAt = arenagamer_format_date(is_array($plan) ? ($plan['expiresAt'] ?? '') : '', 'd/m/Y');
    $targetName = htmlspecialchars((string) ($targetPlan['name'] ?? ''), ENT_QUOTES, 'UTF-8');

    switch ($action) {
        case 'downgrade':
        case 'change':
            return 'O downgrade para ' . $targetName . ' será aplicado em ' . $expiresAt
                . '. Até lá você permanece no plano atual. Confirmar?';
        case 'upgrade':
            return 'Confirmar upgrade para o plano ' . $targetName . '? Será gerada uma fatura para pagamento.';
        case 'subscribe':
            return 'Confirmar contratação do plano ' . $targetName . '? Será gerada uma fatura recorrente mensal.';
        default:
            return 'Confirmar alteração de plano?';
    }
}

/**
 * Plano pago ativo pode ser cancelado pelo contato principal
 */
function arenagamer_can_cancel_plan($plan, $authUser = null)
{
    return arenagamer_plan_is_active($plan)
        && !arenagamer_plan_is_free($plan)
        && !arenagamer_plan_cancel_scheduled($plan)
        && arenagamer_can_subscribe_plan($authUser);
}

/**
 * Verifica se o contato logado é o principal (is_primary) da conta
 */
function arenagamer_contact_is_primary($authUser = null)
{
    if (is_array($authUser) && array_key_exists('isPrimary', $authUser)) {
        return !empty($authUser['isPrimary']);
    }

    if (function_exists('is_primary_contact')) {
        return (bool) is_primary_contact();
    }

    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_model');

    return $CI->arenagamer_model->is_logged_contact_primary();
}

function arenagamer_contact_can_view_wallet($authUser = null)
{
    if (!is_array($authUser)) {
        return false;
    }

    if (array_key_exists('canViewWallet', $authUser)) {
        return (bool) $authUser['canViewWallet'];
    }

    if (arenagamer_contact_is_primary($authUser)) {
        return true;
    }

    return (bool) ($authUser['walletViewAllowed'] ?? false);
}

function arenagamer_contact_can_use_wallet($authUser = null)
{
    if (!is_array($authUser)) {
        return false;
    }

    if (array_key_exists('canUseWallet', $authUser)) {
        return (bool) $authUser['canUseWallet'];
    }

    if (arenagamer_contact_is_primary($authUser)) {
        return true;
    }

    return (bool) ($authUser['walletUseAllowed'] ?? false);
}

/**
 * Somente o contato principal pode contratar ou trocar planos
 */
function arenagamer_can_subscribe_plan($authUser = null)
{
    return arenagamer_contact_is_primary($authUser);
}

/**
 * Mensagem para contatos que não podem contratar planos
 */
function arenagamer_plan_subscribe_blocked_message()
{
    return 'Entre em contato com o administrador da conta para contratar um plano.';
}

/**
 * URL de pagamento de fatura Perfex
 */
function arenagamer_invoice_payment_url($invoiceId)
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get((int) $invoiceId);

    if (!$invoice) {
        return '';
    }

    return site_url('invoice/' . (int) $invoice->id . '/' . $invoice->hash);
}

/**
 * Atualiza perfil/plano do contato na API (contador de torneios, etc.).
 */
function arenagamer_refresh_contact_auth_user($api = null)
{
    $api = $api ?: arenagamer_contact_api();

    if (!$api || !arenagamer_ensure_contact_api($api)) {
        return null;
    }

    $response = $api->get_me(true);

    return arenagamer_api_is_success($response) ? arenagamer_api_data($response) : $api->get_auth_user();
}

/**
 * Instancia API ArenaGamer autenticada pelo contato principal do client
 */
function arenagamer_api_for_client($clientUserId)
{
    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_model');
    $contactId = $CI->arenagamer_model->get_primary_contact_id($clientUserId);

    if ($contactId <= 0) {
        return null;
    }

    $CI->load->library('arenagamer/ArenaGamer_api', [
        'context'    => 'contact',
        'contact_id' => $contactId,
    ], 'arenagamer_client_plan_api');

    $api = $CI->arenagamer_client_plan_api;
    if (!$api->ensure_contact_session()) {
        return null;
    }

    return $api;
}

/**
 * Instancia API ArenaGamer autenticada como staff (admin)
 */
function arenagamer_staff_api()
{
    $CI = &get_instance();
    $CI->load->library('arenagamer/ArenaGamer_api', [], 'arenagamer_staff_api');
    $api = $CI->arenagamer_staff_api;

    if (!$api->has_access_token() && !$api->authenticate()) {
        return null;
    }

    return $api;
}

/**
 * Saldo disponível da carteira do client logado (cache por request)
 */
function arenagamer_client_wallet_balance($api = null)
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $api = $api ?: arenagamer_contact_api();
    if (!$api || !arenagamer_ensure_contact_api($api)) {
        $cached = null;

        return null;
    }

    $authUser = $api->get_auth_user();
    if (!arenagamer_contact_can_view_wallet($authUser)) {
        $cached = null;

        return null;
    }

    $response = $api->get_wallet_balance();
    if (!arenagamer_api_is_success($response)) {
        $cached = null;

        return null;
    }

    $cached = arenagamer_api_data($response);

    return $cached;
}

/**
 * Contato pode iniciar compra de créditos (fatura Perfex)
 */
function arenagamer_contact_can_buy_credits($authUser = null)
{
    if (!function_exists('is_client_logged_in') || !is_client_logged_in()) {
        return false;
    }

    if ($authUser === null) {
        $api = arenagamer_contact_api();
        if (!$api || !arenagamer_ensure_contact_api($api)) {
            return false;
        }
        $authUser = $api->get_auth_user();
    }

    if (!is_array($authUser)) {
        return false;
    }

    return arenagamer_contact_is_primary($authUser) || arenagamer_contact_can_use_wallet($authUser);
}

/**
 * Credita wallet na API após pagamento da fatura de compra de créditos
 */
function arenagamer_process_credit_invoice_paid($invoiceId)
{
    $invoiceId = (int) $invoiceId;
    if ($invoiceId <= 0) {
        return;
    }

    if (!function_exists('arenagamer_invoice_is_fully_paid') || !arenagamer_invoice_is_fully_paid($invoiceId)) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_credit_invoices_model');
    $CI->load->model('arenagamer/arenagamer_model');

    $link = $CI->arenagamer_credit_invoices_model->get_by_invoice_id($invoiceId);
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
        $CI->arenagamer_credit_invoices_model->mark_paid((int) $link['id']);
    }

    $api = arenagamer_staff_api();
    if (!$api) {
        $CI->arenagamer_credit_invoices_model->mark_applied(
            (int) $link['id'],
            'Não foi possível autenticar a API ArenaGamer (staff).'
        );

        return;
    }

    $creditsAmount = (float) ($link['credits_amount'] ?? 0);
    $result = $api->admin_wallet_deposit((int) $link['client_userid'], [
        'amount'      => $creditsAmount,
        'description' => 'Compra de créditos via fatura Perfex #' . $invoiceId,
    ]);

    if (!arenagamer_api_is_success($result)) {
        $error = $api->get_last_error() ?: 'Falha ao creditar saldo na API.';
        $CI->arenagamer_credit_invoices_model->mark_applied((int) $link['id'], $error);
        $CI->arenagamer_model->log_sync('credit_invoice', (int) $link['id'], 'apply', 'error', $error);

        return;
    }

    $CI->arenagamer_credit_invoices_model->mark_applied((int) $link['id']);
    $CI->arenagamer_model->log_sync(
        'credit_invoice',
        (int) $link['id'],
        'apply',
        'success',
        number_format($creditsAmount, 2, '.', '') . ' créditos via fatura #' . $invoiceId
    );
}

/**
 * Custo do plano em créditos (1 crédito = R$ 1,00), conforme período de cobrança.
 */
function arenagamer_plan_credit_cost(array $plan, $billingPeriodMonths = 1)
{
    return arenagamer_plan_billing_amount($plan['monthlyPrice'] ?? 0, $billingPeriodMonths);
}

/**
 * Mapa período => créditos para exibição no front (planos pagos).
 */
function arenagamer_plan_credit_costs_by_period(array $plan, array $billingPeriods = null)
{
    if (!is_array($billingPeriods)) {
        $billingPeriods = arenagamer_billing_periods();
    }

    $costs = [];
    foreach ($billingPeriods as $months => $config) {
        $costs[(int) $months] = arenagamer_plan_credit_cost($plan, (int) $months);
    }

    return $costs;
}

/**
 * Contratação paga exige fatura Perfex; Free e downgrade usam API direta
 */
function arenagamer_plan_requires_invoice($action, array $targetPlan)
{
    if (arenagamer_plan_is_free($targetPlan)) {
        return false;
    }

    return in_array($action, ['subscribe', 'upgrade'], true);
}

/**
 * Períodos de cobrança disponíveis (meses => config)
 */
function arenagamer_billing_periods()
{
    return [
        1  => [
            'months'   => 1,
            'discount' => 0,
            'label'    => 'Mensal',
            'short'    => 'mês',
        ],
        6  => [
            'months'   => 6,
            'discount' => 20,
            'label'    => 'Semestral',
            'short'    => '6 meses',
        ],
        12 => [
            'months'   => 12,
            'discount' => 30,
            'label'    => 'Anual',
            'short'    => '12 meses',
        ],
    ];
}

/**
 * Valida e normaliza período de cobrança (1, 6 ou 12 meses)
 */
function arenagamer_normalize_billing_period_months($months)
{
    $months = (int) $months;
    $periods = arenagamer_billing_periods();

    return isset($periods[$months]) ? $months : 1;
}

/**
 * Percentual de desconto do período (0, 20 ou 30)
 */
function arenagamer_billing_period_discount($months)
{
    $months = arenagamer_normalize_billing_period_months($months);
    $periods = arenagamer_billing_periods();

    return (int) ($periods[$months]['discount'] ?? 0);
}

/**
 * Valor total da fatura para o período (desconto sobre o total do período)
 */
function arenagamer_plan_billing_amount($monthlyPrice, $months)
{
    $months = arenagamer_normalize_billing_period_months($months);
    $monthly = max(0, (float) $monthlyPrice);
    $discount = arenagamer_billing_period_discount($months);
    $gross = $monthly * $months;

    return round($gross * (1 - ($discount / 100)), function_exists('get_decimal_places') ? get_decimal_places() : 2);
}

/**
 * Rótulo do período para exibição
 */
function arenagamer_billing_period_label($months, $withDiscount = true)
{
    $months = arenagamer_normalize_billing_period_months($months);
    $periods = arenagamer_billing_periods();
    $config = $periods[$months] ?? $periods[1];
    $label = (string) ($config['label'] ?? 'Mensal');

    if ($withDiscount && !empty($config['discount'])) {
        $label .= ' (' . (int) $config['discount'] . '% off)';
    }

    return $label;
}

/**
 * Descrição do item na fatura Perfex
 */
function arenagamer_plan_invoice_item_label(array $plan, $months, $isUpgrade = false)
{
    $planName = trim((string) ($plan['name'] ?? 'Plano ArenaGamer'));
    $months = arenagamer_normalize_billing_period_months($months);
    $periodLabel = arenagamer_billing_period_label($months, true);
    $prefix = $isUpgrade ? 'upgrade' : 'assinatura';

    return 'ArenaGamer — ' . $planName . ' (' . $prefix . ' ' . strtolower($periodLabel) . ')';
}

/**
 * Configuração de fatura recorrente Perfex para qualquer período (1, 6 ou 12 meses)
 */
function arenagamer_invoice_recurring_data($billingPeriodMonths)
{
    $months = arenagamer_normalize_billing_period_months($billingPeriodMonths);

    return [
        'recurring'          => $months,
        'recurring_type'     => 'month',
        'cycles'             => 0,
        'custom_recurring'   => 0,
    ];
}

/**
 * Infere período de cobrança (1, 6 ou 12) a partir das datas da assinatura.
 */
function arenagamer_infer_billing_period_months($plan)
{
    if (!is_array($plan)) {
        return 1;
    }

    $starts = $plan['startsAt'] ?? null;
    $expires = $plan['expiresAt'] ?? null;
    if (!$starts || !$expires) {
        return 1;
    }

    try {
        $start = new DateTime(substr((string) $starts, 0, 19));
        $end = new DateTime(substr((string) $expires, 0, 19));
        $interval = $start->diff($end);
        $months = ($interval->y * 12) + $interval->m;

        if ($interval->d >= 15) {
            $months++;
        }

        if ($months >= 11) {
            return 12;
        }

        if ($months >= 5) {
            return 6;
        }

        return 1;
    } catch (Exception $e) {
        return 1;
    }
}

/**
 * Contexto de cobrança da fatura recorrente Perfex (se existir).
 */
function arenagamer_plan_invoice_billing_context($clientUserId, $planId = null)
{
    $clientUserId = (int) $clientUserId;
    if ($clientUserId <= 0) {
        return null;
    }

    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_plan_invoices_model');
    $root = $CI->arenagamer_plan_invoices_model->get_recurring_root_for_client($clientUserId);

    if (!$root) {
        return null;
    }

    if ($planId !== null && (int) $planId > 0 && (int) ($root['api_plan_id'] ?? 0) !== (int) $planId) {
        return null;
    }

    return [
        'billing_period_months' => (int) ($root['billing_period_months'] ?? 1),
        'amount'                => (float) ($root['amount'] ?? 0),
    ];
}

/**
 * Período de cobrança efetivo do plano ativo (API, fatura Perfex ou inferência).
 */
function arenagamer_plan_billing_period_months($plan, $clientUserId = null)
{
    if (!is_array($plan)) {
        return 1;
    }

    if (!empty($plan['billingPeriodMonths'])) {
        return arenagamer_normalize_billing_period_months($plan['billingPeriodMonths']);
    }

    if ($clientUserId) {
        $invoiceContext = arenagamer_plan_invoice_billing_context($clientUserId, (int) ($plan['id'] ?? 0));
        if ($invoiceContext) {
            return arenagamer_normalize_billing_period_months($invoiceContext['billing_period_months']);
        }
    }

    return arenagamer_infer_billing_period_months($plan);
}

/**
 * Textos de preço/período do plano ativo para exibição no portal do cliente.
 */
function arenagamer_plan_active_billing_display($plan, $clientUserId = null)
{
    if (!is_array($plan)) {
        return [
            'period_months' => 1,
            'period_label'  => '—',
            'amount'        => 0,
            'price_line'    => '—',
            'detail_line'   => '—',
        ];
    }

    if (arenagamer_plan_is_free($plan)) {
        return [
            'period_months' => 1,
            'period_label'  => 'Grátis',
            'amount'        => 0,
            'price_line'    => 'Grátis',
            'detail_line'   => 'Plano gratuito',
        ];
    }

    $months = arenagamer_plan_billing_period_months($plan, $clientUserId);
    $monthlyPrice = (float) ($plan['monthlyPrice'] ?? 0);
    $amount = arenagamer_plan_billing_amount($monthlyPrice, $months);
    $invoiceContext = $clientUserId ? arenagamer_plan_invoice_billing_context($clientUserId, (int) ($plan['id'] ?? 0)) : null;

    if ($invoiceContext && $invoiceContext['amount'] > 0) {
        $amount = (float) $invoiceContext['amount'];
    }

    $periodLabel = arenagamer_billing_period_label($months, true);
    $periods = arenagamer_billing_periods();
    $short = $periods[$months]['short'] ?? ($months . ' meses');

    if ($months === 1) {
        return [
            'period_months' => 1,
            'period_label'  => 'Mensal',
            'amount'        => $amount,
            'price_line'    => arenagamer_format_money($amount) . '/mês',
            'detail_line'   => 'Cobrança mensal: ' . arenagamer_format_money($amount),
        ];
    }

    return [
        'period_months' => $months,
        'period_label'  => $periodLabel,
        'amount'        => $amount,
        'price_line'    => arenagamer_format_money($amount) . ' / ' . $short,
        'detail_line'   => 'Cobrança ' . strtolower($periodLabel) . ': ' . arenagamer_format_money($amount),
    ];
}

/**
 * Interrompe fatura recorrente Perfex vinculada ao plano do cliente.
 */
function arenagamer_stop_plan_recurring($clientUserId)
{
    $clientUserId = (int) $clientUserId;
    if ($clientUserId <= 0) {
        return false;
    }

    $CI = &get_instance();
    $CI->load->model('arenagamer/arenagamer_plan_invoices_model');

    return $CI->arenagamer_plan_invoices_model->stop_recurring_for_client($clientUserId);
}

/**
 * Fatura realmente paga (com registro de pagamento), não apenas status PAID automático
 */
function arenagamer_invoice_is_fully_paid($invoiceId)
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get((int) $invoiceId);

    if (!$invoice) {
        return false;
    }

    $total = (float) ($invoice->total ?? 0);
    if ($total <= 0) {
        return false;
    }

    if ((int) ($invoice->status ?? 0) !== Invoices_model::STATUS_PAID) {
        return false;
    }

    $CI->db->select_sum('amount');
    $CI->db->where('invoiceid', (int) $invoiceId);
    $paymentsSum = (float) ($CI->db->get(db_prefix() . 'invoicepaymentrecords')->row()->amount ?? 0);

    if (!class_exists('credit_notes_model', false)) {
        $CI->load->model('credit_notes_model');
    }

    $credits = $CI->credit_notes_model->get_applied_invoice_credits((int) $invoiceId);
    foreach ($credits as $credit) {
        $paymentsSum += (float) ($credit['amount'] ?? 0);
    }

    $decimals = function_exists('get_decimal_places') ? get_decimal_places() : 2;

    if (function_exists('bccomp')) {
        return bccomp((string) $paymentsSum, (string) $total, $decimals) >= 0;
    }

    return round($paymentsSum, $decimals) >= round($total, $decimals);
}

/**
 * Fatura marcada como paga pelo Perfex sem pagamento real (ex.: total zerado no insert)
 */
function arenagamer_invoice_is_false_paid($invoiceId)
{
    $CI = &get_instance();
    $CI->load->model('invoices_model');
    $invoice = $CI->invoices_model->get((int) $invoiceId);

    if (!$invoice) {
        return false;
    }

    if ((float) ($invoice->total ?? 0) <= 0) {
        return (int) ($invoice->status ?? 0) === Invoices_model::STATUS_PAID;
    }

    return (int) ($invoice->status ?? 0) === Invoices_model::STATUS_PAID
        && !arenagamer_invoice_is_fully_paid((int) $invoiceId);
}

/**
 * Recalcula subtotal/total a partir dos itens e corrige status (Unpaid quando aplicável)
 */
function arenagamer_sync_invoice_totals($invoiceId)
{
    $invoiceId = (int) $invoiceId;
    if ($invoiceId <= 0) {
        return false;
    }

    $CI = &get_instance();
    $CI->load->model('invoices_model');

    $invoice = $CI->invoices_model->get($invoiceId);
    if (!$invoice) {
        return false;
    }

    if (!function_exists('get_items_by_type') || !function_exists('calculate_sales_total')) {
        $CI->load->helper('sales');
    }

    $items = get_items_by_type('invoice', $invoiceId);
    if (empty($items)) {
        return false;
    }

    $calcItems = [];
    foreach ($items as $item) {
        $taxes = [];
        if (!empty($item['taxes'])) {
            foreach ($item['taxes'] as $tax) {
                $taxes[] = [
                    'taxname' => $tax['taxname'],
                    'taxrate' => $tax['taxrate'],
                ];
            }
        }

        $calcItems[] = [
            'qty'         => $item['qty'],
            'rate'        => $item['rate'],
            'taxes'       => $taxes,
            'is_optional' => $item['is_optional'] ?? false,
            'is_selected' => $item['is_selected'] ?? true,
        ];
    }

    $totals = calculate_sales_total($calcItems, [
        'discount_percent' => (float) ($invoice->discount_percent ?? 0),
        'discount_total'   => (float) ($invoice->discount_total ?? 0),
        'discount_type'    => $invoice->discount_type ?? '',
        'adjustment'       => (float) ($invoice->adjustment ?? 0),
    ]);

    $decimals = function_exists('get_decimal_places') ? get_decimal_places() : 2;
    $update = [
        'subtotal'  => number_format((float) $totals['subtotal'], $decimals, '.', ''),
        'total_tax' => number_format((float) $totals['total_tax'], $decimals, '.', ''),
        'total'     => number_format((float) $totals['total'], $decimals, '.', ''),
    ];

    $CI->db->where('id', $invoiceId)->update(db_prefix() . 'invoices', $update);

    if (function_exists('update_sales_total_tax_column')) {
        update_sales_total_tax_column($invoiceId, 'invoice', db_prefix() . 'invoices');
    }

    if (function_exists('update_invoice_status')) {
        update_invoice_status($invoiceId, true);
    }

    return (float) $totals['total'] > 0;
}

/**
 * Monta payload de plano para a API
 */
function arenagamer_plan_payload_from_input($input)
{
    $maxTournamentsPerMonth = (int) $input->post('max_tournaments_per_month');

    return [
        'name'                    => trim((string) $input->post('name')),
        'description'             => trim((string) $input->post('description')),
        'freeTournamentsPerMonth' => $maxTournamentsPerMonth,
        'freeMaxParticipants'     => (int) $input->post('free_max_participants'),
        'allowsEntryFee'          => (bool) $input->post('allows_entry_fee'),
        'maxTournamentsPerMonth'  => $maxTournamentsPerMonth,
        'monthlyPrice'            => (float) str_replace(',', '.', (string) $input->post('monthly_price')),
        'hidden'                  => (bool) $input->post('hidden'),
        'active'                  => (bool) $input->post('active'),
        'sortOrder'               => (int) $input->post('sort_order'),
    ];
}

/**
 * Repopula o formulário de plano após erro de validação/API
 */
function arenagamer_plan_from_post($input)
{
    return arenagamer_plan_payload_from_input($input);
}

/**
 * Monta payload de preset para a API.
 */
function arenagamer_preset_payload_from_input($input)
{
    $payload = [
        'gameName'          => trim((string) $input->post('game_name')),
        'platform'          => trim((string) $input->post('platform')),
        'teamSize'          => max(1, (int) $input->post('team_size')),
        'minPlayersPerTeam' => max(1, (int) $input->post('min_players_per_team')),
        'maxPlayersPerTeam' => max(1, (int) $input->post('max_players_per_team')),
        'iconUrl'           => trim((string) $input->post('icon_url')),
        'gameImageUrl'      => trim((string) $input->post('game_image_url')),
        'rulesTemplate'     => trim((string) $input->post('rules_template')),
        'scoringScript'     => trim((string) $input->post('scoring_script')),
        'active'            => (bool) $input->post('active'),
    ];

    $uploadError = arenagamer_apply_preset_image_uploads($payload);
    if ($uploadError !== null) {
        $payload['_upload_error'] = $uploadError;
    }

    return $payload;
}

function arenagamer_preset_from_post($input)
{
    return arenagamer_preset_payload_from_input($input);
}

function arenagamer_preset_upload_dir()
{
    $rel = 'modules/arenagamer/uploads/presets/';
    $abs = FCPATH . $rel;

    if (!is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }

    return [
        'abs' => $abs,
        'rel' => $rel,
        'url' => rtrim(site_url($rel), '/') . '/',
    ];
}

function arenagamer_handle_preset_image_upload($fieldName)
{
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }

    $CI = &get_instance();
    $CI->load->library('upload');
    $dir = arenagamer_preset_upload_dir();
    $CI->upload->initialize([
        'upload_path'   => $dir['abs'],
        'allowed_types' => 'gif|jpg|jpeg|png|webp',
        'max_size'      => 4096,
        'encrypt_name'  => true,
    ]);

    if (!$CI->upload->do_upload($fieldName)) {
        return ['error' => strip_tags($CI->upload->display_errors('', ''))];
    }

    $data = $CI->upload->data();

    return $dir['url'] . $data['file_name'];
}

function arenagamer_apply_preset_image_uploads(array &$payload)
{
    foreach ([
        'icon_file'       => 'iconUrl',
        'game_image_file' => 'gameImageUrl',
    ] as $field => $key) {
        $result = arenagamer_handle_preset_image_upload($field);
        if (is_array($result) && isset($result['error'])) {
            return $result['error'];
        }
        if (is_string($result) && $result !== '') {
            $payload[$key] = $result;
        }
    }

    if (empty($payload['gameImageUrl']) && !empty($payload['iconUrl'])) {
        $payload['gameImageUrl'] = $payload['iconUrl'];
    }

    return null;
}

function arenagamer_preset_game_image_url(array $preset)
{
    if (!empty($preset['gameImageUrl'])) {
        return (string) $preset['gameImageUrl'];
    }

    if (!empty($preset['iconUrl'])) {
        return (string) $preset['iconUrl'];
    }

    return '';
}

function arenagamer_tournament_logo_image_url(array $tournament)
{
    return !empty($tournament['logoImageUrl']) ? (string) $tournament['logoImageUrl'] : '';
}

/**
 * Limite mensal de torneios do plano (campo unificado na UI).
 */
function arenagamer_plan_max_tournaments_per_month($plan)
{
    if (!is_array($plan)) {
        return 0;
    }

    $limit = (int) ($plan['freeTournamentsPerMonth'] ?? 0);
    if ($limit > 0) {
        return $limit;
    }

    return (int) ($plan['maxTournamentsPerMonth'] ?? 0);
}

/**
 * Torneios gratuitos por mês incluídos no plano (sem consumo de créditos).
 */
function arenagamer_plan_free_tournaments_per_month($plan)
{
    return is_array($plan) ? (int) ($plan['freeTournamentsPerMonth'] ?? 0) : 0;
}

/**
 * Torneios já criados no mês corrente (contador da assinatura).
 */
function arenagamer_plan_tournaments_used_this_month($plan)
{
    return is_array($plan) ? (int) ($plan['tournamentsUsedThisMonth'] ?? 0) : 0;
}

/**
 * Máximo de participantes por torneio permitido pelo plano.
 */
function arenagamer_plan_free_max_participants($plan)
{
    return is_array($plan) ? (int) ($plan['freeMaxParticipants'] ?? 0) : 0;
}

/**
 * Plano permite taxa de inscrição em torneios.
 */
function arenagamer_plan_allows_entry_fee($plan)
{
    if (!is_array($plan) || !array_key_exists('allowsEntryFee', $plan)) {
        return false;
    }

    return filter_var($plan['allowsEntryFee'], FILTER_VALIDATE_BOOLEAN);
}

/**
 * Custo mínimo de criação (em créditos) para liberar taxa de inscrição sem benefício do plano.
 */
function arenagamer_entry_fee_min_creation_cost()
{
    return 5.0;
}

/**
 * Custo de criação a partir do mínimo libera taxa de inscrição (sem benefício do plano).
 */
function arenagamer_creation_cost_allows_entry_fee($cost)
{
    return (float) $cost >= arenagamer_entry_fee_min_creation_cost();
}

/**
 * Créditos efetivamente pagos na criação (após isenções do plano), usados para liberar taxa de inscrição.
 */
function arenagamer_tournament_entry_fee_paid_cost($plan, $participantsLimit, array $pricing = null, array $prizeOptions = [])
{
    $cost = arenagamer_calculate_tournament_creation_cost_with_plan($plan, $participantsLimit, $pricing);
    $cost += arenagamer_tournament_prize_pool_creation_cost(
        $prizeOptions['prizeType'] ?? 'MANUAL',
        $prizeOptions['prizeFunding'] ?? 'FIXED',
        $prizeOptions['prizePool'] ?? 0
    );

    return $cost;
}

/**
 * Taxa de inscrição permitida na criação (plano ou 5+ créditos pagos além das isenções).
 */
function arenagamer_tournament_allows_entry_fee($plan, $participantsLimit, array $pricing = null, array $prizeOptions = [])
{
    if (arenagamer_plan_allows_entry_fee($plan)) {
        return true;
    }

    $paidCost = arenagamer_tournament_entry_fee_paid_cost($plan, $participantsLimit, $pricing, $prizeOptions);

    return arenagamer_creation_cost_allows_entry_fee($paidCost);
}

function arenagamer_entry_fee_unlock_message()
{
    return 'Liberada após gastar '
        . arenagamer_format_credits(arenagamer_entry_fee_min_creation_cost())
        . ' na criação (além da isenção do plano) ou com plano que inclui esse benefício.';
}

/**
 * Valida criação de torneio conforme direitos do plano ativo.
 *
 * @return string|null Mensagem de erro ou null se válido
 */
function arenagamer_validate_tournament_against_plan($plan, $participantsLimit, $entryFeeCredits = 0, array $pricing = null, $prizeFunding = 'FIXED', array $prizeOptions = [])
{
    if (!arenagamer_plan_is_active($plan)) {
        return 'Plano ativo necessário para criar torneios.';
    }

    $funding = strtoupper(trim((string) $prizeFunding));
    $prizeOptions = array_merge([
        'prizeType'    => 'MANUAL',
        'prizeFunding' => $funding,
        'prizePool'    => 0,
    ], $prizeOptions);

    if ($funding === 'ENTRY_FEES') {
        $prizeOptions['prizeFunding'] = 'ENTRY_FEES';
        $prizeOptions['prizePool'] = 0;
    }

    $requiresEntryFeeBenefit = $funding === 'ENTRY_FEES' || (float) $entryFeeCredits > 0;

    if ($requiresEntryFeeBenefit && !arenagamer_tournament_allows_entry_fee($plan, $participantsLimit, $pricing, $prizeOptions)) {
        if ($funding === 'ENTRY_FEES') {
            return 'Seu plano não permite prêmio por arrecadação. ' . arenagamer_entry_fee_unlock_message();
        }

        return 'Taxa de inscrição só é permitida com plano que inclui esse benefício ou após gastar '
            . arenagamer_format_credits(arenagamer_entry_fee_min_creation_cost())
            . ' na criação além da isenção do plano.';
    }

    return null;
}

/**
 * Valida saldo de créditos para criação de torneio (além dos direitos do plano).
 *
 * @return string|null Mensagem de erro ou null se válido
 */
function arenagamer_validate_tournament_wallet_balance($plan, $participantsLimit, $walletResponse, array $pricing = null, array $prizeOptions = [])
{
    $cost = arenagamer_calculate_tournament_creation_cost_with_plan($plan, $participantsLimit, $pricing);
    $cost += arenagamer_tournament_prize_pool_creation_cost(
        $prizeOptions['prizeType'] ?? 'MANUAL',
        $prizeOptions['prizeFunding'] ?? 'FIXED',
        $prizeOptions['prizePool'] ?? 0
    );

    if ($cost <= 0) {
        return null;
    }

    $wallet = arenagamer_api_data($walletResponse);
    $available = (float) ($wallet['availableBalance'] ?? 0);

    if ($available >= $cost) {
        return null;
    }

    return 'Saldo insuficiente. Necessário '
        . arenagamer_format_credits($cost)
        . ', disponível '
        . arenagamer_format_credits($available)
        . '. Compre créditos para continuar.';
}

/**
 * Participantes inclusos na configuração global de preços.
 */
function arenagamer_pricing_included_participants(array $pricing = null)
{
    if (!is_array($pricing)) {
        $pricing = arenagamer_tournament_pricing_local();
    }

    return max(2, (int) ($pricing['includedParticipants'] ?? 8));
}

/**
 * Limite mensal de torneios inclusos do plano foi atingido.
 */
function arenagamer_plan_tournament_limit_reached($plan)
{
    $freeTournaments = arenagamer_plan_free_tournaments_per_month($plan);

    if ($freeTournaments <= 0) {
        return false;
    }

    return arenagamer_plan_tournaments_used_this_month($plan) >= $freeTournaments;
}

/**
 * Participantes inclusos no plano antes de cobrar avulso (0 = usar config global).
 * Benefício do plano só vale enquanto o limite mensal de torneios não foi atingido.
 */
function arenagamer_plan_included_participants($plan, array $pricing = null, $applyPlanBenefits = null)
{
    if ($applyPlanBenefits === null) {
        $applyPlanBenefits = !arenagamer_plan_tournament_limit_reached($plan);
    }

    if (!$applyPlanBenefits) {
        return arenagamer_pricing_included_participants($pricing);
    }

    $planIncluded = arenagamer_plan_free_max_participants($plan);

    if ($planIncluded > 0) {
        return $planIncluded;
    }

    return arenagamer_pricing_included_participants($pricing);
}

/**
 * Calcula créditos para criação considerando benefícios do plano.
 */
function arenagamer_calculate_tournament_creation_cost_with_plan($plan, $participantsLimit, array $pricing = null, $waiveBasePrice = false)
{
    if (!is_array($pricing)) {
        $pricing = arenagamer_tournament_pricing_local();
    }

    $used = arenagamer_plan_tournaments_used_this_month($plan);
    $freeTournaments = arenagamer_plan_free_tournaments_per_month($plan);
    $isFreeTournamentSlot = $freeTournaments > 0 && $used < $freeTournaments;
    $tournamentLimitReached = arenagamer_plan_tournament_limit_reached($plan);

    $includedForBilling = arenagamer_plan_included_participants($plan, $pricing, !$tournamentLimitReached);
    $base = (float) ($pricing['baseTournamentPrice'] ?? 0);
    $extraPrice = (float) ($pricing['extraParticipantPrice'] ?? 0);
    $limit = max(0, (int) $participantsLimit);

    $cost = ($waiveBasePrice || $isFreeTournamentSlot) ? 0.0 : $base;

    if ($limit > $includedForBilling) {
        $cost += ($limit - $includedForBilling) * $extraPrice;
    }

    return $cost;
}

/**
 * Detalhamento do custo de criação (preço padrão vs benefícios do plano).
 */
function arenagamer_tournament_creation_cost_breakdown($plan, $participantsLimit, array $pricing = null)
{
    if (!is_array($pricing)) {
        $pricing = arenagamer_tournament_pricing_local();
    }

    $limit = max(0, (int) $participantsLimit);
    $tournamentsUsed = arenagamer_plan_tournaments_used_this_month($plan);
    $freeTournamentsMonth = arenagamer_plan_free_tournaments_per_month($plan);
    $hasFreeTournamentSlot = $freeTournamentsMonth > 0 && $tournamentsUsed < $freeTournamentsMonth;
    $tournamentLimitReached = arenagamer_plan_tournament_limit_reached($plan);
    $planParticipantBenefitsActive = !$tournamentLimitReached;
    $includedForBilling = arenagamer_plan_included_participants($plan, $pricing, $planParticipantBenefitsActive);
    $standardIncludedParticipants = arenagamer_pricing_included_participants($pricing);
    $basePrice = (float) ($pricing['baseTournamentPrice'] ?? 0);
    $extraPrice = (float) ($pricing['extraParticipantPrice'] ?? 0);
    $extraCount = max(0, $limit - $includedForBilling);
    $extraTotal = $extraCount * $extraPrice;
    $normalSubtotal = $basePrice + $extraTotal;
    $participantsOverPlanLimit = $limit > $includedForBilling;

    $baseWaived = $hasFreeTournamentSlot;
    $planDiscount = $baseWaived ? $basePrice : 0.0;
    $total = max(0, $normalSubtotal - $planDiscount);

    return [
        'participantsLimit'         => $limit,
        'includedParticipants'      => $includedForBilling,
        'extraParticipants'         => $extraCount,
        'basePrice'                 => $basePrice,
        'extraPrice'                => $extraPrice,
        'extraTotal'                => $extraTotal,
        'normalSubtotal'            => $normalSubtotal,
        'tournamentsUsed'           => $tournamentsUsed,
        'freeTournamentsMonth'      => $freeTournamentsMonth,
        'maxTournamentsMonth'       => arenagamer_plan_max_tournaments_per_month($plan),
        'hasFreeTournamentSlot'     => $hasFreeTournamentSlot,
        'tournamentLimitReached'    => $tournamentLimitReached,
        'planParticipantBenefitsActive' => $planParticipantBenefitsActive,
        'standardIncludedParticipants' => $standardIncludedParticipants,
        'participantsOverPlanLimit' => $participantsOverPlanLimit,
        'baseWaived'                => $baseWaived,
        'planDiscount'              => $planDiscount,
        'total'                     => $total,
        'freeTournamentsRemaining'  => $hasFreeTournamentSlot
            ? max(0, $freeTournamentsMonth - $tournamentsUsed)
            : 0,
    ];
}

/**
 * Configuração local de preços de torneio (fallback quando API indisponível)
 */
function arenagamer_tournament_pricing_local()
{
    return [
        'baseTournamentPrice'   => (float) get_option('arenagamer_tournament_base_price'),
        'extraParticipantPrice' => (float) get_option('arenagamer_extra_participant_price'),
        'includedParticipants'  => (int) get_option('arenagamer_included_participants_default'),
    ];
}

/**
 * Calcula créditos para criação de torneio conforme limite de participantes
 */
function arenagamer_calculate_tournament_creation_cost($participantsLimit, array $pricing = null)
{
    if (!is_array($pricing)) {
        $pricing = arenagamer_tournament_pricing_local();
    }

    $included = max(2, (int) ($pricing['includedParticipants'] ?? 8));
    $base = (float) ($pricing['baseTournamentPrice'] ?? 0);
    $extraPrice = (float) ($pricing['extraParticipantPrice'] ?? 0);
    $limit = max(0, (int) $participantsLimit);

    if ($limit <= $included) {
        return $base;
    }

    return $base + (($limit - $included) * $extraPrice);
}

/**
 * Monta payload de preços de torneio para a API
 */
function arenagamer_tournament_pricing_payload_from_input($input)
{
    return [
        'baseTournamentPrice'   => (float) str_replace(',', '.', (string) $input->post('tournament_base_price')),
        'extraParticipantPrice' => (float) str_replace(',', '.', (string) $input->post('extra_participant_price')),
        'includedParticipants'  => (int) $input->post('included_participants'),
    ];
}

/**
 * Valida preços de torneio antes de enviar à API
 *
 * @return string|null Mensagem de erro ou null se válido
 */
function arenagamer_validate_tournament_pricing_payload(array $payload)
{
    $included = (int) ($payload['includedParticipants'] ?? 0);
    $base = (float) ($payload['baseTournamentPrice'] ?? 0);
    $extra = (float) ($payload['extraParticipantPrice'] ?? 0);

    if ($included < 2) {
        return 'Participantes incluídos deve ser no mínimo 2.';
    }

    if ($base < 0) {
        return 'Valor do torneio padrão não pode ser negativo.';
    }

    if ($extra < 0) {
        return 'Valor por participante avulso não pode ser negativo.';
    }

    return null;
}

/**
 * Extrai itens de uma resposta paginada da API
 */
function arenagamer_paginated_content($response)
{
    if (!is_array($response)) {
        return [];
    }

    $data = $response['data'] ?? null;

    if (is_array($data) && isset($data['content']) && is_array($data['content'])) {
        return $data['content'];
    }

    if (is_array($data) && !empty($data) && array_values($data) === $data) {
        return $data;
    }

    return [];
}

/**
 * Metadados de paginação da ApiResponse
 */
function arenagamer_pagination_meta($response)
{
    $data = is_array($response) ? ($response['data'] ?? []) : [];

    // Spring Data PageSerializationMode.VIA_DTO (PagedModel: metadados em data.page)
    if (isset($data['page']) && is_array($data['page'])) {
        $pageMeta = $data['page'];
        $size = (int) ($pageMeta['size'] ?? arenagamer_admin_page_size());
        $number = (int) ($pageMeta['number'] ?? 0);
        $totalElements = (int) ($pageMeta['totalElements'] ?? count(arenagamer_paginated_content($response)));
        $totalPages = (int) ($pageMeta['totalPages'] ?? 1);
    } else {
        $size = (int) ($data['size'] ?? arenagamer_admin_page_size());
        $number = (int) ($data['number'] ?? 0);
        $totalElements = (int) ($data['totalElements'] ?? count(arenagamer_paginated_content($response)));
        $totalPages = (int) ($data['totalPages'] ?? 1);
    }

    $from = $totalElements > 0 ? ($number * $size) + 1 : 0;
    $to = min(($number + 1) * $size, $totalElements);

    return [
        'totalPages'    => $totalPages,
        'number'        => $number,
        'size'          => $size > 0 ? $size : arenagamer_admin_page_size(),
        'totalElements' => $totalElements,
        'from'          => $from,
        'to'            => $to,
    ];
}

/**
 * Total de elementos em resposta paginada (formato legado ou PagedModel VIA_DTO).
 */
function arenagamer_pagination_total_elements($response)
{
    return (int) (arenagamer_pagination_meta($response)['totalElements'] ?? 0);
}

/**
 * Total de páginas em resposta paginada (formato legado ou PagedModel VIA_DTO).
 */
function arenagamer_pagination_total_pages($response)
{
    return (int) (arenagamer_pagination_meta($response)['totalPages'] ?? 1);
}

/**
 * Tamanho de página padrão do Perfex (Setup → Settings → General).
 */
function arenagamer_admin_page_size()
{
    $limit = (int) get_option('tables_pagination_limit');

    return $limit > 0 ? $limit : 25;
}

/**
 * Página atual na URL admin (1-based).
 */
function arenagamer_admin_current_page()
{
    $CI = &get_instance();
    $page = (int) $CI->input->get('page');

    return $page > 0 ? $page : 1;
}

/**
 * Índice de página para a API Spring (0-based).
 */
function arenagamer_spring_page_index()
{
    return arenagamer_admin_current_page() - 1;
}

/**
 * Paginação admin no estilo Perfex (CodeIgniter + Bootstrap).
 */
function arenagamer_render_admin_pagination($paginationMeta, $route, array $queryParams = [])
{
    if (!is_array($paginationMeta)) {
        return '';
    }

    $totalElements = (int) ($paginationMeta['totalElements'] ?? 0);
    $perPage = (int) ($paginationMeta['size'] ?? arenagamer_admin_page_size());

    if ($totalElements <= $perPage) {
        return '';
    }

    $CI = &get_instance();
    $CI->load->library('pagination');

    $config = [
        'base_url'             => admin_url($route),
        'total_rows'           => $totalElements,
        'per_page'             => $perPage,
        'use_page_numbers'     => true,
        'page_query_string'    => true,
        'query_string_segment' => 'page',
        'reuse_query_string'   => true,
        'cur_page'             => arenagamer_admin_current_page(),
        'full_tag_open'        => '<ul class="pagination">',
        'full_tag_close'       => '</ul>',
        'first_tag_open'       => '<li>',
        'first_tag_close'      => '</li>',
        'last_tag_open'        => '<li>',
        'last_tag_close'       => '</li>',
        'next_tag_open'        => '<li>',
        'next_tag_close'       => '</li>',
        'prev_tag_open'        => '<li>',
        'prev_tag_close'       => '</li>',
        'cur_tag_open'         => '<li class="active"><a href="#">',
        'cur_tag_close'        => '</a></li>',
        'num_tag_open'         => '<li>',
        'num_tag_close'        => '</li>',
        'first_link'           => '&laquo;',
        'last_link'            => '&raquo;',
        'next_link'            => '&rsaquo;',
        'prev_link'            => '&lsaquo;',
    ];

    $CI->pagination->initialize($config);

    return $CI->pagination->create_links();
}

/**
 * Texto informativo de paginação (estilo DataTables do Perfex).
 */
function arenagamer_pagination_info_text($paginationMeta)
{
    if (!is_array($paginationMeta)) {
        return '';
    }

    $total = (int) ($paginationMeta['totalElements'] ?? 0);
    $from = (int) ($paginationMeta['from'] ?? 0);
    $to = (int) ($paginationMeta['to'] ?? 0);

    if ($total <= 0) {
        return _l('dt_info_empty');
    }

    $text = _l('dt_info');

    return str_replace(['_START_', '_END_', '_TOTAL_'], [$from, $to, $total], $text);
}

/**
 * Badge de vínculo cliente/plataforma do torneio
 */
function arenagamer_tournament_client_badge($tournament, $clientName = null)
{
    $clientUserId = $tournament['clientUserId'] ?? null;

    if ($clientUserId === null || $clientUserId === '') {
        return '<span class="label label-default">Plataforma</span>';
    }

    $label = $clientName !== null && $clientName !== ''
        ? htmlspecialchars($clientName)
        : 'Cliente #' . (int) $clientUserId;

    return '<span class="label label-info">' . $label . '</span>';
}

/**
 * Badge do tipo de proprietário do torneio
 */
function arenagamer_owner_type_badge($ownerType)
{
    $badges = [
        'STAFF'   => '<span class="label label-primary">Staff</span>',
        'CONTACT' => '<span class="label label-info">Contato</span>',
    ];

    return $badges[$ownerType] ?? '<span class="label label-default">' . htmlspecialchars((string) $ownerType) . '</span>';
}

/**
 * Converte datetime-local ou ISO para UTC com sufixo Z (Instant na API Java)
 */
function arenagamer_iso8601_z($value)
{
    if ($value === null || trim((string) $value) === '') {
        return null;
    }

    try {
        $date = new DateTime(trim((string) $value));
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format('Y-m-d\TH:i:s.v\Z');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Lê inteiro opcional do POST (null quando ausente ou vazio)
 */
function arenagamer_post_optional_int($input, $field)
{
    $value = $input->post($field);
    if ($value === null || $value === false || $value === '') {
        return null;
    }

    return (int) $value;
}

/**
 * Monta payload de torneio para a API
 *
 * @param CI_Input $input
 * @param array|null $authUser Usuário autenticado na API (userType, clientUserId)
 */
function arenagamer_tournament_payload_from_input($input, $authUser = null, $presets = null, array $options = [])
{
    $isUpdate = !empty($options['is_update']);
    $presetId = $input->post('preset_id');
    $startDate = trim((string) $input->post('start_date'));
    $registrationDeadline = trim((string) $input->post('registration_deadline'));
    $resolvedPresetId = ($presetId === '' || $presetId === null) ? null : (int) $presetId;
    $presetGameImageLocked = false;
    $selectedPreset = null;

    if ($resolvedPresetId) {
        $selectedPreset = arenagamer_find_preset_by_id($presets, $resolvedPresetId);
        if (!$selectedPreset && !empty($options['api'])) {
            $selectedPreset = arenagamer_fetch_preset_by_id($options['api'], $resolvedPresetId);
        }
        if ($selectedPreset && arenagamer_preset_blocks_game_image_upload($selectedPreset)) {
            $presetGameImageLocked = true;
        }
    }

    $payload = [
        'name'                 => trim((string) $input->post('name')),
        'description'          => trim((string) $input->post('description')),
        'type'                 => (string) $input->post('type'),
        'format'               => (string) $input->post('format'),
        'visibility'           => (string) $input->post('visibility'),
        'minParticipants'      => max(2, (int) ($input->post('min_participants') ?: 2)),
        'presetId'             => $resolvedPresetId,
        'entryFeeCredits'      => (float) str_replace(',', '.', (string) $input->post('entry_fee_credits')),
        'feePercentage'        => (float) str_replace(',', '.', (string) $input->post('fee_percentage')),
        'prizeType'            => strtoupper(trim((string) ($input->post('prize_type') ?: 'MANUAL'))),
        'prizeFunding'         => strtoupper(trim((string) ($input->post('prize_funding') ?: 'FIXED'))),
        'prizePool'            => (float) str_replace(',', '.', (string) $input->post('prize_pool')),
        'groupsCount'          => arenagamer_post_optional_int($input, 'groups_count'),
        'teamsPerGroup'        => arenagamer_post_optional_int($input, 'teams_per_group'),
        'advancePerGroup'      => arenagamer_post_optional_int($input, 'advance_per_group'),
        'bestOf'               => arenagamer_post_optional_int($input, 'best_of'),
        'rules'                => trim((string) $input->post('rules')),
        'tiebreakerRules'      => trim((string) $input->post('tiebreaker_rules')),
        'startDate'            => arenagamer_iso8601_z($startDate),
        'registrationDeadline' => arenagamer_iso8601_z($registrationDeadline),
        'registrationOpensAt'  => arenagamer_iso8601_z(trim((string) $input->post('registration_opens_at'))),
        'expectedEndDate'      => arenagamer_iso8601_z(trim((string) $input->post('expected_end_date'))),
        'gameImageUrl'         => $presetGameImageLocked
            ? trim((string) $selectedPreset['gameImageUrl'])
            : trim((string) $input->post('game_image_url')),
        'coverImageUrl'        => trim((string) $input->post('cover_image_url')),
        'logoImageUrl'         => trim((string) $input->post('logo_image_url')),
        'youtubeUrl'             => trim((string) $input->post('youtube_url')),
        'twitchUrl'              => trim((string) $input->post('twitch_url')),
    ];

    if (!$isUpdate) {
        $payload['participantsLimit'] = max(2, (int) $input->post('participants_limit'));
    }

    if (($payload['format'] ?? '') === 'TEAM') {
        $minPlayers = (int) $input->post('min_players_per_team');
        $maxPlayers = (int) $input->post('max_players_per_team');
        if ($minPlayers > 0) {
            $payload['minPlayersPerTeam'] = $minPlayers;
        }
        if ($maxPlayers > 0) {
            $payload['maxPlayersPerTeam'] = $maxPlayers;
        }
    }

    if (($payload['type'] ?? '') !== 'GROUP_STAGE') {
        $payload['groupsCount'] = null;
        $payload['teamsPerGroup'] = null;
        $payload['advancePerGroup'] = null;
    }

    $prizeError = arenagamer_validate_tournament_prize_settings($payload);
    if ($prizeError !== null) {
        $payload['_prize_error'] = $prizeError;
    }

    $skipUploadFields = $presetGameImageLocked ? ['game_image_file'] : [];
    $uploadError = arenagamer_apply_tournament_image_uploads($payload, $input, $skipUploadFields);
    if ($uploadError !== null) {
        $payload['_upload_error'] = $uploadError;
    }

    $dateError = arenagamer_validate_tournament_dates($payload);
    if ($dateError !== null) {
        $payload['_date_error'] = $dateError;
    }

    if (!$resolvedPresetId) {
        $payload['gameName'] = trim((string) $input->post('game_name'));
        if ($payload['gameName'] === '') {
            $payload['_game_name_error'] = 'Informe o nome do jogo ou selecione um jogo predefinido.';
        }
    } elseif (!$selectedPreset) {
        $payload['_preset_error'] = 'Jogo predefinido selecionado não encontrado ou indisponível.';
    }

    $userType = is_array($authUser) ? ($authUser['userType'] ?? '') : '';
    if ($userType === 'STAFF') {
        $clientUserId = $input->post('client_user_id');
        if ($clientUserId !== null && $clientUserId !== '') {
            $payload['clientUserId'] = (int) $clientUserId;
        }
    }

    return $payload;
}

/**
 * Repopula o formulário de torneio após erro de validação/API
 */
function arenagamer_tournament_from_post($input, $authUser = null, $presets = null, array $options = [])
{
    return arenagamer_tournament_payload_from_input($input, $authUser, $presets, $options);
}

/**
 * Monta payload de time para a API.
 */
function arenagamer_team_payload_from_input($input)
{
    $payload = [
        'name'           => trim((string) $input->post('name')),
        'tag'            => trim((string) $input->post('tag')),
        'logoUrl'        => trim((string) $input->post('logo_url')),
        'youtubeUrl'     => trim((string) $input->post('youtube_url')),
        'instagramUrl'   => trim((string) $input->post('instagram_url')),
        'twitchUrl'      => trim((string) $input->post('twitch_url')),
        'otherSocialUrl' => trim((string) $input->post('other_social_url')),
        'rulesChange'    => trim((string) $input->post('rules_change')),
    ];

    $uploadError = arenagamer_apply_team_logo_upload($payload);
    if ($uploadError !== null) {
        $payload['_upload_error'] = $uploadError;
    }

    return $payload;
}

function arenagamer_team_from_post($input)
{
    return arenagamer_team_payload_from_input($input);
}

function arenagamer_team_upload_dir()
{
    $rel = 'modules/arenagamer/uploads/teams/';
    $abs = FCPATH . $rel;

    if (!is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }

    return [
        'abs' => $abs,
        'rel' => $rel,
        'url' => rtrim(site_url($rel), '/') . '/',
    ];
}

function arenagamer_handle_team_logo_upload($fieldName = 'logo_file')
{
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }

    $CI = &get_instance();
    $CI->load->library('upload');
    $dir = arenagamer_team_upload_dir();
    $CI->upload->initialize([
        'upload_path'   => $dir['abs'],
        'allowed_types' => 'gif|jpg|jpeg|png|webp',
        'max_size'      => 4096,
        'encrypt_name'  => true,
    ]);

    if (!$CI->upload->do_upload($fieldName)) {
        return ['error' => strip_tags($CI->upload->display_errors('', ''))];
    }

    $data = $CI->upload->data();

    return $dir['url'] . $data['file_name'];
}

function arenagamer_apply_team_logo_upload(array &$payload, $fieldName = 'logo_file')
{
    $result = arenagamer_handle_team_logo_upload($fieldName);
    if (is_array($result) && isset($result['error'])) {
        return $result['error'];
    }
    if (is_string($result) && $result !== '') {
        $payload['logoUrl'] = $result;
    }

    return null;
}

function arenagamer_profile_upload_dir()
{
    $rel = 'modules/arenagamer/uploads/profiles/';
    $abs = FCPATH . $rel;

    if (!is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }

    return [
        'abs' => $abs,
        'rel' => $rel,
        'url' => rtrim(site_url($rel), '/') . '/',
    ];
}

function arenagamer_handle_profile_avatar_upload($fieldName = 'avatar_file')
{
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }

    $CI = &get_instance();
    $CI->load->library('upload');
    $dir = arenagamer_profile_upload_dir();
    $CI->upload->initialize([
        'upload_path'   => $dir['abs'],
        'allowed_types' => 'gif|jpg|jpeg|png|webp',
        'max_size'      => 4096,
        'encrypt_name'  => true,
    ]);

    if (!$CI->upload->do_upload($fieldName)) {
        return ['error' => strip_tags($CI->upload->display_errors('', ''))];
    }

    $data = $CI->upload->data();

    return $dir['url'] . $data['file_name'];
}

/**
 * Monta payload de perfil para a API.
 */
function arenagamer_profile_payload_from_input($input)
{
    $payload = [
        'firstName'    => trim((string) $input->post('first_name')),
        'lastName'     => trim((string) $input->post('last_name')),
        'phoneNumber'  => trim((string) $input->post('phone_number')),
        'avatarUrl'    => trim((string) $input->post('avatar_url')),
        'instagramUrl' => trim((string) $input->post('instagram_url')),
        'youtubeUrl'   => trim((string) $input->post('youtube_url')),
        'twitchUrl'    => trim((string) $input->post('twitch_url')),
    ];

    $avatarUpload = arenagamer_handle_profile_avatar_upload();
    if (is_array($avatarUpload) && isset($avatarUpload['error'])) {
        $payload['_upload_error'] = $avatarUpload['error'];
    } elseif (is_string($avatarUpload) && $avatarUpload !== '') {
        $payload['avatarUrl'] = $avatarUpload;
    }

    return $payload;
}

function arenagamer_team_settings_local()
{
    return [
        'maxOwnedTeamsPerClient'        => (int) get_option('arenagamer_max_owned_teams') ?: 1,
        'maxParticipatedTeamsPerClient' => (int) get_option('arenagamer_max_participated_teams') ?: 3,
        'maxTournamentsPerTeam'          => get_option('arenagamer_max_tournaments_per_team') !== ''
            ? (int) get_option('arenagamer_max_tournaments_per_team')
            : null,
        'maxTournamentsPerClient'        => get_option('arenagamer_max_tournaments_per_client') !== ''
            ? (int) get_option('arenagamer_max_tournaments_per_client')
            : null,
        'unlimitedTournamentsPerTeam'  => get_option('arenagamer_max_tournaments_per_team') === '',
        'unlimitedTournamentsPerClient' => get_option('arenagamer_max_tournaments_per_client') === '',
    ];
}

/**
 * Monta linhas de quem pode gerenciar o torneio (principal + delegados).
 */
function arenagamer_tournament_permission_rows(array $primaryContacts, array $managers)
{
    $rows = [];

    foreach ($primaryContacts as $contact) {
        $contactId = (int) ($contact['id'] ?? 0);
        if ($contactId <= 0) {
            continue;
        }

        $rows[] = [
            'contactId'    => $contactId,
            'contactName'  => trim(($contact['firstname'] ?? $contact['firstName'] ?? '') . ' ' . ($contact['lastname'] ?? $contact['lastName'] ?? '')),
            'contactEmail' => (string) ($contact['email'] ?? ''),
            'grantedAt'    => null,
            'type'         => 'primary',
            'canRevoke'    => false,
        ];
    }

    foreach ($managers as $manager) {
        if (!is_array($manager)) {
            continue;
        }

        $rows[] = [
            'contactId'    => (int) ($manager['contactId'] ?? 0),
            'contactName'  => (string) ($manager['contactName'] ?? ''),
            'contactEmail' => (string) ($manager['contactEmail'] ?? ''),
            'grantedAt'    => $manager['grantedAt'] ?? null,
            'type'         => 'delegated',
            'canRevoke'    => true,
        ];
    }

    return $rows;
}

/**
 * Converte datetime-local para ISO 8601
 */
function arenagamer_datetime_local_value($isoDate)
{
    if (empty($isoDate)) {
        return '';
    }

    try {
        $date = new DateTime((string) $isoDate);

        return $date->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        $timestamp = strtotime((string) $isoDate);

        return $timestamp !== false ? date('Y-m-d\TH:i', $timestamp) : '';
    }
}

/**
 * Valor padrão da abertura prevista das inscrições (amanhã).
 */
function arenagamer_tournament_default_registration_opens_at()
{
    $date = new DateTime('now');
    $date->modify('+1 day');

    return $date->format('Y-m-d\TH:i');
}

/**
 * Valor do campo registration_opens_at no formulário.
 */
function arenagamer_tournament_registration_opens_form_value(array $tournament, $isCreate = false)
{
    $value = arenagamer_datetime_local_value($tournament['registrationOpensAt'] ?? '');

    if ($value === '' && $isCreate) {
        return arenagamer_tournament_default_registration_opens_at();
    }

    return $value;
}

/**
 * Converte string de data (ISO/local) em DateTime ou null.
 */
function arenagamer_parse_form_datetime($value)
{
    if ($value === null || trim((string) $value) === '') {
        return null;
    }

    try {
        return new DateTime(trim((string) $value));
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Valida a sequência lógica das datas do torneio.
 *
 * @return string|null Mensagem de erro ou null
 */
function arenagamer_validate_tournament_dates(array $payload)
{
    $opens = arenagamer_parse_form_datetime($payload['registrationOpensAt'] ?? null);
    if ($opens === null) {
        return 'A data prevista de abertura das inscrições é obrigatória.';
    }

    $deadline = arenagamer_parse_form_datetime($payload['registrationDeadline'] ?? null);
    if ($deadline !== null && $deadline < $opens) {
        return 'O prazo máximo de inscrição não pode ser anterior à abertura prevista das inscrições.';
    }

    $start = arenagamer_parse_form_datetime($payload['startDate'] ?? null);
    if ($start !== null && $deadline !== null && $start < $deadline) {
        return 'A data de início não pode ser anterior ao prazo máximo de inscrição.';
    }

    $end = arenagamer_parse_form_datetime($payload['expectedEndDate'] ?? null);
    if ($end !== null && $start !== null && $end <= $start) {
        return 'A data prevista de término deve ser posterior à data de início.';
    }

    return null;
}

/**
 * Extrai a primeira mensagem de erro do payload montado do formulário.
 */
function arenagamer_tournament_payload_error(array $payload)
{
    if (!empty($payload['_upload_error'])) {
        return (string) $payload['_upload_error'];
    }

    if (!empty($payload['_date_error'])) {
        return (string) $payload['_date_error'];
    }

    if (!empty($payload['_preset_error'])) {
        return (string) $payload['_preset_error'];
    }

    if (!empty($payload['_game_name_error'])) {
        return (string) $payload['_game_name_error'];
    }

    if (!empty($payload['_prize_error'])) {
        return (string) $payload['_prize_error'];
    }

    return null;
}

/**
 * Remove chaves internas de erro do payload antes de enviar à API.
 */
function arenagamer_tournament_sanitize_payload(array $payload)
{
    unset($payload['_upload_error'], $payload['_date_error'], $payload['_preset_error'], $payload['_game_name_error'], $payload['_prize_error']);

    return $payload;
}

/**
 * Diretório de upload de imagens de torneio no módulo.
 *
 * @return array{abs:string,rel:string,url:string}
 */
function arenagamer_tournament_upload_dir()
{
    $rel = 'modules/arenagamer/uploads/tournaments/';
    $abs = FCPATH . $rel;

    if (!is_dir($abs)) {
        @mkdir($abs, 0755, true);
    }

    return [
        'abs' => $abs,
        'rel' => $rel,
        'url' => rtrim(site_url($rel), '/') . '/',
    ];
}

/**
 * Faz upload de imagem de torneio (campo multipart).
 *
 * @return string|null URL pública ou null se nenhum arquivo
 */
function arenagamer_handle_tournament_image_upload($fieldName)
{
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }

    $CI = &get_instance();
    $CI->load->library('upload');
    $dir = arenagamer_tournament_upload_dir();
    $CI->upload->initialize([
        'upload_path'   => $dir['abs'],
        'allowed_types' => 'gif|jpg|jpeg|png|webp',
        'max_size'      => 4096,
        'encrypt_name'  => true,
    ]);

    if (!$CI->upload->do_upload($fieldName)) {
        return ['error' => strip_tags($CI->upload->display_errors('', ''))];
    }

    $data = $CI->upload->data();

    return $dir['url'] . $data['file_name'];
}

/**
 * Substitui URLs de imagem no payload quando há upload.
 *
 * @return string|null Mensagem de erro ou null
 */
function arenagamer_apply_tournament_image_uploads(array &$payload, $input = null, array $skipFields = [])
{
        foreach ([
            'game_image_file'  => 'gameImageUrl',
            'cover_image_file' => 'coverImageUrl',
            'logo_image_file'  => 'logoImageUrl',
        ] as $field => $key) {
        if (in_array($field, $skipFields, true)) {
            continue;
        }
        $result = arenagamer_handle_tournament_image_upload($field);
        if (is_array($result) && isset($result['error'])) {
            return $result['error'];
        }
        if (is_string($result) && $result !== '') {
            $payload[$key] = $result;
        }
    }

    return null;
}

/**
 * Filtros da listagem pública de torneios (query param filter).
 *
 * @return array<string,string>
 */
function arenagamer_public_tournament_filters()
{
    return [
        ''                   => 'Em destaque',
        'REGISTRATION_OPEN'  => 'Inscrições abertas',
        'UPCOMING'           => 'Previstos (com data de abertura de inscrições)',
        'IN_PROGRESS'        => 'Em andamento',
        'FINISHED'           => 'Finalizados',
        'CANCELLED'          => 'Cancelados',
    ];
}

/**
 * Rótulo amigável do filtro público.
 */
function arenagamer_public_tournament_filter_label($filter)
{
    $filters = arenagamer_public_tournament_filters();
    $key = strtoupper(trim((string) $filter));

    return $filters[$key] ?? $filters[''];
}

/**
 * Mapa de presets para autofill no formulário de torneio (id => campos).
 */
function arenagamer_presets_autofill_map(array $presets)
{
    $map = [];

    foreach ($presets as $preset) {
        if (!is_array($preset)) {
            continue;
        }

        $id = (int) ($preset['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $map[$id] = [
            'gameName'           => (string) ($preset['gameName'] ?? ''),
            'platform'           => (string) ($preset['platform'] ?? ''),
            'teamSize'           => (int) ($preset['teamSize'] ?? 1),
            'minPlayersPerTeam'  => (int) ($preset['minPlayersPerTeam'] ?? 1),
            'maxPlayersPerTeam'  => (int) ($preset['maxPlayersPerTeam'] ?? 1),
            'iconUrl'            => (string) ($preset['iconUrl'] ?? ''),
            'gameImageUrl'       => (string) ($preset['gameImageUrl'] ?? ($preset['iconUrl'] ?? '')),
            'presetGameImageUrl' => trim((string) ($preset['gameImageUrl'] ?? '')),
            'locksGameImage'     => trim((string) ($preset['gameImageUrl'] ?? '')) !== '',
            'rulesTemplate'      => (string) ($preset['rulesTemplate'] ?? ''),
        ];
    }

    return $map;
}

/**
 * Filtra presets pelo termo digitado (prefixo no nome ou plataforma).
 */
function arenagamer_filter_presets_by_search(array $items, $query)
{
    $needle = mb_strtolower(trim((string) $query));
    if (mb_strlen($needle) < 3) {
        return [];
    }

    return array_values(array_filter($items, function ($preset) use ($needle) {
        if (!is_array($preset)) {
            return false;
        }

        $name = mb_strtolower(trim((string) ($preset['gameName'] ?? '')));
        $platform = mb_strtolower(trim((string) ($preset['platform'] ?? '')));

        return ($name !== '' && mb_strpos($name, $needle) === 0)
            || ($platform !== '' && mb_strpos($platform, $needle) === 0);
    }));
}

/**
 * Lê o termo de busca de presets (query string).
 */
function arenagamer_preset_search_term_from_input($input)
{
    $term = trim((string) $input->get('term'));
    if ($term === '') {
        $term = trim((string) $input->get('q'));
    }
    if ($term === '' && isset($_GET['term'])) {
        $term = trim((string) $_GET['term']);
    }
    if ($term === '' && isset($_GET['q'])) {
        $term = trim((string) $_GET['q']);
    }

    return $term;
}

/**
 * Busca um preset na API quando não está na lista em memória.
 *
 * @param ArenaGamer_api|null $api
 */
function arenagamer_fetch_preset_by_id($api, $id)
{
    $id = (int) $id;
    if ($id <= 0 || $api === null) {
        return null;
    }

    if (!$api->is_contact_context()) {
        $response = $api->get_preset($id);
        if (!arenagamer_api_is_success($response)) {
            return null;
        }

        $preset = arenagamer_api_data($response);
        return is_array($preset) ? $preset : null;
    }

    $response = $api->search_presets(null);
    return arenagamer_find_preset_by_id($response, $id);
}

/**
 * Busca preset pelo ID na lista retornada pela API.
 */
function arenagamer_find_preset_by_id($presets, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }

    foreach (arenagamer_api_data($presets, []) as $preset) {
        if (!is_array($preset)) {
            continue;
        }
        if ((int) ($preset['id'] ?? 0) === $id) {
            return $preset;
        }
    }

    return null;
}

/**
 * Preset com imagem de jogo própria bloqueia upload/URL manual no torneio.
 */
function arenagamer_preset_blocks_game_image_upload(array $preset)
{
    return trim((string) ($preset['gameImageUrl'] ?? '')) !== '';
}

/**
 * Indica se o formulário deve bloquear a imagem do jogo com base no preset selecionado.
 */
function arenagamer_tournament_game_image_locked_by_preset(array $tournament, $presets = null)
{
    $presetId = (int) ($tournament['presetId'] ?? 0);
    if ($presetId <= 0) {
        return false;
    }

    $preset = arenagamer_find_preset_by_id($presets, $presetId);

    return is_array($preset) && arenagamer_preset_blocks_game_image_upload($preset);
}

/**
 * Nome do jogo do torneio (definido pelo preset selecionado).
 */
function arenagamer_tournament_game_name(array $tournament, $default = '—')
{
    $presetName = trim((string) ($tournament['presetName'] ?? ''));
    if ($presetName !== '') {
        return $presetName;
    }

    $gameName = trim((string) ($tournament['gameName'] ?? ''));
    if ($gameName !== '') {
        return $gameName;
    }

    return $default;
}

/**
 * URL de imagem do jogo (torneio ou ícone do preset).
 */
function arenagamer_tournament_game_image_url(array $tournament)
{
    if (!empty($tournament['gameImageUrl'])) {
        return (string) $tournament['gameImageUrl'];
    }

    if (!empty($tournament['presetIconUrl'])) {
        return (string) $tournament['presetIconUrl'];
    }

    return '';
}

/**
 * Email do contato logado no portal Perfex
 */
function arenagamer_get_logged_contact_email()
{
    if (!function_exists('is_client_logged_in') || !is_client_logged_in() || !get_contact_user_id()) {
        return '';
    }

    $CI = &get_instance();
    $CI->load->model('clients_model');
    $contact = $CI->clients_model->get_contact(get_contact_user_id());

    return $contact && !empty($contact->email) ? (string) $contact->email : '';
}

/**
 * Tenta autenticar o contato na API com a senha informada
 */
function arenagamer_relink_contact_api($password, $api = null)
{
    $email = arenagamer_get_logged_contact_email();
    if ($email === '' || $password === null || $password === '') {
        return false;
    }

    $api = $api ?: arenagamer_contact_api();

    if (!$api->login($email, $password, false)) {
        return false;
    }

    arenagamer_finalize_contact_login($email, $password, $api);

    return true;
}

/**
 * Instancia a API no contexto do contato logado (área do cliente)
 */
function arenagamer_contact_api()
{
    $CI = &get_instance();
    $CI->load->library('arenagamer/ArenaGamer_api', ['context' => 'contact'], 'arenagamer_contact_api');

    return $CI->arenagamer_contact_api;
}

/**
 * Garante autenticação JWT do contato na API ArenaGamer
 */
function arenagamer_ensure_contact_api($api = null)
{
    $api = $api ?: arenagamer_contact_api();

    return $api->ensure_contact_session();
}

/**
 * Persiste credenciais básicas e cache do catálogo após login na API.
 */
function arenagamer_finalize_contact_login($email, $password, $api = null)
{
    $CI = &get_instance();

    $CI->session->unset_userdata([
        'arenagamer_pending_sync_email',
        'arenagamer_pending_sync_password',
    ]);
    $CI->session->set_userdata([
        'arenagamer_contact_basic_email'    => $email,
        'arenagamer_contact_basic_password' => $password,
    ]);

    $api = $api ?: arenagamer_contact_api();

    try {
        $api->cache_public_catalog($email, $password);
    } catch (Throwable $e) {
        if (function_exists('log_activity')) {
            log_activity('ArenaGamer catalog cache error: ' . $e->getMessage());
        }
    }
}

/**
 * Presets em cache (catálogo público) para contatos
 */
function arenagamer_contact_get_cached_presets()
{
    if (!function_exists('get_contact_user_id') || !get_contact_user_id()) {
        return [];
    }

    $raw = get_contact_meta(get_contact_user_id(), 'arenagamer_presets_cache');
    if (!$raw) {
        return [];
    }

    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Sincroniza login do contato Perfex com a API ArenaGamer.
 * Não bloqueia o login do Perfex — a autenticação na API ocorre na primeira visita ao ArenaGamer.
 */
function arenagamer_sync_contact_login()
{
    try {
        if (!is_client_logged_in()) {
            return false;
        }

        $CI = &get_instance();
        $CI->load->helper('arenagamer/arenagamer');

        $email = $CI->input->post('email');
        $password = $CI->input->post('password', false);

        if (empty($email) || $password === null || $password === '') {
            return false;
        }

        $CI->session->set_userdata([
            'arenagamer_pending_sync_email'    => $email,
            'arenagamer_pending_sync_password' => $password,
        ]);

        return true;
    } catch (Throwable $e) {
        if (function_exists('log_activity')) {
            log_activity('ArenaGamer login sync error: ' . $e->getMessage());
        }

        return false;
    }
}

/**
 * Executa autenticação pendente após login no portal (não bloqueia o redirect).
 */
function arenagamer_run_pending_contact_sync($api = null)
{
    try {
        $CI = &get_instance();
        $email = $CI->session->userdata('arenagamer_pending_sync_email');
        $password = $CI->session->userdata('arenagamer_pending_sync_password');

        if (empty($email) || $password === null || $password === '') {
            return false;
        }

        $api = $api ?: arenagamer_contact_api();

        if (!$api->login($email, $password, false)) {
            return false;
        }

        arenagamer_finalize_contact_login($email, $password, $api);

        return true;
    } catch (Throwable $e) {
        if (function_exists('log_activity')) {
            log_activity('ArenaGamer pending sync error: ' . $e->getMessage());
        }

        return false;
    }
}

/**
 * Limpa tokens ArenaGamer do contato ao sair
 */
function arenagamer_sync_contact_logout()
{
    try {
        $CI = &get_instance();
        $CI->load->helper('arenagamer/arenagamer');

        if (function_exists('get_contact_user_id') && get_contact_user_id()) {
            $api = arenagamer_contact_api();
            $api->logout();
        }

        $CI->session->unset_userdata([
            'arenagamer_contact_token',
            'arenagamer_contact_refresh_token',
            'arenagamer_contact_auth_user',
            'arenagamer_contact_basic_email',
            'arenagamer_contact_basic_password',
            'arenagamer_pending_sync_email',
            'arenagamer_pending_sync_password',
        ]);
    } catch (Throwable $e) {
        if (function_exists('log_activity')) {
            log_activity('ArenaGamer logout sync error: ' . $e->getMessage());
        }
    }
}

/**
 * URL base da área do cliente ArenaGamer — /perfex/arenagamer
 */
function arenagamer_client_url($path = '')
{
    $path = ltrim((string) $path, '/');

    return site_url('arenagamer' . ($path !== '' ? '/' . $path : ''));
}

/**
 * URL base do painel staff — /perfex/admin/arenagamer
 */
function arenagamer_admin_url($path = '')
{
    $path = ltrim((string) $path, '/');

    return admin_url('arenagamer' . ($path !== '' ? '/' . $path : ''));
}

/**
 * Extrai mensagem legível de um registro de auditoria (campo newValue).
 */
function arenagamer_audit_display_message($audit)
{
    if (!is_array($audit)) {
        return '';
    }

    $raw = $audit['newValue'] ?? '';
    if ($raw === '' || $raw === null) {
        return $audit['oldValue'] ?? '';
    }

    if (is_array($raw)) {
        return $raw['message'] ?? json_encode($raw, JSON_UNESCAPED_UNICODE);
    }

    $decoded = json_decode((string) $raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        if (!empty($decoded['message'])) {
            return (string) $decoded['message'];
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    return (string) $raw;
}

/**
 * Registra auditoria na API ArenaGamer (staff).
 */
function arenagamer_audit_log($action, $entityType, $entityId = null, $oldValue = null, $newValue = null)
{
    $CI = &get_instance();
    $CI->load->library('arenagamer/ArenaGamer_api');

    return $CI->arenagamer_api->create_audit_log($action, $entityType, $entityId, $oldValue, $newValue);
}

/**
 * Atalho para registrar mensagem de auditoria.
 */
function arenagamer_audit_message($action, $entityType, $entityId, $message)
{
    $payload = json_encode(['message' => (string) $message], JSON_UNESCAPED_UNICODE);

    return arenagamer_audit_log($action, $entityType, $entityId, null, $payload);
}
