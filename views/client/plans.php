<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$currentPlan = is_array($current_plan ?? null) ? $current_plan : null;
$availablePlans = is_array($available_plans ?? null) ? $available_plans : [];
$planIsActive = !empty($plan_is_active);
$canSubscribePlan = !empty($can_subscribe_plan);
$canCancelPlan = !empty($can_cancel_plan);
$pendingPlanInvoice = is_array($pending_plan_invoice ?? null) ? $pending_plan_invoice : null;
$billingPeriods = function_exists('arenagamer_billing_periods') ? arenagamer_billing_periods() : [];
$clientUserId = (int) ($client_user_id ?? get_client_user_id());
$walletData = is_array($wallet ?? null) ? $wallet : [];
$walletAvailable = (float) ($walletData['availableBalance'] ?? 0);
$currentPlanBilling = ($currentPlan && function_exists('arenagamer_plan_active_billing_display'))
    ? arenagamer_plan_active_billing_display($currentPlan, $clientUserId)
    : null;
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-credit-card"></i> Planos</h4>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-warning"><?php echo htmlspecialchars($api_error); ?></div>
        <?php endif; ?>

        <?php if ($pendingPlanInvoice): ?>
        <div class="alert alert-info">
            Existe uma fatura pendente para o plano <strong><?php echo htmlspecialchars($pendingPlanInvoice['plan_name'] ?? ''); ?></strong>.
            <a href="<?php echo htmlspecialchars(arenagamer_invoice_payment_url((int) $pendingPlanInvoice['invoice_id'])); ?>" class="alert-link">
                Pagar fatura agora
            </a>
        </div>
        <?php endif; ?>

        <div class="panel_s mbot20">
            <div class="panel-body">
                <h5 class="bold mtop0">Plano da conta</h5>

                <?php if (!$canSubscribePlan): ?>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars(arenagamer_plan_subscribe_blocked_message()); ?>
                </div>
                <?php endif; ?>

                <?php if ($currentPlan): ?>
                <div class="row">
                    <div class="col-md-8">
                        <p class="mtop10">
                            <strong><?php echo htmlspecialchars($currentPlan['name'] ?? '—'); ?></strong>
                            <?php echo arenagamer_plan_status_badge($currentPlan); ?>
                        </p>
                        <?php if (!empty($currentPlan['description'])): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($currentPlan['description']); ?></p>
                        <?php endif; ?>
                        <p>
                            <strong>Início:</strong> <?php echo arenagamer_format_date($currentPlan['startsAt'] ?? '', 'd/m/Y'); ?>
                            &nbsp;|&nbsp;
                            <strong>Expira em:</strong> <?php echo arenagamer_format_date($currentPlan['expiresAt'] ?? '', 'd/m/Y'); ?>
                        </p>
                        <p>
                            <strong>Período:</strong> <?php echo htmlspecialchars($currentPlanBilling['period_label'] ?? 'Mensal'); ?>
                        </p>
                        <p>
                            <strong><?php echo !empty($currentPlanBilling['period_months']) && (int) $currentPlanBilling['period_months'] > 1 ? 'Valor do período' : 'Cobrança'; ?>:</strong>
                            <?php echo htmlspecialchars($currentPlanBilling['price_line'] ?? arenagamer_format_money($currentPlan['monthlyPrice'] ?? 0) . '/mês'); ?>
                        </p>
                        <?php if (!empty($currentPlanBilling['period_months']) && (int) $currentPlanBilling['period_months'] > 1): ?>
                        <p class="text-muted mbot0">
                            <small>Referência: <?php echo arenagamer_format_money($currentPlan['monthlyPrice'] ?? 0); ?>/mês (base do plano)</small>
                        </p>
                        <?php endif; ?>
                        <p class="text-muted mbot0">
                            Torneios usados este mês:
                            <?php echo (int) ($currentPlan['tournamentsUsedThisMonth'] ?? 0); ?>
                            /
                            <?php echo arenagamer_plan_max_tournaments_per_month($currentPlan); ?>
                        </p>
                        <?php $scheduledMessage = arenagamer_plan_scheduled_message($currentPlan); ?>
                        <?php if ($scheduledMessage !== ''): ?>
                        <div class="alert alert-info mtop15 mbot0"><?php echo htmlspecialchars($scheduledMessage); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-right">
                        <?php if (!$planIsActive): ?>
                        <div class="alert alert-danger mtop10 mbot0">
                            <?php if ($canSubscribePlan): ?>
                                <?php echo empty($currentPlan['expiresAt']) ? 'Esta conta não possui um plano ativo.' : 'O plano da conta está expirado. Renove ou escolha outro plano abaixo.'; ?>
                            <?php else: ?>
                                <?php echo empty($currentPlan['expiresAt']) ? 'Esta conta não possui um plano ativo.' : 'O plano da conta está expirado.'; ?>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-success mtop20"><i class="fa fa-check-circle"></i> Plano ativo</p>
                        <?php if ($canCancelPlan): ?>
                        <?php echo form_open(arenagamer_client_url('plans')); ?>
                        <input type="hidden" name="cancel_plan" value="1">
                        <button type="submit" class="btn btn-danger btn-block mtop10"
                            onclick="return confirm('Agendar cancelamento do plano <?php echo htmlspecialchars($currentPlan['name'] ?? ''); ?>? Você permanece no plano até <?php echo arenagamer_format_date($currentPlan['expiresAt'] ?? '', 'd/m/Y'); ?>.');">
                            <i class="fa fa-times"></i> Agendar cancelamento
                        </button>
                        <?php echo form_close(); ?>
                        <?php elseif ($planIsActive && arenagamer_plan_is_free($currentPlan)): ?>
                        <p class="text-muted mtop15 mbot0"><small>O plano Free não pode ser cancelado.</small></p>
                        <?php elseif ($planIsActive && arenagamer_plan_cancel_scheduled($currentPlan)): ?>
                        <p class="text-muted mtop15 mbot0"><small>Cancelamento já agendado para o fim do período.</small></p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning mbot0">
                    <?php if ($canSubscribePlan): ?>
                    Esta conta ainda não possui um plano vinculado. Escolha um plano abaixo para começar a usar o ArenaGamer.
                    <?php else: ?>
                    Esta conta ainda não possui um plano vinculado.
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php get_instance()->load->view('../../modules/arenagamer/views/client/partials/buy_credits_block', ['auth_user' => $auth_user ?? null]); ?>
            </div>
        </div>

        <h5 class="bold">Planos disponíveis</h5>

        <?php if (!$canSubscribePlan && !empty($availablePlans)): ?>
        <p class="text-muted">A visualização abaixo é apenas informativa. Somente o contato principal pode contratar ou alterar planos.</p>
        <?php endif; ?>

        <?php if (!empty($availablePlans)): ?>
        <div class="row">
            <?php foreach ($availablePlans as $plan): ?>
            <?php
            $action = arenagamer_plan_compare_action($currentPlan, $plan);
            $isCurrent = $action === 'current';
            $planPeriodCosts = (!arenagamer_plan_is_free($plan) && !empty($billingPeriods))
                ? arenagamer_plan_credit_costs_by_period($plan, $billingPeriods)
                : [];
            $planBaseMonthly = (float) ($plan['monthlyPrice'] ?? 0);
            ?>
            <div class="col-md-4 mbot20">
                <div class="panel_s plan-card" style="height:100%;<?php echo $isCurrent ? ' border: 2px solid #84c529;' : ''; ?>"
                    <?php if (!empty($planPeriodCosts)): ?>
                    data-period-costs="<?php echo htmlspecialchars(json_encode($planPeriodCosts), ENT_QUOTES, 'UTF-8'); ?>"
                    <?php endif; ?>>
                    <div class="panel-body">
                        <h5 class="bold mtop0">
                            <?php echo htmlspecialchars($plan['name'] ?? ''); ?>
                            <?php if ($isCurrent): ?>
                            <span class="label label-success">Atual</span>
                            <?php endif; ?>
                        </h5>
                        <?php if (!empty($plan['description'])): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($plan['description']); ?></p>
                        <?php endif; ?>
                        <h3 class="text-primary bold mtop15 mbot10 plan-price-heading">
                            <span class="plan-display-monthly-price"><?php echo arenagamer_format_money($planBaseMonthly); ?></span>
                            <small class="text-muted plan-display-monthly-suffix" style="font-size:14px;">/mês</small>
                        </h3>
                        <?php if (!arenagamer_plan_is_free($plan) && !empty($billingPeriods)): ?>
                        <p class="text-muted mbot10" style="font-size:12px;">
                            <?php
                            $periodHints = [];
                            foreach ($billingPeriods as $months => $periodConfig) {
                                if ((int) $months === 1) {
                                    continue;
                                }
                                $periodHints[] = $periodConfig['label'] . ' ' . arenagamer_format_money(
                                    arenagamer_plan_billing_amount($plan['monthlyPrice'] ?? 0, $months)
                                );
                            }
                            echo htmlspecialchars(implode(' · ', $periodHints));
                            ?>
                        </p>
                        <?php endif; ?>
                        <ul class="list-unstyled text-muted">
                            <li><i class="fa fa-check text-success"></i> Máx. <?php echo arenagamer_plan_max_tournaments_per_month($plan); ?> torneios/mês</li>
                            <li><i class="fa fa-check text-success"></i> Até <?php echo (int) ($plan['freeMaxParticipants'] ?? 0); ?> participantes (grátis)</li>
                            <li>
                                <i class="fa fa-<?php echo !empty($plan['allowsEntryFee']) ? 'check text-success' : 'times text-muted'; ?>"></i>
                                Taxa de inscrição <?php echo !empty($plan['allowsEntryFee']) ? 'permitida' : 'não permitida'; ?>
                            </li>
                        </ul>

                        <?php if ($isCurrent): ?>
                        <button type="button" class="btn btn-default btn-block" disabled>
                            <?php echo arenagamer_plan_action_label($action); ?>
                        </button>
                        <?php elseif ($canSubscribePlan && arenagamer_plan_is_pending_target($currentPlan, $plan)): ?>
                        <button type="button" class="btn btn-default btn-block" disabled>
                            Downgrade agendado
                        </button>
                        <?php elseif (!$canSubscribePlan): ?>
                        <button type="button" class="btn btn-default btn-block" disabled title="<?php echo htmlspecialchars(arenagamer_plan_subscribe_blocked_message()); ?>">
                            <?php echo arenagamer_plan_action_label($action); ?>
                        </button>
                        <?php else: ?>
                        <?php
                        $requiresPaidCheckout = arenagamer_plan_requires_invoice($action, $plan);
                        $periodCosts = $requiresPaidCheckout ? $planPeriodCosts : [];
                        ?>
                        <?php echo form_open(arenagamer_client_url('plans'), [
                            'class' => 'plan-subscribe-form',
                            'data-plan-id' => (int) ($plan['id'] ?? 0),
                            'data-plan-name' => (string) ($plan['name'] ?? ''),
                            'data-action' => $action,
                            'data-requires-checkout' => $requiresPaidCheckout ? '1' : '0',
                            'data-period-costs' => htmlspecialchars(json_encode($periodCosts), ENT_QUOTES, 'UTF-8'),
                        ]); ?>
                        <input type="hidden" name="plan_id" value="<?php echo (int) ($plan['id'] ?? 0); ?>">
                        <input type="hidden" name="pay_with_credits" value="0" class="plan-pay-with-credits">
                        <?php if (!arenagamer_plan_is_free($plan) && !empty($billingPeriods)): ?>
                        <div class="mbot15">
                            <p class="text-muted mbot5" style="font-size:12px;"><strong>Período de cobrança</strong></p>
                            <?php foreach ($billingPeriods as $months => $periodConfig): ?>
                            <?php
                            $periodTotal = arenagamer_plan_billing_amount($plan['monthlyPrice'] ?? 0, $months);
                            $periodId = 'billing-' . (int) ($plan['id'] ?? 0) . '-' . (int) $months;
                            ?>
                            <div class="radio radio-primary mtop0 mbot5">
                                <input type="radio"
                                    name="billing_period"
                                    id="<?php echo htmlspecialchars($periodId); ?>"
                                    value="<?php echo (int) $months; ?>"
                                    <?php echo (int) $months === 1 ? 'checked' : ''; ?>>
                                <label for="<?php echo htmlspecialchars($periodId); ?>">
                                    <?php echo htmlspecialchars($periodConfig['label']); ?>
                                    <?php if (!empty($periodConfig['discount'])): ?>
                                    <span class="label label-success" style="font-size:10px;">-<?php echo (int) $periodConfig['discount']; ?>%</span>
                                    <?php endif; ?>
                                    — <?php echo arenagamer_format_money($periodTotal); ?>
                                    <span class="text-muted">(<?php echo arenagamer_format_credits($periodTotal); ?>)</span>
                                    <?php if ((int) $months > 1): ?>
                                    <span class="text-muted">(<?php echo htmlspecialchars($periodConfig['short']); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="billing_period" value="1">
                        <?php endif; ?>
                        <button type="<?php echo $requiresPaidCheckout ? 'button' : 'submit'; ?>"
                            class="btn btn-primary btn-block plan-subscribe-btn"
                            <?php if (!$requiresPaidCheckout && in_array($action, ['downgrade', 'change'], true)): ?>
                            onclick="return confirm('<?php echo htmlspecialchars(arenagamer_plan_action_confirm_message($action, $currentPlan, $plan), ENT_QUOTES, 'UTF-8'); ?>');"
                            <?php endif; ?>>
                            <?php echo arenagamer_plan_action_label($action); ?>
                        </button>
                        <?php if ($requiresPaidCheckout): ?>
                        <p class="text-muted text-center mtop10 mbot0" style="font-size:12px;">
                            Pagamento por fatura (dinheiro) ou créditos da carteira.
                        </p>
                        <?php endif; ?>
                        <?php echo form_close(); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted">Nenhum plano disponível no momento.</p>
        <?php endif; ?>

        <a href="<?php echo arenagamer_client_url(); ?>" class="btn btn-default mtop10">&larr; Voltar ao painel</a>
    </div>
</div>

<div class="modal fade" id="planCheckoutModal" tabindex="-1" role="dialog" aria-labelledby="planCheckoutModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="planCheckoutModalLabel">
                    <i class="fa fa-shopping-cart"></i> Confirmar contratação
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted mtop0">
                    Escolha como deseja pagar. A opção recomendada é gerar a fatura e pagar em dinheiro.
                </p>
                <p class="mbot10">
                    <strong>Plano:</strong> <span id="plan-confirm-name">—</span><br>
                    <strong>Ação:</strong> <span id="plan-confirm-action">—</span><br>
                    <strong>Período:</strong> <span id="plan-confirm-period">—</span>
                </p>
                <table class="table table-condensed table-bordered mbot10">
                    <tbody>
                        <tr class="active">
                            <td><strong>Total a pagar</strong></td>
                            <td class="text-right">
                                <strong id="plan-confirm-total-money">—</strong>
                                <br>
                                <span class="text-muted" id="plan-confirm-total-credits">—</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Seu saldo em créditos</td>
                            <td class="text-right"><strong id="plan-confirm-wallet"><?php echo arenagamer_format_credits($walletAvailable); ?></strong></td>
                        </tr>
                        <tr id="plan-confirm-balance-after-row" style="display:none;">
                            <td class="text-success">Saldo após pagar com créditos</td>
                            <td class="text-right text-success" id="plan-confirm-balance-after">—</td>
                        </tr>
                        <tr id="plan-confirm-missing-row" style="display:none;">
                            <td class="text-danger">Faltam em créditos</td>
                            <td class="text-right text-danger" id="plan-confirm-missing">—</td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-muted mbot0" style="font-size:12px;">
                    1 crédito = R$ 1,00. O total é o mesmo em dinheiro (fatura) ou em créditos.
                </p>
                <div class="alert alert-info mtop10 mbot0">
                    <i class="fa fa-info-circle"></i>
                    <strong>Dinheiro:</strong> gera fatura para pagamento.
                    <strong>Créditos:</strong> debita o saldo e ativa o plano na hora.
                </div>
                <div class="alert alert-danger mtop10 mbot0" id="plan-confirm-insufficient" style="display:none;">
                    <i class="fa fa-exclamation-triangle"></i>
                    Saldo insuficiente para pagar com créditos. Pague com dinheiro (fatura) ou compre mais créditos.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-default" id="plan-credit-confirm-btn">
                    <i class="fa fa-database"></i> Pagar com créditos
                </button>
                <button type="button" class="btn btn-primary" id="plan-invoice-confirm-btn">
                    <i class="fa fa-file-text-o"></i> Pagar com dinheiro (fatura)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var walletAvailable = <?php echo json_encode($walletAvailable); ?>;
    var billingLabels = <?php
    $billingLabelMap = [];
    foreach ($billingPeriods as $months => $periodConfig) {
        $billingLabelMap[(int) $months] = $periodConfig['label'] ?? '';
    }
    echo json_encode($billingLabelMap);
    ?>;
    var actionLabels = {
        subscribe: 'Contratar',
        upgrade: 'Fazer upgrade',
        change: 'Trocar plano',
        downgrade: 'Fazer downgrade'
    };
    var pendingForm = null;
    var checkoutModal = $('#planCheckoutModal');

    function formatCredits(value) {
        return Number(value).toFixed(2).replace('.', ',') + ' créditos';
    }

    function formatMoney(value) {
        return 'R$ ' + Number(value).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function getSelectedBillingPeriod(form) {
        var selected = form.querySelector('input[name="billing_period"]:checked');
        if (selected) {
            return parseInt(selected.value, 10) || 1;
        }
        var hidden = form.querySelector('input[name="billing_period"]');
        return hidden ? (parseInt(hidden.value, 10) || 1) : 1;
    }

    function getPeriodCost(form, months) {
        var costs = {};
        try {
            costs = JSON.parse(form.getAttribute('data-period-costs') || '{}');
        } catch (e) {
            costs = {};
        }
        return parseFloat(costs[months] || costs[String(months)] || 0);
    }

    function submitPlanForm(form, payWithCredits) {
        var payField = form.querySelector('.plan-pay-with-credits');
        if (payField) {
            payField.value = payWithCredits ? '1' : '0';
        }
        checkoutModal.modal('hide');
        form.submit();
    }

    function openCheckoutModal(form) {
        pendingForm = form;
        var months = getSelectedBillingPeriod(form);
        var cost = getPeriodCost(form, months);
        var planName = form.getAttribute('data-plan-name') || '—';
        var action = form.getAttribute('data-action') || 'subscribe';
        var insufficientCredits = cost > 0 && walletAvailable < cost;
        var missingCredits = Math.max(0, cost - walletAvailable);
        var balanceAfter = Math.max(0, walletAvailable - cost);

        document.getElementById('plan-confirm-name').textContent = planName;
        document.getElementById('plan-confirm-action').textContent = actionLabels[action] || action;
        document.getElementById('plan-confirm-period').textContent = billingLabels[months] || (months + ' mês(es)');
        document.getElementById('plan-confirm-total-money').textContent = formatMoney(cost);
        document.getElementById('plan-confirm-total-credits').textContent = formatCredits(cost);
        document.getElementById('plan-confirm-wallet').textContent = formatCredits(walletAvailable);

        var afterRow = document.getElementById('plan-confirm-balance-after-row');
        var missingRow = document.getElementById('plan-confirm-missing-row');
        if (insufficientCredits) {
            if (afterRow) {
                afterRow.style.display = 'none';
            }
            if (missingRow) {
                missingRow.style.display = '';
                document.getElementById('plan-confirm-missing').textContent = formatCredits(missingCredits);
            }
        } else {
            if (missingRow) {
                missingRow.style.display = 'none';
            }
            if (afterRow && cost > 0) {
                afterRow.style.display = '';
                document.getElementById('plan-confirm-balance-after').textContent = formatCredits(balanceAfter);
            } else if (afterRow) {
                afterRow.style.display = 'none';
            }
        }

        document.getElementById('plan-confirm-insufficient').style.display = insufficientCredits ? '' : 'none';

        var creditBtn = document.getElementById('plan-credit-confirm-btn');
        if (creditBtn) {
            creditBtn.disabled = insufficientCredits;
        }

        checkoutModal.modal('show');
    }

    document.querySelectorAll('.plan-subscribe-form').forEach(function (form) {
        var requiresCheckout = form.getAttribute('data-requires-checkout') === '1';
        var submitBtn = form.querySelector('.plan-subscribe-btn');

        if (submitBtn && requiresCheckout) {
            submitBtn.addEventListener('click', function () {
                openCheckoutModal(form);
            });
        }
    });

    var invoiceBtn = document.getElementById('plan-invoice-confirm-btn');
    if (invoiceBtn) {
        invoiceBtn.addEventListener('click', function () {
            if (pendingForm) {
                submitPlanForm(pendingForm, false);
            }
        });
    }

    var creditBtn = document.getElementById('plan-credit-confirm-btn');
    if (creditBtn) {
        creditBtn.addEventListener('click', function () {
            if (pendingForm) {
                submitPlanForm(pendingForm, true);
            }
        });
    }

    var monthlySuffixes = {
        1: '/mês',
        6: '/mês (semestral)',
        12: '/mês (anual)'
    };

    function updatePlanCardMonthlyPrice(panel, months) {
        if (!panel) {
            return;
        }

        var costs = {};
        try {
            costs = JSON.parse(panel.getAttribute('data-period-costs') || '{}');
        } catch (e) {
            costs = {};
        }

        months = parseInt(months, 10) || 1;
        var total = parseFloat(costs[months] || costs[String(months)] || 0);
        var monthly = months > 0 ? (total / months) : total;
        var priceEl = panel.querySelector('.plan-display-monthly-price');
        var suffixEl = panel.querySelector('.plan-display-monthly-suffix');

        if (priceEl) {
            priceEl.textContent = formatMoney(monthly);
        }
        if (suffixEl) {
            suffixEl.textContent = monthlySuffixes[months] || '/mês';
        }
    }

    document.querySelectorAll('.plan-card').forEach(function (panel) {
        var form = panel.querySelector('.plan-subscribe-form');
        if (!form) {
            return;
        }

        var radios = form.querySelectorAll('input[name="billing_period"]');
        radios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked) {
                    updatePlanCardMonthlyPrice(panel, radio.value);
                }
            });
        });

        var checked = form.querySelector('input[name="billing_period"]:checked');
        if (checked) {
            updatePlanCardMonthlyPrice(panel, checked.value);
        }
    });
})();
</script>
