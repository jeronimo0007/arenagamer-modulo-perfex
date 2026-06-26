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
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold">
            <?php echo htmlspecialchars($t['name']); ?>
            <?php echo arenagamer_status_badge($t['status']); ?>
            <?php echo arenagamer_tournament_client_badge($t, $client_name ?? null); ?>
        </h4>
        <a href="<?php echo arenagamer_client_url('tournaments'); ?>" class="btn btn-default btn-xs mbot15">&larr; Voltar</a>
        <?php if ($canManage && arenagamer_tournament_is_editable($t)): ?>
        <a href="<?php echo arenagamer_client_url('tournament_edit/' . $t['slug']); ?>" class="btn btn-warning btn-xs mbot15 mleft5">
            <i class="fa fa-pencil"></i> Editar
        </a>
        <?php endif; ?>

        <?php
        $coverImage = (string) ($t['coverImageUrl'] ?? '');
        $gameImage = arenagamer_tournament_game_image_url($t);
        $logoImage = arenagamer_tournament_logo_image_url($t);
        ?>
        <?php if ($coverImage !== ''): ?>
        <div class="mbot15" style="height:180px;border-radius:4px;background:url('<?php echo htmlspecialchars($coverImage); ?>') center/cover no-repeat;"></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <?php if ($logoImage !== '' || $gameImage !== ''): ?>
                <div class="clearfix mbot10">
                    <?php if ($logoImage !== ''): ?>
                    <img src="<?php echo htmlspecialchars($logoImage); ?>" alt="Imagem do torneio" class="img-thumbnail pull-left mright10" style="max-width:96px;max-height:96px;object-fit:cover;">
                    <?php endif; ?>
                    <?php if ($gameImage !== ''): ?>
                    <img src="<?php echo htmlspecialchars($gameImage); ?>" alt="Imagem do jogo" class="img-thumbnail pull-left mright15" style="max-width:72px;max-height:72px;object-fit:cover;">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <p><strong>Modo:</strong> <?php echo htmlspecialchars(arenagamer_tournament_type_label($t['type'] ?? '')); ?> · <?php echo htmlspecialchars(arenagamer_tournament_format_label($t['format'] ?? '')); ?></p>
                <p><strong>Jogo:</strong> <?php echo htmlspecialchars(arenagamer_tournament_game_name($t)); ?></p>
                <p><strong>Organizador:</strong> <?php echo htmlspecialchars($t['ownerName'] ?? '—'); ?> <?php echo arenagamer_owner_type_badge($t['ownerType'] ?? ''); ?></p>
                <p><strong>Inscritos:</strong>
                    <?php echo (int) ($t['participantCount'] ?? 0); ?> /
                    <?php echo (int) ($t['participantsLimit'] ?? 0); ?>
                    <?php if (($t['format'] ?? '') === 'TEAM'): ?>
                    <span class="text-muted">equipes</span>
                    <?php endif; ?>
                </p>
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
                <?php if (!empty($t['registrationOpensAt'])): ?>
                <p><strong>Abertura prevista das inscrições:</strong> <?php echo arenagamer_format_date($t['registrationOpensAt'], 'd/m/Y H:i'); ?></p>
                <?php endif; ?>
                <?php if (!empty($t['expectedEndDate'])): ?>
                <p><strong>Término previsto:</strong> <?php echo arenagamer_format_date($t['expectedEndDate'], 'd/m/Y H:i'); ?></p>
                <?php endif; ?>
                <?php if (!empty($t['youtubeUrl']) || !empty($t['twitchUrl'])): ?>
                <p>
                    <?php if (!empty($t['youtubeUrl'])): ?>
                    <a href="<?php echo htmlspecialchars($t['youtubeUrl']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-xs">
                        <i class="fa fa-youtube-play"></i> YouTube
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($t['twitchUrl'])): ?>
                    <a href="<?php echo htmlspecialchars($t['twitchUrl']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-xs">
                        <i class="fa fa-twitch"></i> Twitch
                    </a>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($t['description'])): ?>
                <hr><p><?php echo nl2br(htmlspecialchars($t['description'])); ?></p>
                <?php endif; ?>

                <?php if (!empty($matches['data'])): ?>
                <h5 class="bold mtop20">Partidas</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>#</th><th>Confronto</th><th>Quando</th><th>Status</th><th>Vencedor</th><?php if ($canManage): ?><th>Resultado</th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php foreach ($matches['data'] as $m): ?>
                            <?php
                                $homeName = !empty($m['homeParticipantName']) ? $m['homeParticipantName'] : 'A definir';
                                $awayName = !empty($m['awayParticipantName']) ? $m['awayParticipantName'] : 'A definir';
                                $windowLabel = arenagamer_time_window_label($m['timeWindow'] ?? null);
                                $homeId = $m['homeParticipantId'] ?? null;
                                $awayId = $m['awayParticipantId'] ?? null;
                                $winnerId = $m['winnerParticipantId'] ?? null;
                                $matchStatus = $m['status'] ?? '';
                                $bothDefined = !empty($homeId) && !empty($awayId);
                                $isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);
                                $winnerName = '';
                                if (!empty($winnerId)) {
                                    if ($winnerId == $homeId) {
                                        $winnerName = $homeName;
                                    } elseif ($winnerId == $awayId) {
                                        $winnerName = $awayName;
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo (int) $m['matchNumber']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($homeName); ?> vs <?php echo htmlspecialchars($awayName); ?>
                                    <?php if (!empty($m['phaseLabel'])): ?>
                                    <br><small class="text-muted"><i class="fa fa-sitemap"></i> <?php echo htmlspecialchars($m['phaseLabel']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['scheduledAt'])): ?>
                                    <?php echo arenagamer_format_date($m['scheduledAt'], 'd/m/Y H:i'); ?>
                                    <?php if (!empty($windowLabel)): ?>
                                    <span class="text-muted">(<?php echo htmlspecialchars($windowLabel); ?>)</span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted">A definir</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo arenagamer_status_badge($m['status']); ?></td>
                                <td>
                                    <?php if ($winnerName !== ''): ?>
                                    <span class="label label-success"><i class="fa fa-trophy"></i> <?php echo htmlspecialchars($winnerName); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                    <?php if (isset($m['homeScore']) && isset($m['awayScore']) && $m['homeScore'] !== null && $m['awayScore'] !== null): ?>
                                    <br><small class="text-muted">Placar: <?php echo (int) $m['homeScore']; ?> x <?php echo (int) $m['awayScore']; ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($m['resultProofUrl'])): ?>
                                    <br><a href="<?php echo htmlspecialchars($m['resultProofUrl']); ?>" target="_blank" rel="noopener"><i class="fa fa-paperclip"></i> Comprovante</a>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canManage): ?>
                                <td>
                                    <?php if ($bothDefined && !$isFinished): ?>
                                    <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#result-modal-<?php echo (int) $m['id']; ?>">
                                        <i class="fa fa-trophy"></i> Registrar resultado
                                    </button>
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

                <?php if ($canManage): ?>
                    <?php foreach ($matches['data'] as $m): ?>
                    <?php
                        $homeId = $m['homeParticipantId'] ?? null;
                        $awayId = $m['awayParticipantId'] ?? null;
                        $matchStatus = $m['status'] ?? '';
                        $bothDefined = !empty($homeId) && !empty($awayId);
                        $isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);
                        if (!$bothDefined || $isFinished) {
                            continue;
                        }
                        $homeName = !empty($m['homeParticipantName']) ? $m['homeParticipantName'] : 'Casa';
                        $awayName = !empty($m['awayParticipantName']) ? $m['awayParticipantName'] : 'Visitante';
                    ?>
                    <div class="modal fade ag-result-modal" id="result-modal-<?php echo (int) $m['id']; ?>" tabindex="-1" role="dialog"
                         data-home-id="<?php echo (int) $homeId; ?>" data-away-id="<?php echo (int) $awayId; ?>"
                         data-home-name="<?php echo htmlspecialchars($homeName); ?>" data-away-name="<?php echo htmlspecialchars($awayName); ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <?php echo form_open_multipart(arenagamer_client_url('record_match_result/' . (int) $m['id'])); ?>
                                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($t['slug']); ?>">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Resultado — Partida #<?php echo (int) $m['matchNumber']; ?></h4>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-xs-6 form-group">
                                            <label>Placar — <?php echo htmlspecialchars($homeName); ?> <span class="text-danger">*</span></label>
                                            <input type="number" min="0" name="home_score" class="form-control ag-score" required>
                                        </div>
                                        <div class="col-xs-6 form-group">
                                            <label>Placar — <?php echo htmlspecialchars($awayName); ?> <span class="text-danger">*</span></label>
                                            <input type="number" min="0" name="away_score" class="form-control ag-score" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="bold">Vencedor:</label> <span class="ag-auto-winner text-info">defina o placar</span>
                                        <br><small class="text-muted">Definido automaticamente pelo maior placar.</small>
                                    </div>
                                    <div class="form-group">
                                        <button type="button" class="btn btn-warning btn-xs ag-cheat-toggle">
                                            <i class="fa fa-user-secret"></i> Trapaceiro detectado
                                        </button>
                                    </div>
                                    <div class="form-group ag-cheat-block" style="display:none;">
                                        <label class="bold text-warning">Vencedor manual</label>
                                        <div class="radio">
                                            <label><input type="radio" name="winner_participant_id" value="<?php echo (int) $homeId; ?>"> <?php echo htmlspecialchars($homeName); ?></label>
                                        </div>
                                        <div class="radio">
                                            <label><input type="radio" name="winner_participant_id" value="<?php echo (int) $awayId; ?>"> <?php echo htmlspecialchars($awayName); ?></label>
                                        </div>
                                        <small class="text-muted">Com vencedor manual, o placar pode não refletir o vencedor.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Comprovante do vencedor (imagem) <small class="text-muted">— opcional</small></label>
                                        <input type="file" name="proof_file" accept="image/*" class="form-control">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Salvar resultado</button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php echo arenagamer_result_modal_script(); ?>
                <?php endif; ?>
                <?php endif; ?>

                <?php $standingsData = arenagamer_api_data($standings ?? null, []); ?>
                <?php if (is_array($standingsData) && !empty($standingsData)): ?>
                <?php $hasGroups = false; foreach ($standingsData as $s) { if (isset($s['points']) || isset($s['groupNumber'])) { $hasGroups = true; break; } } ?>
                <h5 class="bold mtop20">
                    Classificação
                    <a href="#arenagamer-standings" data-toggle="collapse" class="btn btn-default btn-xs mleft5">
                        <i class="fa fa-trophy"></i> Verificar posições
                    </a>
                </h5>
                <div id="arenagamer-standings" class="collapse in table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if ($hasGroups): ?><th>Grupo</th><?php endif; ?>
                                <th>Participante</th>
                                <?php if ($hasGroups): ?><th title="Pontos">P</th><th title="Vitórias">V</th><th title="Empates">E</th><th title="Derrotas">D</th><?php else: ?><th>Posição</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standingsData as $s): ?>
                            <tr>
                                <td><?php echo isset($s['position']) ? (int) $s['position'] . 'º' : '—'; ?></td>
                                <?php if ($hasGroups): ?><td><?php echo isset($s['groupNumber']) ? 'Grupo ' . (int) $s['groupNumber'] : '—'; ?></td><?php endif; ?>
                                <td>
                                    <?php echo htmlspecialchars($s['participantName'] ?? 'A definir'); ?>
                                    <?php if (!empty($s['note'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($s['note']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <?php if ($hasGroups): ?>
                                <td><?php echo (int) ($s['points'] ?? 0); ?></td>
                                <td><?php echo (int) ($s['wins'] ?? 0); ?></td>
                                <td><?php echo (int) ($s['draws'] ?? 0); ?></td>
                                <td><?php echo (int) ($s['losses'] ?? 0); ?></td>
                                <?php else: ?>
                                <td><?php echo !empty($s['note']) ? htmlspecialchars($s['note']) : '—'; ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <?php if ($canManage): ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="bold">Ações</h5>
                        <?php $status = $t['status']; ?>
                        <?php if ($status === 'DRAFT'): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/open_registration'); ?>" class="btn btn-success btn-block btn-sm">Abrir inscrições</a>
                        <?php endif; ?>
                        <?php if ($status === 'REGISTRATION_OPEN'): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/close_registration'); ?>" class="btn btn-warning btn-block btn-sm mtop5">Fechar inscrições</a>
                        <?php endif; ?>
                        <?php if (in_array($status, ['REGISTRATION_OPEN', 'REGISTRATION_CLOSED'], true)): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/generate_bracket'); ?>" class="btn btn-primary btn-block btn-sm mtop5">Gerar chaves</a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_generate_knockout($matches['data'] ?? null, $t['type'] ?? '')): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/generate_knockout'); ?>" class="btn btn-info btn-block btn-sm mtop5" onclick="return confirm('Gerar o mata-mata com os classificados de cada grupo?')">
                            <i class="fa fa-sitemap"></i> Gerar mata-mata
                        </a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_advance_round($matches['data'] ?? null, $t['type'] ?? '')): ?>
                        <a href="<?php echo arenagamer_client_url('tournament_action/' . $t['slug'] . '/advance_round'); ?>" class="btn btn-info btn-block btn-sm mtop5" onclick="return confirm('Gerar a próxima fase com os vencedores da fase atual?')">
                            <i class="fa fa-sitemap"></i> Gerar próxima fase
                        </a>
                        <?php endif; ?>
                        <?php if ($status === 'IN_PROGRESS' && arenagamer_matches_can_finalize($matches['data'] ?? null, $t['type'] ?? '')): ?>
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
    </div>
</div>
<?php endif; ?>
