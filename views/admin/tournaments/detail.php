<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php $t = isset($tournament['data']) ? $tournament['data'] : null; ?>
        <?php if ($t): ?>
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-trophy"></i> <?php echo htmlspecialchars($t['name']); ?>
                    <?php echo arenagamer_status_badge($t['status']); ?>
                </h4>
                <a href="<?php echo admin_url('arenagamer/tournaments'); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-arrow-left"></i> Voltar
                </a>
                <hr />
            </div>
        </div>

        <!-- Tournament Info -->
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Informações</h4></div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Slug:</strong> <code><?php echo $t['slug']; ?></code></p>
                                <p><strong>Tipo:</strong> <?php echo $t['type']; ?></p>
                                <p><strong>Formato:</strong> <?php echo isset($t['format']) ? $t['format'] : '—'; ?></p>
                                <p><strong>Visibilidade:</strong> <?php echo isset($t['visibility']) ? $t['visibility'] : '—'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Vagas:</strong> <?php echo isset($t['participantsLimit']) ? $t['participantsLimit'] : '—'; ?></p>
                                <p><strong>Mín. Participantes:</strong> <?php echo isset($t['minParticipants']) ? $t['minParticipants'] : '—'; ?></p>
                                <p><strong>Taxa Entrada:</strong> <?php echo isset($t['entryFeeCredits']) ? number_format($t['entryFeeCredits'], 2) . ' créditos' : 'Grátis'; ?></p>
                                <p><strong>Best Of:</strong> <?php echo isset($t['bestOf']) ? $t['bestOf'] : 1; ?></p>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matches['data'] as $m): ?>
                                    <tr>
                                        <td><?php echo $m['matchNumber']; ?></td>
                                        <td><?php echo isset($m['homeParticipantId']) ? "Participante #{$m['homeParticipantId']}" : '<em>BYE</em>'; ?></td>
                                        <td class="text-center"><strong>vs</strong></td>
                                        <td><?php echo isset($m['awayParticipantId']) ? "Participante #{$m['awayParticipantId']}" : '<em>BYE</em>'; ?></td>
                                        <td>
                                            <?php if (isset($m['homeScore']) && isset($m['awayScore'])): ?>
                                            <?php echo $m['homeScore']; ?> - <?php echo $m['awayScore']; ?>
                                            <?php else: ?>
                                            —
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($m['scheduledAt']) ? date('d/m/Y H:i', strtotime($m['scheduledAt'])) : '—'; ?></td>
                                        <td><?php echo arenagamer_status_badge($m['status']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Nenhuma partida gerada ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Sidebar -->
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Ações</h4></div>
                    <div class="panel-body">
                        <?php if (has_permission('arenagamer', '', 'edit')): ?>
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
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Datas</h4></div>
                    <div class="panel-body">
                        <p><strong>Início:</strong> <?php echo isset($t['startDate']) ? date('d/m/Y H:i', strtotime($t['startDate'])) : '—'; ?></p>
                        <p><strong>Deadline Inscrição:</strong> <?php echo isset($t['registrationDeadline']) ? date('d/m/Y H:i', strtotime($t['registrationDeadline'])) : '—'; ?></p>
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
<?php init_foot(); ?>
</body>
</html>
