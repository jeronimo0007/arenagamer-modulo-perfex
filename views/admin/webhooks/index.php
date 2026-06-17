<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-plug"></i> ArenaGamer - Webhooks
                </h4>
                <hr />
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-heading">
                <h4 class="panel-title">Configuração de Webhooks</h4>
            </div>
            <div class="panel-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    O gerenciamento avançado de webhooks estará disponível na próxima versão.
                    Atualmente, webhooks podem ser configurados diretamente via API.
                </div>

                <h5>Eventos Disponíveis:</h5>
                <ul>
                    <li><code>tournament.created</code> - Torneio criado</li>
                    <li><code>tournament.status_changed</code> - Status do torneio alterado</li>
                    <li><code>participant.joined</code> - Participante inscrito</li>
                    <li><code>participant.kicked</code> - Participante removido</li>
                    <li><code>bracket.generated</code> - Chaves geradas</li>
                    <li><code>match.scheduled</code> - Partida agendada</li>
                    <li><code>match.completed</code> - Partida finalizada</li>
                    <li><code>wallet.transaction</code> - Transação de créditos</li>
                </ul>

                <h5>Endpoint de Cadastro:</h5>
                <code>POST <?php echo get_option('arenagamer_api_url'); ?>/webhooks/subscribe</code>

                <h5 class="mtop15">Payload de Exemplo:</h5>
<pre>{
  "url": "https://seu-servidor.com/webhook",
  "events": ["tournament.created", "match.completed"],
  "secret": "sua-chave-secreta"
}</pre>
            </div>
        </div>
    </div>
</div>
<?php init_foot(); ?>
</body>
</html>
