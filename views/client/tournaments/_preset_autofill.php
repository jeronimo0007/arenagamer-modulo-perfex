<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$presetsData = arenagamer_api_data($presets ?? null, []);
$presetMap = arenagamer_presets_autofill_map($presetsData);
?>
<script>
(function () {
    var presets = <?php echo json_encode($presetMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var presetInput = document.getElementById('preset_id');

    if (!presetInput) {
        return;
    }

    function field(id) {
        return document.getElementById(id);
    }

    function toAutofillEntry(preset) {
        var gameImageUrl = preset.gameImageUrl || preset.iconUrl || '';
        return {
            gameName: preset.gameName || '',
            platform: preset.platform || '',
            teamSize: parseInt(preset.teamSize, 10) || 1,
            minPlayersPerTeam: parseInt(preset.minPlayersPerTeam, 10) || 1,
            maxPlayersPerTeam: parseInt(preset.maxPlayersPerTeam, 10) || 1,
            iconUrl: preset.iconUrl || '',
            gameImageUrl: gameImageUrl,
            presetGameImageUrl: (preset.gameImageUrl || '').trim(),
            locksGameImage: ((preset.gameImageUrl || '').trim() !== ''),
            rulesTemplate: preset.rulesTemplate || ''
        };
    }

    window.arenagamerRegisterPreset = function (preset) {
        if (!preset || !preset.id) {
            return;
        }
        presets[preset.id] = toAutofillEntry(preset);
    };

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
                ? 'Definida pelo jogo selecionado (não editável).'
                : 'Se vazio, usa a imagem do jogo selecionado.';
        }

        updateGameImagePreview(locked ? preset.presetGameImageUrl : (urlEl ? urlEl.value : ''));
    }

    function applyPreset(presetId) {
        var preset = presets[presetId] || presets[String(presetId)];
        if (!preset) {
            setGameImageLock(null);
            return;
        }

        setSelectValue('format', preset.teamSize > 1 ? 'TEAM' : 'SOLO');

        if (preset.minPlayersPerTeam > 0 && field('min_players_per_team')) {
            setValue('min_players_per_team', preset.minPlayersPerTeam);
        }

        if (preset.maxPlayersPerTeam > 0 && field('max_players_per_team')) {
            setValue('max_players_per_team', preset.maxPlayersPerTeam);
        }

        if (field('format')) {
            field('format').dispatchEvent(new Event('change', { bubbles: true }));
        }

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
            setValue('min_participants', Math.max(4, preset.minPlayersPerTeam));
        }

        if (preset.platform && field('description')) {
            var descriptionEl = field('description');
            var platformLine = 'Plataforma: ' + preset.platform;
            if (descriptionEl.value.trim() === '') {
                setValue('description', platformLine);
            }
        }
    }

    presetInput.addEventListener('change', function () {
        var presetId = parseInt(this.value, 10);
        if (!presetId) {
            setGameImageLock(null);
            return;
        }
        applyPreset(presetId);
    });

    var initialPresetId = parseInt(presetInput.value, 10);
    if (initialPresetId) {
        var initialPreset = presets[initialPresetId] || presets[String(initialPresetId)];
        if (initialPreset && initialPreset.locksGameImage) {
            setGameImageLock(initialPreset);
        }
    }
})();
</script>
