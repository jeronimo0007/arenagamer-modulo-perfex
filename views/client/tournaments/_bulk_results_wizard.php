<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$allMatches = is_array($all_matches ?? null) ? $all_matches : [];
$slug = (string) ($slug ?? '');
$recordUrlBase = (string) ($record_url_base ?? '');
$canManage = !empty($can_manage);
$wizardItems = arenagamer_match_result_wizard_items($allMatches);
$pendingCount = count($wizardItems);

if (!$canManage || $pendingCount === 0 || $slug === '' || $recordUrlBase === '') {
    return;
}

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
?>
<div class="ag-bulk-results-toolbar mbot15">
    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#ag-bulk-results-wizard">
        <i class="fa fa-list-ol"></i> Preencher todos os resultados (<?php echo $pendingCount; ?>)
    </button>
    <span class="text-muted mleft5"><small>Informe o placar de cada jogo em sequência, sem fechar o assistente.</small></span>
</div>

<div class="modal fade ag-bulk-results-wizard" id="ag-bulk-results-wizard" tabindex="-1" role="dialog"
     data-record-url-base="<?php echo htmlspecialchars(rtrim($recordUrlBase, '/') . '/'); ?>"
     data-slug="<?php echo htmlspecialchars($slug); ?>"
     data-csrf-name="<?php echo htmlspecialchars($csrfName); ?>"
     data-csrf-hash="<?php echo htmlspecialchars($csrfHash); ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-list-ol"></i> Resultados em lote</h4>
            </div>
            <div class="modal-body">
                <div class="ag-bulk-results-wizard__progress-wrap">
                    <div class="ag-bulk-results-wizard__progress-text">
                        Partida <span class="ag-bulk-results-wizard__current">1</span>
                        de <span class="ag-bulk-results-wizard__total"><?php echo $pendingCount; ?></span>
                    </div>
                    <div class="progress ag-bulk-results-wizard__progress">
                        <div class="progress-bar progress-bar-success no-percent-text not-dynamic ag-bulk-results-wizard__progress-bar"
                             role="progressbar"
                             aria-valuemin="0"
                             aria-valuemax="100"
                             aria-valuenow="0"
                             data-percent="0"
                             style="width: 0%;"></div>
                    </div>
                </div>

                <div class="ag-bulk-results-wizard__alert alert alert-danger" style="display:none;" role="alert"></div>
                <div class="ag-bulk-results-wizard__success alert alert-success" style="display:none;" role="alert"></div>

                <div class="ag-bulk-results-wizard__step">
                    <p class="ag-bulk-results-wizard__context text-muted mbot5"></p>
                    <div class="ag-bulk-results-wizard__matchup"></div>

                    <form class="ag-bulk-results-wizard__form" enctype="multipart/form-data">
                        <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                        <input type="hidden" name="ajax" value="1">
                        <input type="hidden" name="<?php echo htmlspecialchars($csrfName); ?>" value="<?php echo htmlspecialchars($csrfHash); ?>" class="ag-bulk-results-csrf">

                        <div class="row mtop10">
                            <div class="col-xs-6 form-group">
                                <label class="ag-bulk-results-wizard__home-label">Placar — <span class="ag-bulk-results-wizard__home-name"></span> <span class="text-danger">*</span></label>
                                <input type="number" min="0" name="home_score" class="form-control ag-score ag-bulk-results-wizard__home-score" required autocomplete="off">
                            </div>
                            <div class="col-xs-6 form-group">
                                <label class="ag-bulk-results-wizard__away-label">Placar — <span class="ag-bulk-results-wizard__away-name"></span> <span class="text-danger">*</span></label>
                                <input type="number" min="0" name="away_score" class="form-control ag-score ag-bulk-results-wizard__away-score" required autocomplete="off">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="bold">Vencedor:</label>
                            <span class="ag-auto-winner ag-bulk-results-wizard__auto-winner text-info">defina o placar</span>
                        </div>

                        <div class="form-group">
                            <button type="button" class="btn btn-warning btn-xs ag-cheat-toggle">
                                <i class="fa fa-user-secret"></i> Trapaceiro detectado
                            </button>
                        </div>

                        <div class="form-group ag-cheat-block ag-bulk-results-wizard__cheat-block" style="display:none;">
                            <label class="bold text-warning">Vencedor manual</label>
                            <div class="radio ag-bulk-results-wizard__cheat-radios"></div>
                        </div>

                        <div class="form-group">
                            <label>Comprovante (opcional)</label>
                            <input type="file" name="proof_file" accept="image/*" class="form-control">
                        </div>
                    </form>
                </div>

                <div class="ag-bulk-results-wizard__done text-center" style="display:none;">
                    <i class="fa fa-check-circle fa-3x text-success"></i>
                    <p class="mtop15 bold">Todos os resultados foram registrados!</p>
                    <p class="text-muted">A página será recarregada para atualizar a classificação.</p>
                </div>
            </div>
            <div class="modal-footer ag-bulk-results-wizard__footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-default ag-bulk-results-wizard__skip">Pular</button>
                <button type="button" class="btn btn-success ag-bulk-results-wizard__submit">
                    <i class="fa fa-check"></i> <span class="ag-bulk-results-wizard__submit-label">Salvar e próximo</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="ag-bulk-results-data"><?php echo json_encode($wizardItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
