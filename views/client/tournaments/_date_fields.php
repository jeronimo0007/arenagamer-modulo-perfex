<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$isCreate = !empty($is_create);
$registrationOpens = arenagamer_tournament_registration_opens_form_value($t, $isCreate);
$registrationDeadline = arenagamer_datetime_local_value($t['registrationDeadline'] ?? '');
$startDate = arenagamer_datetime_local_value($t['startDate'] ?? '');
$expectedEndDate = arenagamer_datetime_local_value($t['expectedEndDate'] ?? '');
?>
<div class="panel_s mtop10 mbot15">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-calendar"></i> Datas do torneio</h4>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="registration_opens_at">Abertura prevista das inscrições <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="registration_opens_at" id="registration_opens_at"
                           class="form-control arenagamer-date-field" required
                           value="<?php echo htmlspecialchars($registrationOpens); ?>">
                    <small class="text-muted">Obrigatória. Na criação, vem preenchida com amanhã.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="registration_deadline">Prazo de inscrição</label>
                    <input type="datetime-local" name="registration_deadline" id="registration_deadline"
                           class="form-control arenagamer-date-field"
                           value="<?php echo htmlspecialchars($registrationDeadline); ?>">
                    <small class="text-muted">Opcional. Não pode ser anterior à abertura prevista.</small>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date">Data de início</label>
                    <input type="datetime-local" name="start_date" id="start_date"
                           class="form-control arenagamer-date-field"
                           value="<?php echo htmlspecialchars($startDate); ?>">
                    <small class="text-muted">Opcional. Se houver prazo de inscrição, não pode ser anterior a ele.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="expected_end_date">Término previsto do torneio</label>
                    <input type="datetime-local" name="expected_end_date" id="expected_end_date"
                           class="form-control arenagamer-date-field"
                           value="<?php echo htmlspecialchars($expectedEndDate); ?>">
                    <small class="text-muted">Opcional. Deve ser posterior à data de início (se informada).</small>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var opensEl = document.getElementById('registration_opens_at');
    var deadlineEl = document.getElementById('registration_deadline');
    var startEl = document.getElementById('start_date');
    var endEl = document.getElementById('expected_end_date');

    if (!opensEl) {
        return;
    }

    function parseLocal(value) {
        if (!value) {
            return null;
        }
        var date = new Date(value);
        return isNaN(date.getTime()) ? null : date;
    }

    function addMinutes(date, minutes) {
        return new Date(date.getTime() + minutes * 60000);
    }

    function toLocalInputValue(date) {
        if (!date) {
            return '';
        }
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) +
            'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    function syncDateConstraints() {
        var opens = parseLocal(opensEl.value);
        var deadline = parseLocal(deadlineEl ? deadlineEl.value : '');
        var start = parseLocal(startEl ? startEl.value : '');

        if (deadlineEl) {
            deadlineEl.min = opens ? toLocalInputValue(opens) : '';
        }

        if (startEl) {
            startEl.min = deadline ? toLocalInputValue(deadline) : '';
        }

        if (endEl) {
            endEl.min = start ? toLocalInputValue(addMinutes(start, 1)) : '';
        }
    }

    ['change', 'input'].forEach(function (eventName) {
        opensEl.addEventListener(eventName, syncDateConstraints);
        if (deadlineEl) {
            deadlineEl.addEventListener(eventName, syncDateConstraints);
        }
        if (startEl) {
            startEl.addEventListener(eventName, syncDateConstraints);
        }
    });

    syncDateConstraints();
})();
</script>
