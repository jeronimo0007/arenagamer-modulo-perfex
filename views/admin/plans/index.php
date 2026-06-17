<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-list"></i> ArenaGamer - Planos
                </h4>
                <hr />
            </div>
        </div>

        <div class="row">
            <?php if (isset($plans['data']) && !empty($plans['data'])): ?>
                <?php foreach ($plans['data'] as $plan): ?>
                <div class="col-md-4">
                    <div class="panel_s">
                        <div class="panel-heading">
                            <h4 class="panel-title"><?php echo htmlspecialchars($plan['name']); ?></h4>
                        </div>
                        <div class="panel-body">
                            <ul class="list-unstyled">
                                <li><strong>Torneios grátis/mês:</strong> <?php echo isset($plan['freeLimitPerMonth']) ? $plan['freeLimitPerMonth'] : '—'; ?></li>
                                <li><strong>Máx. participantes grátis:</strong> <?php echo isset($plan['freeMaxParticipants']) ? $plan['freeMaxParticipants'] : '—'; ?></li>
                                <li><strong>Permite taxa:</strong>
                                    <?php echo (isset($plan['allowsFee']) && $plan['allowsFee']) ?
                                        '<span class="text-success"><i class="fa fa-check"></i> Sim</span>' :
                                        '<span class="text-danger"><i class="fa fa-times"></i> Não</span>'; ?>
                                </li>
                                <li><strong>Oculto:</strong>
                                    <?php echo (isset($plan['hidden']) && $plan['hidden']) ?
                                        '<span class="text-warning">Sim</span>' :
                                        'Não'; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <p class="text-muted">Nenhum plano encontrado. Verifique a conexão com a API.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php init_foot(); ?>
</body>
</html>
