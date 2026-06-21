<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$authUser = $auth_user ?? null;
if (!function_exists('arenagamer_contact_can_buy_credits') || !arenagamer_contact_can_buy_credits($authUser)) {
    return;
}

$wallet = function_exists('arenagamer_client_wallet_balance') ? arenagamer_client_wallet_balance() : null;
$available = $wallet ? (float) ($wallet['availableBalance'] ?? 0) : null;
?>
<div class="arenagamer-buy-credits-block mtop15">
    <div class="row">
        <div class="col-sm-8">
            <h5 class="bold mtop0 mbot5"><i class="fa fa-money"></i> Créditos ArenaGamer</h5>
            <?php if ($available !== null): ?>
            <p class="text-muted mbot0">
                Saldo disponível:
                <strong><?php echo arenagamer_format_credits($available); ?></strong>
                <span class="text-muted">&mdash; 1 crédito = R$ 1,00</span>
            </p>
            <?php else: ?>
            <p class="text-muted mbot0">Compre créditos para criar torneios e pagar taxas. Preço: 1 crédito = R$ 1,00.</p>
            <?php endif; ?>
        </div>
        <div class="col-sm-4 text-right">
            <label class="visible-md visible-lg">&nbsp;</label>
            <button type="button"
                    class="btn btn-primary btn-block"
                    data-toggle="modal"
                    data-target="#arenagamerBuyCreditsModal">
                <i class="fa fa-shopping-cart"></i> Comprar créditos
            </button>
        </div>
    </div>
</div>
