<?php

defined('BASEPATH') or exit('No direct script access allowed');

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
        'IN_PROGRESS'         => '<span class="label label-info">Em Andamento</span>',
        'COMPLETED'           => '<span class="label label-primary">Concluída</span>',
        'WALKOVER'            => '<span class="label label-warning">W.O.</span>',
        'RESCHEDULED'         => '<span class="label label-warning">Reagendada</span>',
        'CANCELLED'           => '<span class="label label-danger">Cancelada</span>',
        'PENDING'             => '<span class="label label-default">Pendente</span>',
    ];

    return isset($badges[$status]) ? $badges[$status] : '<span class="label label-default">' . htmlspecialchars($status) . '</span>';
}

/**
 * Format ArenaGamer date
 */
function arenagamer_format_date($date, $format = 'd/m/Y H:i')
{
    if (empty($date)) return '—';
    return date($format, strtotime($date));
}

/**
 * Format credits amount
 */
function arenagamer_format_credits($amount)
{
    return number_format((float)$amount, 2, ',', '.') . ' créditos';
}
