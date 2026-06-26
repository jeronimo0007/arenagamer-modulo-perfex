<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-gamepad"></i> ArenaGamer - Dashboard
                </h4>
                <hr />
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h3 class="text-success bold">
                            <?php echo arenagamer_pagination_total_elements($tournaments ?? null) ?: '—'; ?>
                        </h3>
                        <span class="text-muted">Torneios gerenciáveis</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h3 class="text-info bold">
                            <?php echo arenagamer_pagination_total_elements($users ?? null) ?: '—'; ?>
                        </h3>
                        <span class="text-muted">Usuários</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h3 class="text-warning bold">
                            <?php
                            $wallet = arenagamer_api_data($wallet ?? null);
                            echo $wallet && isset($wallet['availableBalance'])
                                ? arenagamer_format_credits($wallet['availableBalance'])
                                : '—';
                            ?>
                        </h3>
                        <span class="text-muted">Saldo Disponível</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h3 class="text-danger bold">
                            <?php
                            echo $wallet && isset($wallet['heldBalance'])
                                ? arenagamer_format_credits($wallet['heldBalance'])
                                : '—';
                            ?>
                        </h3>
                        <span class="text-muted">Saldo Retido</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Tournaments -->
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h4 class="panel-title">Torneios Recentes</h4>
                    </div>
                    <div class="panel-body">
                        <?php if (isset($tournaments['data']['content']) && !empty($tournaments['data']['content'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Participantes</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tournaments['data']['content'] as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                                        <td><span class="label label-default"><?php echo htmlspecialchars(arenagamer_tournament_type_label($t['type'] ?? '')); ?></span></td>
                                        <td><?php echo arenagamer_status_badge($t['status']); ?></td>
                                        <td><?php echo isset($t['participantCount']) ? $t['participantCount'] . ' / ' . ($t['participantsLimit'] ?? '—') : (isset($t['participantsLimit']) ? $t['participantsLimit'] : '—'); ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('arenagamer/tournament_detail/' . $t['slug']); ?>"
                                               class="btn btn-default btn-xs">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted">Nenhum torneio encontrado. Verifique a conexão com a API.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading">
                        <h4 class="panel-title">Ações Rápidas</h4>
                    </div>
                    <div class="panel-body">
                        <a href="<?php echo admin_url('arenagamer/tournaments?view=my-managed'); ?>" class="btn btn-primary btn-block mtop5">
                            <i class="fa fa-trophy"></i> Gerenciar Torneios
                        </a>
                        <a href="<?php echo admin_url('arenagamer/users'); ?>" class="btn btn-info btn-block mtop5">
                            <i class="fa fa-users"></i> Ver Usuários
                        </a>
                        <a href="<?php echo admin_url('arenagamer/plans'); ?>" class="btn btn-success btn-block mtop5">
                            <i class="fa fa-list"></i> Gerenciar Planos
                        </a>
                        <a href="<?php echo admin_url('arenagamer/settings'); ?>" class="btn btn-default btn-block mtop5">
                            <i class="fa fa-cog"></i> Configurações
                        </a>
                    </div>
                </div>

                <!-- API Status -->
                <div class="panel_s">
                    <div class="panel-heading">
                        <h4 class="panel-title">Status da API</h4>
                    </div>
                    <div class="panel-body">
                        <p class="tw-mb-1"><strong>Auth:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_auth')); ?></code></p>
                        <p class="tw-mb-1"><strong>Common:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_common')); ?></code></p>
                        <p class="tw-mb-1"><strong>Admin:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_admin')); ?></code></p>
                        <p><strong>Public:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_public')); ?></code></p>
                        <button id="btn-test-api" class="btn btn-default btn-xs" onclick="testApiConnection()">
                            <i class="fa fa-plug"></i> Testar Conexão
                        </button>
                        <span id="api-status" class="mleft5"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
function testApiConnection() {
    $('#api-status').html('<i class="fa fa-spinner fa-spin"></i>');
    $.get('<?php echo admin_url("arenagamer/test_connection"); ?>', function(data) {
        var res = typeof data === 'string' ? JSON.parse(data) : data;
        if (res.success) {
            $('#api-status').html('<span class="text-success"><i class="fa fa-check"></i> Conectado</span>');
        } else {
            $('#api-status').html('<span class="text-danger"><i class="fa fa-times"></i> ' + res.message + '</span>');
        }
    }).fail(function() {
        $('#api-status').html('<span class="text-danger"><i class="fa fa-times"></i> Erro de conexão</span>');
    });
}
</script>
