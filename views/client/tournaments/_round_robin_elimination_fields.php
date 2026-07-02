<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$showFields = ($t['type'] ?? '') === 'ROUND_ROBIN_ELIMINATION';
$formatFieldsLocked = !empty($format_fields_locked);
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$participantsLimit = (int) ($t['participantsLimit'] ?? 0);
$maxAdvance = $participantsLimit > 2 ? arenagamer_largest_power_of_two_up_to($participantsLimit - 1) : 0;
?>
<div class="row mtop15" id="round_robin_elimination_fields"<?php echo $showFields ? '' : ' style="display:none;"'; ?>>
    <div class="col-md-6">
        <div class="form-group">
            <label for="advance_to_knockout" class="<?php echo htmlspecialchars($labelClass); ?>">
                Classificados para o mata-mata <span class="text-danger round-robin-required">*</span>
            </label>
            <input type="number"
                   min="2"
                   step="any"
                   name="advance_to_knockout"
                   id="advance_to_knockout"
                   class="form-control"
                   value="<?php echo htmlspecialchars($t['advanceToKnockout'] ?? ''); ?>"
                   <?php echo ($showFields && !$formatFieldsLocked) ? 'required' : ''; ?>
                   <?php echo $formatFieldsLocked ? 'readonly' : ''; ?>
                   <?php echo $maxAdvance > 0 ? 'max="' . (int) $maxAdvance . '"' : ''; ?>>
            <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0" id="advance_to_knockout_help">
                Use 2, 4, 8, 16… menor que o total de inscritos.
                Ex.: 12 inscritos → máximo 8 classificados para quartas de final.
            </p>
        </div>
    </div>
</div>
<script>
(function () {
    var typeEl = document.getElementById('type');
    var fields = document.getElementById('round_robin_elimination_fields');
    var advanceEl = document.getElementById('advance_to_knockout');
    var helpEl = document.getElementById('advance_to_knockout_help');
    var participantsLimitEl = document.getElementById('participants_limit');
    var formatFieldsLocked = <?php echo $formatFieldsLocked ? 'true' : 'false'; ?>;

    if (!typeEl || !fields) {
        return;
    }

    function largestPowerOfTwoUpTo(limit) {
        limit = parseInt(limit, 10);
        if (!limit || limit < 2) {
            return 0;
        }
        var power = 1;
        while ((power * 2) <= limit) {
            power *= 2;
        }
        return power;
    }

    function isPowerOfTwo(value) {
        value = parseInt(value, 10);
        return value >= 2 && (value & (value - 1)) === 0;
    }

    function nextAdvanceValue(value) {
        value = parseInt(value, 10);
        if (isNaN(value) || value < 2) {
            return 2;
        }
        if (isPowerOfTwo(value)) {
            return value * 2;
        }
        var power = 2;
        while (power < value) {
            power *= 2;
        }
        return power;
    }

    function prevAdvanceValue(value) {
        value = parseInt(value, 10);
        if (isNaN(value) || value <= 2) {
            return 2;
        }
        if (isPowerOfTwo(value)) {
            return Math.max(2, value / 2);
        }
        return Math.max(2, largestPowerOfTwoUpTo(value));
    }

    var committedAdvance = advanceEl ? parseInt(advanceEl.value, 10) || 0 : 0;
    if (committedAdvance > 0 && !isPowerOfTwo(committedAdvance)) {
        committedAdvance = largestPowerOfTwoUpTo(committedAdvance) || 2;
    }

    function getMaxAdvance() {
        var participantsLimit = participantsLimitEl ? parseInt(participantsLimitEl.value, 10) : 0;
        return participantsLimit > 2 ? largestPowerOfTwoUpTo(participantsLimit - 1) : 0;
    }

    function clampAdvance(value) {
        value = parseInt(value, 10);
        if (isNaN(value) || value < 2) {
            value = 2;
        }
        var maxAdvance = getMaxAdvance();
        if (maxAdvance >= 2 && value > maxAdvance) {
            value = maxAdvance;
        }
        return value;
    }

    function commitAdvance(value, options) {
        if (!advanceEl) {
            return;
        }
        options = options || {};
        value = clampAdvance(value);
        if (!isPowerOfTwo(value)) {
            value = largestPowerOfTwoUpTo(value) || 2;
            value = clampAdvance(value);
        }
        committedAdvance = value;
        advanceEl.value = String(value);
        if (options.notify) {
            advanceEl.dispatchEvent(new Event('input', { bubbles: true }));
            advanceEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function nudgeAdvance(direction) {
        var base = committedAdvance > 0 ? committedAdvance : clampAdvance(parseInt(advanceEl.value, 10) || 2);
        var next = direction > 0 ? nextAdvanceValue(base) : prevAdvanceValue(base);
        commitAdvance(next, { notify: true });
    }

    function syncAdvanceConstraints() {
        if (!advanceEl || formatFieldsLocked) {
            return;
        }

        var participantsLimit = participantsLimitEl ? parseInt(participantsLimitEl.value, 10) : 0;
        var maxAdvance = participantsLimit > 2 ? largestPowerOfTwoUpTo(participantsLimit - 1) : 0;

        if (maxAdvance >= 2) {
            advanceEl.setAttribute('max', String(maxAdvance));
            if (helpEl) {
                helpEl.textContent = 'Use 2, 4, 8, 16… menor que ' + participantsLimit
                    + ' inscritos. Máximo permitido: ' + maxAdvance + '.';
            }
        } else {
            advanceEl.removeAttribute('max');
            if (helpEl) {
                helpEl.textContent = 'Use 2, 4, 8, 16… menor que o total de inscritos.';
            }
        }

        var current = parseInt(advanceEl.value, 10);
        if (current > 0 && maxAdvance >= 2 && current > maxAdvance) {
            commitAdvance(maxAdvance);
        } else if (current > 0 && isPowerOfTwo(current)) {
            committedAdvance = current;
        }
    }

    function snapAdvanceToPowerOfTwo() {
        if (!advanceEl || formatFieldsLocked) {
            return;
        }

        var value = parseInt(advanceEl.value, 10);
        if (!value || value < 2) {
            commitAdvance(2);
            return;
        }

        if (isPowerOfTwo(value)) {
            commitAdvance(value);
            return;
        }

        var lower = 1;
        var upper = 2;
        while (upper < value) {
            lower = upper;
            upper *= 2;
        }

        var pick = (value - lower) <= (upper - value) ? lower : upper;
        commitAdvance(pick < 2 ? 2 : pick);
    }

    function syncVisibility() {
        var isRoundRobinElimination = typeEl.value === 'ROUND_ROBIN_ELIMINATION';
        fields.style.display = isRoundRobinElimination ? '' : 'none';

        if (!formatFieldsLocked && advanceEl) {
            if (isRoundRobinElimination) {
                advanceEl.removeAttribute('disabled');
                advanceEl.setAttribute('required', 'required');
            } else {
                advanceEl.setAttribute('disabled', 'disabled');
                advanceEl.removeAttribute('required');
            }
        }

        syncAdvanceConstraints();
    }

    if (!formatFieldsLocked) {
        typeEl.addEventListener('change', syncVisibility);
        if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
            jQuery(typeEl).on('changed.bs.select', syncVisibility);
        }
        if (participantsLimitEl) {
            participantsLimitEl.addEventListener('input', syncAdvanceConstraints);
            participantsLimitEl.addEventListener('change', syncAdvanceConstraints);
        }
        if (advanceEl) {
            advanceEl.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    nudgeAdvance(1);
                } else if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    nudgeAdvance(-1);
                }
            });
            advanceEl.addEventListener('input', function () {
                var value = parseInt(advanceEl.value, 10);
                if (!value || isPowerOfTwo(value)) {
                    if (value >= 2) {
                        committedAdvance = clampAdvance(value);
                    }
                    return;
                }
                var prev = committedAdvance || 2;
                if (Math.abs(value - prev) === 1) {
                    var next = value > prev ? nextAdvanceValue(prev) : prevAdvanceValue(prev);
                    commitAdvance(next, { notify: true });
                }
            });
            advanceEl.addEventListener('blur', snapAdvanceToPowerOfTwo);
            advanceEl.addEventListener('change', snapAdvanceToPowerOfTwo);
        }
    }

    if (advanceEl && committedAdvance > 0) {
        commitAdvance(committedAdvance);
    }

    syncVisibility();
})();
</script>
