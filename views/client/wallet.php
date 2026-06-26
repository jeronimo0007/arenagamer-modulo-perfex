<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$walletData = arenagamer_api_data($balance ?? null);
$txContent = arenagamer_paginated_content($transactions ?? null);
$permissions = arenagamer_api_data($wallet_permissions ?? null, []);
$canUseWallet = !empty($can_use_wallet);
$isPrimary = !empty($is_primary);
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-money"></i> Carteira da empresa</h4>
        <p class="text-muted">Saldo compartilhado por todos os contatos da empresa, conforme permissões definidas pelo contato principal.</p>

        <div class="row mbot20">
            <div class="col-md-4"><strong>Saldo:</strong> <?php echo $walletData ? arenagamer_format_credits($walletData['balance'] ?? 0) : '—'; ?></div>
            <div class="col-md-4"><strong>Retido:</strong> <?php echo $walletData ? arenagamer_format_credits($walletData['heldBalance'] ?? 0) : '—'; ?></div>
            <div class="col-md-4"><strong>Disponível:</strong> <?php echo $walletData ? arenagamer_format_credits($walletData['availableBalance'] ?? 0) : '—'; ?></div>
        </div>

        <?php if (!$canUseWallet): ?>
        <div class="alert alert-info">Você pode visualizar o saldo, mas não tem permissão para movimentar créditos.</div>
        <?php endif; ?>

        <?php if ($canUseWallet): ?>
        <div class="row mbot20">
            <div class="col-md-6">
                <h5>Adicionar créditos</h5>
                <p class="text-muted">A compra de créditos gera uma fatura no Perfex. O saldo é creditado automaticamente após o pagamento.</p>
                <?php if (function_exists('arenagamer_contact_can_buy_credits') && arenagamer_contact_can_buy_credits($auth_user ?? null)): ?>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#arenagamerBuyCreditsModal">
                    <i class="fa fa-shopping-cart"></i> Comprar créditos
                </button>
                <?php else: ?>
                <p class="text-muted"><em>Você não tem permissão para comprar créditos.</em></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <?php echo form_open(arenagamer_client_url('wallet')); ?>
                <input type="hidden" name="wallet_action" value="withdraw">
                <h5>Sacar</h5>
                <div class="form-group"><input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Valor" required></div>
                <div class="form-group"><input type="text" name="description" class="form-control" placeholder="Descrição"></div>
                <button type="submit" class="btn btn-warning btn-sm">Sacar</button>
                <?php echo form_close(); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isPrimary && !empty($permissions)): ?>
        <hr />
        <h5 class="bold">Permissões de créditos (contatos secundários)</h5>
        <?php echo form_open(arenagamer_client_url('wallet')); ?>
        <input type="hidden" name="save_wallet_permissions" value="1">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Contato</th>
                        <th class="text-center">Pode visualizar</th>
                        <th class="text-center">Pode usar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $perm): ?>
                    <?php $permContactId = (int) ($perm['contactId'] ?? 0); ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($perm['contactName'] ?? ''); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($perm['contactEmail'] ?? ''); ?></small>
                            <input type="hidden" name="permission_contact_id[]" value="<?php echo $permContactId; ?>">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="permission_view[]" value="<?php echo $permContactId; ?>"
                                <?php echo !empty($perm['walletViewAllowed']) ? 'checked' : ''; ?>>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="permission_use[]" value="<?php echo $permContactId; ?>"
                                <?php echo !empty($perm['walletUseAllowed']) ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Salvar permissões</button>
        <?php echo form_close(); ?>
        <?php elseif ($isPrimary): ?>
        <p class="text-muted">Nenhum contato secundário para configurar permissões.</p>
        <?php endif; ?>

        <h5 class="bold mtop20">Transações</h5>
        <?php if (!empty($txContent)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Data</th><th>Realizado por</th><th>Tipo</th><th>Valor</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($txContent as $tx): ?>
                    <tr>
                        <td><?php echo arenagamer_format_date($tx['createdAt'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($tx['performedByContactName'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars(arenagamer_wallet_transaction_type_label($tx['type'] ?? '')); ?></td>
                        <td><?php echo arenagamer_format_credits($tx['amount'] ?? 0); ?></td>
                        <td><?php echo arenagamer_status_badge($tx['status'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">Nenhuma transação.</p>
        <?php endif; ?>
    </div>
</div>
