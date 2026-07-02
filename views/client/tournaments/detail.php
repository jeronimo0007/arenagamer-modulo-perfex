<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = isset($tournament['data']) ? $tournament['data'] : null;
$canManage = !empty($can_manage);
$canGrantPermissions = !empty($can_grant_permissions);
$permissionRows = is_array($permission_rows ?? null) ? $permission_rows : [];
$grantableContacts = is_array($grantable_contacts ?? null) ? $grantable_contacts : [];
$showPermissionsPanel = $canManage && !empty($t['clientUserId']);
?>
<?php if (!$t): ?>
<div class="alert alert-danger">Torneio não encontrado.</div>
<?php else: ?>
<?php
$allMatches = is_array($all_matches ?? null) ? $all_matches : ($matches['data'] ?? []);
$matchesPagination = is_array($matches['pagination'] ?? null) ? $matches['pagination'] : [];
$matchesBaseUrl = arenagamer_client_url('tournament_detail/' . $t['slug']);
$matchFilters = is_array($match_filters ?? null) ? $match_filters : [];
$matchView = (string) ($match_view ?? 'pending');
$knockoutBracket = arenagamer_build_knockout_bracket($allMatches);
$isDoubleElimination = arenagamer_is_double_elimination_tournament_type($t['type'] ?? '');
$doubleEliminationBrackets = $isDoubleElimination
    ? arenagamer_build_double_elimination_brackets($allMatches)
    : null;
$hasDoubleEliminationBrackets = $isDoubleElimination && !empty($doubleEliminationBrackets['has_brackets']);
$standingsData = arenagamer_api_data($standings ?? null, []);
$showClassification = arenagamer_tournament_shows_classification($t);
$groupStageBlocks = (($t['type'] ?? '') === 'GROUP_STAGE')
    ? arenagamer_build_group_stage_blocks($standingsData, $allMatches)
    : [];
$participantStandingMap = $showClassification
    ? arenagamer_build_participant_standing_map($standingsData)
    : [];
$finalTop3 = arenagamer_build_final_top3_list($standingsData, $t['status'] ?? '', $allMatches);
$isRoundRobinElimination = ($t['type'] ?? '') === 'ROUND_ROBIN_ELIMINATION';
$isSwiss = ($t['type'] ?? '') === 'SWISS';
$hasRoundRobinMatches = false;
$hasSwissMatches = false;
if ($isRoundRobinElimination) {
    foreach ($allMatches as $m) {
        if (is_array($m) && arenagamer_is_league_phase_match($m, 'ROUND_ROBIN_ELIMINATION')) {
            $hasRoundRobinMatches = true;
            break;
        }
    }
}
if ($isSwiss) {
    foreach ($allMatches as $m) {
        if (is_array($m) && arenagamer_is_swiss_phase_match($m)) {
            $hasSwissMatches = true;
            break;
        }
    }
}
$knockoutConfirmMessage = htmlspecialchars(arenagamer_generate_knockout_confirm_message($t), ENT_QUOTES, 'UTF-8');
$advanceRoundConfirmMessage = htmlspecialchars(arenagamer_advance_round_confirm_message($t), ENT_QUOTES, 'UTF-8');
$advanceRoundButtonLabel = arenagamer_advance_round_button_label($t);
?>
<div class="panel_s">
    <div class="panel-body ag-tournament-detail">
        <div class="ag-tournament-detail__header">
            <a href="<?php echo arenagamer_client_url('tournaments'); ?>" class="ag-tournament-detail__back">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
            <h4 class="ag-tournament-detail__title tw-font-semibold">
                <?php echo htmlspecialchars($t['name']); ?>
                <?php echo arenagamer_status_badge($t['status']); ?>
                <?php echo arenagamer_tournament_client_badge($t, $client_name ?? null); ?>
            </h4>
        </div>

        <?php
        $coverImage = (string) ($t['coverImageUrl'] ?? '');
        $gameImage = arenagamer_tournament_game_image_url($t);
        $logoImage = arenagamer_tournament_logo_image_url($t);
        ?>
        <?php if ($coverImage !== ''): ?>
        <div class="ag-tournament-detail__cover mbot15" style="background-image:url('<?php echo htmlspecialchars($coverImage); ?>');"></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <?php if ($logoImage !== '' || $gameImage !== ''): ?>
                <div class="ag-tournament-detail__media mbot15">
                    <?php if ($logoImage !== ''): ?>
                    <img src="<?php echo htmlspecialchars($logoImage); ?>" alt="Imagem do torneio" class="ag-tournament-detail__logo">
                    <?php endif; ?>
                    <?php if ($gameImage !== ''): ?>
                    <img src="<?php echo htmlspecialchars($gameImage); ?>" alt="Imagem do jogo" class="ag-tournament-detail__game">
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php
                $groupStageSummary = arenagamer_tournament_group_stage_summary($t);
                $roundRobinEliminationSummary = arenagamer_tournament_round_robin_elimination_summary($t);
                $swissSummary = arenagamer_tournament_swiss_summary($t);
                ?>
                <div class="ag-tournament-detail__meta">
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-sitemap"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Modo</span>
                            <span class="ag-tournament-detail__meta-value">
                                <?php echo htmlspecialchars(arenagamer_tournament_type_label($t['type'] ?? '')); ?>
                                · <?php echo htmlspecialchars(arenagamer_tournament_format_label($t['format'] ?? '')); ?>
                            </span>
                        </div>
                    </div>
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-gamepad"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Jogo</span>
                            <span class="ag-tournament-detail__meta-value"><?php echo htmlspecialchars(arenagamer_tournament_game_name($t)); ?></span>
                        </div>
                    </div>
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-user"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Organizador</span>
                            <span class="ag-tournament-detail__meta-value">
                                <?php echo htmlspecialchars($t['ownerName'] ?? '—'); ?>
                                <?php echo arenagamer_owner_type_badge($t['ownerType'] ?? ''); ?>
                            </span>
                        </div>
                    </div>
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-users"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Inscritos</span>
                            <span class="ag-tournament-detail__meta-value">
                                <?php echo (int) ($t['participantCount'] ?? 0); ?> / <?php echo (int) ($t['participantsLimit'] ?? 0); ?>
                                <?php if (($t['format'] ?? '') === 'TEAM'): ?>
                                <span class="text-muted">equipes</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php if (($t['format'] ?? '') === 'TEAM' && (!empty($t['minPlayersPerTeam']) || !empty($t['maxPlayersPerTeam']))): ?>
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-id-badge"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Jogadores por equipe</span>
                            <span class="ag-tournament-detail__meta-value">
                                <?php if (!empty($t['minPlayersPerTeam'])): ?>
                                mín. <?php echo (int) $t['minPlayersPerTeam']; ?>
                                <?php endif; ?>
                                <?php if (!empty($t['minPlayersPerTeam']) && !empty($t['maxPlayersPerTeam'])): ?>
                                —
                                <?php endif; ?>
                                <?php if (!empty($t['maxPlayersPerTeam'])): ?>
                                máx. <?php echo (int) $t['maxPlayersPerTeam']; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($groupStageSummary !== null): ?>
                    <div class="ag-tournament-detail__meta-item ag-tournament-detail__meta-item--wide">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-th-large"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Fase de grupos</span>
                            <span class="ag-tournament-detail__meta-value"><?php echo htmlspecialchars($groupStageSummary); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($roundRobinEliminationSummary !== null): ?>
                    <div class="ag-tournament-detail__meta-item ag-tournament-detail__meta-item--wide">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-refresh"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Formato</span>
                            <span class="ag-tournament-detail__meta-value"><?php echo htmlspecialchars($roundRobinEliminationSummary); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($swissSummary !== null): ?>
                    <div class="ag-tournament-detail__meta-item ag-tournament-detail__meta-item--wide">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-random"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Sistema suíço</span>
                            <span class="ag-tournament-detail__meta-value"><?php echo htmlspecialchars($swissSummary); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($t['registrationOpensAt'])): ?>
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-calendar-plus-o"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Abertura das inscrições</span>
                            <span class="ag-tournament-detail__meta-value"><?php echo arenagamer_format_date($t['registrationOpensAt'], 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($t['expectedEndDate'])): ?>
                    <div class="ag-tournament-detail__meta-item">
                        <span class="ag-tournament-detail__meta-icon"><i class="fa fa-calendar-check-o"></i></span>
                        <div class="ag-tournament-detail__meta-body">
                            <span class="ag-tournament-detail__meta-label">Término previsto</span>
                            <span class="ag-tournament-detail__meta-value"><?php echo arenagamer_format_date($t['expectedEndDate'], 'd/m/Y H:i'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_tournament_type_how_it_works_panel', [
                    'tournament' => $t,
                ]); ?>

                <?php if (!empty($t['youtubeUrl']) || !empty($t['twitchUrl'])): ?>
                <div class="ag-tournament-detail__streams mbot15">
                    <?php if (!empty($t['youtubeUrl'])): ?>
                    <a href="<?php echo htmlspecialchars($t['youtubeUrl']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-sm">
                        <i class="fa fa-youtube-play"></i> YouTube
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($t['twitchUrl'])): ?>
                    <a href="<?php echo htmlspecialchars($t['twitchUrl']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-sm">
                        <i class="fa fa-twitch"></i> Twitch
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($t['description'])): ?>
                <div class="ag-tournament-detail__description">
                    <p><?php echo nl2br(htmlspecialchars($t['description'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <?php
                $status = $t['status'];
                $showActionsPanel = $canManage && $status !== 'COMPLETED' && $status !== 'CANCELLED';
                ?>
                <?php if ($showActionsPanel): ?>
                <div class="panel_s ag-tournament-detail__actions">
                    <div class="panel-body">
                        <h5 class="ag-tournament-detail__actions-title"><i class="fa fa-bolt"></i> Ações</h5>
                        <?php if ($canManage && arenagamer_tournament_is_editable($t)): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_edit/' . $t['slug']); ?>" class="btn btn-default btn-block btn-sm">
                            <i class="fa fa-pencil"></i> Editar torneio
                        </a>
                        <?php endif; ?>
                        <?php if ($status === 'DRAFT'): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/open_registration'); ?>" class="btn btn-success btn-block btn-sm">Abrir inscrições</a>
                        <?php endif; ?>
                        <?php if ($status === 'REGISTRATION_OPEN'): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/close_registration'); ?>" class="btn btn-warning btn-block btn-sm mtop5">Fechar inscrições</a>
                        <?php endif; ?>
                        <?php if (arenagamer_tournament_can_generate_bracket($t, $allMatches)): ?>
                        <?php $bracketHint = arenagamer_tournament_bracket_hint($t); ?>
                        <?php if ($bracketHint !== null): ?>
                        <p class="text-muted"><small><?php echo htmlspecialchars($bracketHint); ?></small></p>
                        <?php endif; ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/generate_bracket'); ?>" class="btn btn-primary btn-block btn-sm mtop5">Gerar chaves</a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS'): ?>
                        <?php $knockoutPendingHint = arenagamer_tournament_generate_knockout_pending_hint($allMatches, $t['type'] ?? ''); ?>
                        <?php if ($knockoutPendingHint !== null): ?>
                        <p class="text-muted mtop5 mbot0"><small><?php echo htmlspecialchars($knockoutPendingHint); ?></small></p>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_generate_knockout($allMatches, $t['type'] ?? '')): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/generate_knockout'); ?>" class="btn btn-info btn-block btn-sm mtop5" onclick="return confirm('<?php echo $knockoutConfirmMessage; ?>')">
                            <i class="fa fa-sitemap"></i> Gerar mata-mata
                        </a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_regenerate_knockout($allMatches, $t['type'] ?? '')): ?>
                        <p class="text-muted mtop5 mbot0"><small>Chave incorreta? Peça ao administrador para limpar as partidas e regerar a chave.</small></p>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/generate_knockout'); ?>" class="btn btn-warning btn-block btn-sm mtop5" onclick="return confirm('ATENÇÃO: prefira limpar e regerar a chave completa. Continuar mesmo assim?')">
                            <i class="fa fa-refresh"></i> Regerar mata-mata
                        </a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_advance_round($allMatches, $t['type'] ?? '', $t)): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/advance_round'); ?>" class="btn btn-info btn-block btn-sm mtop5" onclick="return confirm('<?php echo $advanceRoundConfirmMessage; ?>')">
                            <i class="fa fa-random"></i> <?php echo htmlspecialchars($advanceRoundButtonLabel); ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_finalize($allMatches, $t['type'] ?? '', $t)): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/finalize'); ?>" class="btn btn-success btn-block btn-sm mtop5" onclick="return confirm('Todas as posições estão definidas. Finalizar o torneio?')">
                            <i class="fa fa-flag-checkered"></i> Finalizar torneio
                        </a>
                        <?php endif; ?>
                        <?php if ($status !== 'COMPLETED' && $status !== 'CANCELLED'): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/cancel'); ?>" class="btn btn-danger btn-block btn-sm mtop5" onclick="return confirm('Cancelar torneio?')">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($t['status'] === 'REGISTRATION_OPEN'): ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(arenagamer_client_url('join_tournament/' . $t['slug'])); ?>
                        <?php if (($t['format'] ?? '') === 'TEAM'): ?>
                        <p class="text-muted">Este campeonato exige inscrição por equipe.</p>
                        <?php
                        $myTeams = is_array($my_teams ?? null) ? $my_teams : [];
                        $minRoster = (int) ($t['minPlayersPerTeam'] ?? 0);
                        $maxRoster = (int) ($t['maxPlayersPerTeam'] ?? 0);
                        ?>
                        <?php if (empty($myTeams)): ?>
                        <div class="alert alert-warning mbot10">
                            Você não participa de nenhum time. <a href="<?php echo arenagamer_client_url('teams'); ?>">Criar ou gerenciar times</a>
                        </div>
                        <button type="submit" class="btn btn-success btn-block" disabled>Inscrever equipe</button>
                        <?php else: ?>
                        <div class="form-group">
                            <label>Selecione o time</label>
                            <select name="team_id" class="form-control" required>
                                <option value="">— Escolha —</option>
                                <?php foreach ($myTeams as $team): ?>
                                <?php
                                $memberCount = (int) ($team['memberCount'] ?? 0);
                                $canRegister = !empty($team['canRegisterInTournament']);
                                $underLimit = $minRoster > 0 && $memberCount < $minRoster;
                                $overLimit = $maxRoster > 0 && $memberCount > $maxRoster;
                                $ineligible = !$canRegister || $underLimit || $overLimit;
                                ?>
                                <option value="<?php echo (int) ($team['id'] ?? 0); ?>" <?php echo $ineligible ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($team['name'] ?? ('Time #' . (int) ($team['id'] ?? 0))); ?>
                                    <?php if (!empty($team['tag'])): ?>
                                    [<?php echo htmlspecialchars($team['tag']); ?>]
                                    <?php endif; ?>
                                    — <?php echo $memberCount; ?> jogador(es)
                                    <?php if (!$canRegister): ?>
                                    (sem permissão — só dono ou capitão inscreve)
                                    <?php elseif ($underLimit): ?>
                                    (mínimo exigido: <?php echo $minRoster; ?>)
                                    <?php elseif ($overLimit): ?>
                                    (excede máximo de <?php echo $maxRoster; ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">Inscrever equipe</button>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="text-muted">Inscrição individual (solo).</p>
                        <button type="submit" class="btn btn-success btn-block">Inscrever-se</button>
                        <?php endif; ?>
                        <?php echo form_close(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($showPermissionsPanel): ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="bold mtop0"><i class="fa fa-shield"></i> Quem pode gerenciar</h5>
                        <p class="text-muted">
                            Contatos com permissão para editar, abrir inscrições e administrar este torneio.
                        </p>

                        <?php if (!empty($permissionRows)): ?>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped mbot10">
                                <thead>
                                    <tr>
                                        <th>Contato</th>
                                        <th>Tipo</th>
                                        <?php if ($canGrantPermissions): ?>
                                        <th></th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permissionRows as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['contactName'] ?: ('Contato #' . (int) $row['contactId'])); ?></strong>
                                            <?php if (!empty($row['contactEmail'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['contactEmail']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($row['grantedAt'])): ?>
                                            <br><small class="text-muted">Desde <?php echo arenagamer_format_date($row['grantedAt'], 'd/m/Y H:i'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($row['type'] ?? '') === 'primary'): ?>
                                            <span class="label label-primary">Contato principal</span>
                                            <?php else: ?>
                                            <span class="label label-default">Delegado</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($canGrantPermissions): ?>
                                        <td class="text-right">
                                            <?php if (!empty($row['canRevoke'])): ?>
                                            <a href="<?php echo arenagamer_client_url('revoke_manager/' . $t['slug'] . '/' . (int) $row['contactId']); ?>"
                                               class="btn btn-danger btn-xs"
                                               onclick="return confirm('Revogar permissão deste contato?')"
                                               title="Revogar permissão">
                                                <i class="fa fa-user-times"></i>
                                            </a>
                                            <?php else: ?>
                                            <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Nenhuma permissão registrada.</p>
                        <?php endif; ?>

                        <?php if ($canGrantPermissions): ?>
                            <?php if (!empty($grantableContacts)): ?>
                            <hr class="hr-panel-separator">
                            <label class="control-label">Conceder permissão a</label>
                            <form class="form-inline" onsubmit="return grantManager(event)">
                                <select id="grant-contact" class="form-control">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($grantableContacts as $c): ?>
                                    <option value="<?php echo (int) $c['id']; ?>">
                                        <?php
                                        $label = trim(($c['firstName'] ?? '') . ' ' . ($c['lastName'] ?? ''));
                                        echo htmlspecialchars($label !== '' ? $label : ($c['email'] ?? ('Contato #' . $c['id'])));
                                        ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-success btn-sm mleft5">
                                    <i class="fa fa-user-plus"></i> Conceder
                                </button>
                            </form>
                            <?php else: ?>
                            <p class="text-muted mbot0">Todos os contatos secundários elegíveis já possuem permissão.</p>
                            <?php endif; ?>
                        <?php elseif ($canManage): ?>
                        <p class="text-muted mbot0"><i class="fa fa-info-circle"></i> Apenas o contato principal pode conceder ou revogar permissões.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <script>
                function grantManager(e) {
                    e.preventDefault();
                    var id = document.getElementById('grant-contact').value;
                    if (!id) {
                        return false;
                    }
                    window.location.href = '<?php echo arenagamer_client_url('grant_manager/' . $t['slug']); ?>/' + id;
                    return false;
                }
                </script>
                <?php endif; ?>
            </div>
        </div>

        <div class="row ag-tournament-detail__stages mtop20">
            <div class="col-md-12">

                <?php if ($hasDoubleEliminationBrackets): ?>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_double_elimination_brackets', [
                        'double_elimination_brackets' => $doubleEliminationBrackets,
                        'can_manage'                  => $canManage,
                        'participant_standing_map'    => $participantStandingMap,
                        'final_top3'                  => $finalTop3,
                    ]); ?>
                <?php elseif (!empty($knockoutBracket['rounds'])): ?>
                <section class="ag-matches-section ag-matches-section--knockout">
                    <h5 class="ag-tournament-section-title"><i class="fa fa-sitemap"></i> Chave eliminatória</h5>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_knockout_bracket', [
                        'knockout_bracket'          => $knockoutBracket,
                        'can_manage'                => $canManage,
                        'participant_standing_map'  => $participantStandingMap,
                        'final_top3'                => $finalTop3,
                    ]); ?>
                </section>
                <?php endif; ?>

                <?php if (!empty($groupStageBlocks)): ?>
                <h5 class="ag-tournament-section-title"><i class="fa fa-th-large"></i> Fase de grupos</h5>
                <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_group_stage_blocks', [
                    'group_stage_blocks'       => $groupStageBlocks,
                    'can_manage'               => $canManage,
                    'participant_standing_map' => $participantStandingMap,
                    'advance_per_group'        => arenagamer_group_stage_advance_per_group(),
                    'show_matches'             => false,
                    'show_classification'      => $showClassification,
                    'tournament'               => $t,
                ]); ?>
                <?php elseif (is_array($standingsData) && !empty($standingsData)): ?>
                <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_standings_table', [
                    'standings_data'       => $standingsData,
                    'show_classification'  => $showClassification,
                    'tournament'           => $t,
                ]); ?>
                <?php endif; ?>

                <?php
                $hasMatchesSection = !empty($groupStageBlocks)
                    || $hasRoundRobinMatches
                    || $hasSwissMatches
                    || $hasDoubleEliminationBrackets
                    || !empty($knockoutBracket['rounds'])
                    || !empty($matches['data'])
                    || !empty($matchesPagination['totalElements']);
                ?>
                <?php if ($hasMatchesSection): ?>
                <div class="ag-tournament-detail__matches">
                    <?php if ($canManage): ?>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_bulk_results_wizard', [
                        'all_matches'      => $allMatches,
                        'slug'             => $t['slug'],
                        'record_url_base'  => arenagamer_client_url('record_match_result/'),
                        'can_manage'       => $canManage,
                    ]); ?>
                    <?php endif; ?>

                    <?php if (!empty($groupStageBlocks)): ?>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_group_stage_matches', [
                        'group_stage_blocks'       => $groupStageBlocks,
                        'can_manage'               => $canManage,
                        'participant_standing_map' => $participantStandingMap,
                    ]); ?>
                    <?php endif; ?>

                    <?php if ($hasSwissMatches): ?>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_swiss_matches', [
                        'all_matches'              => $allMatches,
                        'can_manage'               => $canManage,
                        'participant_standing_map' => $participantStandingMap,
                        'tournament'               => $t,
                    ]); ?>
                    <?php endif; ?>

                    <?php if ($hasRoundRobinMatches): ?>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_round_robin_matches', [
                        'all_matches'              => $allMatches,
                        'can_manage'               => $canManage,
                        'participant_standing_map' => $participantStandingMap,
                        'matches_base_url'         => $matchesBaseUrl,
                    ]); ?>
                    <?php endif; ?>

                    <?php if (!empty($matches['data']) || !empty($matchesPagination['totalElements'])): ?>
                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_matches_list', [
                        'matches_data'             => $matches['data'] ?? [],
                        'matches_pagination'       => $matchesPagination,
                        'matches_base_url'         => $matchesBaseUrl,
                        'match_view'               => $matchView,
                        'participant_standing_map' => $participantStandingMap,
                        'can_manage'               => $canManage,
                        'skip_knockout'            => $hasDoubleEliminationBrackets || !empty($knockoutBracket['rounds']),
                        'skip_group_stage'         => !empty($groupStageBlocks),
                        'skip_round_robin'         => $hasRoundRobinMatches,
                        'skip_swiss'               => $hasSwissMatches,
                        'title'                    => 'Partidas',
                    ]); ?>
                    <?php endif; ?>

                    <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_match_result_modals', [
                        'all_matches'      => $allMatches,
                        'slug'             => $t['slug'],
                        'record_url_base'  => arenagamer_client_url('record_match_result/'),
                        'can_manage'       => $canManage,
                    ]); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
