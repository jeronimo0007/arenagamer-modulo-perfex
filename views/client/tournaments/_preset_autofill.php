<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$presetsData = arenagamer_api_data($presets ?? null, []);
$presetMap = arenagamer_presets_autofill_map($presetsData);
?>
<script>
(function () {
    var presets = <?php echo json_encode($presetMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var presetSelect = document.getElementById('preset_id');

    if (!presetSelect) {
        return;
    }

    function field(id) {
        return document.getElementById(id);
    }

    function setValue(id, value) {
        var el = field(id);
        if (!el || value === null || value === undefined) {
            return;
        }
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setSelectValue(id, value) {
        var el = field(id);
        if (!el || !value) {
            return;
        }
        el.value = value;
        if (typeof jQuery !== 'undefined' && jQuery(el).hasClass('selectpicker')) {
            jQuery(el).selectpicker('refresh');
        }
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function updateGameImagePreview(url) {
        var preview = document.getElementById('game_image_preview');
        if (!preview) {
            return;
        }

        if (url) {
            preview.innerHTML = '<img src="' + escapeHtml(url) + '" alt="Imagem do jogo" class="img-thumbnail" style="max-height:80px;">';
            preview.style.display = '';
            return;
        }

        preview.innerHTML = '';
        preview.style.display = 'none';
    }

    function setGameImageLock(preset) {
        var urlEl = field('game_image_url');
        var fileEl = field('game_image_file');
        var helpEl = document.getElementById('game_image_help');
        var locked = !!(preset && preset.locksGameImage && preset.presetGameImageUrl);

        if (urlEl) {
            urlEl.readOnly = locked;
            if (locked) {
                urlEl.value = preset.presetGameImageUrl;
            }
        }

        if (fileEl) {
            fileEl.disabled = locked;
            if (locked) {
                fileEl.value = '';
            }
        }

        if (helpEl) {
            helpEl.textContent = locked
                ? 'Definida pelo preset selecionado (não editável).'
                : 'Se vazio, usa a imagem do preset selecionado.';
        }

        updateGameImagePreview(locked ? preset.presetGameImageUrl : (urlEl ? urlEl.value : ''));
    }

    function applyPreset(presetId) {
        var preset = presets[presetId] || presets[String(presetId)];
        if (!preset) {
            setGameImageLock(null);
            return;
        }

        if (preset.gameName) {
            setValue('game_name', preset.gameName);
        }

        setSelectValue('format', preset.teamSize > 1 ? 'TEAM' : 'SOLO');

        if (preset.rulesTemplate) {
            setValue('rules', preset.rulesTemplate);
        }

        if (preset.locksGameImage && preset.presetGameImageUrl) {
            setValue('game_image_url', preset.presetGameImageUrl);
            setGameImageLock(preset);
        } else {
            setGameImageLock(null);
            if (preset.iconUrl) {
                setValue('game_image_url', preset.gameImageUrl || preset.iconUrl);
            } else if (preset.gameImageUrl) {
                setValue('game_image_url', preset.gameImageUrl);
            }
        }

        if (preset.minPlayersPerTeam > 0 && field('min_participants')) {
            setValue('min_participants', Math.max(2, preset.minPlayersPerTeam));
        }

        if (preset.platform && field('description')) {
            var descriptionEl = field('description');
            var platformLine = 'Plataforma: ' + preset.platform;
            if (descriptionEl.value.trim() === '') {
                setValue('description', platformLine);
            }
        }
    }

    presetSelect.addEventListener('change', function () {
        var presetId = parseInt(this.value, 10);
        if (!presetId) {
            setGameImageLock(null);
            return;
        }
        applyPreset(presetId);
    });

    var initialPresetId = parseInt(presetSelect.value, 10);
    if (initialPresetId) {
        var initialPreset = presets[initialPresetId] || presets[String(initialPresetId)];
        if (initialPreset && initialPreset.locksGameImage) {
            setGameImageLock(initialPreset);
        }
    }
})();
</script>
