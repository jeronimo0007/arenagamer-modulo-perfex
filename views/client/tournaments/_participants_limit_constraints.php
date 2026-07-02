<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
(function () {
    var config = {
        isEdit: <?php echo !empty($is_edit) ? 'true' : 'false'; ?>,
        planMax: <?php echo (int) ($plan_max_participants ?? 0); ?>,
        floor: <?php echo max(2, (int) ($min_participants ?? arenagamer_tournament_min_participants())); ?>,
        step: <?php echo (int) arenagamer_tournament_participants_step(); ?>,
        groupStageStep: <?php echo (int) arenagamer_group_stage_teams_per_group(); ?>,
        defaultLimit: <?php echo max(2, (int) ($default_participants_limit ?? arenagamer_tournament_min_participants())); ?>,
        defaultMin: <?php echo max(2, (int) ($default_min_participants ?? arenagamer_tournament_min_participants())); ?>
    };

    if (config.isEdit) {
        return;
    }

    var limitInput = document.getElementById('participants_limit');
    var minInput = document.getElementById('min_participants');

    if (!limitInput || !minInput || limitInput.readOnly) {
        return;
    }

    var committed = {
        limit: config.defaultLimit,
        min: Math.min(config.defaultMin, config.defaultLimit)
    };

    function getType() {
        var el = document.getElementById('type');
        return el ? String(el.value || '') : '';
    }

    function isEliminationType(type) {
        type = type || getType();
        return type === 'SINGLE_ELIMINATION' || type === 'DOUBLE_ELIMINATION';
    }

    function isGroupStageType(type) {
        type = type || getType();
        return type === 'GROUP_STAGE';
    }

    function isMultipleOf(value, step) {
        value = parseInt(value, 10);
        step = parseInt(step, 10);
        return value >= step && value % step === 0;
    }

    function largestMultipleUpTo(limit, step) {
        limit = parseInt(limit, 10);
        step = parseInt(step, 10);
        if (!limit || limit < step) {
            return 0;
        }
        return limit - (limit % step);
    }

    function nextGroupStageValue(value) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric) || numeric < config.groupStageStep) {
            return config.groupStageStep;
        }
        if (isMultipleOf(numeric, config.groupStageStep)) {
            return numeric + config.groupStageStep;
        }
        return Math.ceil(numeric / config.groupStageStep) * config.groupStageStep;
    }

    function prevGroupStageValue(value) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric) || numeric <= config.groupStageStep) {
            return config.groupStageStep;
        }
        if (isMultipleOf(numeric, config.groupStageStep)) {
            return Math.max(config.groupStageStep, numeric - config.groupStageStep);
        }
        return Math.max(config.groupStageStep, largestMultipleUpTo(numeric, config.groupStageStep));
    }

    function snapGroupStageDown(value, maxCap) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric) || numeric < config.groupStageStep) {
            numeric = config.groupStageStep;
        } else if (!isMultipleOf(numeric, config.groupStageStep)) {
            numeric = largestMultipleUpTo(numeric, config.groupStageStep);
            if (numeric < config.groupStageStep) {
                numeric = config.groupStageStep;
            }
        }
        return clampMax(numeric, maxCap);
    }

    function isPowerOfTwo(value) {
        value = parseInt(value, 10);
        return value >= 2 && (value & (value - 1)) === 0;
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

    function nextPowerOfTwo(value) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric) || numeric < 2) {
            return config.floor;
        }
        if (isPowerOfTwo(numeric)) {
            return numeric * 2;
        }
        var power = 2;
        while (power < numeric) {
            power *= 2;
        }
        return power;
    }

    function prevPowerOfTwo(value) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric) || numeric <= config.floor) {
            return config.floor;
        }
        if (isPowerOfTwo(numeric)) {
            return Math.max(config.floor, numeric / 2);
        }
        return Math.max(config.floor, largestPowerOfTwoUpTo(numeric));
    }

    function clampMax(value, maxCap) {
        if (maxCap > 0 && value > maxCap) {
            if (isEliminationType()) {
                value = largestPowerOfTwoUpTo(maxCap);
            } else if (isGroupStageType()) {
                value = largestMultipleUpTo(maxCap, config.groupStageStep);
            } else {
                value = maxCap - ((maxCap - config.floor) % config.step);
            }
            if (value < config.floor) {
                value = config.floor;
            }
        }
        return value;
    }

    function snapStepDown(value, maxCap) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric)) {
            numeric = config.floor;
        }
        if (numeric < config.floor) {
            numeric = config.floor;
        }
        var remainder = (numeric - config.floor) % config.step;
        if (remainder !== 0) {
            numeric -= remainder;
            if (numeric < config.floor) {
                numeric = config.floor;
            }
        }
        return clampMax(numeric, maxCap);
    }

    function snapStepUp(value, maxCap) {
        var down = snapStepDown(value, maxCap);
        if (down < value) {
            down += config.step;
        }
        return clampMax(down, maxCap);
    }

    function snapStepDownBySteps(value, maxCap, steps) {
        var numeric = snapStepDown(value, maxCap);
        numeric -= config.step * steps;
        if (numeric < config.floor) {
            numeric = config.floor;
        }
        return snapStepDown(numeric, maxCap);
    }

    function snapStepUpBySteps(value, maxCap, steps) {
        var numeric = snapStepDown(value, maxCap);
        if (parseInt(value, 10) > numeric) {
            numeric += config.step;
        }
        numeric += config.step * (steps - 1);
        return clampMax(numeric, maxCap);
    }

    function snapPowerOfTwoDown(value, maxCap) {
        var numeric = parseInt(value, 10);
        if (isNaN(numeric) || numeric < config.floor) {
            numeric = config.floor;
        } else if (!isPowerOfTwo(numeric)) {
            numeric = largestPowerOfTwoUpTo(numeric);
            if (numeric < config.floor) {
                numeric = config.floor;
            }
        }
        return clampMax(numeric, maxCap);
    }

    function adjustByDirection(value, maxCap, previous, goingUp) {
        var numeric = parseInt(value, 10);
        previous = parseInt(previous, 10);

        if (isEliminationType()) {
            if (goingUp) {
                return clampMax(isNaN(previous) ? nextPowerOfTwo(numeric) : nextPowerOfTwo(previous), maxCap);
            }
            if (!isNaN(previous)) {
                return clampMax(prevPowerOfTwo(previous), maxCap);
            }
            return snapPowerOfTwoDown(numeric, maxCap);
        }

        if (isGroupStageType()) {
            if (goingUp) {
                return clampMax(isNaN(previous) ? nextGroupStageValue(numeric) : nextGroupStageValue(previous), maxCap);
            }
            if (!isNaN(previous)) {
                return clampMax(prevGroupStageValue(previous), maxCap);
            }
            return snapGroupStageDown(numeric, maxCap);
        }

        if (goingUp) {
            return isNaN(previous) ? snapStepUp(numeric, maxCap) : snapStepUpBySteps(previous, maxCap, 1);
        }
        if (!isNaN(previous)) {
            return snapStepDownBySteps(previous, maxCap, 1);
        }
        return snapStepDown(numeric, maxCap);
    }

    function resolveValue(rawValue, maxCap, previous) {
        var numeric = parseInt(rawValue, 10);
        previous = parseInt(previous, 10);

        if (isNaN(numeric)) {
            return isNaN(previous) ? config.floor : previous;
        }

        if (isEliminationType()) {
            if (isPowerOfTwo(numeric)) {
                return clampMax(numeric, maxCap);
            }
            if (!isNaN(previous)) {
                if (numeric > previous) {
                    return adjustByDirection(numeric, maxCap, previous, true);
                }
                if (numeric < previous) {
                    return adjustByDirection(numeric, maxCap, previous, false);
                }
            }
            return snapPowerOfTwoDown(numeric, maxCap);
        }

        if (isGroupStageType()) {
            if (isMultipleOf(numeric, config.groupStageStep)) {
                return clampMax(numeric, maxCap);
            }
            if (!isNaN(previous)) {
                if (numeric > previous) {
                    return adjustByDirection(numeric, maxCap, previous, true);
                }
                if (numeric < previous) {
                    return adjustByDirection(numeric, maxCap, previous, false);
                }
            }
            return snapGroupStageDown(numeric, maxCap);
        }

        if ((numeric - config.floor) % config.step === 0) {
            return clampMax(numeric, maxCap);
        }

        if (!isNaN(previous)) {
            if (numeric > previous) {
                return adjustByDirection(numeric, maxCap, previous, true);
            }
            if (numeric < previous) {
                return adjustByDirection(numeric, maxCap, previous, false);
            }
        }

        return snapStepDown(numeric, maxCap);
    }

    function getLimitMax() {
        return config.planMax > 0 ? config.planMax : 0;
    }

    function applyInputAttributes(limit) {
        var elimination = isEliminationType();
        var groupStage = isGroupStageType();
        limitInput.min = String(config.floor);
        limitInput.step = elimination || groupStage ? 'any' : String(config.step);
        if (getLimitMax() > 0) {
            limitInput.max = String(getLimitMax());
        } else {
            limitInput.removeAttribute('max');
        }

        minInput.min = String(config.floor);
        minInput.step = elimination || groupStage ? 'any' : String(config.step);
        minInput.max = String(limit);
    }

    function getConstraintHint() {
        if (isEliminationType()) {
            return 'Na eliminação, use 4, 8, 16, 32…';
        }
        if (isGroupStageType()) {
            return 'Na fase de grupos, use 4, 8, 12, 16…';
        }
        return 'Valores de ' + config.step + ' em ' + config.step + ', a partir de ' + config.floor + '.';
    }

    function updateHelp(limit) {
        var limitHelp = document.getElementById('participants_limit_help');
        var minHelp = document.getElementById('min_participants_help');
        var formatEl = document.getElementById('format');
        var isTeam = formatEl && formatEl.value === 'TEAM';
        var unit = isTeam ? 'equipes' : 'participantes';
        var hint = getConstraintHint();

        if (limitHelp && limitHelp.dataset) {
            var baseHelp = isTeam && limitHelp.dataset.teamHelp
                ? limitHelp.dataset.teamHelp
                : (limitHelp.dataset.soloHelp || '');
            limitHelp.textContent = baseHelp ? (baseHelp + ' ' + hint) : hint;
            if (config.planMax > 0) {
                limitHelp.textContent += ' Máximo do plano: ' + config.planMax + '.';
            }
        }

        if (minHelp) {
            var minBase = isTeam && minHelp.dataset.teamHelp
                ? minHelp.dataset.teamHelp
                : (minHelp.dataset.soloHelp || '');
            minHelp.textContent = minBase + ' ' + hint + ' Máximo: ' + limit + ' ' + unit + '.';
        }
    }

    function notifyUpdated(limit, min) {
        document.dispatchEvent(new CustomEvent('arenagamer:participants-fields-updated', {
            detail: {
                limit: limit,
                min: min,
                step: isEliminationType() || isGroupStageType() ? 0 : config.step,
                elimination: isEliminationType(),
                groupStage: isGroupStageType()
            }
        }));
        document.dispatchEvent(new CustomEvent('arenagamer:participants-limit-constraint-changed'));
    }

    function commitLimit(limit) {
        committed.limit = limit;
        limitInput.value = String(limit);
        applyInputAttributes(limit);

        var min = resolveValue(minInput.value, limit, committed.min);
        if (min > limit) {
            min = limit;
        }
        commitMin(min, limit, false);
        updateHelp(limit);
        notifyUpdated(limit, committed.min);
    }

    function commitMin(min, limit, updateLimitField) {
        limit = limit || committed.limit;
        min = resolveValue(min, limit, committed.min);
        if (min > limit) {
            min = limit;
        }
        committed.min = min;
        minInput.value = String(min);
        minInput.max = String(limit);

        if (updateLimitField) {
            limitInput.value = String(limit);
        }
    }

    function syncFromLimit() {
        var limit = resolveValue(limitInput.value, getLimitMax(), committed.limit);
        commitLimit(limit);
    }

    function syncFromMin() {
        var limit = committed.limit;
        var min = resolveValue(minInput.value, limit, committed.min);
        commitMin(min, limit, false);
        updateHelp(limit);
        notifyUpdated(limit, committed.min);
    }

    function nudgeField(input, field, direction) {
        var maxCap = field === 'limit' ? getLimitMax() : committed.limit;
        var previous = field === 'limit' ? committed.limit : committed.min;
        var goingUp = direction > 0;
        var next = adjustByDirection(previous, maxCap, previous, goingUp);

        if (field === 'limit') {
            limitInput.value = String(next);
            commitLimit(next);
        } else {
            minInput.value = String(next);
            syncFromMin();
        }
    }

    function bindArrowKeys(input, field) {
        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                nudgeField(input, field, 1);
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                nudgeField(input, field, -1);
            }
        });
    }

    limitInput.addEventListener('change', syncFromLimit);
    limitInput.addEventListener('blur', syncFromLimit);
    minInput.addEventListener('change', syncFromMin);
    minInput.addEventListener('blur', syncFromMin);
    bindArrowKeys(limitInput, 'limit');
    bindArrowKeys(minInput, 'min');

    var typeEl = document.getElementById('type');
    if (typeEl) {
        typeEl.addEventListener('change', syncFromLimit);
        if (typeof jQuery !== 'undefined' && jQuery(typeEl).hasClass('selectpicker')) {
            jQuery(typeEl).on('changed.bs.select', syncFromLimit);
        }
    }

    document.addEventListener('arenagamer:format-changed', syncFromLimit);

    window.arenagamerParticipantsLimitConstraint = {
        isEliminationType: isEliminationType,
        isGroupStageType: isGroupStageType,
        isPowerOfTwo: isPowerOfTwo,
        getStep: function () {
            if (isEliminationType() || isGroupStageType()) {
                return 0;
            }
            return config.step;
        },
        snap: function (value) {
            return resolveValue(value, getLimitMax(), committed.limit);
        },
        snapMin: function (value) {
            return resolveValue(value, committed.limit, committed.min);
        },
        getLimit: function () {
            return committed.limit;
        },
        getMin: function () {
            return committed.min;
        },
        setLimit: function (value) {
            limitInput.value = String(value);
            syncFromLimit();
        },
        setMin: function (value) {
            minInput.value = String(value);
            syncFromMin();
        },
        eachLimitFrom: function (startLimit, maxLimit, callback) {
            startLimit = resolveValue(startLimit, maxLimit, startLimit);
            if (isEliminationType()) {
                var power = config.floor;
                while (power <= maxLimit) {
                    if (power >= startLimit && callback(power) === false) {
                        return;
                    }
                    power *= 2;
                }
                return;
            }
            if (isGroupStageType()) {
                for (var groupLimit = config.groupStageStep; groupLimit <= maxLimit; groupLimit += config.groupStageStep) {
                    if (groupLimit >= startLimit && callback(groupLimit) === false) {
                        return;
                    }
                }
                return;
            }
            for (var limit = startLimit; limit <= maxLimit; limit += config.step) {
                if (callback(limit) === false) {
                    return;
                }
            }
        },
        sync: syncFromLimit
    };

    var initialLimit = resolveValue(config.defaultLimit, getLimitMax(), config.defaultLimit);
    var initialMin = resolveValue(
        Math.min(config.defaultMin, initialLimit),
        initialLimit,
        config.defaultMin
    );
    committed.limit = initialLimit;
    committed.min = initialMin;
    limitInput.value = String(initialLimit);
    minInput.value = String(initialMin);
    applyInputAttributes(initialLimit);
    updateHelp(initialLimit);
    notifyUpdated(initialLimit, initialMin);
})();
</script>
