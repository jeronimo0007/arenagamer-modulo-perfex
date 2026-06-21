<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$plansData = is_array($plans_list ?? null) ? $plans_list : arenagamer_api_data($plans ?? null, []);
$subscriptions = is_array($subscriptions ?? null) ? $subscriptions : [];
$subscriptionsPagination = is_array($subscriptions_pagination ?? null) ? $subscriptions_pagination : [];
$clientOptions = is_array($client_options ?? null) ? $client_options : [];
$billingPeriods = is_array($billing_periods ?? null) ? $billing_periods : [];
$canManage = !empty($can_manage_subscriptions);
$searchTerm = trim((string) ($search ?? ''));
$clientSearchTerm = trim((string) ($client_search ?? ''));
$paginationQuery = array_filter([
    'search'        => $searchTerm,
    'client_search' => $clientSearchTerm,
]);
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-users"></i> ArenaGamer - Assinaturas de clientes
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

        <?php if (!$canManage): ?>
        <div class="alert alert-info">
            Você pode visualizar as assinaturas. Somente staff com permissão de <strong>editar</strong> ArenaGamer pode atribuir ou remover planos.
        </div>
        <?php endif; ?>

        <?php if ($canManage): ?>
        <div class="panel_s mbot20">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-user-plus"></i> Atribuir plano a cliente</h4>
            </div>
            <div class="panel-body">
                <?php echo form_open(admin_url('arenagamer/subscriptions'), ['method' => 'get', 'class' => 'mbot15']); ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group mbot0">
                            <label for="client_search">Buscar cliente para atribuir</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="client_search" id="client_search"
                                    value="<?php echo htmlspecialchars($clientSearchTerm); ?>"
                                    placeholder="Nome da empresa ou ID do cliente">
                                <?php if ($searchTerm !== ''): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                                <?php endif; ?>
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>

                <?php echo form_open(admin_url('arenagamer/subscriptions')); ?>
                <input type="hidden" name="assign_subscription" value="1">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="client_userid">Cliente <span class="text-danger">*</span></label>
                            <select name="client_userid" id="client_userid" class="form-control selectpicker" data-live-search="true" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($clientOptions as $client): ?>
                                <option value="<?php echo (int) ($client['userid'] ?? 0); ?>">
                                    #<?php echo (int) ($client['userid'] ?? 0); ?> — <?php echo htmlspecialchars($client['company'] ?? ''); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_id">Plano <span class="text-danger">*</span></label>
                            <select name="plan_id" id="plan_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($plansData as $plan): ?>
                                <?php if (empty($plan['active'])) { continue; } ?>
                                <option value="<?php echo (int) ($plan['id'] ?? 0); ?>">
                                    <?php echo htmlspecialchars($plan['name'] ?? ''); ?>
                                    (<?php echo arenagamer_format_credits($plan['monthlyPrice'] ?? 0); ?>/mês)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="billing_period">Período</label>
                            <select name="billing_period" id="billing_period" class="form-control">
                                <?php foreach ($billingPeriods as $months => $periodConfig): ?>
                                <option value="<?php echo (int) $months; ?>">
                                    <?php echo htmlspecialchars($periodConfig['label']); ?>
                                    <?php if (!empty($periodConfig['discount'])): ?>
                                    (-<?php echo (int) $periodConfig['discount']; ?>%)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="visible-md visible-lg">&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Atribuir este plano ao cliente selecionado?');">
                            <i class="fa fa-check"></i> Atribuir plano
                        </button>
                        <p class="text-muted mtop10 mbot0">
                            <small>Ação imediata na API — não gera fatura Perfex.</small>
                        </p>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel_s">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-list"></i> Clientes com plano ativo</h4>
            </div>
            <div class="panel-body panel-table-full">
                <?php echo form_open(admin_url('arenagamer/subscriptions'), ['method' => 'get']); ?>
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
                                <th>Plano</th>
                                <th>Torneios/mês</th>
                                <th>Início</th>
                                <th>Expira em</th>
                                <th>Status</th>
                                <?php if ($canManage): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $row): ?>
                            <?php
                            $plan = is_array($row['plan'] ?? null) ? $row['plan'] : [];
                            $clientUserId = (int) ($row['clientUserId'] ?? 0);
                            $tournamentsUsed = (int) ($plan['tournamentsUsedThisMonth'] ?? 0);
                            $tournamentsLimit = (int) ($plan['freeTournamentsPerMonth'] ?? $plan['maxTournamentsPerMonth'] ?? 0);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('clients/client/' . $clientUserId); ?>" target="_blank">
                                        #<?php echo $clientUserId; ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['clientCompany'] ?? '—'); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($plan['name'] ?? '—'); ?></strong>
                                    <br><small class="text-muted">ID plano: <?php echo (int) ($plan['id'] ?? 0); ?></small>
                                </td>
                                <td>
                                    <?php if ($tournamentsLimit > 0): ?>
                                    <strong><?php echo $tournamentsUsed; ?></strong> / <?php echo $tournamentsLimit; ?>
                                    <?php if ($tournamentsUsed >= $tournamentsLimit): ?>
                                    <br><span class="label label-danger">Limite</span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo arenagamer_format_date($plan['startsAt'] ?? '', 'd/m/Y'); ?></td>
                                <td><?php echo arenagamer_format_date($plan['expiresAt'] ?? '', 'd/m/Y'); ?></td>
                                <td>
                                    <?php if (!empty($plan['scheduledChangeAtPeriodEnd'])): ?>
                                    <span class="label label-warning">Agendado</span>
                                    <?php elseif (!empty($plan['cancelAtPeriodEnd'])): ?>
                                    <span class="label label-default">Cancel. agendado</span>
                                    <?php else: ?>
                                    <span class="label label-success">Ativo</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canManage): ?>
                                <td class="text-right">
                                    <?php echo form_open(admin_url('arenagamer/subscriptions'), ['style' => 'display:inline;']); ?>
                                    <input type="hidden" name="reset_subscription_usage" value="1">
                                    <input type="hidden" name="client_userid" value="<?php echo $clientUserId; ?>">
                                    <button type="submit" class="btn btn-warning btn-xs mright5"
                                        title="Zera contagem mensal de torneios e remove cancelamento/downgrade agendado"
                                        onclick="return confirm('Resetar uso mensal do plano de <?php echo htmlspecialchars($row['clientCompany'] ?? ('cliente #' . $clientUserId), ENT_QUOTES, 'UTF-8'); ?>?\n\nIsso zera a contagem de torneios inclusos deste mês e remove agendamentos de cancelamento ou troca de plano.');">
                                        <i class="fa fa-refresh"></i> Resetar uso
                                    </button>
                                    <?php echo form_close(); ?>
                                    <?php echo form_open(admin_url('arenagamer/subscriptions'), ['style' => 'display:inline;']); ?>
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

                <div class="row mtop15">
                    <div class="col-md-6">
                        <p class="text-muted mbot0">
                            <?php echo arenagamer_pagination_info_text($subscriptionsPagination); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php echo arenagamer_render_admin_pagination($subscriptionsPagination, 'arenagamer/subscriptions', $paginationQuery); ?>
                    </div>
                </div>

                <?php else: ?>
                <p class="text-muted text-center mtop15">Nenhuma assinatura ativa encontrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
