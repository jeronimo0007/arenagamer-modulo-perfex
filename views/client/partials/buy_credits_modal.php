<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="arenagamerBuyCreditsModal" tabindex="-1" role="dialog" aria-labelledby="arenagamerBuyCreditsModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open(arenagamer_client_url('buy_credits')); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="arenagamerBuyCreditsModalLabel">
                    <i class="fa fa-shopping-cart"></i> Comprar créditos
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Preço: <strong>1 crédito = R$ 1,00</strong>. Após o pagamento da fatura, os créditos são adicionados automaticamente à sua conta.
                </p>
                <div class="form-group">
                    <label for="arenagamer_credits_qty">Quantidade de créditos</label>
                    <input type="number"
                           name="credits"
                           id="arenagamer_credits_qty"
                           class="form-control"
                           min="1"
                           step="1"
                           value="10"
                           required>
                </div>
                <p class="mbot0">
                    Total estimado:
                    <strong id="arenagamer_credits_total"><?php echo function_exists('arenagamer_format_money') ? arenagamer_format_money(10) : 'R$ 10,00'; ?></strong>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-file-text-o"></i> Comprar
                </button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
