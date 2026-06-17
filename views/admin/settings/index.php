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

                        <div class="form-group">
                            <label for="api_url">URL da API</label>
                            <input type="url" name="api_url" id="api_url" class="form-control"
                                   value="<?php echo get_option('arenagamer_api_url'); ?>"
                                   placeholder="http://localhost:8080/api/v1" required>
                            <small class="text-muted">URL base da ArenaGamer API (sem trailing slash)</small>
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
                            <li><strong>API URL:</strong> <code><?php echo get_option('arenagamer_api_url'); ?></code></li>
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
<?php init_foot(); ?>
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
</script>
</body>
</html>
