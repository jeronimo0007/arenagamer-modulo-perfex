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
        'ROUND_ROBIN'              => 'Pontos corridos',
        'ROUND_ROBIN_ELIMINATION'  => 'Pontos corridos + eliminatória',
        'GROUP_STAGE'              => 'Fase de grupos',
        'SWISS'              => 'Sistema suíço',
    ];
}

function arenagamer_tournament_type_options()
{
    return array_keys(arenagamer_tournament_type_labels());
}

/**
 * Lista padrão de sistemas de torneio (todos habilitados).
 *
 * @return array<int,array{type:string,label:string,enabled:bool,displayOrder:int}>
 */
function arenagamer_tournament_systems_default()
{
    $systems = [];
    $order = 1;

    foreach (arenagamer_tournament_type_labels() as $type => $label) {
        $systems[] = [
            'type'         => $type,
            'label'        => $label,
            'enabled'      => true,
            'displayOrder' => $order++,
        ];
    }

    return $systems;
}

/**
 * Normaliza a resposta da API de sistemas de torneio.
 *
 * @return array<int,array<string,mixed>>
 */
function arenagamer_tournament_systems_normalize($data)
{
    if (!is_array($data)) {
        return [];
    }

    if (isset($data['systems']) && is_array($data['systems'])) {
        $data = $data['systems'];
    }

    if (isset($data['type']) && !array_key_exists(0, $data)) {
        $data = [$data];
    }

    $defaults = arenagamer_tournament_type_labels();
    $systems = [];

    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }

        $type = strtoupper(trim((string) ($item['type'] ?? '')));
        if ($type === '') {
            continue;
        }

        $systems[] = [
            'type'         => $type,
            'label'        => trim((string) ($item['label'] ?? '')) ?: ($defaults[$type] ?? $type),
            'enabled'      => array_key_exists('enabled', $item) ? (bool) $item['enabled'] : true,
            'displayOrder' => (int) ($item['displayOrder'] ?? 0),
        ];
    }

    usort($systems, function ($a, $b) {
        $order = ((int) ($a['displayOrder'] ?? 0)) <=> ((int) ($b['displayOrder'] ?? 0));
        if ($order !== 0) {
            return $order;
        }

        return strcmp((string) ($a['type'] ?? ''), (string) ($b['type'] ?? ''));
    });

    return $systems;
}

/**
 * Carrega sistemas de torneio da API com fallback local.
 *
 * @param ArenaGamer_api $api
 * @return array<int,array<string,mixed>>
 */
function arenagamer_resolve_tournament_systems($api, $publicOnly = false)
{
    if (!is_object($api)) {
        return arenagamer_tournament_systems_default();
    }

    $method = $publicOnly ? 'get_public_tournament_systems' : 'get_tournament_systems';
    if (!method_exists($api, $method)) {
        return arenagamer_tournament_systems_default();
    }

    $response = $api->$method();
    $systems = arenagamer_tournament_systems_normalize(arenagamer_api_data($response));

    if (empty($systems)) {
        return arenagamer_tournament_systems_default();
    }

    if ($publicOnly) {
        $systems = array_values(array_filter($systems, function ($system) {
            return !empty($system['enabled']);
        }));
    }

    return !empty($systems) ? $systems : arenagamer_tournament_systems_default();
}

/**
 * Rótulos mesclando API e catálogo local.
 *
 * @return array<string,string>
 */
function arenagamer_tournament_type_labels_from_systems(array $systems)
{
    $defaults = arenagamer_tournament_type_labels();
    $labels = [];

    foreach ($systems as $system) {
        if (!is_array($system)) {
            continue;
        }

        $type = strtoupper(trim((string) ($system['type'] ?? '')));
        if ($type === '') {
            continue;
        }

        $labels[$type] = trim((string) ($system['label'] ?? '')) ?: ($defaults[$type] ?? $type);
    }

    foreach ($defaults as $type => $label) {
        if (!isset($labels[$type])) {
            $labels[$type] = $label;
        }
    }

    return $labels;
}

/**
 * Tipos disponíveis no formulário (habilitados + tipo atual em edição).
 *
 * @return array<int,string>
 */
function arenagamer_tournament_type_options_for_form(array $systems, $currentType = null)
{
    $currentType = strtoupper(trim((string) $currentType));
    $options = [];

    foreach ($systems as $system) {
        if (!is_array($system)) {
            continue;
        }

        $type = strtoupper(trim((string) ($system['type'] ?? '')));
        if ($type === '') {
            continue;
        }

        if (!empty($system['enabled']) || ($currentType !== '' && $type === $currentType)) {
            $options[] = $type;
        }
    }

    if (empty($options)) {
        return arenagamer_tournament_type_options();
    }

    return array_values(array_unique($options));
}

/**
 * Valida se o tipo pode ser usado em criação ou alteração de torneio.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_system_type($type, array $systems, $existingType = null)
{
    $type = strtoupper(trim((string) $type));
    $existingType = strtoupper(trim((string) $existingType));

    if ($type === '') {
        return 'Selecione um modo de torneio.';
    }

    if ($existingType !== '' && $type === $existingType) {
        return null;
    }

    foreach ($systems as $system) {
        if (!is_array($system)) {
            continue;
        }

        if (strtoupper(trim((string) ($system['type'] ?? ''))) === $type) {
            if (!empty($system['enabled'])) {
                return null;
            }

            return 'O modo de torneio selecionado está desabilitado e não pode ser usado em novos torneios.';
        }
    }

    $labels = arenagamer_tournament_type_labels();

    if (!isset($labels[$type])) {
        return 'Modo de torneio inválido.';
    }

    return 'O modo de torneio selecionado está desabilitado e não pode ser usado em novos torneios.';
}

/**
 * Monta payload PUT para atualização em lote dos sistemas.
 *
 * @return array{systems:array<int,array{type:string,enabled:bool}>}
 */
function arenagamer_tournament_systems_payload_from_input($input, array $knownSystems = [])
{
    if (empty($knownSystems)) {
        $knownSystems = arenagamer_tournament_systems_default();
    }

    $systems = [];

    foreach ($knownSystems as $system) {
        if (!is_array($system)) {
            continue;
        }

        $type = strtoupper(trim((string) ($system['type'] ?? '')));
        if ($type === '') {
            continue;
        }

        $systems[] = [
            'type'    => $type,
            'enabled' => $input->post('tournament_system_' . $type) === '1',
        ];
    }

    return ['systems' => $systems];
}

/**
 * Preenche dados do formulário de torneio com sistemas habilitados.
 *
 * @param array<string,mixed> $data
 * @return array<int,array<string,mixed>>
 */
function arenagamer_assign_tournament_form_systems(array &$data, $api, $publicOnly = true)
{
    $systems = arenagamer_resolve_tournament_systems($api, $publicOnly);
    $currentType = (string) ($data['tournament']['type'] ?? '');

    $data['tournament_systems'] = $systems;
    $data['tournament_type_options'] = arenagamer_tournament_type_options_for_form($systems, $currentType);
    $data['tournament_type_labels'] = arenagamer_tournament_type_labels_from_systems($systems);

    return $systems;
}

function arenagamer_tournament_type_label($type)
{
    $labels = arenagamer_tournament_type_labels();

    return $labels[$type] ?? htmlspecialchars((string) $type);
}

/**
 * Descrição de cada modo de torneio (texto explicativo para a UI).
 *
 * @return array<string,array{icon:string,description:string,tip:?string}>
 */
function arenagamer_tournament_type_how_it_works_catalog()
{
    return [
        'SINGLE_ELIMINATION' => [
            'icon'        => 'fa-sitemap',
            'description' => 'Eliminação simples — sistema de chaves em que cada derrota elimina o participante. '
                . 'Os confrontos seguem rodada a rodada até a final, com disputa de 3º lugar.',
            'tip' => 'Limite de participantes: 4, 8, 16, 32…',
        ],
        'DOUBLE_ELIMINATION' => [
            'icon'        => 'fa-code-fork',
            'description' => 'Eliminação dupla — chave superior (mata-mata principal) e repescagem (segunda chance). '
                . 'Quem perde na superior cai para a repescagem; quem perde na repescagem sai. '
                . 'Campeão de cada chave disputa a grande final. '
                . 'Registre os resultados e use Avançar rodada alternando repescagem e superior '
                . '(a chave superior só avança da 2ª rodada em diante depois da repescagem anterior).',
            'tip' => 'Limite de participantes: 4, 8, 16, 32…',
        ],
        'ROUND_ROBIN' => [
            'icon'        => 'fa-refresh',
            'description' => 'Pontos corridos — todos jogam contra todos em uma única fase. '
                . 'A classificação é por pontos (empate vale 1 ponto para cada lado) e o campeão é o 1º da tabela.',
            'tip' => null,
        ],
        'ROUND_ROBIN_ELIMINATION' => [
            'icon'        => 'fa-list-ol',
            'description' => 'Pontos corridos + eliminatória — começa com todos contra todos (empate = 1 ponto) '
                . 'e depois os melhores colocados seguem para um mata-mata com disputa de 3º lugar e final.',
            'tip' => 'Classificados para o mata-mata: 2, 4, 8, 16…',
        ],
        'GROUP_STAGE' => [
            'icon'        => 'fa-th-large',
            'description' => 'Fase de grupos — os inscritos são divididos em grupos de 4 que jogam entre si. '
                . 'Os 2 melhores de cada grupo avançam para chave eliminatória; vagas extras podem ser preenchidas pelos melhores 3ºs.',
            'tip' => 'O total de inscritos deve ser múltiplo de 4 (ex.: 4, 8, 12, 16 inscritos).',
        ],
        'SWISS' => [
            'icon'        => 'fa-random',
            'description' => 'Sistema suíço — várias rodadas com emparelhamento por desempenho. '
                . 'Vitória vale 3 pontos, derrota 0, sem empate. Rodada 1 por seed; depois, quem tem pontuação parecida se enfrenta. '
                . 'Com número ímpar, o último colocado recebe bye (+3 pts).',
            'tip' => 'O número de rodadas é calculado automaticamente conforme a quantidade de participantes.',
        ],
    ];
}

/**
 * Dados do painel "Como funciona" para um tipo de torneio.
 *
 * @return array{title:string,icon:string,description:string,tip:?string,extra:?string}
 */
function arenagamer_tournament_type_how_it_works($type, array $context = [])
{
    $type = strtoupper(trim((string) $type));
    $catalog = arenagamer_tournament_type_how_it_works_catalog();
    $entry = $catalog[$type] ?? null;

    if ($entry === null) {
        return [
            'title'       => arenagamer_tournament_type_label($type),
            'icon'        => 'fa-info-circle',
            'description' => 'Selecione um modo de torneio para ver como funciona.',
            'tip'         => null,
            'extra'       => null,
        ];
    }

    $result = [
        'title'       => arenagamer_tournament_type_label($type),
        'icon'        => (string) ($entry['icon'] ?? 'fa-info-circle'),
        'description' => (string) ($entry['description'] ?? ''),
        'tip'         => $entry['tip'] ?? null,
        'extra'       => null,
    ];

    $participantsLimit = (int) ($context['participantsLimit'] ?? 0);
    $isTeam = ($context['format'] ?? '') === 'TEAM';

    if ($type === 'SWISS' && $participantsLimit >= 2) {
        $result['extra'] = arenagamer_swiss_rounds_count_label($participantsLimit, $isTeam);
    }

    if ($type === 'DOUBLE_ELIMINATION' && $participantsLimit >= 2) {
        $result['extra'] = arenagamer_double_elimination_rounds_summary($participantsLimit, $isTeam);
    }

    if ($type === 'ROUND_ROBIN_ELIMINATION') {
        $advance = (int) ($context['advanceToKnockout'] ?? 0);
        if ($advance >= 2 && $participantsLimit > $advance) {
            $result['extra'] = $advance . ' classificam para o mata-mata (de ' . $participantsLimit . ' inscritos).';
        }
    }

    if ($type === 'GROUP_STAGE') {
        $unit = $isTeam ? 'equipes' : 'participantes';
        $extra = arenagamer_group_stage_teams_per_group() . ' ' . $unit . ' por grupo, '
            . arenagamer_group_stage_advance_per_group() . ' classificam por grupo para o mata-mata.';
        $groupsCount = arenagamer_group_stage_groups_count_from_participants($participantsLimit);
        if ($groupsCount > 0) {
            $extra .= ' → ' . arenagamer_group_stage_groups_count_label($groupsCount);
        }
        $result['extra'] = $extra;
    }

    return $result;
}

/**
 * Painel "Como funciona" para o detalhe do torneio.
 *
 * @return array{title:string,icon:string,description:string,tip:?string,extra:?string}|null
 */
function arenagamer_tournament_how_it_works_for_detail(array $tournament)
{
    $type = $tournament['type'] ?? '';
    if ($type === '') {
        return null;
    }

    $context = [
        'format'            => $tournament['format'] ?? 'SOLO',
        'participantsLimit' => (int) ($tournament['participantsLimit'] ?? 0),
        'advanceToKnockout' => (int) ($tournament['advanceToKnockout'] ?? 0),
    ];

    $participantCount = (int) ($tournament['participantCount'] ?? 0);
    if ($participantCount >= 2) {
        $context['participantsLimit'] = $participantCount;
    }

    $info = arenagamer_tournament_type_how_it_works($type, $context);

    if ($type === 'SWISS' && $context['participantsLimit'] >= 2) {
        $info['extra'] = arenagamer_swiss_rounds_count_label(
            $context['participantsLimit'],
            ($tournament['format'] ?? '') === 'TEAM'
        );
    }

    if ($type === 'DOUBLE_ELIMINATION' && $context['participantsLimit'] >= 2) {
        $info['extra'] = arenagamer_double_elimination_rounds_summary(
            $context['participantsLimit'],
            ($tournament['format'] ?? '') === 'TEAM'
        );
    }

    return $info;
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
 * Torneio cancelado ou concluído não pode ser editado.
 */
function arenagamer_tournament_is_editable($tournament)
{
    if (!is_array($tournament)) {
        return false;
    }

    $status = strtoupper(trim((string) ($tournament['status'] ?? '')));

    return !in_array($status, ['CANCELLED', 'COMPLETED'], true);
}

function arenagamer_tournament_not_editable_message()
{
    return 'Torneios concluídos ou cancelados não podem ser editados.';
}

/**
 * Quantidade de inscritos aprovados no torneio (a partir dos dados da API).
 */
function arenagamer_tournament_participant_count($tournament)
{
    if (!is_array($tournament)) {
        return 0;
    }

    return max(0, (int) ($tournament['participantCount'] ?? 0));
}

/**
 * Torneio já possui inscritos aprovados.
 */
function arenagamer_tournament_has_participants($tournament)
{
    return arenagamer_tournament_participant_count($tournament) > 0;
}

/**
 * Formato/modo do torneio não pode ser alterado após inscrições.
 */
function arenagamer_tournament_format_fields_locked($tournament)
{
    return arenagamer_tournament_has_participants($tournament);
}

function arenagamer_tournament_format_locked_message()
{
    return 'Formato e modo do torneio não podem ser alterados após haver inscrições.';
}

/**
 * Campos de formato/modo bloqueados na atualização quando já há inscritos.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_format_locked_fields(array $existing, array $payload)
{
    if (!arenagamer_tournament_format_fields_locked($existing)) {
        return null;
    }

    $checks = [
        'type'   => strtoupper(trim((string) ($existing['type'] ?? ''))),
        'format' => strtoupper(trim((string) ($existing['format'] ?? ''))),
    ];

    foreach ($checks as $field => $current) {
        if (!array_key_exists($field, $payload)) {
            continue;
        }
        $next = strtoupper(trim((string) $payload[$field]));
        if ($next !== '' && $next !== $current) {
            return arenagamer_tournament_format_locked_message();
        }
    }

    $intChecks = [
        'minPlayersPerTeam' => $existing['minPlayersPerTeam'] ?? null,
        'maxPlayersPerTeam' => $existing['maxPlayersPerTeam'] ?? null,
        'advanceToKnockout' => $existing['advanceToKnockout'] ?? null,
    ];

    foreach ($intChecks as $field => $current) {
        if (!array_key_exists($field, $payload)) {
            continue;
        }
        $next = $payload[$field];
        if ($next === null || $next === '') {
            continue;
        }
        if ((int) $next !== (int) $current) {
            return arenagamer_tournament_format_locked_message();
        }
    }

    return null;
}

/**
 * Remove campos de formato/modo do payload de atualização quando bloqueados.
 */
function arenagamer_strip_locked_tournament_format_fields(array &$payload, array $existing)
{
    if (!arenagamer_tournament_format_fields_locked($existing)) {
        return;
    }

    foreach (['type', 'format', 'minPlayersPerTeam', 'maxPlayersPerTeam', 'advanceToKnockout'] as $field) {
        unset($payload[$field]);
    }
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
 * Tipos de rodada considerados mata-mata (eliminatória).
 */
function arenagamer_knockout_round_types()
{
    return [
        'FINAL',
        'SEMIFINAL',
        'QUARTERFINAL',
        'ROUND_OF_16',
        'ROUND_OF_32',
        'OITAVAS',
        'SEMI_FINAL',
        'KNOCKOUT',
        'THIRD_PLACE',
        'FOURTH_PLACE',
        'FIFTH_PLACE',
        'GRAND_FINAL',
        'WINNERS_BRACKET',
        'LOSERS_BRACKET',
    ];
}

/**
 * Indica se a partida pertence ao mata-mata.
 */
function arenagamer_is_knockout_match(array $match)
{
    return in_array(arenagamer_match_round_type($match), arenagamer_knockout_round_types(), true);
}

/**
 * Rótulo legível da fase eliminatória.
 */
function arenagamer_knockout_round_label($roundType, $phaseLabel = null, $roundNumber = null)
{
    if ($phaseLabel !== null && trim((string) $phaseLabel) !== '') {
        return trim((string) $phaseLabel);
    }

    $key = strtoupper(trim((string) $roundType));
    $roundNumber = (int) $roundNumber;

    if ($key === 'LOSERS_BRACKET' && $roundNumber > 0) {
        return 'Repescagem — Rodada ' . $roundNumber;
    }

    if ($key === 'WINNERS_BRACKET' && $roundNumber > 0) {
        return 'Chave superior — Rodada ' . $roundNumber;
    }

    $labels = [
        'ROUND_OF_32'  => '32 avos de final',
        'ROUND_OF_16'  => 'Oitavas de final',
        'OITAVAS'      => 'Oitavas de final',
        'QUARTERFINAL' => 'Quartas de final',
        'SEMIFINAL'    => 'Semifinal',
        'SEMI_FINAL'   => 'Semifinal',
        'FINAL'        => 'Final',
        'THIRD_PLACE'  => 'Disputa de 3º lugar',
        'FOURTH_PLACE' => 'Disputa de 4º lugar',
        'FIFTH_PLACE'  => 'Disputa de 5º lugar',
        'GRAND_FINAL'  => 'Grande final',
        'KNOCKOUT'     => 'Mata-mata',
        'WINNERS_BRACKET' => 'Chave superior',
        'LOSERS_BRACKET'  => 'Repescagem',
    ];

    return $labels[$key] ?? ($key !== '' ? ucfirst(strtolower(str_replace('_', ' ', $key))) : 'Rodada');
}

/**
 * Faixa visual da posição na classificação dos grupos.
 *
 * @return string|null gold|silver|bronze|green
 */
function arenagamer_position_tier($position)
{
    switch ((int) $position) {
        case 1:
            return 'gold';
        case 2:
            return 'silver';
        case 3:
            return 'bronze';
        case 4:
        case 5:
            return 'green';
        default:
            return null;
    }
}

/**
 * Classe CSS da borda lateral conforme a colocação.
 */
function arenagamer_participant_tier_class($tier)
{
    $tier = trim((string) $tier);
    if ($tier === '') {
        return '';
    }

    return ' ag-participant--tier-' . preg_replace('/[^a-z0-9-]/', '', $tier);
}

/**
 * Indica se o torneio deve exibir tabela de classificação (em andamento ou concluído).
 */
function arenagamer_tournament_shows_classification(array $tournament)
{
    $status = strtoupper(trim((string) ($tournament['status'] ?? '')));

    return in_array($status, ['IN_PROGRESS', 'COMPLETED'], true);
}

/**
 * Título da seção de inscritos/classificação conforme o status do torneio.
 */
function arenagamer_tournament_standings_section_title(array $tournament)
{
    return arenagamer_tournament_shows_classification($tournament) ? 'Classificação' : 'Participantes';
}

/**
 * Mapa participantId → posição/tier (classificação da fase de grupos).
 *
 * @return array<int,array{position:int,groupNumber:int,tier:?string,rankLabel:string,note:string}>
 */
function arenagamer_build_participant_standing_map($standings)
{
    $map = [];

    if (!is_array($standings)) {
        return $map;
    }

    foreach ($standings as $row) {
        if (!is_array($row) || empty($row['participantId'])) {
            continue;
        }

        $position = (int) ($row['position'] ?? 0);
        $map[(int) $row['participantId']] = [
            'position'    => $position,
            'groupNumber' => (int) ($row['groupNumber'] ?? 0),
            'tier'        => arenagamer_position_tier($position),
            'rankLabel'   => $position > 0 ? $position . 'º' : '',
            'note'        => trim((string) ($row['note'] ?? '')),
        ];
    }

    return $map;
}

/**
 * Lista de melhores colocados na fase de grupos (1º ao Nº), para legenda da chave.
 *
 * @return array<int,array{participantId:int,participantName:string,position:int,groupNumber:int,tier:?string,rankLabel:string}>
 */
function arenagamer_build_top_standings_list($standings, $maxPosition = 5)
{
    $items = [];

    if (!is_array($standings)) {
        return $items;
    }

    foreach ($standings as $row) {
        if (!is_array($row)) {
            continue;
        }

        $position = (int) ($row['position'] ?? 0);
        if ($position < 1 || $position > (int) $maxPosition) {
            continue;
        }

        $name = trim((string) ($row['participantName'] ?? ''));
        if ($name === '' && !empty($row['participantId'])) {
            $name = 'Participante #' . (int) $row['participantId'];
        }

        $items[] = [
            'participantId'   => (int) ($row['participantId'] ?? 0),
            'participantName' => $name,
            'position'        => $position,
            'groupNumber'     => (int) ($row['groupNumber'] ?? 0),
            'tier'            => arenagamer_position_tier($position),
            'rankLabel'       => $position . 'º',
        ];
    }

    usort($items, function ($a, $b) {
        $byPosition = $a['position'] <=> $b['position'];
        if ($byPosition !== 0) {
            return $byPosition;
        }

        $byGroup = $a['groupNumber'] <=> $b['groupNumber'];
        if ($byGroup !== 0) {
            return $byGroup;
        }

        return strcasecmp($a['participantName'], $b['participantName']);
    });

    return $items;
}

/**
 * Top 3 final do torneio — exibido somente após status COMPLETED.
 *
 * @return array<int,array{participantId:int,participantName:string,position:int,tier:?string,rankLabel:string}>
 */
function arenagamer_build_final_top3_list($standings, $tournamentStatus = '', $matches = [])
{
    if (strtoupper(trim((string) $tournamentStatus)) !== 'COMPLETED') {
        return [];
    }

    $byPosition = [];

    if (is_array($matches)) {
        foreach ($matches as $match) {
            if (!is_array($match) || !arenagamer_is_knockout_match($match)) {
                continue;
            }
            if (!arenagamer_is_knockout_placement_match($match)) {
                continue;
            }

            foreach (['home', 'away'] as $side) {
                $position = arenagamer_knockout_side_tournament_placement($match, $side);
                if ($position < 1 || $position > 3) {
                    continue;
                }

                $id = (int) ($match[$side === 'home' ? 'homeParticipantId' : 'awayParticipantId'] ?? 0);
                $name = trim((string) ($match[$side === 'home' ? 'homeParticipantName' : 'awayParticipantName'] ?? ''));
                if ($name === '' && $id > 0) {
                    $name = 'Participante #' . $id;
                }
                if ($name === '') {
                    continue;
                }

                $byPosition[$position] = [
                    'participantId'   => $id,
                    'participantName' => $name,
                    'position'        => $position,
                    'tier'            => arenagamer_position_tier($position),
                    'rankLabel'       => $position . 'º',
                ];
            }
        }
    }

    if (empty($byPosition) && is_array($standings)) {
        foreach ($standings as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ((int) ($row['groupNumber'] ?? 0) > 0) {
                continue;
            }

            $position = (int) ($row['position'] ?? 0);
            if ($position < 1 || $position > 3 || isset($byPosition[$position])) {
                continue;
            }

            $name = trim((string) ($row['participantName'] ?? ''));
            if ($name === '' && !empty($row['participantId'])) {
                $name = 'Participante #' . (int) $row['participantId'];
            }
            if ($name === '') {
                continue;
            }

            $byPosition[$position] = [
                'participantId'   => (int) ($row['participantId'] ?? 0),
                'participantName' => $name,
                'position'        => $position,
                'tier'            => arenagamer_position_tier($position),
                'rankLabel'       => $position . 'º',
            ];
        }
    }

    if (empty($byPosition)) {
        return [];
    }

    ksort($byPosition);

    return array_values($byPosition);
}

/**
 * Posição na fase de grupos informada pela API na partida (lado home/away).
 */
function arenagamer_match_side_group_position(array $match, $side)
{
    $prefix = $side === 'home' ? 'home' : 'away';
    $keys = [
        $prefix . 'GroupPosition',
        $prefix . 'GroupRank',
        $prefix . 'OriginPosition',
        $prefix . 'Position',
    ];

    foreach ($keys as $key) {
        if (!isset($match[$key]) || $match[$key] === '' || $match[$key] === null) {
            continue;
        }
        $position = (int) $match[$key];
        if ($position > 0 && $position <= 8) {
            return $position;
        }
    }

    return 0;
}

/**
 * Posições finais disputadas numa partida eliminatória (ex.: Final → 1º e 2º).
 *
 * @return array{0:int,1:int}|null [posição vencedor, posição perdedor] ou null se não for disputa de colocação
 */
function arenagamer_knockout_placement_slots(array $match)
{
    $roundType = strtoupper(trim((string) ($match['roundType'] ?? '')));
    $phase = mb_strtolower(trim((string) ($match['phaseLabel'] ?? '')));

    switch ($roundType) {
        case 'FINAL':
            return [1, 2];
        case 'THIRD_PLACE':
            return [3, 4];
        case 'FOURTH_PLACE':
            return [4, 5];
        case 'FIFTH_PLACE':
            return [5, 6];
    }

    if ($phase !== '') {
        if (preg_match('/\b(3[\sº°o]|terceir[\w]*)\s*(lugar|coloca)/u', $phase)) {
            return [3, 4];
        }
        if (preg_match('/\b(4[\sº°o]|quart[\w]*)\s*(lugar|coloca)/u', $phase)) {
            return [4, 5];
        }
        if (preg_match('/\b(5[\sº°o]|quint[\w]*)\s*(lugar|coloca)/u', $phase)) {
            return [5, 6];
        }
        if (preg_match('/\b(1[\sº°o]|primeir[\w]*)\s*(lugar|coloca)/u', $phase)
            && strpos($phase, 'semi') === false
            && strpos($phase, '11') === false
            && strpos($phase, '21') === false) {
            return [1, 2];
        }
    }

    return null;
}

/**
 * Indica se a partida eliminatória define colocação final (1º–5º).
 */
function arenagamer_is_knockout_placement_match(array $match)
{
    $slots = arenagamer_knockout_placement_slots($match);
    if ($slots === null) {
        return false;
    }

    return $slots[0] >= 1 && $slots[0] <= 5;
}

/**
 * Colocação final do participante na chave eliminatória (somente após resultado).
 */
function arenagamer_knockout_side_tournament_placement(array $match, $side)
{
    $slots = arenagamer_knockout_placement_slots($match);
    if ($slots === null || $slots[0] > 5) {
        return 0;
    }

    if (!in_array($match['status'] ?? '', ['COMPLETED', 'WALKOVER'], true)) {
        return 0;
    }

    $isHome = $side === 'home';
    $winnerId = $match['winnerParticipantId'] ?? null;
    $participantId = $match[$isHome ? 'homeParticipantId' : 'awayParticipantId'] ?? null;

    if ($winnerId === null || $winnerId === '' || $participantId === null || $participantId === '') {
        return 0;
    }

    $isWinner = (string) $winnerId === (string) $participantId;
    $position = $isWinner ? (int) $slots[0] : (int) $slots[1];

    return ($position >= 1 && $position <= 5) ? $position : 0;
}

/**
 * Badge HTML de posição (canto superior direito dos quadradinhos da chave).
 */
function arenagamer_render_participant_rank_badge(array $participant)
{
    $label = trim((string) ($participant['rankLabel'] ?? ''));
    $tier = trim((string) ($participant['tier'] ?? ''));

    if ($label === '' || $tier === '') {
        return '';
    }

    $tierClass = htmlspecialchars(preg_replace('/[^a-z0-9-]/', '', $tier));
    $title = trim((string) ($participant['rankTitle'] ?? ''));
    if ($title === '') {
        $title = 'Colocação na fase de grupos';
    }

    return '<span class="ag-rank-badge ag-rank-badge--' . $tierClass . '" title="' . htmlspecialchars($title) . '">'
        . htmlspecialchars($label) . '</span>';
}

/**
 * Dados de exibição de um participante na chave.
 *
 * @param array $options context: 'group' (padrão) | 'knockout'
 */
function arenagamer_bracket_participant_row(array $match, $side, array $standingMap = [], array $options = [])
{
    $isHome = $side === 'home';
    $id = $match[$isHome ? 'homeParticipantId' : 'awayParticipantId'] ?? null;
    $name = trim((string) ($match[$isHome ? 'homeParticipantName' : 'awayParticipantName'] ?? ''));
    $score = $match[$isHome ? 'homeScore' : 'awayScore'] ?? null;
    $winnerId = $match['winnerParticipantId'] ?? null;
    $empty = ($name === '' && ($id === null || $id === ''));
    $context = (string) ($options['context'] ?? 'group');

    if ($name === '' && !$empty) {
        $name = 'Participante #' . (int) $id;
    } elseif ($empty) {
        $name = 'A definir';
    }

    $isWinner = !$empty && !empty($winnerId) && (string) $winnerId === (string) $id;
    $isFinished = in_array($match['status'] ?? '', ['COMPLETED', 'WALKOVER'], true);

    $rankTitle = 'Colocação na fase de grupos';
    $position = 0;
    $isPlacementMatch = arenagamer_is_knockout_placement_match($match);
    $useKnockoutPlacement = $context === 'knockout' || $isPlacementMatch;

    if ($useKnockoutPlacement) {
        $position = arenagamer_knockout_side_tournament_placement($match, $side);
        $rankTitle = 'Colocação final no torneio';
    } elseif ($isFinished) {
        $position = arenagamer_match_side_group_position($match, $side);
    }

    $tier = arenagamer_position_tier($position);
    $rankLabel = ($position >= 1 && $position <= 5) ? ($position . 'º') : '';

    return [
        'label'     => $name,
        'empty'     => $empty,
        'winner'    => $isWinner,
        'score'     => ($score !== null && $score !== '') ? (int) $score : null,
        'finished'  => $isFinished,
        'position'  => $position,
        'tier'      => $tier,
        'rankLabel' => $rankLabel,
        'rankTitle' => $rankTitle,
        'tierClass' => arenagamer_participant_tier_class($tier),
    ];
}

/**
 * Monta colunas da chave eliminatória (oitavas → quartas → semi → final).
 *
 * @return array{rounds:array<int,array{label:string,roundType:string,roundNumber:int,matches:array}>,third_place:?array}
 */
function arenagamer_build_knockout_bracket($matches)
{
    $result = [
        'rounds'      => [],
        'third_place' => null,
    ];

    if (!is_array($matches) || empty($matches)) {
        return $result;
    }

    $byRound = [];

    foreach ($matches as $match) {
        if (!is_array($match) || !arenagamer_is_knockout_match($match)) {
            continue;
        }

        if (($match['roundType'] ?? '') === 'THIRD_PLACE') {
            $result['third_place'] = $match;
            continue;
        }

        $roundNumber = (int) ($match['roundNumber'] ?? 0);
        if ($roundNumber < 1) {
            $roundNumber = 999;
        }

        $byRound[$roundNumber][] = $match;
    }

    if (empty($byRound)) {
        return $result;
    }

    ksort($byRound);

    foreach ($byRound as $roundNumber => $roundMatches) {
        usort($roundMatches, function ($a, $b) {
            return ((int) ($a['matchNumber'] ?? 0)) <=> ((int) ($b['matchNumber'] ?? 0));
        });

        $first = $roundMatches[0];
        $roundType = (string) ($first['roundType'] ?? '');

        $result['rounds'][] = [
            'label'       => arenagamer_knockout_round_label($roundType, $first['phaseLabel'] ?? null, $roundNumber),
            'roundType'   => $roundType,
            'roundNumber' => (int) $roundNumber,
            'matches'     => $roundMatches,
        ];
    }

    return $result;
}

/**
 * Indica se o tipo de torneio é eliminação dupla.
 */
function arenagamer_is_double_elimination_tournament_type($type)
{
    return strtoupper(trim((string) $type)) === 'DOUBLE_ELIMINATION';
}

/**
 * Lado da chave em torneio de eliminação dupla.
 *
 * @return string|null WINNERS|LOSERS|GRAND_FINAL
 */
function arenagamer_match_bracket_side(array $match)
{
    $roundType = arenagamer_match_round_type($match);

    if ($roundType === 'GRAND_FINAL') {
        return 'GRAND_FINAL';
    }

    if ($roundType === 'LOSERS_BRACKET') {
        return 'LOSERS';
    }

    if ($roundType === 'WINNERS_BRACKET') {
        return 'WINNERS';
    }

    $groupNumber = (int) ($match['groupNumber'] ?? 0);
    if ($groupNumber === 2) {
        return 'LOSERS';
    }
    if ($groupNumber === 1) {
        return 'WINNERS';
    }

    $raw = $match['bracketSide'] ?? $match['bracket_side'] ?? '';
    $side = strtoupper(trim((string) $raw));

    if (in_array($side, ['WINNERS', 'WINNER', 'UPPER', 'SUPERIOR'], true)) {
        return 'WINNERS';
    }

    if (in_array($side, ['LOSERS', 'LOSER', 'LOWER', 'INFERIOR', 'REPESCAGEM'], true)) {
        return 'LOSERS';
    }

    if ($side === 'GRAND_FINAL') {
        return 'GRAND_FINAL';
    }

    return null;
}

/**
 * Indica se a partida exige registro de resultado (dois inscritos definidos).
 */
function arenagamer_match_requires_result(array $match)
{
    $homeId = $match['homeParticipantId'] ?? null;
    $awayId = $match['awayParticipantId'] ?? null;

    return !empty($homeId) && !empty($awayId);
}

/**
 * Indica se a partida pertence à estrutura de eliminação dupla.
 */
function arenagamer_is_double_elimination_phase_match(array $match)
{
    if (in_array(arenagamer_match_round_type($match), ['WINNERS_BRACKET', 'LOSERS_BRACKET', 'GRAND_FINAL'], true)) {
        return true;
    }

    $side = arenagamer_match_bracket_side($match);

    return $side !== null || arenagamer_is_knockout_match($match);
}

/**
 * Rodadas da chave superior em eliminação dupla: ceil(log₂ N).
 */
function arenagamer_double_elimination_superior_rounds($participantCount)
{
    return (int) max(1, (int) ceil(log(max(2, (int) $participantCount), 2)));
}

/**
 * Resumo de rodadas da eliminação dupla.
 */
function arenagamer_double_elimination_rounds_summary($participantCount, $isTeam = false)
{
    $count = max(2, (int) $participantCount);
    $superiorRounds = arenagamer_double_elimination_superior_rounds($count);
    $inferiorRounds = 2 * max(0, $superiorRounds - 1);
    $unit = $isTeam ? 'equipes' : 'participantes';

    return $count . ' ' . $unit
        . ' → superior ' . $superiorRounds . ' rodada' . ($superiorRounds === 1 ? '' : 's')
        . ', repescagem ' . $inferiorRounds
        . ', grande final';
}

/**
 * Monta chaves superior, repescagem e grande final.
 *
 * @return array{winners:array,losers:array,grand_final:?array,has_brackets:bool}
 */
function arenagamer_build_double_elimination_brackets($matches)
{
    $result = [
        'winners'       => ['rounds' => [], 'third_place' => null],
        'losers'        => ['rounds' => [], 'third_place' => null],
        'grand_final'   => null,
        'has_brackets'  => false,
    ];

    if (!is_array($matches) || empty($matches)) {
        return $result;
    }

    $winnersMatches = [];
    $losersMatches = [];

    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }

        $side = arenagamer_match_bracket_side($match);

        if ($side === 'GRAND_FINAL') {
            $result['grand_final'] = $match;
            continue;
        }

        if ($side === 'WINNERS') {
            $winnersMatches[] = $match;
            continue;
        }

        if ($side === 'LOSERS') {
            $losersMatches[] = $match;
            continue;
        }

        if (arenagamer_is_knockout_match($match)) {
            $winnersMatches[] = $match;
        }
    }

    $result['winners'] = arenagamer_build_knockout_bracket($winnersMatches);
    $result['losers'] = arenagamer_build_knockout_bracket(
        arenagamer_filter_linked_knockout_matches($losersMatches, $matches)
    );
    $result['has_brackets'] = !empty($result['winners']['rounds'])
        || !empty($result['losers']['rounds'])
        || !empty($result['grand_final']);

    return $result;
}

/**
 * Indica se a eliminação dupla pode ser finalizada (grande final concluída e demais jogos decididos).
 */
function arenagamer_double_elimination_can_finalize($matchesData)
{
    if (!is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    $finished = ['COMPLETED', 'WALKOVER'];
    $grandFinalComplete = false;

    foreach ($matchesData as $match) {
        if (!is_array($match) || !arenagamer_is_double_elimination_phase_match($match)) {
            continue;
        }

        $homeId = $match['homeParticipantId'] ?? null;
        $awayId = $match['awayParticipantId'] ?? null;

        if (empty($homeId) && empty($awayId)) {
            continue;
        }

        if (!in_array($match['status'] ?? '', $finished, true)) {
            return false;
        }

        if (arenagamer_match_bracket_side($match) === 'GRAND_FINAL') {
            $grandFinalComplete = true;
        }
    }

    return $grandFinalComplete;
}

/**
 * IDs de partidas ligadas após o resultado (vitória ou derrota).
 *
 * @return array<int,int>
 */
function arenagamer_match_advance_link_ids(array $match)
{
    $ids = [];

    foreach (['nextMatchId', 'next_match_id'] as $field) {
        $id = (int) ($match[$field] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    foreach (['loserNextMatchId', 'loser_next_match_id'] as $field) {
        $id = (int) ($match[$field] ?? 0);
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Indica se ainda há jogos em disputa em uma chave da eliminação dupla.
 */
function arenagamer_double_elimination_side_has_pending_results(array $matchesData, $bracketSide)
{
    $bracketSide = strtoupper(trim((string) $bracketSide));
    $finished = ['COMPLETED', 'WALKOVER'];

    foreach ($matchesData as $match) {
        if (!is_array($match) || !arenagamer_is_double_elimination_phase_match($match)) {
            continue;
        }
        if (arenagamer_match_bracket_side($match) !== $bracketSide) {
            continue;
        }
        if (!arenagamer_match_requires_result($match)) {
            continue;
        }
        if (!in_array($match['status'] ?? '', $finished, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Indica se todos os jogos disputáveis de uma rodada/chave foram concluídos.
 */
function arenagamer_double_elimination_side_round_is_complete(array $matchesData, $bracketSide, $roundNumber)
{
    $bracketSide = strtoupper(trim((string) $bracketSide));
    $roundNumber = (int) $roundNumber;
    $finished = ['COMPLETED', 'WALKOVER'];
    $hasPlayable = false;

    foreach ($matchesData as $match) {
        if (!is_array($match) || arenagamer_match_bracket_side($match) !== $bracketSide) {
            continue;
        }
        if ((int) ($match['roundNumber'] ?? 0) !== $roundNumber) {
            continue;
        }
        if (!arenagamer_match_requires_result($match)) {
            continue;
        }
        $hasPlayable = true;
        if (!in_array($match['status'] ?? '', $finished, true)) {
            return false;
        }
    }

    return $hasPlayable;
}

/**
 * Indica se ainda há jogos em disputa na eliminação dupla (qualquer chave).
 */
function arenagamer_double_elimination_has_pending_results(array $matchesData)
{
    foreach (['WINNERS', 'LOSERS'] as $side) {
        if (arenagamer_double_elimination_side_has_pending_results($matchesData, $side)) {
            return true;
        }
    }

    return false;
}

/**
 * Agrupa partidas de uma chave da eliminação dupla por número de rodada.
 *
 * @return array<int,array<int,array>>
 */
function arenagamer_double_elimination_side_rounds_map(array $matchesData, $bracketSide)
{
    $bracketSide = strtoupper(trim((string) $bracketSide));
    $rounds = [];

    foreach ($matchesData as $match) {
        if (!is_array($match) || arenagamer_match_bracket_side($match) !== $bracketSide) {
            continue;
        }

        $roundNumber = $match['roundNumber'] ?? null;
        if ($roundNumber === null) {
            continue;
        }

        $rounds[(int) $roundNumber][] = $match;
    }

    ksort($rounds);

    return $rounds;
}

/**
 * Indica se a repescagem já avançou da rodada informada (vencedores na rodada seguinte).
 */
function arenagamer_double_elimination_losers_round_has_been_advanced(array $matchesData, $roundNumber)
{
    $roundNumber = (int) $roundNumber;
    if ($roundNumber < 1) {
        return false;
    }

    if (!arenagamer_double_elimination_side_round_is_complete($matchesData, 'LOSERS', $roundNumber)) {
        return false;
    }

    $rounds = arenagamer_double_elimination_side_rounds_map($matchesData, 'LOSERS');
    $nextRound = $roundNumber + 1;

    if (!isset($rounds[$nextRound])) {
        return false;
    }

    foreach ($rounds[$nextRound] as $match) {
        if (!empty($match['homeParticipantId']) || !empty($match['awayParticipantId'])) {
            return true;
        }
    }

    return false;
}

/**
 * Chave superior (rodada 2+): só pode avançar após a repescagem anterior ter sido avançada.
 */
function arenagamer_double_elimination_winners_round_may_advance(array $matchesData, $roundNumber)
{
    $roundNumber = (int) $roundNumber;

    if ($roundNumber <= 1) {
        return true;
    }

    return arenagamer_double_elimination_losers_round_has_been_advanced($matchesData, $roundNumber - 1);
}

/**
 * Remove partidas vazias sem links de avanço (fantasmas de bracket cheio).
 *
 * @return array<int,array>
 */
function arenagamer_filter_linked_knockout_matches(array $matches, array $allMatches = [])
{
    if (empty($matches)) {
        return [];
    }

    $pool = !empty($allMatches) ? $allMatches : $matches;
    $linkedTargets = [];

    foreach ($pool as $match) {
        if (!is_array($match)) {
            continue;
        }
        foreach (arenagamer_match_advance_link_ids($match) as $targetId) {
            $linkedTargets[(int) $targetId] = true;
        }
    }

    $filtered = [];

    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }

        if (!empty($match['homeParticipantId']) || !empty($match['awayParticipantId'])) {
            $filtered[] = $match;
            continue;
        }

        $matchId = (int) ($match['id'] ?? 0);
        if ($matchId > 0 && !empty($linkedTargets[$matchId])) {
            $filtered[] = $match;
        }
    }

    return $filtered;
}

/**
 * Indica se há resultados registrados aguardando advance-round (links com vagas abertas).
 */
function arenagamer_double_elimination_has_pending_advance_links(array $matchesData)
{
    $finished = ['COMPLETED', 'WALKOVER'];
    $byId = [];

    foreach ($matchesData as $match) {
        if (!is_array($match) || empty($match['id']) || !arenagamer_is_double_elimination_phase_match($match)) {
            continue;
        }
        $byId[(int) $match['id']] = $match;
    }

    if (empty($byId)) {
        return false;
    }

    foreach ($byId as $match) {
        if (!in_array($match['status'] ?? '', $finished, true)) {
            continue;
        }

        $side = arenagamer_match_bracket_side($match);
        $roundNumber = (int) ($match['roundNumber'] ?? 0);
        if ($side !== null && $roundNumber > 0
            && !arenagamer_double_elimination_side_round_is_complete($matchesData, $side, $roundNumber)) {
            continue;
        }

        if ($side === 'WINNERS' && $roundNumber >= 2
            && !arenagamer_double_elimination_winners_round_may_advance($matchesData, $roundNumber)) {
            continue;
        }

        foreach (arenagamer_match_advance_link_ids($match) as $targetId) {
            if (!isset($byId[$targetId])) {
                continue;
            }

            $target = $byId[$targetId];
            if (empty($target['homeParticipantId']) || empty($target['awayParticipantId'])) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Verifica avanço por rodada dentro de uma chave (superior ou repescagem).
 */
function arenagamer_double_elimination_bracket_side_can_advance_round(array $matchesData, $bracketSide)
{
    $bracketSide = strtoupper(trim((string) $bracketSide));
    if (!in_array($bracketSide, ['WINNERS', 'LOSERS'], true)) {
        return false;
    }

    $rounds = [];
    $finished = ['COMPLETED', 'WALKOVER'];

    foreach ($matchesData as $match) {
        if (!is_array($match) || arenagamer_match_bracket_side($match) !== $bracketSide) {
            continue;
        }

        $roundNumber = $match['roundNumber'] ?? null;
        if ($roundNumber === null) {
            continue;
        }

        $rounds[(int) $roundNumber][] = $match;
    }

    if (empty($rounds)) {
        return false;
    }

    ksort($rounds);

    foreach (array_keys($rounds) as $roundNumber) {
        $populated = false;
        $allFinished = true;

        foreach ($rounds[$roundNumber] as $match) {
            if (!empty($match['homeParticipantId']) || !empty($match['awayParticipantId'])) {
                $populated = true;
            }
            if (arenagamer_match_requires_result($match)
                && !in_array($match['status'] ?? '', $finished, true)) {
                $allFinished = false;
            }
        }

        if (!$populated || !$allFinished) {
            continue;
        }

        if ($bracketSide === 'WINNERS' && $roundNumber >= 2
            && !arenagamer_double_elimination_winners_round_may_advance($matchesData, $roundNumber)) {
            continue;
        }

        $nextRound = $roundNumber + 1;
        if (!isset($rounds[$nextRound])) {
            continue;
        }

        foreach ($rounds[$nextRound] as $nextMatch) {
            if (!empty($nextMatch['homeParticipantId']) || !empty($nextMatch['awayParticipantId'])) {
                continue 2;
            }
        }

        return true;
    }

    return false;
}

/**
 * Indica se a próxima rodada da eliminação dupla pode ser gerada (advance-round).
 *
 * Repescagem tem prioridade; chave superior (rodada 2+) exige repescagem anterior avançada.
 */
function arenagamer_double_elimination_matches_can_advance_round($matchesData)
{
    if (!is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    if (arenagamer_double_elimination_bracket_side_can_advance_round($matchesData, 'LOSERS')) {
        return true;
    }

    if (arenagamer_double_elimination_has_pending_advance_links($matchesData)) {
        return true;
    }

    if (arenagamer_double_elimination_side_has_pending_results($matchesData, 'WINNERS')) {
        return false;
    }

    if (arenagamer_double_elimination_bracket_side_can_advance_round($matchesData, 'WINNERS')) {
        return true;
    }

    if (arenagamer_double_elimination_side_has_pending_results($matchesData, 'LOSERS')) {
        return false;
    }

    return arenagamer_double_elimination_bracket_side_can_advance_round($matchesData, 'LOSERS');
}

/**
 * Dica antes de gerar chaves em torneio de eliminação dupla.
 *
 * @return string|null
 */
function arenagamer_tournament_bracket_double_elimination_hint(array $tournament)
{
    if (!arenagamer_is_double_elimination_tournament_type($tournament['type'] ?? '')) {
        return null;
    }

    $participants = max(2, (int) ($tournament['participantCount'] ?? $tournament['participantsLimit'] ?? 0));
    $isTeam = ($tournament['format'] ?? '') === 'TEAM';
    $summary = arenagamer_double_elimination_rounds_summary($participants, $isTeam);

    return $summary . '. Apenas a 1ª rodada da superior vem preenchida (BYEs automáticos quando necessário); '
        . 'a repescagem é proporcional aos jogos reais da 1ª rodada. '
        . 'Registre os resultados e use Avançar rodada: primeiro envie perdedores à repescagem, '
        . 'jogue e avance a repescagem, depois avance a chave superior — alternando as duas chaves. '
        . 'Se as chaves ficaram incorretas, apague as partidas e gere as chaves novamente.';
}

/**
 * roundType normalizado (camelCase ou snake_case da API).
 */
function arenagamer_match_round_type(array $match)
{
    $raw = $match['roundType'] ?? $match['round_type'] ?? '';

    return strtoupper(trim((string) $raw));
}

/**
 * Indica se a partida é da fase de grupos.
 */
function arenagamer_is_group_stage_match(array $match)
{
    return arenagamer_match_round_type($match) === 'GROUP_STAGE';
}

/**
 * Indica se a partida é da fase de pontos corridos (todos contra todos).
 */
function arenagamer_is_round_robin_match(array $match)
{
    return arenagamer_match_round_type($match) === 'ROUND_ROBIN';
}

/**
 * Indica se o tipo de torneio é sistema suíço.
 */
function arenagamer_is_swiss_tournament_type($type)
{
    return strtoupper(trim((string) $type)) === 'SWISS';
}

/**
 * Indica se a partida pertence a uma rodada suíça.
 */
function arenagamer_is_swiss_match(array $match)
{
    return arenagamer_match_round_type($match) === 'SWISS';
}

/**
 * Partida da fase suíça (inclui fallback quando a API não envia roundType).
 */
function arenagamer_is_swiss_phase_match(array $match)
{
    if (arenagamer_is_swiss_match($match)) {
        return true;
    }

    if (arenagamer_is_knockout_match($match)
        || arenagamer_is_group_stage_match($match)
        || arenagamer_is_round_robin_match($match)) {
        return false;
    }

    $roundType = arenagamer_match_round_type($match);

    return $roundType === '';
}

/**
 * Participantes efetivos para calcular rodadas suíças.
 */
function arenagamer_swiss_participant_count(array $tournament)
{
    $count = (int) ($tournament['participantCount'] ?? 0);
    if ($count >= 2) {
        return $count;
    }

    if (!empty($tournament['participants']) && is_array($tournament['participants'])) {
        $count = count($tournament['participants']);
        if ($count >= 2) {
            return $count;
        }
    }

    return max(2, (int) ($tournament['participantsLimit'] ?? 0));
}

/**
 * Total de rodadas suíças: ceil(log₂ participantes).
 */
function arenagamer_swiss_total_rounds($participantCount)
{
    $count = max(2, (int) $participantCount);

    return (int) max(1, (int) ceil(log($count, 2)));
}

/**
 * Total de rodadas suíças para um torneio.
 */
function arenagamer_swiss_total_rounds_for_tournament(array $tournament)
{
    return arenagamer_swiss_total_rounds(arenagamer_swiss_participant_count($tournament));
}

/**
 * Extrai partidas suíças ordenadas por rodada e número do jogo.
 *
 * @return array<int,array>
 */
function arenagamer_collect_swiss_matches(array $allMatches)
{
    $matches = [];

    foreach ($allMatches as $match) {
        if (is_array($match) && arenagamer_is_swiss_phase_match($match)) {
            $matches[] = $match;
        }
    }

    usort($matches, function ($a, $b) {
        $roundOrder = (int) ($a['roundNumber'] ?? 0) <=> (int) ($b['roundNumber'] ?? 0);
        if ($roundOrder !== 0) {
            return $roundOrder;
        }

        return ((int) ($a['matchNumber'] ?? 0)) <=> ((int) ($b['matchNumber'] ?? 0));
    });

    return $matches;
}

/**
 * Agrupa partidas suíças por rodada.
 *
 * @return array<int,array{roundNumber:int,label:string,matches:array}>
 */
function arenagamer_build_swiss_round_blocks(array $matches)
{
    $byRound = [];

    foreach ($matches as $match) {
        if (!is_array($match)) {
            continue;
        }
        $roundNumber = (int) ($match['roundNumber'] ?? 0);
        if ($roundNumber < 1) {
            $roundNumber = 1;
        }
        $byRound[$roundNumber][] = $match;
    }

    if (empty($byRound)) {
        return [];
    }

    ksort($byRound);

    $blocks = [];
    foreach ($byRound as $roundNumber => $roundMatches) {
        usort($roundMatches, function ($a, $b) {
            return ((int) ($a['matchNumber'] ?? 0)) <=> ((int) ($b['matchNumber'] ?? 0));
        });

        $blocks[] = [
            'roundNumber' => (int) $roundNumber,
            'label'       => 'Rodada ' . (int) $roundNumber,
            'matches'     => $roundMatches,
        ];
    }

    return $blocks;
}

/**
 * Indica se a partida suíça já foi resolvida (inclui bye automático).
 */
function arenagamer_swiss_match_is_resolved(array $match)
{
    $homeId = $match['homeParticipantId'] ?? null;
    $awayId = $match['awayParticipantId'] ?? null;

    if (empty($homeId) && empty($awayId)) {
        return true;
    }

    return in_array($match['status'] ?? '', ['COMPLETED', 'WALKOVER'], true);
}

/**
 * Indica se todos os jogos de uma rodada suíça foram concluídos.
 */
function arenagamer_swiss_round_is_complete(array $roundMatches)
{
    if (empty($roundMatches)) {
        return false;
    }

    foreach ($roundMatches as $match) {
        if (!is_array($match)) {
            continue;
        }
        $homeId = $match['homeParticipantId'] ?? null;
        $awayId = $match['awayParticipantId'] ?? null;

        if (empty($homeId) && empty($awayId)) {
            continue;
        }

        if (!arenagamer_swiss_match_is_resolved($match)) {
            return false;
        }
    }

    return true;
}

/**
 * Indica se a rodada suíça já tem emparelhamentos definidos.
 */
function arenagamer_swiss_round_has_pairings(array $roundMatches)
{
    foreach ($roundMatches as $match) {
        if (!is_array($match)) {
            continue;
        }
        if (!empty($match['homeParticipantId']) || !empty($match['awayParticipantId'])) {
            return true;
        }
    }

    return false;
}

/**
 * Agrupa partidas suíças por número de rodada.
 *
 * @return array<int,array<int,array>>
 */
function arenagamer_swiss_matches_by_round($matchesData)
{
    $rounds = [];

    if (!is_array($matchesData)) {
        return $rounds;
    }

    foreach ($matchesData as $match) {
        if (!is_array($match) || !arenagamer_is_swiss_phase_match($match)) {
            continue;
        }
        $roundNumber = (int) ($match['roundNumber'] ?? 0);
        if ($roundNumber < 1) {
            $roundNumber = 1;
        }
        $rounds[$roundNumber][] = $match;
    }

    ksort($rounds);

    return $rounds;
}

/**
 * Indica se a próxima rodada suíça pode ser gerada.
 */
function arenagamer_swiss_matches_can_advance_round($matchesData, array $tournament)
{
    if (!is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    $rounds = arenagamer_swiss_matches_by_round($matchesData);
    if (empty($rounds)) {
        return false;
    }

    $totalRounds = arenagamer_swiss_total_rounds_for_tournament($tournament);
    $currentRound = max(array_keys($rounds));

    if (!arenagamer_swiss_round_is_complete($rounds[$currentRound])) {
        return false;
    }

    if ($currentRound >= $totalRounds) {
        return false;
    }

    $nextRound = $currentRound + 1;

    if (isset($rounds[$nextRound]) && arenagamer_swiss_round_has_pairings($rounds[$nextRound])) {
        return false;
    }

    return true;
}

/**
 * Indica se o torneio suíço pode ser finalizado.
 */
function arenagamer_swiss_matches_can_finalize($matchesData, array $tournament)
{
    if (!is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    $rounds = arenagamer_swiss_matches_by_round($matchesData);
    if (empty($rounds)) {
        return false;
    }

    $totalRounds = arenagamer_swiss_total_rounds_for_tournament($tournament);
    $maxRound = max(array_keys($rounds));

    if ($maxRound < $totalRounds) {
        return false;
    }

    foreach ($matchesData as $match) {
        if (!is_array($match) || !arenagamer_is_swiss_phase_match($match)) {
            continue;
        }
        if (!arenagamer_swiss_match_is_resolved($match)) {
            return false;
        }
    }

    return true;
}

/**
 * Texto "N participantes → X rodadas" para torneio suíço.
 */
function arenagamer_swiss_rounds_count_label($participantCount, $isTeam = false)
{
    $count = max(2, (int) $participantCount);
    $rounds = arenagamer_swiss_total_rounds($count);
    $unit = $isTeam ? 'equipes' : 'participantes';

    return $count . ' ' . $unit . ' → ' . $rounds . ' rodada' . ($rounds === 1 ? '' : 's');
}

/**
 * Resumo do formato suíço para exibição no detalhe do torneio.
 *
 * @return string|null
 */
function arenagamer_tournament_swiss_summary(array $tournament)
{
    if (!arenagamer_is_swiss_tournament_type($tournament['type'] ?? '')) {
        return null;
    }

    $participants = arenagamer_swiss_participant_count($tournament);
    $isTeam = ($tournament['format'] ?? '') === 'TEAM';
    $roundsLabel = arenagamer_swiss_rounds_count_label($participants, $isTeam);

    return $roundsLabel . ' · vitória 3 pts · derrota 0 · sem empate';
}

/**
 * Dica antes de gerar chaves em torneio suíço.
 *
 * @return string|null
 */
function arenagamer_tournament_bracket_swiss_hint(array $tournament)
{
    if (!arenagamer_is_swiss_tournament_type($tournament['type'] ?? '')) {
        return null;
    }

    $participants = arenagamer_swiss_participant_count($tournament);
    $isTeam = ($tournament['format'] ?? '') === 'TEAM';
    $roundsLabel = arenagamer_swiss_rounds_count_label($participants, $isTeam);

    return $roundsLabel . '. Será criada a rodada 1 com emparelhamento por seed (estilo suíço clássico).';
}

/**
 * Mensagem de confirmação ao gerar a próxima rodada suíça.
 */
function arenagamer_advance_round_confirm_message(array $tournament)
{
    if (arenagamer_is_swiss_tournament_type($tournament['type'] ?? '')) {
        return 'Gerar a próxima rodada suíça com emparelhamento por pontuação similar (evitando repetir adversários quando possível)?';
    }

    if (arenagamer_is_double_elimination_tournament_type($tournament['type'] ?? '')) {
        return 'Avançar a próxima rodada? '
            . 'Perdedores da superior vão para a repescagem; vencedores da repescagem seguem na repescagem. '
            . 'A chave superior só avança da 2ª rodada em diante depois que a repescagem anterior foi avançada.';
    }

    return 'Gerar a próxima fase com os vencedores da fase atual?';
}

/**
 * Rótulo do botão advance-round conforme o tipo de torneio.
 */
function arenagamer_advance_round_button_label(array $tournament)
{
    if (arenagamer_is_swiss_tournament_type($tournament['type'] ?? '')) {
        return 'Gerar próxima rodada';
    }

    if (arenagamer_is_double_elimination_tournament_type($tournament['type'] ?? '')) {
        return 'Avançar rodada';
    }

    return 'Gerar próxima fase';
}

/**
 * Torneios com fase inicial em tabela seguida de mata-mata.
 */
function arenagamer_tournament_has_league_knockout_flow($tournamentType)
{
    return in_array(strtoupper(trim((string) $tournamentType)), ['GROUP_STAGE', 'ROUND_ROBIN_ELIMINATION'], true);
}

/**
 * Indica se a partida pertence à fase de tabela inicial do torneio.
 */
function arenagamer_is_league_phase_match(array $match, $tournamentType)
{
    $type = strtoupper(trim((string) $tournamentType));

    if ($type === 'GROUP_STAGE') {
        return arenagamer_is_group_stage_match($match);
    }

    if ($type === 'ROUND_ROBIN_ELIMINATION') {
        if (arenagamer_is_round_robin_match($match)) {
            return true;
        }

        // Fallback: partidas da fase inicial ainda sem roundType explícito na API.
        return !arenagamer_is_knockout_match($match);
    }

    return false;
}

/**
 * Verifica se um número é potência de 2 (2, 4, 8, 16…).
 */
function arenagamer_is_power_of_two($value)
{
    $value = (int) $value;

    return $value >= 2 && ($value & ($value - 1)) === 0;
}

/**
 * Maior potência de 2 menor ou igual ao limite informado.
 */
function arenagamer_largest_power_of_two_up_to($limit)
{
    $limit = (int) $limit;
    if ($limit < 2) {
        return 0;
    }

    $power = 1;
    while (($power * 2) <= $limit) {
        $power *= 2;
    }

    return $power;
}

/**
 * Resolve o número do grupo de uma partida.
 */
function arenagamer_match_group_number(array $match, array $participantGroup = [])
{
    if (isset($match['groupNumber']) && (int) $match['groupNumber'] > 0) {
        return (int) $match['groupNumber'];
    }

    $phase = (string) ($match['phaseLabel'] ?? '');
    if (preg_match('/grupo\s*(\d+)/iu', $phase, $matches)) {
        return (int) $matches[1];
    }

    foreach (['homeParticipantId', 'awayParticipantId'] as $key) {
        $participantId = (int) ($match[$key] ?? 0);
        if ($participantId > 0 && isset($participantGroup[$participantId])) {
            return (int) $participantGroup[$participantId];
        }
    }

    return 0;
}

/**
 * Verifica se a classificação possui grupos.
 */
function arenagamer_standings_has_groups($standings)
{
    if (!is_array($standings)) {
        return false;
    }

    foreach ($standings as $row) {
        if (is_array($row) && (isset($row['groupNumber']) || isset($row['points']))) {
            return isset($row['groupNumber']);
        }
    }

    return false;
}

/**
 * Monta blocos da fase de grupos (classificação + partidas por grupo).
 *
 * @return array<int,array{number:int,standings:array,matches:array}>
 */
function arenagamer_build_group_stage_blocks($standings, $matches)
{
    $blocks = [];
    $participantGroup = [];

    if (!is_array($standings)) {
        $standings = [];
    }
    if (!is_array($matches)) {
        $matches = [];
    }

    foreach ($standings as $row) {
        if (!is_array($row) || !isset($row['groupNumber'])) {
            continue;
        }
        $groupNumber = (int) $row['groupNumber'];
        if ($groupNumber < 1) {
            continue;
        }
        if (isset($row['participantId'])) {
            $participantGroup[(int) $row['participantId']] = $groupNumber;
        }
        if (!isset($blocks[$groupNumber])) {
            $blocks[$groupNumber] = [
                'number'    => $groupNumber,
                'standings' => [],
                'matches'   => [],
            ];
        }
        $blocks[$groupNumber]['standings'][] = $row;
    }

    foreach ($matches as $match) {
        if (!is_array($match) || !arenagamer_is_group_stage_match($match)) {
            continue;
        }
        $groupNumber = arenagamer_match_group_number($match, $participantGroup);
        if ($groupNumber < 1) {
            continue;
        }
        if (!isset($blocks[$groupNumber])) {
            $blocks[$groupNumber] = [
                'number'    => $groupNumber,
                'standings' => [],
                'matches'   => [],
            ];
        }
        $blocks[$groupNumber]['matches'][] = $match;
    }

    ksort($blocks);

    foreach ($blocks as &$block) {
        usort($block['standings'], function ($a, $b) {
            return ((int) ($a['position'] ?? 999)) <=> ((int) ($b['position'] ?? 999));
        });
        usort($block['matches'], function ($a, $b) {
            return ((int) ($a['matchNumber'] ?? 0)) <=> ((int) ($b['matchNumber'] ?? 0));
        });
    }
    unset($block);

    return array_values($blocks);
}

/**
 * Busca todas as partidas de um torneio (paginação da API).
 *
 * @param ArenaGamer_api $api
 */
function arenagamer_fetch_all_tournament_matches($api, $slug, $pageSize = 100, $maxPages = 50)
{
    $all = [];
    $page = 0;

    do {
        $response = $api->get_tournament_matches($slug, [
            'page' => $page,
            'size' => $pageSize,
        ]);
        $content = arenagamer_paginated_content($response);
        if (!is_array($content) || empty($content)) {
            break;
        }

        $all = array_merge($all, $content);
        $meta = arenagamer_pagination_meta($response);
        $page++;

        if ($page >= (int) ($meta['totalPages'] ?? 1)) {
            break;
        }
    } while ($page < $maxPages);

    return $all;
}

/**
 * Tamanho da página na listagem de jogos de pontos corridos.
 */
function arenagamer_round_robin_matches_page_size()
{
    return 10;
}

/**
 * Extrai e ordena partidas da fase de pontos corridos.
 */
function arenagamer_collect_round_robin_matches(array $allMatches)
{
    $matches = [];

    foreach ($allMatches as $match) {
        if (!is_array($match)) {
            continue;
        }
        if (arenagamer_is_round_robin_match($match) || !arenagamer_is_knockout_match($match)) {
            $matches[] = $match;
        }
    }

    usort($matches, function ($a, $b) {
        return ((int) ($a['matchNumber'] ?? 0)) <=> ((int) ($b['matchNumber'] ?? 0));
    });

    return $matches;
}

/**
 * Pagina um array local (metadados compatíveis com arenagamer_pagination_info_text).
 *
 * @return array{items:array,pagination:array}
 */
function arenagamer_paginate_array(array $items, $page, $size)
{
    $total = count($items);
    $size = max(1, (int) $size);
    $totalPages = (int) max(1, (int) ceil($total / $size));
    $page = min(max(0, (int) $page), $totalPages - 1);
    $offset = $page * $size;
    $from = $total > 0 ? $offset + 1 : 0;
    $to = min($offset + $size, $total);

    return [
        'items'      => array_slice($items, $offset, $size),
        'pagination' => [
            'number'        => $page,
            'size'          => $size,
            'totalElements' => $total,
            'totalPages'    => $totalPages,
            'from'          => $from,
            'to'            => $to,
        ],
    ];
}

/**
 * Query string do detalhe do torneio (filtros de partidas + pontos corridos).
 */
function arenagamer_tournament_detail_query(array $overrides = [])
{
    $CI = &get_instance();
    $params = [];

    foreach (['match_page', 'match_view', 'rr_page'] as $key) {
        if (array_key_exists($key, $overrides)) {
            $value = $overrides[$key];
            if ($value === null || $value === '') {
                continue;
            }
            if ($key === 'match_view' && $value === 'pending') {
                continue;
            }
            if (in_array($key, ['match_page', 'rr_page'], true) && (int) $value <= 0) {
                continue;
            }
            $params[$key] = $value;
            continue;
        }

        $value = $CI->input->get($key);
        if ($value === null || $value === '') {
            continue;
        }
        if ($key === 'match_view' && $value === 'pending') {
            continue;
        }
        if (in_array($key, ['match_page', 'rr_page'], true) && (int) $value <= 0) {
            continue;
        }
        $params[$key] = $value;
    }

    return $params;
}

/**
 * URL do detalhe do torneio preservando filtros ativos.
 */
function arenagamer_tournament_detail_url($baseUrl, array $overrides = [])
{
    $params = arenagamer_tournament_detail_query($overrides);
    $query = http_build_query($params);

    return rtrim((string) $baseUrl, '?') . ($query !== '' ? '?' . $query : '');
}

/**
 * Página atual dos jogos de pontos corridos (query rr_page).
 */
function arenagamer_round_robin_matches_page_from_request()
{
    $CI = &get_instance();

    return max(0, (int) ($CI->input->get('rr_page') ?? 0));
}

/**
 * Estado da listagem de partidas (filtros da API + visão ativa na UI).
 *
 * @return array{filters:array,view:string}
 */
function arenagamer_tournament_matches_list_query($input)
{
    $filters = [
        'page' => max(0, (int) ($input->get('match_page') ?? 0)),
        'size' => max(1, min(100, (int) ($input->get('match_size') ?? 10))),
    ];

    $view = strtolower(trim((string) $input->get('match_view')));

    if ($view === '') {
        $finished = $input->get('match_finished');
        if ($finished === 'true' || $finished === '1') {
            $view = 'finished';
        } elseif ($finished === 'false' || $finished === '0') {
            $view = 'pending';
        } else {
            $scheduled = $input->get('match_scheduled');
            if ($scheduled === 'false' || $scheduled === '0') {
                $view = 'unscheduled';
            } elseif ($scheduled === 'true' || $scheduled === '1') {
                $view = 'scheduled';
            } else {
                $view = 'pending';
            }
        }
    }

    switch ($view) {
        case 'all':
            break;
        case 'finished':
            $filters['finished'] = true;
            break;
        case 'pending':
            $filters['finished'] = false;
            break;
        case 'unscheduled':
            $filters['scheduled'] = false;
            break;
        case 'scheduled':
            $filters['scheduled'] = true;
            break;
        default:
            $view = 'pending';
            $filters['finished'] = false;
            break;
    }

    return [
        'filters' => $filters,
        'view'    => $view,
    ];
}

/**
 * Filtros de listagem de partidas a partir da query string.
 */
function arenagamer_tournament_matches_filters_from_request($input)
{
    return arenagamer_tournament_matches_list_query($input)['filters'];
}

/**
 * Monta URL de detalhe do torneio preservando filtro de partidas.
 */
function arenagamer_tournament_matches_filter_url($baseUrl, $view, $page = 0)
{
    $params = [];

    if ($page > 0) {
        $params['match_page'] = (int) $page;
    }

    if ($view !== 'pending') {
        $params['match_view'] = $view;
    }

    $query = http_build_query($params);

    return rtrim((string) $baseUrl, '?') . ($query !== '' ? '?' . $query : '');
}

/**
 * Opções de filtro rápido para a listagem de partidas.
 */
function arenagamer_tournament_matches_filter_options()
{
    return [
        ['id' => 'pending', 'label' => 'Pendentes'],
        ['id' => 'scheduled', 'label' => 'Agendadas'],
        ['id' => 'all', 'label' => 'Todas'],
        ['id' => 'finished', 'label' => 'Finalizadas'],
        ['id' => 'unscheduled', 'label' => 'Sem horário'],
    ];
}

/**
 * Indica se é possível gerar a próxima fase da chave eliminatória:
 * todos os jogos da fase atual concluídos e existe uma próxima fase ainda não preenchida.
 */
function arenagamer_matches_can_advance_round($matchesData, $tournamentType, array $tournament = [])
{
    $tournamentType = strtoupper(trim((string) $tournamentType));

    if ($tournamentType === 'SWISS') {
        return arenagamer_swiss_matches_can_advance_round($matchesData, $tournament);
    }

    if ($tournamentType === 'DOUBLE_ELIMINATION') {
        return arenagamer_double_elimination_matches_can_advance_round($matchesData);
    }

    if (!in_array($tournamentType, ['SINGLE_ELIMINATION', 'GROUP_STAGE', 'ROUND_ROBIN_ELIMINATION'], true)
        || !is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    if (arenagamer_tournament_has_league_knockout_flow($tournamentType)
        && !arenagamer_matches_has_knockout($matchesData)) {
        return false;
    }

    $rounds = [];
    foreach ($matchesData as $m) {
        $rn = $m['roundNumber'] ?? null;
        if ($rn === null) {
            continue;
        }
        if (arenagamer_tournament_has_league_knockout_flow($tournamentType)
            && arenagamer_is_league_phase_match($m, $tournamentType)) {
            continue;
        }
        $rounds[(int) $rn][] = $m;
    }

    if (empty($rounds)) {
        return false;
    }

    ksort($rounds);
    $finished = ['COMPLETED', 'WALKOVER'];

    foreach (array_keys($rounds) as $rn) {
        $populated = false;
        $allFinished = true;

        foreach ($rounds[$rn] as $m) {
            if (!empty($m['homeParticipantId']) || !empty($m['awayParticipantId'])) {
                $populated = true;
            }
            if (!in_array($m['status'] ?? '', $finished, true)) {
                $allFinished = false;
            }
        }

        if (!$populated) {
            continue;
        }
        if (!$allFinished) {
            return false;
        }

        $nextRn = $rn + 1;
        if (!isset($rounds[$nextRn])) {
            return false;
        }

        foreach ($rounds[$nextRn] as $nm) {
            if (!empty($nm['homeParticipantId']) || !empty($nm['awayParticipantId'])) {
                continue 2;
            }
        }

        return true;
    }

    return false;
}

/**
 * Indica se já existe um mata-mata (eliminatória) gerado entre as partidas.
 */
function arenagamer_matches_has_knockout($matchesData)
{
    if (!is_array($matchesData)) {
        return false;
    }

    foreach ($matchesData as $m) {
        if (arenagamer_is_knockout_match($m)) {
            return true;
        }
    }

    return false;
}

/**
 * Verifica se todos os jogos da fase de tabela inicial foram concluídos.
 */
function arenagamer_matches_league_phase_complete($matchesData, $tournamentType)
{
    if (!arenagamer_tournament_has_league_knockout_flow($tournamentType)
        || !is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    $finished = ['COMPLETED', 'WALKOVER'];
    $hasLeagueMatch = false;

    foreach ($matchesData as $m) {
        if (!arenagamer_is_league_phase_match($m, $tournamentType)) {
            continue;
        }
        $hasLeagueMatch = true;
        if (!in_array($m['status'] ?? '', $finished, true)) {
            return false;
        }
    }

    return $hasLeagueMatch;
}

/**
 * Indica se o mata-mata pode ser gerado após a fase de tabela inicial.
 */
function arenagamer_matches_can_generate_knockout($matchesData, $tournamentType)
{
    $tournamentType = strtoupper(trim((string) $tournamentType));

    if (!arenagamer_tournament_has_league_knockout_flow($tournamentType)
        || !is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    if (arenagamer_matches_has_knockout($matchesData)) {
        return false;
    }

    return arenagamer_matches_league_phase_complete($matchesData, $tournamentType);
}

/**
 * Indica se as chaves iniciais podem ser geradas.
 */
function arenagamer_tournament_can_generate_bracket(array $tournament, $matchesData = null)
{
    $status = strtoupper(trim((string) ($tournament['status'] ?? '')));
    $matches = is_array($matchesData) ? $matchesData : [];

    if (in_array($status, ['REGISTRATION_OPEN', 'REGISTRATION_CLOSED'], true)) {
        return true;
    }

    // Torneio em andamento sem partidas (ex.: falha na geração ou status adiantado pela API).
    if ($status === 'IN_PROGRESS' && empty($matches)) {
        return true;
    }

    return false;
}

/**
 * Dica quando o mata-mata ainda não pode ser gerado (fase inicial incompleta).
 *
 * @return string|null
 */
function arenagamer_tournament_generate_knockout_pending_hint($matchesData, $tournamentType)
{
    $tournamentType = strtoupper(trim((string) $tournamentType));

    if (!arenagamer_tournament_has_league_knockout_flow($tournamentType)
        || !is_array($matchesData) || empty($matchesData)) {
        return null;
    }

    if (arenagamer_matches_can_generate_knockout($matchesData, $tournamentType)
        || arenagamer_matches_has_knockout($matchesData)) {
        return null;
    }

    if (!arenagamer_matches_league_phase_complete($matchesData, $tournamentType)) {
        return 'Registre o resultado de todas as partidas da fase inicial para habilitar a geração do mata-mata.';
    }

    return null;
}

/**
 * Indica se o mata-mata pode ser regerado (fase de tabela concluída e mata-mata já existente).
 */
function arenagamer_matches_can_regenerate_knockout($matchesData, $tournamentType)
{
    $tournamentType = strtoupper(trim((string) $tournamentType));

    if (!arenagamer_tournament_has_league_knockout_flow($tournamentType)
        || !is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    if (!arenagamer_matches_has_knockout($matchesData)) {
        return false;
    }

    return arenagamer_matches_league_phase_complete($matchesData, $tournamentType);
}

/**
 * Indica se as partidas da chave podem ser limpas (endpoint admin DELETE /matches).
 */
function arenagamer_tournament_can_clear_matches(array $tournament, $matchesData = null)
{
    $status = strtoupper(trim((string) ($tournament['status'] ?? '')));

    if (in_array($status, ['COMPLETED', 'CANCELLED', 'DRAFT'], true)) {
        return false;
    }

    return is_array($matchesData) && !empty($matchesData);
}

/**
 * Mensagem de sucesso ao limpar partidas (data = quantidade removida).
 */
function arenagamer_clear_matches_success_message($response, $default = 'Partidas removidas com sucesso.')
{
    if (!arenagamer_api_is_success($response)) {
        return $default;
    }

    $count = arenagamer_api_data($response);
    if (is_numeric($count)) {
        $n = (int) $count;

        return $n . ' partida' . ($n === 1 ? '' : 's') . ' removida' . ($n === 1 ? '' : 's')
            . '. O torneio voltou para inscrições fechadas — gere a chave novamente quando estiver pronto.';
    }

    return arenagamer_api_message($response, $default);
}

/**
 * Indica se o torneio pode ser finalizado: todas as posições definidas,
 * ou seja, todas as partidas concluídas. Em mata-mata/fase de grupos exige uma final concluída.
 */
function arenagamer_matches_can_finalize($matchesData, $tournamentType = '', array $tournament = [])
{
    if (!is_array($matchesData) || empty($matchesData)) {
        return false;
    }

    $tournamentType = strtoupper(trim((string) $tournamentType));

    if ($tournamentType === 'SWISS') {
        return arenagamer_swiss_matches_can_finalize($matchesData, $tournament);
    }

    if ($tournamentType === 'DOUBLE_ELIMINATION') {
        return arenagamer_double_elimination_can_finalize($matchesData);
    }

    $finished = ['COMPLETED', 'WALKOVER'];
    $hasFinishedFinal = false;

    foreach ($matchesData as $m) {
        if (!in_array($m['status'] ?? '', $finished, true)) {
            return false;
        }
        if (($m['roundType'] ?? '') === 'FINAL' && in_array($m['status'] ?? '', $finished, true)) {
            $hasFinishedFinal = true;
        }
    }

    if (in_array($tournamentType, ['SINGLE_ELIMINATION', 'GROUP_STAGE', 'ROUND_ROBIN_ELIMINATION'], true)) {
        return $hasFinishedFinal;
    }

    return true;
}

/**
 * Rótulo amigável para a faixa de horário (timeWindow) de uma partida
 */
function arenagamer_time_window_label($window)
{
    $map = [
        'MORNING'   => 'Manhã',
        'AFTERNOON' => 'Tarde',
        'EVENING'   => 'Noite',
        'NIGHT'     => 'Madrugada',
    ];

    if (empty($window)) {
        return '';
    }

    return $map[$window] ?? $window;
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
 * Benefícios de participantes do plano ainda válidos (limite mensal de torneios não estourado).
 */
function arenagamer_plan_participant_benefits_active($plan)
{
    return is_array($plan) && !arenagamer_plan_tournament_limit_reached($plan);
}

/**
 * Teto de participantes enquanto os benefícios do plano estão ativos.
 * Acima disso só com preço padrão (após esgotar torneios inclusos no mês).
 *
 * @return int|null null = sem teto do plano
 */
function arenagamer_plan_participants_hard_limit($plan)
{
    if (!arenagamer_plan_participant_benefits_active($plan)) {
        return null;
    }

    $max = arenagamer_plan_free_max_participants($plan);

    return $max > 0 ? $max : null;
}

/**
 * Valida limite de participantes conforme o plano ativo.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_participants_for_plan($plan, $participantsLimit, $tournamentType = '', $teamsPerGroup = null)
{
    $hardLimit = arenagamer_plan_participants_hard_limit($plan);
    $limit = (int) $participantsLimit;
    $typeError = arenagamer_validate_tournament_participants_count_for_type(
        $limit,
        $tournamentType,
        'limite de participantes'
    );
    if ($typeError !== null) {
        return $typeError;
    }

    if ($hardLimit === null) {
        return null;
    }

    if ($limit > $hardLimit) {
        return 'Seu plano permite no máximo ' . $hardLimit . ' participantes por torneio. '
            . 'Reduza o limite ou aguarde o próximo período (torneios inclusos esgotados seguem o preço padrão).';
    }

    return null;
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
 * Mínimo de participantes ou equipes exigido para iniciar o torneio.
 */
function arenagamer_tournament_min_participants()
{
    return 4;
}

/**
 * Incremento permitido nos campos de limite/mínimo de participantes.
 */
function arenagamer_tournament_participants_step()
{
    return 2;
}

/**
 * Participantes por grupo em torneios de fase de grupos (fixo na API).
 */
function arenagamer_group_stage_teams_per_group()
{
    return 4;
}

/**
 * Classificados por grupo em torneios de fase de grupos (fixo na API).
 */
function arenagamer_group_stage_advance_per_group()
{
    return 2;
}

/**
 * Quantidade de grupos a partir do total de inscritos (limite ou inscritos reais).
 *
 * @return int 0 se o total não for múltiplo de participantes por grupo
 */
function arenagamer_group_stage_groups_count_from_participants($participantsCount)
{
    $perGroup = arenagamer_group_stage_teams_per_group();
    $total = (int) $participantsCount;

    if ($total < $perGroup || $total % $perGroup !== 0) {
        return 0;
    }

    return (int) ($total / $perGroup);
}

/**
 * Número de grupos do torneio (persistido ou estimado pelo limite de inscritos).
 */
function arenagamer_tournament_group_stage_groups_count(array $tournament)
{
    $groupsCount = (int) ($tournament['groupsCount'] ?? 0);
    if ($groupsCount > 0) {
        return $groupsCount;
    }

    $limit = (int) ($tournament['participantsLimit'] ?? 0);
    if ($limit > 0) {
        return arenagamer_group_stage_groups_count_from_participants($limit);
    }

    $participantCount = (int) ($tournament['participantCount'] ?? 0);
    if ($participantCount > 0) {
        return arenagamer_group_stage_groups_count_from_participants($participantCount);
    }

    return 0;
}

/**
 * Rótulo legível para a quantidade de grupos (ex.: "2 grupos").
 */
function arenagamer_group_stage_groups_count_label($groupsCount)
{
    $groupsCount = (int) $groupsCount;
    if ($groupsCount < 1) {
        return '';
    }

    return $groupsCount . ' grupo' . ($groupsCount === 1 ? '' : 's');
}

/**
 * Normaliza campos de fase de grupos para os valores fixos da API.
 */
function arenagamer_normalize_group_stage_tournament(array &$tournament)
{
    if (strtoupper(trim((string) ($tournament['type'] ?? ''))) !== 'GROUP_STAGE') {
        return;
    }

    $tournament['teamsPerGroup'] = arenagamer_group_stage_teams_per_group();
    $tournament['advancePerGroup'] = arenagamer_group_stage_advance_per_group();
}

/**
 * Ajusta um valor ao degrau permitido (ex.: 4, 6, 8…).
 */
function arenagamer_snap_tournament_participants_count($value, $max = null, $floor = null)
{
    $step = arenagamer_tournament_participants_step();
    $floor = $floor !== null ? max(2, (int) $floor) : arenagamer_tournament_min_participants();
    $value = (int) $value;

    if ($value < $floor) {
        $value = $floor;
    }

    $remainder = ($value - $floor) % $step;
    if ($remainder !== 0) {
        $value -= $remainder;
        if ($value < $floor) {
            $value = $floor;
        }
    }

    $max = $max !== null ? (int) $max : 0;
    if ($max > 0 && $value > $max) {
        $value = $max - (($max - $floor) % $step);
        if ($value < $floor) {
            $value = $floor;
        }
    }

    return $value;
}

/**
 * Valida se o valor segue o degrau permitido.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_participants_step($value, $label = 'limite')
{
    $floor = arenagamer_tournament_min_participants();
    $step = arenagamer_tournament_participants_step();
    $value = (int) $value;

    if ($value < $floor) {
        return 'O ' . $label . ' deve ser pelo menos ' . $floor . '.';
    }

    if (($value - $floor) % $step !== 0) {
        return 'O ' . $label . ' deve aumentar de ' . $step . ' em ' . $step
            . ' (ex.: ' . $floor . ', ' . ($floor + $step) . ', ' . ($floor + ($step * 2)) . '…).';
    }

    return null;
}

/**
 * Indica se o tipo de torneio usa chave eliminatória (potência de 2).
 */
function arenagamer_is_elimination_tournament_type($type)
{
    $type = strtoupper(trim((string) $type));

    return in_array($type, ['SINGLE_ELIMINATION', 'DOUBLE_ELIMINATION'], true);
}

/**
 * Ajusta um valor à potência de 2 mais próxima (para baixo), respeitando piso e teto.
 */
function arenagamer_snap_tournament_participants_power_of_two($value, $max = null, $floor = null)
{
    $floor = $floor !== null ? max(2, (int) $floor) : arenagamer_tournament_min_participants();
    $value = (int) $value;

    if ($value < $floor) {
        $value = $floor;
    }

    if (!arenagamer_is_power_of_two($value)) {
        $value = arenagamer_largest_power_of_two_up_to($value);
        if ($value < $floor) {
            $power = 2;
            while ($power < $floor) {
                $power *= 2;
            }
            $value = $power;
        }
    }

    $max = $max !== null ? (int) $max : 0;
    if ($max > 0 && $value > $max) {
        $value = arenagamer_largest_power_of_two_up_to($max);
        if ($value < $floor) {
            $value = $floor;
        }
    }

    return $value;
}

/**
 * Valida limite/mínimo conforme o tipo de torneio (potência de 2 ou degrau fixo).
 *
 * @return string|null
 */
function arenagamer_validate_tournament_participants_count_for_type($value, $type, $label = 'limite de participantes')
{
    $floor = arenagamer_tournament_min_participants();
    $value = (int) $value;

    if ($value < $floor) {
        return 'O ' . $label . ' deve ser pelo menos ' . $floor . '.';
    }

    if (arenagamer_is_elimination_tournament_type($type)) {
        if (!arenagamer_is_power_of_two($value)) {
            return 'Na eliminação, o ' . $label . ' deve ser 4, 8, 16, 32…';
        }

        return null;
    }

    if (strtoupper(trim((string) $type)) === 'GROUP_STAGE') {
        $perGroup = arenagamer_group_stage_teams_per_group();
        if ($value % $perGroup !== 0) {
            return 'Na fase de grupos, o ' . $label . ' deve ser múltiplo de ' . $perGroup . ' (ex.: 4, 8, 12, 16…).';
        }

        return null;
    }

    return arenagamer_validate_tournament_participants_step($value, $label);
}

/**
 * Opções válidas para o limite de participantes (espelha a regra do formulário).
 *
 * @return array<int,int>|null null = entrada livre
 */
function arenagamer_tournament_participant_limit_options(array $payload, $planMax = null)
{
    $type = strtoupper(trim((string) ($payload['type'] ?? '')));
    $min = arenagamer_tournament_min_participants();
    $max = $planMax !== null ? (int) $planMax : 0;

    if ($type === 'GROUP_STAGE') {
        $perGroup = arenagamer_group_stage_teams_per_group();
        $groupMax = $max > 0 ? $max : 512;
        $values = [];
        for ($v = $perGroup; $v <= $groupMax; $v += $perGroup) {
            if ($v >= $min) {
                $values[] = $v;
            }
        }

        return $values ?: [$perGroup];
    }

    if ($max > 0) {
        if (in_array($type, ['SINGLE_ELIMINATION', 'DOUBLE_ELIMINATION'], true)) {
            $values = [];
            for ($power = 2; $power <= $max; $power *= 2) {
                if ($power >= $min) {
                    $values[] = $power;
                }
            }

            return $values ?: [max($min, 2)];
        }

        $values = [];
        for ($v = $min; $v <= $max; $v += $min) {
            $values[] = $v;
        }

        return $values ?: [$min];
    }

    if ($type === 'GROUP_STAGE') {
        $perGroup = arenagamer_group_stage_teams_per_group();
        $values = [];
        for ($v = $perGroup; $v <= 512; $v += $perGroup) {
            if ($v >= $min) {
                $values[] = $v;
            }
        }

        return $values;
    }

    if (in_array($type, ['SINGLE_ELIMINATION', 'DOUBLE_ELIMINATION'], true)) {
        $values = [];
        for ($power = 2; $power <= 512; $power *= 2) {
            if ($power >= $min) {
                $values[] = $power;
            }
        }

        return $values;
    }

    return null;
}

/**
 * Opções válidas para o mínimo de participantes (≤ limite).
 *
 * @param array<int,int>|null $limitOptions
 * @return array<int,int>
 */
function arenagamer_tournament_min_participant_options($limitOptions, $participantsLimit)
{
    $floor = arenagamer_tournament_min_participants();
    $limit = max(0, (int) $participantsLimit);

    if (is_array($limitOptions) && !empty($limitOptions)) {
        $values = array_values(array_filter($limitOptions, static function ($value) use ($limit, $floor) {
            $value = (int) $value;

            return $value >= $floor && ($limit <= 0 || $value <= $limit);
        }));

        return $values ?: [$floor];
    }

    if ($limit < $floor) {
        return [$floor];
    }

    $values = [];
    for ($v = $floor; $v <= $limit; $v += $floor) {
        $values[] = $v;
    }

    return $values ?: [$floor];
}

/**
 * Valida mínimo de participantes em relação ao limite e às opções permitidas.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_min_participants_settings(array $payload, $plan = null)
{
    $min = (int) ($payload['minParticipants'] ?? 0);
    $limit = (int) ($payload['participantsLimit'] ?? 0);
    $floor = arenagamer_tournament_min_participants();

    if ($min < $floor) {
        return 'O mínimo de participantes deve ser pelo menos ' . $floor . '.';
    }

    if ($limit > 0 && $min > $limit) {
        return 'O mínimo de participantes não pode ser maior que o limite (' . $limit . ').';
    }

    $type = strtoupper(trim((string) ($payload['type'] ?? '')));
    $minTypeError = arenagamer_validate_tournament_participants_count_for_type(
        $min,
        $type,
        'mínimo de participantes'
    );
    if ($minTypeError !== null) {
        return $minTypeError;
    }

    return null;
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

    $participantsError = arenagamer_validate_tournament_participants_for_plan(
        $plan,
        $participantsLimit,
        $prizeOptions['type'] ?? '',
        $prizeOptions['teamsPerGroup'] ?? null
    );
    if ($participantsError !== null) {
        return $participantsError;
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
 * Valida configuração de fase de grupos antes de enviar à API.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_group_stage_settings(array $payload)
{
    if (strtoupper(trim((string) ($payload['type'] ?? ''))) !== 'GROUP_STAGE') {
        return null;
    }

    $teamsPerGroup = arenagamer_group_stage_teams_per_group();
    $participantsLimit = $payload['participantsLimit'] ?? null;
    if ($participantsLimit !== null && (int) $participantsLimit > 0) {
        if ((int) $participantsLimit % $teamsPerGroup !== 0) {
            $remainder = (int) $participantsLimit % $teamsPerGroup;
            $add = $teamsPerGroup - $remainder;
            $remove = $remainder;

            return 'O limite de participantes (' . (int) $participantsLimit . ') deve ser múltiplo de '
                . $teamsPerGroup . ' (participantes por grupo). '
                . 'Escolha ' . ((int) $participantsLimit + $add) . ' ou ' . ((int) $participantsLimit - $remove) . '.';
        }
    }

    return null;
}

/**
 * Texto resumido da configuração de fase de grupos para exibição.
 *
 * @return string|null
 */
function arenagamer_tournament_group_stage_summary(array $tournament)
{
    if (strtoupper(trim((string) ($tournament['type'] ?? ''))) !== 'GROUP_STAGE') {
        return null;
    }

    $teamsPerGroup = arenagamer_group_stage_teams_per_group();
    $advancePerGroup = arenagamer_group_stage_advance_per_group();
    $groupsCount = arenagamer_tournament_group_stage_groups_count($tournament);
    $isTeam = ($tournament['format'] ?? '') === 'TEAM';
    $unit = $isTeam ? 'equipes' : 'participantes';

    $summary = $teamsPerGroup . ' ' . $unit . ' por grupo, ' . $advancePerGroup . ' classificam por grupo';
    if ($groupsCount > 0) {
        $summary .= ', ' . arenagamer_group_stage_groups_count_label($groupsCount);
    }

    return $summary;
}

/**
 * Dica exibida antes de gerar chaves em torneios com fase de grupos.
 *
 * @return string|null
 */
function arenagamer_tournament_bracket_group_stage_hint(array $tournament)
{
    if (strtoupper(trim((string) ($tournament['type'] ?? ''))) !== 'GROUP_STAGE') {
        return null;
    }

    $teamsPerGroup = arenagamer_group_stage_teams_per_group();
    $isTeam = ($tournament['format'] ?? '') === 'TEAM';
    $unit = $isTeam ? 'equipes inscritas' : 'inscritos';

    return 'O total de ' . $unit . ' deve ser múltiplo de ' . $teamsPerGroup
        . ' (participantes por grupo). O número de grupos será calculado automaticamente.';
}

/**
 * Valida configuração de pontos corridos + eliminatória antes de enviar à API.
 *
 * @return string|null
 */
function arenagamer_validate_tournament_round_robin_elimination_settings(array $payload)
{
    if (strtoupper(trim((string) ($payload['type'] ?? ''))) !== 'ROUND_ROBIN_ELIMINATION') {
        return null;
    }

    $advanceToKnockout = $payload['advanceToKnockout'] ?? null;
    $participantsLimit = (int) ($payload['participantsLimit'] ?? 0);

    if ($advanceToKnockout === null || (int) $advanceToKnockout < 2) {
        return 'Informe quantos classificam para o mata-mata (mínimo 2).';
    }

    $advanceToKnockout = (int) $advanceToKnockout;

    if (!arenagamer_is_power_of_two($advanceToKnockout)) {
        return 'Os classificados para o mata-mata devem ser 2, 4, 8, 16…';
    }

    if ($participantsLimit > 0 && $advanceToKnockout >= $participantsLimit) {
        return 'Os classificados para o mata-mata devem ser menores que o total de inscritos ('
            . $participantsLimit . ').';
    }

    return null;
}

/**
 * Texto resumido da configuração pontos corridos + eliminatória.
 *
 * @return string|null
 */
function arenagamer_tournament_round_robin_elimination_summary(array $tournament)
{
    if (strtoupper(trim((string) ($tournament['type'] ?? ''))) !== 'ROUND_ROBIN_ELIMINATION') {
        return null;
    }

    $advanceToKnockout = (int) ($tournament['advanceToKnockout'] ?? 0);
    if ($advanceToKnockout < 2) {
        return null;
    }

    $isTeam = ($tournament['format'] ?? '') === 'TEAM';
    $unit = $isTeam ? 'equipes' : 'participantes';

    return 'Pontos corridos (todos contra todos) — ' . $advanceToKnockout . ' ' . $unit
        . ' classificam para o mata-mata';
}

/**
 * Dica exibida antes de gerar chaves em torneios pontos corridos + eliminatória.
 *
 * @return string|null
 */
function arenagamer_tournament_bracket_round_robin_elimination_hint(array $tournament)
{
    if (strtoupper(trim((string) ($tournament['type'] ?? ''))) !== 'ROUND_ROBIN_ELIMINATION') {
        return null;
    }

    $advanceToKnockout = (int) ($tournament['advanceToKnockout'] ?? 0);
    if ($advanceToKnockout < 2) {
        return null;
    }

    return 'Será gerada apenas a fase de pontos corridos. Após todos os resultados, use Gerar mata-mata '
        . 'para levar os ' . $advanceToKnockout . ' melhores colocados à eliminatória.';
}

/**
 * Dica unificada antes de gerar chaves (grupos ou pontos corridos + eliminatória).
 *
 * @return string|null
 */
function arenagamer_tournament_bracket_hint(array $tournament)
{
    $groupHint = arenagamer_tournament_bracket_group_stage_hint($tournament);
    if ($groupHint !== null) {
        return $groupHint;
    }

    $swissHint = arenagamer_tournament_bracket_swiss_hint($tournament);
    if ($swissHint !== null) {
        return $swissHint;
    }

    $doubleElimHint = arenagamer_tournament_bracket_double_elimination_hint($tournament);
    if ($doubleElimHint !== null) {
        return $doubleElimHint;
    }

    return arenagamer_tournament_bracket_round_robin_elimination_hint($tournament);
}

/**
 * Mensagem de confirmação ao gerar o mata-mata.
 */
function arenagamer_generate_knockout_confirm_message(array $tournament)
{
    $type = strtoupper(trim((string) ($tournament['type'] ?? '')));

    if ($type === 'ROUND_ROBIN_ELIMINATION') {
        $n = (int) ($tournament['advanceToKnockout'] ?? 0);

        return 'Gerar o mata-mata com os ' . ($n > 0 ? $n : 'melhores')
            . ' colocados da tabela de pontos corridos?';
    }

    if ($type === 'GROUP_STAGE') {
        return 'Gerar o mata-mata com cruzamento inteligente entre grupos (evita confrontos do mesmo grupo na 1ª rodada)?';
    }

    return 'Gerar o mata-mata?';
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
        'minParticipants'      => max(arenagamer_tournament_min_participants(), (int) ($input->post('min_participants') ?: arenagamer_tournament_min_participants())),
        'presetId'             => $resolvedPresetId,
        'entryFeeCredits'      => (float) str_replace(',', '.', (string) $input->post('entry_fee_credits')),
        'feePercentage'        => (float) str_replace(',', '.', (string) $input->post('fee_percentage')),
        'prizeType'            => strtoupper(trim((string) ($input->post('prize_type') ?: 'MANUAL'))),
        'prizeFunding'         => strtoupper(trim((string) ($input->post('prize_funding') ?: 'FIXED'))),
        'prizePool'            => (float) str_replace(',', '.', (string) $input->post('prize_pool')),
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
    } elseif (!empty($options['existing_tournament']['participantsLimit'])) {
        $payload['participantsLimit'] = (int) $options['existing_tournament']['participantsLimit'];
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

    $payload['teamsPerGroup'] = null;
    $payload['groupsCount'] = null;
    $payload['advancePerGroup'] = null;
    $payload['advanceToKnockout'] = null;
    if (($payload['type'] ?? '') === 'GROUP_STAGE') {
        $payload['teamsPerGroup'] = arenagamer_group_stage_teams_per_group();
        $payload['advancePerGroup'] = arenagamer_group_stage_advance_per_group();
        $groupStageError = arenagamer_validate_tournament_group_stage_settings($payload);
        if ($groupStageError !== null) {
            $payload['_group_stage_error'] = $groupStageError;
        }
    }
    if (($payload['type'] ?? '') === 'ROUND_ROBIN_ELIMINATION') {
        $payload['advanceToKnockout'] = arenagamer_post_optional_int($input, 'advance_to_knockout');
        $rrError = arenagamer_validate_tournament_round_robin_elimination_settings($payload);
        if ($rrError !== null) {
            $payload['_round_robin_elimination_error'] = $rrError;
        }
    }

    $minParticipantsError = arenagamer_validate_tournament_min_participants_settings(
        $payload,
        $options['plan'] ?? null
    );
    if ($minParticipantsError !== null) {
        $payload['_min_participants_error'] = $minParticipantsError;
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

    $existingTournament = is_array($options['existing_tournament'] ?? null) ? $options['existing_tournament'] : null;
    $tournamentSystems = is_array($options['tournament_systems'] ?? null) ? $options['tournament_systems'] : null;
    if ($tournamentSystems !== null) {
        $existingType = $isUpdate && $existingTournament !== null ? ($existingTournament['type'] ?? null) : null;
        $typeError = arenagamer_validate_tournament_system_type($payload['type'] ?? '', $tournamentSystems, $existingType);
        if ($typeError !== null) {
            $payload['_type_error'] = $typeError;
        }
    }

    if ($isUpdate && $existingTournament !== null) {
        $formatLockError = arenagamer_validate_tournament_format_locked_fields($existingTournament, $payload);
        if ($formatLockError !== null) {
            $payload['_format_lock_error'] = $formatLockError;
        }
        arenagamer_strip_locked_tournament_format_fields($payload, $existingTournament);
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

    if (!empty($payload['_format_lock_error'])) {
        return (string) $payload['_format_lock_error'];
    }

    if (!empty($payload['_type_error'])) {
        return (string) $payload['_type_error'];
    }

    if (!empty($payload['_group_stage_error'])) {
        return (string) $payload['_group_stage_error'];
    }

    if (!empty($payload['_round_robin_elimination_error'])) {
        return (string) $payload['_round_robin_elimination_error'];
    }

    if (!empty($payload['_min_participants_error'])) {
        return (string) $payload['_min_participants_error'];
    }

    return null;
}

/**
 * Remove chaves internas de erro do payload antes de enviar à API.
 */
function arenagamer_tournament_sanitize_payload(array $payload)
{
    unset(
        $payload['_upload_error'],
        $payload['_date_error'],
        $payload['_preset_error'],
        $payload['_game_name_error'],
        $payload['_prize_error'],
        $payload['_format_lock_error'],
        $payload['_type_error'],
        $payload['_group_stage_error'],
        $payload['_round_robin_elimination_error'],
        $payload['_min_participants_error']
    );

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
 * Partida aguardando registro de resultado (ambos participantes definidos e não finalizada).
 */
function arenagamer_match_needs_result(array $match)
{
    $homeId = $match['homeParticipantId'] ?? null;
    $awayId = $match['awayParticipantId'] ?? null;
    $matchStatus = $match['status'] ?? '';
    $bothDefined = !empty($homeId) && !empty($awayId);
    $isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);

    return $bothDefined && !$isFinished;
}

/**
 * Partidas pendentes de resultado, ordenadas por grupo → rodada → número.
 *
 * @return array<int,array>
 */
function arenagamer_collect_pending_result_matches($matches)
{
    if (!is_array($matches)) {
        return [];
    }

    $pending = [];
    foreach ($matches as $match) {
        if (is_array($match) && arenagamer_match_needs_result($match)) {
            $pending[] = $match;
        }
    }

    usort($pending, function ($a, $b) {
        $groupOrder = arenagamer_match_group_number($a) <=> arenagamer_match_group_number($b);
        if ($groupOrder !== 0) {
            return $groupOrder;
        }

        $roundOrder = (int) ($a['roundNumber'] ?? 0) <=> (int) ($b['roundNumber'] ?? 0);
        if ($roundOrder !== 0) {
            return $roundOrder;
        }

        return (int) ($a['matchNumber'] ?? 0) <=> (int) ($b['matchNumber'] ?? 0);
    });

    return $pending;
}

/**
 * Rótulo contextual da partida no assistente de resultados.
 */
function arenagamer_match_result_context_label(array $match)
{
    if (arenagamer_is_group_stage_match($match)) {
        $groupNumber = arenagamer_match_group_number($match);

        return $groupNumber > 0 ? 'Grupo ' . $groupNumber : 'Fase de grupos';
    }

    if (arenagamer_is_round_robin_match($match)) {
        return 'Pontos corridos';
    }

    if (arenagamer_is_swiss_phase_match($match)) {
        $roundNumber = (int) ($match['roundNumber'] ?? 0);

        return $roundNumber > 0 ? 'Rodada suíça ' . $roundNumber : 'Sistema suíço';
    }

    if (arenagamer_is_knockout_match($match) || arenagamer_match_bracket_side($match) !== null) {
        $roundNumber = (int) ($match['roundNumber'] ?? 0);

        return arenagamer_knockout_round_label(
            $match['roundType'] ?? '',
            $match['phaseLabel'] ?? null,
            $roundNumber > 0 ? $roundNumber : null
        );
    }

    if (!empty($match['phaseLabel'])) {
        return (string) $match['phaseLabel'];
    }

    return 'Partida #' . (int) ($match['matchNumber'] ?? 0);
}

/**
 * Payload JSON para o assistente em lote de resultados.
 *
 * @return array<int,array<string,mixed>>
 */
function arenagamer_match_result_wizard_items($matches)
{
    $items = [];

    foreach (arenagamer_collect_pending_result_matches($matches) as $match) {
        $homeId = (int) ($match['homeParticipantId'] ?? 0);
        $awayId = (int) ($match['awayParticipantId'] ?? 0);
        $homeName = trim((string) ($match['homeParticipantName'] ?? ''));
        $awayName = trim((string) ($match['awayParticipantName'] ?? ''));

        if ($homeName === '') {
            $homeName = 'Casa';
        }
        if ($awayName === '') {
            $awayName = 'Visitante';
        }

        $items[] = [
            'id'          => (int) ($match['id'] ?? 0),
            'matchNumber' => (int) ($match['matchNumber'] ?? 0),
            'context'     => arenagamer_match_result_context_label($match),
            'homeId'      => $homeId,
            'awayId'      => $awayId,
            'homeName'    => $homeName,
            'awayName'    => $awayName,
            'scheduledAt' => !empty($match['scheduledAt'])
                ? arenagamer_format_date($match['scheduledAt'], 'd/m/Y H:i')
                : '',
        ];
    }

    return $items;
}

/**
 * Resposta JSON padronizada (Perfex / CodeIgniter).
 */
function arenagamer_json_response(array $payload, $statusCode = 200)
{
    $CI = &get_instance();
    $CI->output
        ->set_status_header((int) $statusCode)
        ->set_content_type('application/json', 'utf-8')
        ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    $CI->output->_display();
    exit;
}

/**
 * Indica se a requisição espera JSON (assistente em lote / AJAX).
 */
function arenagamer_request_wants_json()
{
    $CI = &get_instance();

    return $CI->input->is_ajax_request() || $CI->input->post('ajax') === '1';
}

/**
 * Processa POST de registro de resultado de partida.
 *
 * @param ArenaGamer_api $api
 * @return array{success:bool,message:string,slug:string}
 */
function arenagamer_process_record_match_result($api, $matchId, $input, $canManageCallback = null)
{
    $slug = (string) $input->post('slug');
    $winnerParticipantId = (int) $input->post('winner_participant_id');
    $homeScore = $input->post('home_score');
    $awayScore = $input->post('away_score');

    if (!$matchId || $slug === '') {
        return [
            'success' => false,
            'message' => 'Dados inválidos para registrar o resultado',
            'slug'    => $slug,
        ];
    }

    if ($homeScore === '' || $homeScore === null || $awayScore === '' || $awayScore === null) {
        return [
            'success' => false,
            'message' => 'Informe o placar da partida',
            'slug'    => $slug,
        ];
    }

    $canManage = is_callable($canManageCallback)
        ? (bool) call_user_func($canManageCallback, $slug)
        : (bool) $api->can_manage_tournament($slug);

    if (!$canManage) {
        return [
            'success' => false,
            'message' => 'Sem permissão para gerenciar este torneio',
            'slug'    => $slug,
        ];
    }

    $proofUrl = '';
    $proofUpload = arenagamer_handle_match_proof_upload('proof_file');
    if (is_array($proofUpload) && isset($proofUpload['error'])) {
        return [
            'success' => false,
            'message' => 'Erro no upload do comprovante: ' . $proofUpload['error'],
            'slug'    => $slug,
        ];
    }
    if (is_string($proofUpload)) {
        $proofUrl = $proofUpload;
    }

    $result = $api->record_match_result($matchId, [
        'winnerParticipantId' => $winnerParticipantId,
        'homeScore'           => $homeScore,
        'awayScore'           => $awayScore,
        'proofUrl'            => $proofUrl,
    ]);

    if (arenagamer_api_is_success($result)) {
        return [
            'success' => true,
            'message' => arenagamer_api_message($result, 'Resultado registrado com sucesso'),
            'slug'    => $slug,
        ];
    }

    return [
        'success' => false,
        'message' => 'Erro: ' . $api->get_last_error(),
        'slug'    => $slug,
    ];
}

/**
 * Finaliza registro de resultado: redirect ou JSON conforme a requisição.
 *
 * @param ArenaGamer_api $api
 */
function arenagamer_finish_record_match_result($api, $matchId, $input, $redirectUrl, $canManageCallback = null, $onSuccess = null, $onFailure = null)
{
    $outcome = arenagamer_process_record_match_result($api, $matchId, $input, $canManageCallback);

    if (arenagamer_request_wants_json()) {
        $CI = &get_instance();
        $payload = [
            'success'  => $outcome['success'],
            'message'  => $outcome['message'],
            'csrfHash' => $CI->security->get_csrf_hash(),
        ];
        arenagamer_json_response($payload, $outcome['success'] ? 200 : 422);

        return $outcome;
    }

    if ($outcome['success']) {
        set_alert('success', $outcome['message']);
        if (is_callable($onSuccess)) {
            call_user_func($onSuccess, $matchId, $outcome);
        }
    } else {
        set_alert('danger', $outcome['message']);
        if (is_callable($onFailure)) {
            call_user_func($onFailure, $matchId, $outcome);
        }
    }

    redirect($redirectUrl($outcome['slug']));

    return $outcome;
}

/**
 * Script JS dos modais de resultado: vencedor automático pelo placar + botão "Trapacear".
 */
function arenagamer_result_modal_script()
{
    return <<<'HTML'
<script>
(function () {
    function updateAutoWinner(modal) {
        var homeInput = modal.querySelector('input[name="home_score"]');
        var awayInput = modal.querySelector('input[name="away_score"]');
        var display = modal.querySelector('.ag-auto-winner');
        if (!homeInput || !awayInput || !display) { return; }
        var home = parseInt(homeInput.value, 10);
        var away = parseInt(awayInput.value, 10);
        var homeName = modal.getAttribute('data-home-name') || 'Casa';
        var awayName = modal.getAttribute('data-away-name') || 'Visitante';
        if (isNaN(home) || isNaN(away)) {
            display.textContent = 'defina o placar';
        } else if (home > away) {
            display.textContent = homeName;
        } else if (away > home) {
            display.textContent = awayName;
        } else {
            display.textContent = 'Empate — use Trapacear para escolher';
        }
    }

    document.addEventListener('input', function (e) {
        if (!e.target.classList || !e.target.classList.contains('ag-score')) { return; }
        var modal = e.target.closest('.ag-result-modal');
        if (modal) { updateAutoWinner(modal); }
    });

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest ? e.target.closest('.ag-cheat-toggle') : null;
        if (!toggle) { return; }
        var modal = toggle.closest('.ag-result-modal');
        if (!modal) { return; }
        var block = modal.querySelector('.ag-cheat-block');
        if (!block) { return; }
        if (block.style.display === 'none' || block.style.display === '') {
            block.style.display = 'block';
        } else {
            block.style.display = 'none';
            modal.querySelectorAll('.ag-cheat-block input[type="radio"]').forEach(function (r) { r.checked = false; });
        }
    });
})();
</script>
HTML;
}

/**
 * Diretório de upload dos comprovantes de resultado das partidas.
 *
 * @return array{abs:string,rel:string,url:string}
 */
function arenagamer_match_proof_upload_dir()
{
    $rel = 'modules/arenagamer/uploads/match_proofs/';
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
 * Faz upload da imagem de comprovante do resultado (campo multipart).
 *
 * @return string|array{error:string}|null URL pública, erro ou null quando não há arquivo
 */
function arenagamer_handle_match_proof_upload($fieldName = 'proof_file')
{
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }

    $CI = &get_instance();
    $CI->load->library('upload');
    $dir = arenagamer_match_proof_upload_dir();
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
 * URL do stylesheet do módulo (com cache bust).
 */
function arenagamer_stylesheet_url()
{
    $file = defined('ARENAGAMER_MODULE_PATH')
        ? ARENAGAMER_MODULE_PATH . 'assets/css/arenagamer.css'
        : '';
    $ver = ($file !== '' && is_file($file)) ? filemtime($file) : time();

    return module_dir_url(ARENAGAMER_MODULE_NAME, 'assets/css/arenagamer.css') . '?v=' . $ver;
}

/**
 * Emite o link do CSS uma única vez por request.
 */
function arenagamer_enqueue_stylesheet()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    echo '<link rel="stylesheet" href="' . htmlspecialchars(arenagamer_stylesheet_url()) . '">' . "\n";
}

/**
 * Rota atual é a área do cliente ArenaGamer (/arenagamer/...).
 */
function arenagamer_is_client_route()
{
    $CI = &get_instance();

    return strtolower((string) $CI->uri->segment(1)) === 'arenagamer';
}

/**
 * Rota atual é o painel staff ArenaGamer (/admin/arenagamer/...).
 */
function arenagamer_is_admin_route()
{
    $CI = &get_instance();

    return strtolower((string) $CI->uri->segment(2)) === 'arenagamer';
}

/**
 * Nome curto para exibição (IDs longos da API).
 */
function arenagamer_participant_display_label($name, $maxLength = 22)
{
    $name = trim((string) $name);
    if ($name === '') {
        return '—';
    }
    if (function_exists('mb_strlen') && mb_strlen($name) > $maxLength) {
        return mb_substr($name, 0, $maxLength - 1) . '…';
    }
    if (strlen($name) > $maxLength) {
        return substr($name, 0, $maxLength - 1) . '…';
    }

    return $name;
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
