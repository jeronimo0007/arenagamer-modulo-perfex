<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$walletData = arenagamer_api_data($wallet ?? null);
$authUser = $auth_user ?? null;

?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold tw-mb-4"><i class="fa fa-gamepad"></i> ArenaGamer</h4>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-warning"><?php echo htmlspecialchars($api_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($arenagamer_needs_relink)): ?>
        <div class="panel_s mtop15">
            <div class="panel-body">
                <h5 class="bold">Conectar conta ArenaGamer</h5>
                <p class="text-muted">
                    Use a mesma senha do portal Perfex. O e-mail deve existir na plataforma ArenaGamer.
                </p>
                <?php echo form_open(arenagamer_client_url('link_account')); ?>
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($arenagamer_contact_email ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="password" class="form-control" required autocomplete="current-password">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="visible-md visible-lg">&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Conectar</button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (is_array($authUser)): ?>
        <p class="text-muted">
            Olá, <?php echo htmlspecialchars(trim(($authUser['firstName'] ?? '') . ' ' . ($authUser['lastName'] ?? ''))); ?>
            <?php echo arenagamer_role_badge($authUser['role'] ?? 'PLAYER'); ?>
        </p>
        <?php endif; ?>

        <?php
        $currentPlan = is_array($current_plan ?? null) ? $current_plan : arenagamer_contact_plan($authUser ?? null);
        $planIsActive = !empty($plan_is_active) || arenagamer_plan_is_active($currentPlan);
        $canSubscribePlan = !empty($can_subscribe_plan) || arenagamer_can_subscribe_plan($authUser ?? null);
        $clientUserId = (int) ($client_user_id ?? get_client_user_id());
        $currentPlanBilling = ($currentPlan && function_exists('arenagamer_plan_active_billing_display'))
            ? arenagamer_plan_active_billing_display($currentPlan, $clientUserId)
            : null;
        ?>
        <div class="panel_s mtop15">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="bold mtop0"><i class="fa fa-credit-card"></i> Plano da conta</h5>
                        <?php if ($currentPlan): ?>
                        <p class="mbot5">
                            <strong><?php echo htmlspecialchars($currentPlan['name'] ?? '—'); ?></strong>
                            <?php echo arenagamer_plan_status_badge($currentPlan); ?>
                        </p>
                        <p class="text-muted mbot0">
                            Expira em: <?php echo arenagamer_format_date($currentPlan['expiresAt'] ?? '', 'd/m/Y'); ?>
                            &nbsp;|&nbsp;
                            <?php echo htmlspecialchars($currentPlanBilling['price_line'] ?? (arenagamer_format_money($currentPlan['monthlyPrice'] ?? 0) . '/mês')); ?>
                            <?php if (!empty($currentPlanBilling['period_label']) && (int) ($currentPlanBilling['period_months'] ?? 1) > 1): ?>
                            <span class="label label-info"><?php echo htmlspecialchars($currentPlanBilling['period_label']); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php else: ?>
                        <p class="text-muted mbot0">Nenhum plano vinculado à conta.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="<?php echo arenagamer_client_url('plans'); ?>" class="btn btn-<?php echo ($planIsActive || !$canSubscribePlan) ? 'default' : 'warning'; ?> btn-block">
                            <?php
                            if ($planIsActive) {
                                echo 'Ver plano';
                            } elseif ($canSubscribePlan) {
                                echo 'Contratar plano';
                            } else {
                                echo 'Ver plano';
                            }
                            ?>
                        </a>
                    </div>
                </div>
                <?php if (!$planIsActive && empty($arenagamer_needs_relink)): ?>
                <div class="alert alert-warning mtop15 mbot0">
                    <?php if ($canSubscribePlan): ?>
                    Esta conta precisa de um plano ativo para criar torneios e usar a carteira.
                    <?php else: ?>
                    <?php echo htmlspecialchars(arenagamer_plan_subscribe_blocked_message()); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php $this->load->view('../../modules/arenagamer/views/client/partials/buy_credits_block', ['auth_user' => $authUser ?? null]); ?>
            </div>
        </div>

        <div class="row mtop20">
            <?php $showWallet = !empty($can_view_wallet) || arenagamer_contact_can_view_wallet($authUser ?? null); ?>
            <?php if ($showWallet): ?>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <h3 class="text-success bold">
                            <?php echo $walletData ? arenagamer_format_credits($walletData['availableBalance'] ?? 0) : '—'; ?>
                        </h3>
                        <span class="text-muted">Saldo da empresa</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="<?php echo $showWallet ? 'col-md-4' : 'col-md-6'; ?>">
                <div class="panel_s">
                    <div class="panel-body text-center">
                        <h3 class="text-info bold">
                            <?php echo arenagamer_pagination_total_elements($tournaments ?? null) ?: '—'; ?>
                        </h3>
                        <span class="text-muted">Torneios gerenciáveis</span>
                    </div>
                </div>
            </div>
            <div class="<?php echo $showWallet ? 'col-md-4' : 'col-md-6'; ?>">
                <div class="panel_s">
                    <div class="panel-body">
                        <a href="<?php echo arenagamer_client_url('tournament'); ?>" class="btn btn-primary btn-block">
                            <i class="fa fa-plus"></i> Criar torneio
                        </a>
                        <a href="<?php echo arenagamer_client_url('tournaments'); ?>" class="btn btn-default btn-block mtop5">
                            <i class="fa fa-trophy"></i> Ver torneios
                        </a>
                        <a href="<?php echo arenagamer_client_url('public_tournaments'); ?>" class="btn btn-default btn-block mtop5">
                            <i class="fa fa-globe"></i> Torneios públicos
                        </a>
                        <a href="<?php echo arenagamer_client_url('teams'); ?>" class="btn btn-default btn-block mtop5">
                            <i class="fa fa-users"></i> Meus times
                        </a>
                        <a href="<?php echo arenagamer_client_url('profile'); ?>" class="btn btn-default btn-block mtop5">
                            <i class="fa fa-user"></i> Meu perfil
                        </a>
                        <?php if ($showWallet): ?>
                        <a href="<?php echo arenagamer_client_url('wallet'); ?>" class="btn btn-default btn-block mtop5">
                            <i class="fa fa-money"></i> Carteira
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($tournaments['data']['content'] ?? null)): ?>
        <h5 class="bold mtop20">Torneios recentes</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments['data']['content'] as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                        <td><?php echo arenagamer_status_badge($t['status']); ?></td>
                        <td>
                            <a href="<?php echo arenagamer_client_url('tournament_detail/' . $t['slug']); ?>" class="btn btn-default btn-xs">
                                Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
