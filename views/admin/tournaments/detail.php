<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $t = isset($tournament['data']) ? $tournament['data'] : null; ?>
        <?php $canManage = !empty($can_manage); ?>
        <?php if ($t): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="ag-tournament-detail__header mbot15">
                    <a href="<?php echo admin_url('arenagamer/tournaments'); ?>" class="ag-tournament-detail__back">
                        <i class="fa fa-arrow-left"></i> Voltar
                    </a>
                    <h4 class="ag-tournament-detail__title tw-font-semibold tw-text-lg tw-text-neutral-700">
                        <i class="fa fa-trophy"></i> <?php echo htmlspecialchars($t['name']); ?>
                        <?php echo arenagamer_status_badge($t['status']); ?>
                        <?php echo arenagamer_tournament_client_badge($t, $client_name ?? null); ?>
                    </h4>
                </div>
            </div>
        </div>

        <?php
        $coverImage = (string) ($t['coverImageUrl'] ?? '');
        $gameImage = arenagamer_tournament_game_image_url($t);
        $logoImage = arenagamer_tournament_logo_image_url($t);
        $allMatches = is_array($all_matches ?? null) ? $all_matches : ($matches['data'] ?? []);
        $matchesPagination = is_array($matches['pagination'] ?? null) ? $matches['pagination'] : [];
        $matchesBaseUrl = admin_url('arenagamer/tournament_detail/' . $t['slug']);
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
        <?php if ($coverImage !== '' || $logoImage !== '' || $gameImage !== ''): ?>
        <div class="row mbot15">
            <div class="col-md-12">
                <?php if ($coverImage !== ''): ?>
                <div class="mbot10" style="height:160px;border-radius:4px;background:url('<?php echo htmlspecialchars($coverImage); ?>') center/cover no-repeat;"></div>
                <?php endif; ?>
                <?php if ($logoImage !== '' || $gameImage !== ''): ?>
                <div>
                    <?php if ($logoImage !== ''): ?>
                    <img src="<?php echo htmlspecialchars($logoImage); ?>" alt="Imagem do torneio" class="img-thumbnail mright10" style="max-width:96px;max-height:96px;object-fit:cover;">
                    <?php endif; ?>
                    <?php if ($gameImage !== ''): ?>
                    <img src="<?php echo htmlspecialchars($gameImage); ?>" alt="Imagem do jogo" class="img-thumbnail" style="max-width:72px;max-height:72px;object-fit:cover;">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tournament Info -->
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Informações</h4></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Slug:</strong> <code><?php echo $t['slug']; ?></code></p>
                                <p><strong>Modo:</strong> <?php echo htmlspecialchars(arenagamer_tournament_type_label($t['type'] ?? '')); ?></p>
                                <p><strong>Formato:</strong> <?php echo isset($t['format']) ? htmlspecialchars(arenagamer_tournament_format_label($t['format'])) : '—'; ?></p>
                                <?php if (($t['format'] ?? '') === 'TEAM' && (!empty($t['minPlayersPerTeam']) || !empty($t['maxPlayersPerTeam']))): ?>
                                <p><strong>Jogadores por equipe:</strong>
                                    <?php if (!empty($t['minPlayersPerTeam'])): ?>
                                    mín. <?php echo (int) $t['minPlayersPerTeam']; ?>
                                    <?php endif; ?>
                                    <?php if (!empty($t['minPlayersPerTeam']) && !empty($t['maxPlayersPerTeam'])): ?>
                                    —
                                    <?php endif; ?>
                                    <?php if (!empty($t['maxPlayersPerTeam'])): ?>
                                    máx. <?php echo (int) $t['maxPlayersPerTeam']; ?>
                                    <?php endif; ?>
                                </p>
                                <?php endif; ?>
                                <?php
                                $groupStageSummary = arenagamer_tournament_group_stage_summary($t);
                                $roundRobinEliminationSummary = arenagamer_tournament_round_robin_elimination_summary($t);
                                $swissSummary = arenagamer_tournament_swiss_summary($t);
                                ?>
                                <?php if ($groupStageSummary !== null): ?>
                                <p><strong>Fase de grupos:</strong> <?php echo htmlspecialchars($groupStageSummary); ?></p>
                                <?php endif; ?>
                                <?php if ($roundRobinEliminationSummary !== null): ?>
                                <p><strong>Formato:</strong> <?php echo htmlspecialchars($roundRobinEliminationSummary); ?></p>
                                <?php endif; ?>
                                <?php if ($swissSummary !== null): ?>
                                <p><strong>Sistema suíço:</strong> <?php echo htmlspecialchars($swissSummary); ?></p>
                                <?php endif; ?>
                                <p><strong>Organizador:</strong> <?php echo isset($t['ownerName']) ? htmlspecialchars($t['ownerName']) : '—'; ?>
                                    <?php if (!empty($t['ownerType'])): ?>
                                    <?php echo arenagamer_owner_type_badge($t['ownerType']); ?>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Vínculo:</strong> <?php echo arenagamer_tournament_client_badge($t, $client_name ?? null); ?></p>
                                <p><strong>Jogo:</strong> <?php echo htmlspecialchars(arenagamer_tournament_game_name($t)); ?></p>
                                <p><strong>Inscritos:</strong> <?php echo isset($t['participantCount']) ? $t['participantCount'] : 0; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Vagas:</strong> <?php echo isset($t['participantsLimit']) ? $t['participantsLimit'] : '—'; ?>
                                    <?php if (($t['format'] ?? '') === 'TEAM'): ?>
                                    <span class="text-muted">(equipes)</span>
                                    <?php endif; ?>
                                </p>
                                <p><strong>Mín. Participantes:</strong> <?php echo isset($t['minParticipants']) ? $t['minParticipants'] : '—'; ?></p>
                                <p><strong>Taxa de inscrição:</strong> <?php echo isset($t['entryFeeCredits']) ? number_format($t['entryFeeCredits'], 2) . ' créditos' : 'Grátis'; ?></p>
                                <p><strong>Visibilidade:</strong> <?php echo isset($t['visibility']) ? htmlspecialchars(arenagamer_tournament_visibility_label($t['visibility'])) : '—'; ?></p>
                                <p><strong>Prêmio:</strong> <?php echo isset($t['prizePool']) ? arenagamer_format_credits($t['prizePool']) : '—'; ?></p>
                            </div>
                        </div>
                        <?php if (isset($t['description']) && $t['description']): ?>
                        <hr />
                        <p><strong>Descrição:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($t['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (isset($t['rules']) && $t['rules']): ?>
                        <hr />
                        <p><strong>Regras:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($t['rules'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_tournament_type_how_it_works_panel', [
                    'tournament' => $t,
                ]); ?>

                <?php if ($hasDoubleEliminationBrackets): ?>
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-code-fork"></i> Chaves — eliminação dupla</h4></div>
                    <div class="panel-body" style="padding: 12px;">
                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_double_elimination_brackets', [
                            'double_elimination_brackets' => $doubleEliminationBrackets,
                            'can_manage'                  => $canManage,
                            'participant_standing_map'    => $participantStandingMap,
                            'final_top3'                  => $finalTop3,
                            'show_section_title'          => false,
                        ]); ?>
                    </div>
                </div>
                <?php elseif (!empty($knockoutBracket['rounds'])): ?>
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-sitemap"></i> Chave eliminatória</h4></div>
                    <div class="panel-body" style="padding: 12px;">
                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_knockout_bracket', [
                            'knockout_bracket'         => $knockoutBracket,
                            'can_manage'               => $canManage,
                            'participant_standing_map' => $participantStandingMap,
                            'final_top3'               => $finalTop3,
                        ]); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($groupStageBlocks)): ?>
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-th-large"></i> Fase de grupos</h4></div>
                    <div class="panel-body" style="padding: 12px;">
                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_group_stage_blocks', [
                            'group_stage_blocks'       => $groupStageBlocks,
                            'can_manage'               => $canManage,
                            'participant_standing_map' => $participantStandingMap,
                            'advance_per_group'        => arenagamer_group_stage_advance_per_group(),
                            'show_matches'             => false,
                            'show_classification'      => $showClassification,
                            'tournament'               => $t,
                        ]); ?>
                    </div>
                </div>
                <?php elseif (is_array($standingsData) && !empty($standingsData)): ?>
                <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_standings_table', [
                    'standings_data'      => $standingsData,
                    'show_classification' => $showClassification,
                    'tournament'          => $t,
                    'wrapper_class'       => 'ag-standings',
                ]); ?>
                <?php endif; ?>


                <?php if (!empty($t['participants']) && is_array($t['participants'])): ?>
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Participantes</h4></div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Status</th>
                                        <?php if ($canManage): ?>
                                        <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($t['participants'] as $p): ?>
                                    <tr>
                                        <td><?php echo (int) ($p['id'] ?? $p['participantId'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($p['name'] ?? $p['participantName'] ?? '—'); ?></td>
                                        <td><?php echo isset($p['status']) ? arenagamer_status_badge($p['status']) : '—'; ?></td>
                                        <?php if ($canManage): ?>
                                        <td>
                                            <?php $participantId = (int) ($p['id'] ?? $p['participantId'] ?? 0); ?>
                                            <?php if ($participantId > 0): ?>
                                            <a href="<?php echo admin_url("arenagamer/expel_participant/{$t['slug']}/{$participantId}"); ?>"
                                               class="btn btn-danger btn-xs"
                                               onclick="return confirm('Expulsar este participante?')">
                                                <i class="fa fa-user-times"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($t['clientUserId']) && $canManage): ?>
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Permissões de gestão</h4></div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            O contato principal da empresa sempre tem acesso total aos torneios vinculados — não aparece nesta lista.
                        </div>

                        <?php $managersList = is_array($managers ?? null) ? $managers : []; ?>
                        <?php if (!empty($managersList)): ?>
                        <div class="table-responsive mbot15">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Contato</th>
                                        <th>Email</th>
                                        <th>Concedido em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($managersList as $manager): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($manager['contactName'] ?? ('Contato #' . ($manager['contactId'] ?? ''))); ?></td>
                                        <td><?php echo htmlspecialchars($manager['contactEmail'] ?? '—'); ?></td>
                                        <td><?php echo !empty($manager['grantedAt']) ? arenagamer_format_date($manager['grantedAt']) : '—'; ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('arenagamer/revoke_tournament_manager/' . $t['slug'] . '/' . (int) ($manager['contactId'] ?? 0)); ?>"
                                               class="btn btn-danger btn-xs"
                                               onclick="return confirm('Revogar permissão deste contato?')">
                                                <i class="fa fa-user-times"></i> Revogar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Nenhum contato secundário com permissão explícita.</p>
                        <?php endif; ?>

                        <?php $grantable = is_array($grantable_contacts ?? null) ? $grantable_contacts : []; ?>
                        <?php if (!empty($grantable)): ?>
                        <hr />
                        <form method="get" action="<?php echo admin_url('arenagamer/grant_tournament_manager/' . $t['slug'] . '/0'); ?>" id="grant-manager-form" class="form-inline">
                            <div class="form-group">
                                <label class="mr-sm-2">Conceder a:</label>
                                <select name="contact_id" id="grant-contact-select" class="form-control selectpicker" data-live-search="true" required>
                                    <option value="">Selecione um contato</option>
                                    <?php foreach ($grantable as $contact): ?>
                                    <option value="<?php echo (int) $contact['id']; ?>">
                                        <?php
                                        $label = trim(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? ''));
                                        echo htmlspecialchars($label !== '' ? $label : ($contact['email'] ?? ('Contato #' . $contact['id'])));
                                        ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-success mleft5" onclick="submitGrantManager()">
                                <i class="fa fa-user-plus"></i> Conceder
                            </button>
                        </form>
                        <?php else: ?>
                        <p class="text-muted mtop10">Não há contatos secundários elegíveis para conceder permissão.</p>
                        <?php endif; ?>
                    </div>
                </div>
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
                <div class="panel_s ag-tournament-detail__matches">
                    <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-gamepad"></i> Partidas</h4></div>
                    <div class="panel-body">
                        <?php if ($canManage): ?>
                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_bulk_results_wizard', [
                            'all_matches'     => $allMatches,
                            'slug'            => $t['slug'],
                            'record_url_base' => admin_url('arenagamer/record_match_result/'),
                            'can_manage'      => $canManage,
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
                            'title'                    => 'Lista de partidas',
                            'show_section_title'       => true,
                            'show_reschedule'          => true,
                        ]); ?>
                        <?php elseif (empty($groupStageBlocks) && !$hasRoundRobinMatches && !$hasSwissMatches && !$hasDoubleEliminationBrackets && empty($knockoutBracket['rounds'])): ?>
                        <p class="ag-matches-empty">Nenhuma partida gerada ainda.</p>
                        <?php endif; ?>

                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_match_result_modals', [
                            'all_matches'      => $allMatches,
                            'slug'             => $t['slug'],
                            'record_url_base'  => admin_url('arenagamer/record_match_result/'),
                            'can_manage'       => $canManage,
                        ]); ?>
                        <?php $this->load->view('../../modules/arenagamer/views/admin/tournaments/_match_reschedule_modals', [
                            'matches_data' => $matches['data'] ?? [],
                            'slug'         => $t['slug'],
                            'can_manage'   => $canManage,
                        ]); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions Sidebar -->
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Ações</h4></div>
                    <div class="panel-body">
                        <?php if ($canManage): ?>
                            <?php $status = $t['status']; ?>

                            <?php if (arenagamer_tournament_is_editable($t)): ?>
                            <a href="<?php echo admin_url('arenagamer/tournament_edit/' . $t['slug']); ?>"
                               class="btn btn-default btn-block btn-sm">
                                <i class="fa fa-pencil"></i> Editar torneio
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'DRAFT'): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/open_registration"); ?>"
                               class="btn btn-success btn-block mtop5"
                               onclick="return confirm('Abrir inscrições?')">
                                <i class="fa fa-unlock"></i> Abrir Inscrições
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'REGISTRATION_OPEN'): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/close_registration"); ?>"
                               class="btn btn-warning btn-block mtop5"
                               onclick="return confirm('Fechar inscrições?')">
                                <i class="fa fa-lock"></i> Fechar Inscrições
                            </a>
                            <?php endif; ?>

                            <?php if (arenagamer_tournament_can_generate_bracket($t, $allMatches)): ?>
                            <?php $bracketHint = arenagamer_tournament_bracket_hint($t); ?>
                            <?php if ($bracketHint !== null): ?>
                            <p class="text-muted"><small><?php echo htmlspecialchars($bracketHint); ?></small></p>
                            <?php endif; ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/generate_bracket"); ?>"
                               class="btn btn-primary btn-block mtop5"
                               onclick="return confirm('Gerar chaves do torneio?')">
                                <i class="fa fa-sitemap"></i> Gerar Chaves
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS'): ?>
                            <?php $knockoutPendingHint = arenagamer_tournament_generate_knockout_pending_hint($allMatches, $t['type'] ?? ''); ?>
                            <?php if ($knockoutPendingHint !== null): ?>
                            <p class="text-muted mtop5 mbot0"><small><?php echo htmlspecialchars($knockoutPendingHint); ?></small></p>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS'): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/schedule"); ?>"
                               class="btn btn-info btn-block mtop5"
                               onclick="return confirm('Agendar partidas automaticamente?')">
                                <i class="fa fa-calendar"></i> Agendar Partidas
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS' && arenagamer_tournament_can_clear_matches($t, $allMatches)): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/clear_matches"); ?>"
                               class="btn btn-danger btn-block mtop5"
                               onclick="return confirm('Limpar TODAS as partidas, rodadas, seeds e classificação?\n\n• Inscrições são mantidas\n• Status volta para inscrições fechadas\n• Depois: Gerar chaves → resultados dos grupos → Gerar mata-mata\n\nEsta ação não pode ser desfeita.')">
                                <i class="fa fa-trash"></i> Limpar partidas
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_generate_knockout($allMatches, $t['type'] ?? '')): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/generate_knockout"); ?>"
                               class="btn btn-primary btn-block mtop5"
                               onclick="return confirm('<?php echo $knockoutConfirmMessage; ?>')">
                                <i class="fa fa-sitemap"></i> Gerar Mata-mata
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_regenerate_knockout($allMatches, $t['type'] ?? '')): ?>
                            <p class="text-muted mtop5 mbot0"><small>Chave incorreta? Use <strong>Limpar partidas</strong>, depois <strong>Gerar chaves</strong> e <strong>Gerar mata-mata</strong>.</small></p>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/generate_knockout"); ?>"
                               class="btn btn-warning btn-block mtop5"
                               onclick="return confirm('ATENÇÃO: prefira Limpar partidas antes de regerar. Continuar mesmo assim?')">
                                <i class="fa fa-refresh"></i> Regerar Mata-mata
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_advance_round($allMatches, $t['type'] ?? '', $t)): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/advance_round"); ?>"
                               class="btn btn-primary btn-block mtop5"
                               onclick="return confirm('<?php echo $advanceRoundConfirmMessage; ?>')">
                                <i class="fa fa-random"></i> <?php echo htmlspecialchars($advanceRoundButtonLabel); ?>
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_finalize($allMatches, $t['type'] ?? '', $t)): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/finalize"); ?>"
                               class="btn btn-success btn-block mtop5"
                               onclick="return confirm('Todas as posições estão definidas. Finalizar o torneio?')">
                                <i class="fa fa-flag-checkered"></i> Finalizar Torneio
                            </a>
                            <?php endif; ?>

                            <?php if ($status !== 'COMPLETED' && $status !== 'CANCELLED'): ?>
                            <hr />
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/cancel"); ?>"
                               class="btn btn-danger btn-block"
                               onclick="return confirm('ATENÇÃO: Cancelar torneio? Esta ação não pode ser desfeita.')">
                                <i class="fa fa-times"></i> Cancelar Torneio
                            </a>
                            <?php endif; ?>
                        <?php elseif (staff_can('edit', 'arenagamer')): ?>
                        <p class="text-muted">Você não possui permissão para gerenciar este torneio.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Datas</h4></div>
                    <div class="panel-body">
                        <p><strong>Início:</strong> <?php echo isset($t['startDate']) ? date('d/m/Y H:i', strtotime($t['startDate'])) : '—'; ?></p>
                        <p><strong>Prazo máximo de inscrição:</strong> <?php echo isset($t['registrationDeadline']) ? date('d/m/Y H:i', strtotime($t['registrationDeadline'])) : '—'; ?></p>
                        <p><strong>Criado em:</strong> <?php echo isset($t['createdAt']) ? date('d/m/Y H:i', strtotime($t['createdAt'])) : '—'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center mtop30">
            <p class="text-danger">Torneio não encontrado ou erro na API.</p>
            <a href="<?php echo admin_url('arenagamer/tournaments'); ?>" class="btn btn-default">Voltar</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php init_tail(); ?>
<script>
function submitGrantManager() {
    var select = document.getElementById('grant-contact-select');
    var contactId = select ? select.value : '';
    if (!contactId) {
        alert('Selecione um contato.');
        return;
    }
    var baseUrl = '<?php echo admin_url('arenagamer/grant_tournament_manager/' . ($t['slug'] ?? '')); ?>';
    window.location.href = baseUrl + '/' + contactId;
}
</script>
