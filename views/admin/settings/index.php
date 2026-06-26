<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-cog"></i> ArenaGamer - Configurações
                </h4>
                <hr />
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Conexão com a API</h4></div>
                    <div class="panel-body">
                        <?php echo form_open(admin_url('arenagamer/settings')); ?>

                        <p class="text-muted">
                            A API foi dividida em <strong>4 microserviços independentes</strong>, cada um com seu
                            domínio. Informe a URL base de cada serviço (o módulo adiciona automaticamente
                            <code>/api/v1</code>; o Swagger fica em <code>/swagger-ui</code>).
                        </p>

                        <?php
                        $authUrl   = get_option('arenagamer_api_url_auth') ?: 'https://auth.omnyarena.com';
                        $commonUrl = get_option('arenagamer_api_url_common') ?: 'https://common.omnyarena.com';
                        $adminUrl  = get_option('arenagamer_api_url_admin') ?: 'https://admin.omnyarena.com';
                        $publicUrl = get_option('arenagamer_api_url_public') ?: 'https://public.omnyarena.com';
                        ?>

                        <div class="form-group">
                            <label for="api_url_auth">URL do serviço Auth</label>
                            <input type="url" name="api_url_auth" id="api_url_auth" class="form-control"
                                   value="<?php echo htmlspecialchars((string) $authUrl); ?>"
                                   placeholder="https://auth.omnyarena.com" required>
                            <small class="text-muted">Login, registro, refresh, perfil do usuário e avatar (<code>/public/auth/*</code>, <code>/common/auth/*</code>, <code>/common/users/*</code>).</small>
                        </div>

                        <div class="form-group">
                            <label for="api_url_common">URL do serviço Common</label>
                            <input type="url" name="api_url_common" id="api_url_common" class="form-control"
                                   value="<?php echo htmlspecialchars((string) $commonUrl); ?>"
                                   placeholder="https://common.omnyarena.com" required>
                            <small class="text-muted">Torneios, times, carteira, assinaturas, presets e uploads (<code>/common/*</code>).</small>
                        </div>

                        <div class="form-group">
                            <label for="api_url_admin">URL do serviço Admin</label>
                            <input type="url" name="api_url_admin" id="api_url_admin" class="form-control"
                                   value="<?php echo htmlspecialchars((string) $adminUrl); ?>"
                                   placeholder="https://admin.omnyarena.com" required>
                            <small class="text-muted">Painel staff: planos, presets, assinaturas, carteira, auditoria (<code>/admin/*</code>).</small>
                        </div>

                        <div class="form-group">
                            <label for="api_url_public">URL do serviço Public</label>
                            <input type="url" name="api_url_public" id="api_url_public" class="form-control"
                                   value="<?php echo htmlspecialchars((string) $publicUrl); ?>"
                                   placeholder="https://public.omnyarena.com" required>
                            <small class="text-muted">Catálogo público com HTTP Basic: torneios, times, planos, presets (<code>/public/*</code>).</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            Use credenciais de um usuário <strong>STAFF</strong> com role <strong>ADMIN</strong> na API.
                            Usuários MANAGER conseguem fazer login, mas recebem erro 403 nos endpoints <code>/admin/*</code>.
                        </div>

                        <div class="form-group">
                            <label for="admin_email">Email do Admin</label>
                            <input type="email" name="admin_email" id="admin_email" class="form-control"
                                   value="<?php echo get_option('arenagamer_admin_email'); ?>"
                                   placeholder="admin@arenagamer.com" required>
                        </div>

                        <div class="form-group">
                            <label for="admin_password">Senha do Admin</label>
                            <input type="password" name="admin_password" id="admin_password" class="form-control"
                                   placeholder="Deixe em branco para manter a senha atual">
                        </div>

                        <div class="form-group">
                            <label for="internal_secret">Segredo interno (callback da API)</label>
                            <input type="text" name="internal_secret_display" id="internal_secret" class="form-control"
                                   value="<?php echo htmlspecialchars((string) get_option('arenagamer_internal_secret')); ?>"
                                   readonly>
                            <small class="text-muted">
                                Use este valor na configuração da API Java
                                (<code>arenagamer.perfex.internal-secret</code> ou variável
                                <code>PERFEX_INTERNAL_SECRET</code>). A API usa este segredo para criar a
                                fatura de compra de créditos no Perfex.
                            </small>
                            <div class="checkbox checkbox-primary mtop10">
                                <input type="checkbox" name="regenerate_internal_secret" id="regenerate_internal_secret" value="1">
                                <label for="regenerate_internal_secret">Gerar um novo segredo ao salvar (atualize também a API Java)</label>
                            </div>
                        </div>

                        <hr />

                        <h4 class="tw-font-semibold mtop15 mbot15">Preços de criação de torneio</h4>
                        <p class="text-muted">
                            O torneio padrão inclui até a quantidade de participantes configurada abaixo.
                            Participantes acima desse limite são cobrados como avulsos.
                        </p>

                        <?php
                        $pricing = $tournament_pricing ?? arenagamer_tournament_pricing_local();
                        $includedDefault = (int) ($pricing['includedParticipants'] ?? 8);
                        $basePrice = (float) ($pricing['baseTournamentPrice'] ?? 0);
                        $extraPrice = (float) ($pricing['extraParticipantPrice'] ?? 0);
                        ?>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tournament_base_price">Valor do torneio padrão (créditos)</label>
                                    <input type="number" step="0.01" min="0" name="tournament_base_price" id="tournament_base_price"
                                           class="form-control" value="<?php echo htmlspecialchars(number_format($basePrice, 2, '.', '')); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="included_participants">Participantes incluídos</label>
                                    <input type="number" min="2" name="included_participants" id="included_participants"
                                           class="form-control" value="<?php echo $includedDefault; ?>" required>
                                    <small class="text-muted">Ex.: 8 participantes já inclusos no valor padrão.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="extra_participant_price">Valor por participante avulso (créditos)</label>
                                    <input type="number" step="0.01" min="0" name="extra_participant_price" id="extra_participant_price"
                                           class="form-control" value="<?php echo htmlspecialchars(number_format($extraPrice, 2, '.', '')); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <strong>Exemplo:</strong> com <?php echo $includedDefault; ?> incluídos,
                            torneio para 12 participantes = <?php echo arenagamer_format_credits(arenagamer_calculate_tournament_creation_cost(12, $pricing)); ?>.
                        </div>

                        <hr />

                        <h4 class="tw-font-semibold mtop15 mbot15">Regras de times</h4>
                        <?php
                        $teamSettings = is_array($team_settings ?? null) ? $team_settings : arenagamer_team_settings_local();
                        $maxOwned = (int) ($teamSettings['maxOwnedTeamsPerClient'] ?? $teamSettings['maxOwnedTeamsPerContact'] ?? 1);
                        $maxParticipated = (int) ($teamSettings['maxParticipatedTeamsPerClient'] ?? $teamSettings['maxParticipatedTeamsPerContact'] ?? 3);
                        $maxTournamentsTeam = $teamSettings['maxTournamentsPerTeam'] ?? null;
                        $maxTournamentsClient = $teamSettings['maxTournamentsPerClient'] ?? null;
                        $unlimitedTournamentsTeam = !empty($teamSettings['unlimitedTournamentsPerTeam']) || $maxTournamentsTeam === null;
                        $unlimitedTournamentsClient = !empty($teamSettings['unlimitedTournamentsPerClient']) || $maxTournamentsClient === null;
                        ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_owned_teams">Times como dono por cliente</label>
                                    <input type="number" min="1" name="max_owned_teams" id="max_owned_teams" class="form-control" value="<?php echo $maxOwned; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_participated_teams">Participação em times por cliente</label>
                                    <input type="number" min="1" name="max_participated_teams" id="max_participated_teams" class="form-control" value="<?php echo $maxParticipated; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_tournaments_per_team">Campeonatos simultâneos por equipe</label>
                                    <input type="number" min="1" name="max_tournaments_per_team" id="max_tournaments_per_team" class="form-control"
                                           value="<?php echo $maxTournamentsTeam !== null ? (int) $maxTournamentsTeam : ''; ?>"
                                           <?php echo $unlimitedTournamentsTeam ? 'disabled' : ''; ?>>
                                    <label class="mtop5">
                                        <input type="checkbox" name="unlimited_tournaments_per_team" value="1" id="unlimited_tournaments_per_team"
                                            <?php echo $unlimitedTournamentsTeam ? 'checked' : ''; ?>>
                                        Ilimitado
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_tournaments_per_client">Campeonatos simultâneos por cliente</label>
                                    <input type="number" min="1" name="max_tournaments_per_client" id="max_tournaments_per_client" class="form-control"
                                           value="<?php echo $maxTournamentsClient !== null ? (int) $maxTournamentsClient : ''; ?>"
                                           <?php echo $unlimitedTournamentsClient ? 'disabled' : ''; ?>>
                                    <p class="help-block">Inscrições solo ou em equipes em que o cliente participa.</p>
                                    <label class="mtop5">
                                        <input type="checkbox" name="unlimited_tournaments_per_client" value="1" id="unlimited_tournaments_per_client"
                                            <?php echo $unlimitedTournamentsClient ? 'checked' : ''; ?>>
                                        Ilimitado
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr />

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_sync" value="1"
                                    <?php echo get_option('arenagamer_auto_sync') == '1' ? 'checked' : ''; ?>>
                                Sincronização Automática
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="sync_interval">Intervalo de Sincronização (segundos)</label>
                            <select name="sync_interval" id="sync_interval" class="form-control">
                                <option value="60" <?php echo get_option('arenagamer_sync_interval') == '60' ? 'selected' : ''; ?>>1 minuto</option>
                                <option value="300" <?php echo get_option('arenagamer_sync_interval') == '300' ? 'selected' : ''; ?>>5 minutos</option>
                                <option value="600" <?php echo get_option('arenagamer_sync_interval') == '600' ? 'selected' : ''; ?>>10 minutos</option>
                                <option value="1800" <?php echo get_option('arenagamer_sync_interval') == '1800' ? 'selected' : ''; ?>>30 minutos</option>
                                <option value="3600" <?php echo get_option('arenagamer_sync_interval') == '3600' ? 'selected' : ''; ?>>1 hora</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Salvar Configurações
                        </button>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Testar Conexão</h4></div>
                    <div class="panel-body">
                        <p class="text-muted">Verifique se a API está acessível e as credenciais estão corretas.</p>
                        <button id="btn-test" class="btn btn-success btn-block" onclick="testApiConnection()">
                            <i class="fa fa-plug"></i> Testar Conexão
                        </button>
                        <div id="test-result" class="mtop10"></div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading"><h4 class="panel-title">Informações</h4></div>
                    <div class="panel-body">
                        <ul class="list-unstyled">
                            <li><strong>Módulo:</strong> ArenaGamer v1.0.0</li>
                            <li><strong>Auth:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_auth')); ?></code></li>
                            <li><strong>Common:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_common')); ?></code></li>
                            <li><strong>Admin:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_admin')); ?></code></li>
                            <li><strong>Public:</strong> <code><?php echo htmlspecialchars((string) get_option('arenagamer_api_url_public')); ?></code></li>
                            <li><strong>Token:</strong>
                                <?php echo get_option('arenagamer_api_token') ?
                                    '<span class="text-success">Configurado</span>' :
                                    '<span class="text-warning">Não configurado</span>'; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
function testApiConnection() {
    var btn = $('#btn-test');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testando...');
    $('#test-result').html('');

    $.get('<?php echo admin_url("arenagamer/test_connection"); ?>', function(data) {
        var res = typeof data === 'string' ? JSON.parse(data) : data;
        if (res.success) {
            $('#test-result').html('<div class="alert alert-success"><i class="fa fa-check"></i> ' + res.message + '</div>');
        } else {
            $('#test-result').html('<div class="alert alert-danger"><i class="fa fa-times"></i> ' + res.message + '</div>');
        }
    }).fail(function() {
        $('#test-result').html('<div class="alert alert-danger"><i class="fa fa-times"></i> Erro de conexão</div>');
    }).always(function() {
        btn.prop('disabled', false).html('<i class="fa fa-plug"></i> Testar Conexão');
    });
}

$('#unlimited_tournaments_per_team').on('change', function () {
    $('#max_tournaments_per_team').prop('disabled', this.checked);
    if (this.checked) {
        $('#max_tournaments_per_team').val('');
    }
});

$('#unlimited_tournaments_per_client').on('change', function () {
    $('#max_tournaments_per_client').prop('disabled', this.checked);
    if (this.checked) {
        $('#max_tournaments_per_client').val('');
    }
});
</script>
