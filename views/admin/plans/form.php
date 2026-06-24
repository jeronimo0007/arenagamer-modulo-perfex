<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit = !empty($plan);
$formUrl = $isEdit ? admin_url('arenagamer/plan/' . (int) $plan['id']) : admin_url('arenagamer/plan');
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-list"></i> <?php echo htmlspecialchars($title); ?>
                </h4>
                <a href="<?php echo admin_url('arenagamer/plans'); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-arrow-left"></i> Voltar
                </a>
                <hr />

                <?php if (!empty($api_error)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Erro na API:</strong> <?php echo htmlspecialchars($api_error); ?>
                </div>
                <?php endif; ?>

                <?php echo form_open($formUrl); ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="name" class="control-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required
                                   value="<?php echo htmlspecialchars($plan['name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description" class="control-label">Descrição</label>
                            <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($plan['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="monthly_price" class="control-label">Preço mensal (créditos)</label>
                                    <input type="number" step="0.01" min="0" name="monthly_price" id="monthly_price" class="form-control"
                                           value="<?php echo htmlspecialchars($plan['monthlyPrice'] ?? '0'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sort_order" class="control-label">Ordem de exibição</label>
                                    <input type="number" min="0" name="sort_order" id="sort_order" class="form-control"
                                           value="<?php echo (int) ($plan['sortOrder'] ?? 0); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_tournaments_per_month" class="control-label">Máx. torneios/mês</label>
                                    <input type="number" min="0" name="max_tournaments_per_month" id="max_tournaments_per_month" class="form-control"
                                           value="<?php echo (int) arenagamer_plan_max_tournaments_per_month($plan ?? []); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="free_max_participants" class="control-label">Máx. participantes grátis</label>
                                    <input type="number" min="0" name="free_max_participants" id="free_max_participants" class="form-control"
                                           value="<?php echo (int) ($plan['freeMaxParticipants'] ?? 0); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="allows_entry_fee" id="allows_entry_fee" value="1"
                                <?php echo !empty($plan['allowsEntryFee']) ? 'checked' : ''; ?>>
                            <label for="allows_entry_fee">Permite taxa de inscrição</label>
                        </div>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="active" value="1"
                                <?php echo (!$isEdit || !empty($plan['active'])) ? 'checked' : ''; ?>>
                            <label for="active">Plano ativo</label>
                        </div>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="hidden" id="hidden" value="1"
                                <?php echo !empty($plan['hidden']) ? 'checked' : ''; ?>>
                            <label for="hidden">Ocultar plano</label>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <a href="<?php echo admin_url('arenagamer/plans'); ?>" class="btn btn-default">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?php echo $isEdit ? 'Salvar alterações' : 'Criar plano'; ?>
                        </button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
