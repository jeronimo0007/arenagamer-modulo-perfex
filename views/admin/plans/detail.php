<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$subscriptions = is_array($subscriptions ?? null) ? $subscriptions : [];
$subscriptionsPagination = is_array($subscriptions_pagination ?? null) ? $subscriptions_pagination : [];
$searchTerm = trim((string) ($subscriptions_search ?? ''));
$canManage = !empty($can_manage_subscriptions);
$planId = (int) ($plan['id'] ?? 0);
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-list"></i> <?php echo htmlspecialchars($plan['name'] ?? 'Plano'); ?>
                    <?php if (!empty($plan['active'])): ?>
                        <span class="label label-success">Ativo</span>
                    <?php else: ?>
                        <span class="label label-default">Inativo</span>
                    <?php endif; ?>
                </h4>
                <a href="<?php echo admin_url('arenagamer/plans'); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-arrow-left"></i> Voltar
                </a>
                <?php if (staff_can('edit', 'arenagamer')): ?>
                <a href="<?php echo admin_url('arenagamer/plan/' . $planId); ?>" class="btn btn-primary btn-xs">
                    <i class="fa fa-pencil"></i> Editar
                </a>
                <?php endif; ?>
                <hr />

                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (!empty($plan['description'])): ?>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
                        <hr />
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>ID:</strong> <?php echo $planId; ?></p>
                                <p><strong>Preço mensal:</strong> <?php echo isset($plan['monthlyPrice']) ? arenagamer_format_credits($plan['monthlyPrice']) : '—'; ?></p>
                                <p><strong>Máx. torneios/mês:</strong> <?php echo arenagamer_plan_max_tournaments_per_month($plan); ?></p>
                                <p><strong>Máx. participantes grátis:</strong> <?php echo $plan['freeMaxParticipants'] ?? '—'; ?></p>
                                <p><strong>Ordem:</strong> <?php echo $plan['sortOrder'] ?? '—'; ?></p>
                                <p><strong>Permite taxa de inscrição:</strong>
                                    <?php echo !empty($plan['allowsEntryFee']) ? 'Sim' : 'Não'; ?>
                                </p>
                                <p><strong>Oculto:</strong> <?php echo !empty($plan['hidden']) ? 'Sim' : 'Não'; ?></p>
                                <p><strong>Ativo:</strong> <?php echo !empty($plan['active']) ? 'Sim' : 'Não'; ?></p>
                            </div>
                        </div>

                        <?php if (!empty($plan['createdAt']) || !empty($plan['updatedAt'])): ?>
                        <hr />
                        <p><strong>Criado em:</strong> <?php echo arenagamer_format_date($plan['createdAt'] ?? ''); ?></p>
                        <p><strong>Atualizado em:</strong> <?php echo arenagamer_format_date($plan['updatedAt'] ?? ''); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fa fa-users"></i> Assinantes deste plano
                            <?php if (!empty($subscriptionsPagination['totalElements'])): ?>
                            <span class="badge"><?php echo (int) $subscriptionsPagination['totalElements']; ?></span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="panel-body">
                        <?php if (!empty($subscriptions_error)): ?>
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i>
                            Erro ao carregar assinantes: <?php echo htmlspecialchars($subscriptions_error); ?>
                        </div>
                        <?php endif; ?>

                        <?php echo form_open(admin_url('arenagamer/plan_detail/' . $planId), ['method' => 'get']); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                                        placeholder="Buscar por empresa do cliente...">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php echo form_close(); ?>

                        <hr />

                        <?php if (!empty($subscriptions)): ?>
                        <div class="table-responsive mtop15">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Cliente</th>
                                        <th>Empresa</th>
                                        <th>Início</th>
                                        <th>Expira em</th>
                                        <th>Status</th>
                                        <?php if ($canManage): ?><th></th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $row): ?>
                                    <?php
                                    $rowPlan = is_array($row['plan'] ?? null) ? $row['plan'] : [];
                                    $clientUserId = (int) ($row['clientUserId'] ?? 0);
                                    $currentPlanId = (int) ($rowPlan['id'] ?? 0);
                                    $pendingPlanId = (int) ($rowPlan['pendingPlanId'] ?? 0);
                                    $isScheduledForThisPlan = $pendingPlanId === $planId && $currentPlanId !== $planId;
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('clients/client/' . $clientUserId); ?>" target="_blank">
                                                #<?php echo $clientUserId; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['clientCompany'] ?? '—'); ?></td>
                                        <td><?php echo arenagamer_format_date($rowPlan['startsAt'] ?? '', 'd/m/Y'); ?></td>
                                        <td><?php echo arenagamer_format_date($rowPlan['expiresAt'] ?? '', 'd/m/Y'); ?></td>
                                        <td>
                                            <?php if ($isScheduledForThisPlan): ?>
                                            <span class="label label-info">Troca agendada</span>
                                            <br><small class="text-muted">Plano atual: <?php echo htmlspecialchars($rowPlan['name'] ?? '—'); ?></small>
                                            <?php elseif (!empty($rowPlan['scheduledChangeAtPeriodEnd'])): ?>
                                            <span class="label label-warning">Agendado</span>
                                            <?php elseif (!empty($rowPlan['cancelAtPeriodEnd'])): ?>
                                            <span class="label label-default">Cancel. agendado</span>
                                            <?php else: ?>
                                            <span class="label label-success">Ativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($canManage): ?>
                                        <td class="text-right">
                                            <?php echo form_open(admin_url('arenagamer/subscriptions')); ?>
                                            <input type="hidden" name="remove_subscription" value="1">
                                            <input type="hidden" name="client_userid" value="<?php echo $clientUserId; ?>">
                                            <button type="submit" class="btn btn-danger btn-xs"
                                                onclick="return confirm('Remover o plano de <?php echo htmlspecialchars($row['clientCompany'] ?? ('cliente #' . $clientUserId), ENT_QUOTES, 'UTF-8'); ?>?');">
                                                <i class="fa fa-trash"></i> Remover
                                            </button>
                                            <?php echo form_close(); ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($subscriptionsPagination['totalElements']) && (int) $subscriptionsPagination['totalElements'] > (int) ($subscriptionsPagination['size'] ?? 25)): ?>
                        <div class="row mtop15">
                            <div class="col-md-6">
                                <p class="text-muted mbot0">
                                    <?php echo arenagamer_pagination_info_text($subscriptionsPagination); ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php
                                $paginationQuery = array_filter(['search' => $searchTerm]);
                                echo arenagamer_render_admin_pagination(
                                    $subscriptionsPagination,
                                    'arenagamer/plan_detail/' . $planId,
                                    $paginationQuery
                                );
                                ?>
                            </div>
                        </div>
                        <?php elseif (!empty($subscriptionsPagination['totalElements'])): ?>
                        <p class="text-muted mtop15 mbot0">
                            <?php echo arenagamer_pagination_info_text($subscriptionsPagination); ?>
                        </p>
                        <?php endif; ?>

                        <?php else: ?>
                        <p class="text-muted text-center mtop15">Nenhum assinante ativo neste plano.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
