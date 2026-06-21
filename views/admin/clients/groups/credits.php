<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$CI->load->helper('arenagamer/arenagamer');
$CI->load->library('arenagamer/ArenaGamer_api');

$clientUserId = isset($client) ? (int) $client->userid : 0;
$canManageCredits = staff_can('manage_client_credits', 'arenagamer');
$canViewCredits = staff_can('view', 'arenagamer');
$txPage = $CI->input->get('tx_page') ? (int) $CI->input->get('tx_page') : 0;

$walletResponse = $canViewCredits ? $CI->arenagamer_api->get_admin_client_wallet($clientUserId) : null;
$transactionsResponse = $canViewCredits
    ? $CI->arenagamer_api->get_admin_client_wallet_transactions($clientUserId, $txPage, 20)
    : null;

$clientWallet = arenagamer_api_data($walletResponse, []);
$walletData = $clientWallet['wallet'] ?? null;
$transactions = arenagamer_paginated_content($transactionsResponse);
$apiError = (!$canViewCredits)
    ? 'Sem permissão para visualizar créditos ArenaGamer.'
    : (($walletResponse === null && $transactionsResponse === null)
        ? $CI->arenagamer_api->get_last_error()
        : '');
?>
<?php if (!$canViewCredits): ?>
<div class="alert alert-warning">Você não tem permissão para visualizar créditos ArenaGamer.</div>
<?php else: ?>
    <?php if (!empty($apiError)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($apiError); ?></div>
    <?php endif; ?>

    <h4 class="tw-font-semibold mtop0 mbot15"><i class="fa fa-money"></i> Créditos ArenaGamer</h4>
    <p class="text-muted">
        Saldo único da empresa (cliente). Todos os contatos compartilham esta carteira conforme permissões definidas pelo contato principal.
    </p>

    <div class="panel_s mbot20">
        <div class="panel-body">
            <h5 class="bold mtop0">
                <?php echo htmlspecialchars($clientWallet['companyName'] ?? ($client->company ?? 'Cliente #' . $clientUserId)); ?>
            </h5>
            <?php if ($walletData): ?>
            <div class="row mtop15">
                <div class="col-md-4"><strong>Saldo:</strong> <?php echo arenagamer_format_credits($walletData['balance'] ?? 0); ?></div>
                <div class="col-md-4"><strong>Retido:</strong> <?php echo arenagamer_format_credits($walletData['heldBalance'] ?? 0); ?></div>
                <div class="col-md-4"><strong>Disponível:</strong> <?php echo arenagamer_format_credits($walletData['availableBalance'] ?? 0); ?></div>
            </div>
            <?php else: ?>
            <p class="text-muted mtop10 mbot0">Carteira ainda não criada na API. Adicionar créditos cria a carteira automaticamente.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canManageCredits): ?>
    <div class="row mbot25">
        <div class="col-md-6">
            <div class="panel_s">
                <div class="panel-body">
                    <?php echo form_open(admin_url('arenagamer/client_wallet/' . $clientUserId)); ?>
                    <input type="hidden" name="wallet_action" value="deposit">
                    <h5 class="bold mtop0 text-success"><i class="fa fa-plus-circle"></i> Adicionar créditos</h5>
                    <div class="form-group">
                        <label>Valor (créditos)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" name="description" class="form-control" placeholder="Ex.: Compra manual de créditos">
                    </div>
                    <button type="submit" class="btn btn-success">Adicionar créditos</button>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel_s">
                <div class="panel-body">
                    <?php echo form_open(admin_url('arenagamer/client_wallet/' . $clientUserId)); ?>
                    <input type="hidden" name="wallet_action" value="withdraw">
                    <h5 class="bold mtop0 text-danger"><i class="fa fa-minus-circle"></i> Remover créditos</h5>
                    <div class="form-group">
                        <label>Valor (créditos)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" name="description" class="form-control" placeholder="Ex.: Ajuste manual">
                    </div>
                    <button type="submit" class="btn btn-danger">Remover créditos</button>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <h5 class="bold">Histórico de movimentações</h5>
    <?php if (!empty($transactions)): ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Realizado por</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <?php
                $isCreditIn = arenagamer_wallet_transaction_is_credit_in($tx['type'] ?? '', $tx['amount'] ?? 0);
                $amountClass = $isCreditIn ? 'text-success' : 'text-danger';
                ?>
                <tr>
                    <td><?php echo arenagamer_format_date($tx['createdAt'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($tx['performedByContactName'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars(arenagamer_wallet_transaction_type_label($tx['type'] ?? '')); ?></td>
                    <td class="<?php echo $amountClass; ?>">
                        <?php echo arenagamer_format_credits($tx['amount'] ?? 0); ?>
                    </td>
                    <td><?php echo arenagamer_status_badge($tx['status'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($tx['description'] ?? '—'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $pagination = is_array($transactionsResponse['data'] ?? null) ? $transactionsResponse['data'] : [];
    $totalPages = (int) ($pagination['totalPages'] ?? 0);
    $currentPage = (int) ($pagination['number'] ?? $txPage);
    if ($totalPages > 1):
    ?>
    <div class="tw-flex tw-gap-2">
        <?php if ($currentPage > 0): ?>
        <a class="btn btn-default btn-sm"
           href="<?php echo admin_url('clients/client/' . $clientUserId . '?group=arenagamer_credits&tx_page=' . ($currentPage - 1)); ?>">
            &larr; Anterior
        </a>
        <?php endif; ?>
        <span class="tw-self-center text-muted">Página <?php echo $currentPage + 1; ?> de <?php echo $totalPages; ?></span>
        <?php if ($currentPage + 1 < $totalPages): ?>
        <a class="btn btn-default btn-sm"
           href="<?php echo admin_url('clients/client/' . $clientUserId . '?group=arenagamer_credits&tx_page=' . ($currentPage + 1)); ?>">
            Próxima &rarr;
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <p class="text-muted">Nenhuma movimentação registrada.</p>
    <?php endif; ?>
<?php endif; ?>
