<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$allMatches = is_array($all_matches ?? null) ? $all_matches : [];
$slug = (string) ($slug ?? '');
$recordUrlBase = (string) ($record_url_base ?? '');
$canManage = !empty($can_manage);

if (!$canManage || empty($allMatches) || $slug === '' || $recordUrlBase === '') {
    return;
}

$rendered = false;
foreach ($allMatches as $m) {
    if (!is_array($m)) {
        continue;
    }

    $homeId = $m['homeParticipantId'] ?? null;
    $awayId = $m['awayParticipantId'] ?? null;
    $matchStatus = $m['status'] ?? '';
    $bothDefined = !empty($homeId) && !empty($awayId);
    $isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);
    if (!$bothDefined || $isFinished) {
        continue;
    }

    $homeName = !empty($m['homeParticipantName']) ? $m['homeParticipantName'] : 'Mandante';
    $awayName = !empty($m['awayParticipantName']) ? $m['awayParticipantName'] : 'Visitante';

    if (arenagamer_is_knockout_match($m)) {
        $modalTitle = 'Resultado — ' . arenagamer_knockout_round_label($m['roundType'] ?? '', $m['phaseLabel'] ?? null);
    } elseif (arenagamer_is_round_robin_match($m)) {
        $modalTitle = 'Resultado — Pontos corridos';
    } elseif (arenagamer_is_swiss_phase_match($m)) {
        $roundNumber = (int) ($m['roundNumber'] ?? 0);
        $modalTitle = 'Resultado — Rodada suíça' . ($roundNumber > 0 ? ' ' . $roundNumber : '');
    } elseif (arenagamer_is_group_stage_match($m)) {
        $modalTitle = 'Resultado — Grupo ' . (int) arenagamer_match_group_number($m);
    } else {
        $modalTitle = 'Resultado — Partida #' . (int) ($m['matchNumber'] ?? 0);
    }

    $rendered = true;
    ?>
    <div class="modal fade ag-result-modal" id="result-modal-<?php echo (int) $m['id']; ?>" tabindex="-1" role="dialog"
         data-home-id="<?php echo (int) $homeId; ?>" data-away-id="<?php echo (int) $awayId; ?>"
         data-home-name="<?php echo htmlspecialchars($homeName); ?>" data-away-name="<?php echo htmlspecialchars($awayName); ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <?php echo form_open_multipart(rtrim($recordUrlBase, '/') . '/' . (int) $m['id']); ?>
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?php echo htmlspecialchars($modalTitle); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-xs-6 form-group">
                            <label>Placar — <?php echo htmlspecialchars($homeName); ?> <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="home_score" class="form-control ag-score" required>
                        </div>
                        <div class="col-xs-6 form-group">
                            <label>Placar — <?php echo htmlspecialchars($awayName); ?> <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="away_score" class="form-control ag-score" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="bold">Vencedor:</label> <span class="ag-auto-winner text-info">defina o placar</span>
                    </div>
                    <div class="form-group">
                        <button type="button" class="btn btn-warning btn-xs ag-cheat-toggle">
                            <i class="fa fa-user-secret"></i> Trapaceiro detectado
                        </button>
                    </div>
                    <div class="form-group ag-cheat-block" style="display:none;">
                        <label class="bold text-warning">Vencedor manual</label>
                        <div class="radio">
                            <label><input type="radio" name="winner_participant_id" value="<?php echo (int) $homeId; ?>"> <?php echo htmlspecialchars($homeName); ?></label>
                        </div>
                        <div class="radio">
                            <label><input type="radio" name="winner_participant_id" value="<?php echo (int) $awayId; ?>"> <?php echo htmlspecialchars($awayName); ?></label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Comprovante (opcional)</label>
                        <input type="file" name="proof_file" accept="image/*" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Salvar</button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
    <?php
}

if ($rendered) {
    echo arenagamer_result_modal_script();
}
