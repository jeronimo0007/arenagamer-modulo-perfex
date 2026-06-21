<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$plansData = is_array($plans_list ?? null) ? $plans_list : arenagamer_api_data($plans ?? null, []);
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2">
                    <?php if (staff_can('create', 'arenagamer')): ?>
                    <a href="<?php echo admin_url('arenagamer/plan'); ?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Plano
                    </a>
                    <?php endif; ?>
                </div>
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-list"></i> ArenaGamer - Planos
                </h4>
                <hr />
            </div>
        </div>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>Erro na API:</strong> <?php echo htmlspecialchars($api_error); ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <?php if (!empty($plansData)): ?>
                <?php foreach ($plansData as $plan): ?>
                <div class="col-md-4">
                    <div class="panel_s">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <?php echo htmlspecialchars($plan['name']); ?>
                                <?php if (!empty($plan['active'])): ?>
                                    <span class="label label-success pull-right">Ativo</span>
                                <?php else: ?>
                                    <span class="label label-default pull-right">Inativo</span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="panel-body">
                            <?php if (!empty($plan['description'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($plan['description']); ?></p>
                            <?php endif; ?>
                            <ul class="list-unstyled">
                                <li><strong>ID:</strong> <?php echo (int) ($plan['id'] ?? 0); ?></li>
                                <li><strong>Preço mensal:</strong> <?php echo isset($plan['monthlyPrice']) ? arenagamer_format_credits($plan['monthlyPrice']) : '—'; ?></li>
                                <li><strong>Máx. torneios/mês:</strong> <?php echo arenagamer_plan_max_tournaments_per_month($plan); ?></li>
                                <li><strong>Máx. participantes grátis:</strong> <?php echo $plan['freeMaxParticipants'] ?? '—'; ?></li>
                                <li><strong>Ordem:</strong> <?php echo $plan['sortOrder'] ?? '—'; ?></li>
                                <li><strong>Permite taxa de entrada:</strong>
                                    <?php echo !empty($plan['allowsEntryFee']) ?
                                        '<span class="text-success"><i class="fa fa-check"></i> Sim</span>' :
                                        '<span class="text-danger"><i class="fa fa-times"></i> Não</span>'; ?>
                                </li>
                                <li><strong>Oculto:</strong>
                                    <?php echo !empty($plan['hidden']) ? '<span class="text-warning">Sim</span>' : 'Não'; ?>
                                </li>
                            </ul>

                            <?php if (staff_can('edit', 'arenagamer') || staff_can('delete', 'arenagamer')): ?>
                            <hr class="hr-panel-separator" />
                            <div class="btn-group">
                                <a href="<?php echo admin_url('arenagamer/plan_detail/' . (int) $plan['id']); ?>" class="btn btn-default btn-sm">
                                    <i class="fa fa-eye"></i> Ver
                                </a>
                                <?php if (staff_can('edit', 'arenagamer')): ?>
                                <a href="<?php echo admin_url('arenagamer/plan/' . (int) $plan['id']); ?>" class="btn btn-default btn-sm">
                                    <i class="fa fa-pencil"></i> Editar
                                </a>
                                <?php endif; ?>
                                <?php if (staff_can('delete', 'arenagamer')): ?>
                                <a href="<?php echo admin_url('arenagamer/delete_plan/' . (int) $plan['id']); ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Remover este plano?');">
                                    <i class="fa fa-trash"></i> Remover
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <p class="text-muted">Nenhum plano encontrado.</p>
                        <?php if (staff_can('create', 'arenagamer')): ?>
                        <a href="<?php echo admin_url('arenagamer/plan'); ?>" class="btn btn-primary mtop10">
                            <i class="fa fa-plus"></i> Criar primeiro plano
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
