<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$matches = is_array($matches_data ?? null) ? $matches_data : [];
$slug = (string) ($slug ?? '');
$canManage = !empty($can_manage);

if (!$canManage || empty($matches) || $slug === '') {
    return;
}

foreach ($matches as $m) {
    if (!is_array($m)) {
        continue;
    }
    ?>
    <div class="modal fade" id="reschedule-modal-<?php echo (int) $m['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <?php echo form_open(admin_url('arenagamer/reschedule_match/' . (int) $m['id'])); ?>
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Reagendar partida #<?php echo (int) $m['matchNumber']; ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nova data/hora</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required
                               value="<?php echo arenagamer_datetime_local_value($m['scheduledAt'] ?? ''); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Reagendar</button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
    <?php
}
