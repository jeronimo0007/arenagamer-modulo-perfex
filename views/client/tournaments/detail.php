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
                        <thead><tr><th>#</th><th>Confronto</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($matches['data'] as $m): ?>
                            <tr>
                                <td><?php echo (int) $m['matchNumber']; ?></td>
                                <td><?php echo htmlspecialchars($m['homeParticipantName'] ?? 'BYE'); ?> vs <?php echo htmlspecialchars($m['awayParticipantName'] ?? 'BYE'); ?></td>
                                <td><?php echo arenagamer_status_badge($m['status']); ?></td>
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
