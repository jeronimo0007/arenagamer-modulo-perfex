<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$presetsData = arenagamer_api_data($presets ?? null, []);
$plansData = is_array($plans ?? null) ? $plans : [];
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-list"></i> Catálogo</h4>

        <div class="mbot20">
            <a href="<?php echo arenagamer_client_url('public_tournaments'); ?>" class="btn btn-primary btn-sm">
                <i class="fa fa-globe"></i> Ver torneios públicos
            </a>
        </div>

        <h5 class="bold">Planos</h5>
        <?php if (!empty($plansData)): ?>
        <div class="table-responsive mbot20">
            <table class="table table-striped">
                <thead><tr><th>Nome</th><th>Preço/mês</th><th>Máx. torneios/mês</th></tr></thead>
                <tbody>
                    <?php foreach ($plansData as $plan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($plan['name'] ?? ''); ?></td>
                        <td>R$ <?php echo number_format((float) ($plan['monthlyPrice'] ?? 0), 2, ',', '.'); ?></td>
                        <td><?php echo arenagamer_plan_max_tournaments_per_month($plan); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">Planos indisponíveis.</p>
        <?php endif; ?>

        <h5 class="bold">Jogos / Presets</h5>
        <?php if (!empty($presetsData)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Jogo</th><th>Plataforma</th><th>Tamanho do time</th></tr></thead>
                <tbody>
                    <?php foreach ($presetsData as $preset): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($preset['gameName'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($preset['platform'] ?? '—'); ?></td>
                        <td><?php echo (int) ($preset['teamSize'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">Presets indisponíveis. Faça login novamente para atualizar o catálogo.</p>
        <?php endif; ?>
    </div>
</div>
