<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $t = isset($tournament['data']) ? $tournament['data'] : null; ?>
        <?php $canManage = !empty($can_manage); ?>
        <?php if ($t): ?>
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-trophy"></i> <?php echo htmlspecialchars($t['name']); ?>
                    <?php echo arenagamer_status_badge($t['status']); ?>
                    <?php echo arenagamer_tournament_client_badge($t, $client_name ?? null); ?>
                </h4>
                <a href="<?php echo admin_url('arenagamer/tournaments'); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-arrow-left"></i> Voltar
                </a>
                <?php if ($canManage && arenagamer_tournament_is_editable($t)): ?>
                <a href="<?php echo admin_url('arenagamer/tournament_edit/' . $t['slug']); ?>" class="btn btn-warning btn-xs">
                    <i class="fa fa-pencil"></i> Editar
                </a>
                <?php endif; ?>
                <hr />
            </div>
        </div>

        <?php
        $coverImage = (string) ($t['coverImageUrl'] ?? '');
        $gameImage = arenagamer_tournament_game_image_url($t);
        $logoImage = arenagamer_tournament_logo_image_url($t);
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

                <!-- Matches -->
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Partidas</h4></div>
                    <div class="panel-body">
                        <?php if (isset($matches['data']) && !empty($matches['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Mandante</th>
                                        <th>vs</th>
                                        <th>Visitante</th>
                                        <th>Placar</th>
                                        <th>Agendamento</th>
                                        <th>Status</th>
                                        <?php if ($canManage): ?>
                                        <th>Ações</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matches['data'] as $m): ?>
                                    <tr>
                                        <td><?php echo $m['matchNumber']; ?></td>
                                        <td>
                                            <?php if (!empty($m['homeParticipantName'])): ?>
                                                <?php echo htmlspecialchars($m['homeParticipantName']); ?>
                                            <?php elseif (!empty($m['homeParticipantId'])): ?>
                                                Participante #<?php echo (int) $m['homeParticipantId']; ?>
                                            <?php else: ?>
                                                <em>BYE</em>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><strong>vs</strong></td>
                                        <td>
                                            <?php if (!empty($m['awayParticipantName'])): ?>
                                                <?php echo htmlspecialchars($m['awayParticipantName']); ?>
                                            <?php elseif (!empty($m['awayParticipantId'])): ?>
                                                Participante #<?php echo (int) $m['awayParticipantId']; ?>
                                            <?php else: ?>
                                                <em>BYE</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($m['homeScore']) && isset($m['awayScore'])): ?>
                                            <?php echo $m['homeScore']; ?> - <?php echo $m['awayScore']; ?>
                                            <?php else: ?>
                                            —
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($m['scheduledAt']) ? date('d/m/Y H:i', strtotime($m['scheduledAt'])) : '—'; ?></td>
                                        <td><?php echo arenagamer_status_badge($m['status']); ?></td>
                                        <?php if ($canManage): ?>
                                        <td>
                                            <button type="button" class="btn btn-default btn-xs" data-toggle="modal"
                                                    data-target="#reschedule-modal-<?php echo (int) $m['id']; ?>">
                                                <i class="fa fa-calendar"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($canManage): ?>
                            <?php foreach ($matches['data'] as $m): ?>
                            <div class="modal fade" id="reschedule-modal-<?php echo (int) $m['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <?php echo form_open(admin_url('arenagamer/reschedule_match/' . (int) $m['id'])); ?>
                                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($t['slug']); ?>">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            <h4 class="modal-title">Reagendar partida #<?php echo (int) $m['matchNumber']; ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Nova data/hora</label>
                                                <input type="datetime-local" name="scheduled_at" class="form-control" required
                                                       value="<?php echo arenagamer_datetime_local_value($m['scheduledAt'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Reagendar</button>
                                        </div>
                                        <?php echo form_close(); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php else: ?>
                        <p class="text-muted">Nenhuma partida gerada ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>

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
            </div>

            <!-- Actions Sidebar -->
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Ações</h4></div>
                    <div class="panel-body">
                        <?php if ($canManage): ?>
                            <?php $status = $t['status']; ?>

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

                            <?php if ($status === 'REGISTRATION_CLOSED' || $status === 'REGISTRATION_OPEN'): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/generate_bracket"); ?>"
                               class="btn btn-primary btn-block mtop5"
                               onclick="return confirm('Gerar chaves do torneio?')">
                                <i class="fa fa-sitemap"></i> Gerar Chaves
                            </a>
                            <?php endif; ?>

                            <?php if ($status === 'IN_PROGRESS'): ?>
                            <a href="<?php echo admin_url("arenagamer/tournament_action/{$t['slug']}/schedule"); ?>"
                               class="btn btn-info btn-block mtop5"
                               onclick="return confirm('Agendar partidas automaticamente?')">
                                <i class="fa fa-calendar"></i> Agendar Partidas
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
