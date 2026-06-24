<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$selectedId = (int) ($t['presetId'] ?? 0);
$selectedPreset = $selected_preset ?? null;
if (!$selectedPreset && $selectedId > 0) {
    $selectedPreset = arenagamer_find_preset_by_id($presets ?? null, $selectedId);
}
$selectedLabel = '';
if (is_array($selectedPreset)) {
    $selectedLabel = trim((string) ($selectedPreset['gameName'] ?? ''));
}
if ($selectedLabel === '' && $selectedId > 0) {
    $selectedLabel = arenagamer_tournament_game_name($t, 'Jogo #' . $selectedId);
}
$manualGameName = $selectedId > 0 ? '' : trim((string) ($t['gameName'] ?? ''));
$searchUrl = (string) ($search_url ?? '');
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$minChars = 3;
?>
<div class="ag-game-fields" id="ag-game-fields">
    <div class="form-group ag-game-search" id="ag-game-search">
        <label for="preset_search" class="<?php echo htmlspecialchars($labelClass); ?>">
            Jogo predefinido <span class="text-muted">(opcional)</span>
        </label>
        <input type="hidden" name="preset_id" id="preset_id" value="<?php echo $selectedId > 0 ? $selectedId : ''; ?>">
        <div class="ag-game-search-wrap" style="position:relative;">
            <div class="input-group">
                <input type="text"
                       id="preset_search"
                       class="form-control"
                       autocomplete="off"
                       placeholder="Digite pelo menos <?php echo (int) $minChars; ?> letras para buscar..."
                       value="<?php echo htmlspecialchars($selectedLabel); ?>"
                       aria-autocomplete="list"
                       aria-controls="preset_search_results"
                       aria-expanded="false">
                <span class="input-group-btn">
                    <button type="button" class="btn btn-default" id="preset_clear_btn" title="Não usar predefinição">
                        Limpar
                    </button>
                </span>
            </div>
            <ul id="preset_search_results"
                class="list-group ag-game-search-results"
                role="listbox"
                style="display:none;position:absolute;z-index:1050;width:100%;max-height:260px;overflow-y:auto;margin-top:2px;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
        </div>
        <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0">
            Se escolher um jogo predefinido, regras, formato sugerido e imagem são preenchidos automaticamente.
        </p>
    </div>

    <div class="form-group" id="game_name_manual_group"<?php echo $selectedId > 0 ? ' style="display:none;"' : ''; ?>>
        <label for="game_name" class="<?php echo htmlspecialchars($labelClass); ?>">
            Nome do jogo <span class="text-danger">*</span>
        </label>
        <input type="text"
               name="game_name"
               id="game_name"
               class="form-control"
               maxlength="100"
               placeholder="Ex.: Counter-Strike 2"
               value="<?php echo htmlspecialchars($manualGameName); ?>"
               <?php echo $selectedId > 0 ? '' : 'required'; ?>>
        <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0">
            Use quando não quiser um jogo predefinido do catálogo.
        </p>
    </div>
</div>
<style>
.ag-game-search-results .list-group-item {
    cursor: pointer;
    border-left: 0;
    border-right: 0;
}
.ag-game-search-results .list-group-item:first-child {
    border-top: 0;
}
.ag-game-search-results .list-group-item:hover,
.ag-game-search-results .list-group-item:focus {
    background: #f5f5f5;
}
.ag-game-search-results .ag-game-search-empty {
    cursor: default;
    color: #777;
}
</style>
<script>
(function () {
    var searchUrl = <?php echo json_encode($searchUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var minChars = <?php echo (int) $minChars; ?>;
    var committedLabel = <?php echo json_encode($selectedLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var input = document.getElementById('preset_search');
    var hidden = document.getElementById('preset_id');
    var results = document.getElementById('preset_search_results');
    var wrap = input ? input.closest('.ag-game-search-wrap') : null;
    var clearBtn = document.getElementById('preset_clear_btn');
    var manualGroup = document.getElementById('game_name_manual_group');
    var manualInput = document.getElementById('game_name');

    if (!input || !hidden || !results || !searchUrl) {
        return;
    }

    var debounceTimer = null;
    var requestSeq = 0;
    var lastResults = [];
    var replacingSelection = false;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeTerm(value) {
        return String(value || '').trim().toLowerCase();
    }

    function matchesSearchTerm(preset, term) {
        var needle = normalizeTerm(term);
        if (needle.length < minChars) {
            return false;
        }

        var name = normalizeTerm(preset.gameName);
        var platform = normalizeTerm(preset.platform);

        return (name && name.indexOf(needle) === 0)
            || (platform && platform.indexOf(needle) === 0);
    }

    function filterResults(items, term) {
        return (Array.isArray(items) ? items : []).filter(function (preset) {
            return matchesSearchTerm(preset, term);
        });
    }

    function hideResults() {
        results.style.display = 'none';
        results.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        lastResults = [];
    }

    function syncManualGameField() {
        var hasPreset = hidden.value !== '';

        if (manualGroup) {
            manualGroup.style.display = hasPreset ? 'none' : '';
        }

        if (manualInput) {
            if (hasPreset) {
                manualInput.removeAttribute('required');
            } else {
                manualInput.setAttribute('required', 'required');
            }
        }
    }

    function showMessage(text, className) {
        results.innerHTML = '<li class="list-group-item ag-game-search-empty ' + (className || '') + '">' + escapeHtml(text) + '</li>';
        results.style.display = 'block';
        input.setAttribute('aria-expanded', 'true');
    }

    function renderResults(items, term) {
        var filtered = filterResults(items, term);
        lastResults = filtered;

        if (!filtered.length) {
            showMessage('Nenhum jogo predefinido encontrado.');
            return;
        }

        results.innerHTML = filtered.map(function (preset, index) {
            var label = preset.gameName || ('Jogo #' + preset.id);
            var meta = preset.platform ? ' <span class="text-muted">(' + escapeHtml(preset.platform) + ')</span>' : '';
            return '<li class="list-group-item" role="option" tabindex="0" data-index="' + index + '">'
                + escapeHtml(label) + meta + '</li>';
        }).join('');
        results.style.display = 'block';
        input.setAttribute('aria-expanded', 'true');
    }

    function clearPresetSelection(focusManual) {
        hidden.value = '';
        input.value = '';
        committedLabel = '';
        replacingSelection = false;
        hideResults();
        syncManualGameField();

        hidden.dispatchEvent(new Event('change', { bubbles: true }));

        if (focusManual && manualInput) {
            manualInput.focus();
        }
    }

    function selectPreset(preset) {
        if (!preset || !preset.id) {
            return;
        }

        hidden.value = String(preset.id);
        input.value = preset.gameName || ('Jogo #' + preset.id);
        committedLabel = input.value;
        replacingSelection = false;
        hideResults();

        if (manualInput) {
            manualInput.value = '';
        }

        syncManualGameField();

        if (typeof window.arenagamerRegisterPreset === 'function') {
            window.arenagamerRegisterPreset(preset);
        }

        hidden.dispatchEvent(new Event('change', { bubbles: true }));
        document.dispatchEvent(new CustomEvent('arenagamer:preset-selected', {
            bubbles: true,
            detail: preset
        }));
    }

    function buildSearchUrl(term) {
        var joiner = searchUrl.indexOf('?') === -1 ? '?' : '&';
        return searchUrl + joiner + 'term=' + encodeURIComponent(term);
    }

    function searchPresets(query) {
        var term = query.trim();
        if (term.length < minChars) {
            hideResults();
            return;
        }

        var seq = ++requestSeq;
        showMessage('Buscando...', 'text-muted');

        fetch(buildSearchUrl(term), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (seq !== requestSeq) {
                    return;
                }
                if (!payload || !payload.success) {
                    showMessage((payload && payload.message) ? payload.message : 'Erro ao buscar jogos.', 'text-danger');
                    return;
                }
                renderResults(Array.isArray(payload.data) ? payload.data : [], term);
            })
            .catch(function () {
                if (seq !== requestSeq) {
                    return;
                }
                showMessage('Erro ao buscar jogos.', 'text-danger');
            });
    }

    input.addEventListener('focus', function () {
        if (hidden.value && input.value === committedLabel && committedLabel !== '') {
            replacingSelection = true;
            input.select();
        }
    });

    input.addEventListener('input', function () {
        if (replacingSelection) {
            replacingSelection = false;
            hidden.value = '';
            committedLabel = '';
        } else {
            hidden.value = '';
        }

        syncManualGameField();

        var query = input.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < minChars) {
            hideResults();
            return;
        }

        debounceTimer = setTimeout(function () {
            searchPresets(query);
        }, 280);
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            clearPresetSelection(true);
        });
    }

    results.addEventListener('click', function (event) {
        var item = event.target.closest('[data-index]');
        if (!item) {
            return;
        }
        var index = parseInt(item.getAttribute('data-index'), 10);
        if (!isNaN(index) && lastResults[index]) {
            selectPreset(lastResults[index]);
        }
    });

    document.addEventListener('click', function (event) {
        if (wrap && !wrap.contains(event.target)) {
            hideResults();
        }
    });

    var form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function (event) {
            if (hidden.value) {
                return;
            }

            var manualName = manualInput ? manualInput.value.trim() : '';
            if (!manualName) {
                event.preventDefault();
                if (manualInput) {
                    manualInput.focus();
                }
                return;
            }
        });
    }

    syncManualGameField();
})();
</script>
