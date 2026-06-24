<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = is_array($tournament ?? null) ? $tournament : [];
$isTeamFormat = ($t['format'] ?? 'SOLO') === 'TEAM';
$minPlayers = (int) ($t['minPlayersPerTeam'] ?? 5);
$maxPlayers = (int) ($t['maxPlayersPerTeam'] ?? 5);
if ($minPlayers < 1) {
    $minPlayers = 5;
}
if ($maxPlayers < 1) {
    $maxPlayers = 5;
}
?>
<div class="row mtop15" id="team-format-fields" style="<?php echo $isTeamFormat ? '' : 'display:none;'; ?>">
    <div class="col-md-4">
        <div class="form-group">
            <label for="min_players_per_team" class="control-label">
                Tamanho mínimo da equipe <span class="text-danger team-format-required">*</span>
            </label>
            <input type="number"
                   min="1"
                   max="99"
                   name="min_players_per_team"
                   id="min_players_per_team"
                   class="form-control"
                   value="<?php echo $minPlayers; ?>"
                   <?php echo $isTeamFormat ? 'required' : ''; ?>>
            <p class="help-block text-muted">
                Mínimo de jogadores (clientes) por equipe para se inscrever.
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="max_players_per_team" class="control-label">
                Tamanho máximo da equipe <span class="text-danger team-format-required">*</span>
            </label>
            <input type="number"
                   min="1"
                   max="99"
                   name="max_players_per_team"
                   id="max_players_per_team"
                   class="form-control"
                   value="<?php echo $maxPlayers; ?>"
                   <?php echo $isTeamFormat ? 'required' : ''; ?>>
            <p class="help-block text-muted">
                Máximo de jogadores (clientes) por equipe inscrita neste torneio.
            </p>
        </div>
    </div>
</div>
<script>
(function () {
    var formatEl = document.getElementById('format');
    var teamFields = document.getElementById('team-format-fields');
    var minPlayersEl = document.getElementById('min_players_per_team');
    var maxPlayersEl = document.getElementById('max_players_per_team');
    var participantsLabel = document.getElementById('participants_limit_label');
    var participantsHelp = document.getElementById('participants_limit_help');

    if (!formatEl || !teamFields) {
        return;
    }

    function isTeamFormat() {
        return formatEl.value === 'TEAM';
    }

    function updateParticipantsCopy() {
        var team = isTeamFormat();
        if (participantsLabel) {
            participantsLabel.textContent = team ? 'Limite de equipes' : 'Limite de participantes';
        }
        if (participantsHelp && participantsHelp.dataset) {
            var soloHelp = participantsHelp.dataset.soloHelp || '';
            var teamHelp = participantsHelp.dataset.teamHelp || '';
            if (team && teamHelp) {
                participantsHelp.textContent = teamHelp;
            } else if (!team && soloHelp) {
                participantsHelp.textContent = soloHelp;
            }
        }
    }

    function syncFormatFields() {
        var team = isTeamFormat();
        teamFields.style.display = team ? '' : 'none';
        [minPlayersEl, maxPlayersEl].forEach(function (el) {
            if (!el) {
                return;
            }
            if (team) {
                el.setAttribute('required', 'required');
            } else {
                el.removeAttribute('required');
            }
        });
        updateParticipantsCopy();
        if (typeof jQuery !== 'undefined' && jQuery(formatEl).hasClass('selectpicker')) {
            jQuery(formatEl).selectpicker('refresh');
        }
    }

    formatEl.addEventListener('change', syncFormatFields);
    syncFormatFields();
})();
</script>
