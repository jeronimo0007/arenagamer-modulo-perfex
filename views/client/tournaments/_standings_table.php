<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php

$standingsData = is_array($standings_data ?? null) ? $standings_data : [];

if (empty($standingsData)) {

    return;

}



$tournament = is_array($tournament ?? null) ? $tournament : [];

if (isset($show_classification)) {

    $showClassification = !empty($show_classification);

} elseif (!empty($tournament)) {

    $showClassification = arenagamer_tournament_shows_classification($tournament);

} else {

    $showClassification = true;

}



$sectionTitle = $showClassification ? 'Classificação' : 'Participantes';

$sectionIcon = $showClassification ? 'fa-list-ol' : 'fa-users';



$hasGroups = false;

foreach ($standingsData as $s) {

    if (!is_array($s)) {

        continue;

    }

    if ($showClassification && (isset($s['points']) || isset($s['groupNumber']))) {

        $hasGroups = true;

        break;

    }

    if (!$showClassification && !empty($s['groupNumber'])) {

        $hasGroups = true;

        break;

    }

}



$wrapperClass = trim((string) ($wrapper_class ?? 'ag-standings mtop20'));

$collapseId = (string) ($collapse_id ?? 'arenagamer-standings');

$showToggle = !isset($show_toggle) || $show_toggle;

?>

<div class="<?php echo htmlspecialchars($wrapperClass); ?>">

    <div class="ag-standings__header">

        <h5 class="ag-standings__title"><i class="fa <?php echo htmlspecialchars($sectionIcon); ?>"></i> <?php echo htmlspecialchars($sectionTitle); ?></h5>

        <?php if ($showToggle): ?>

        <a href="#<?php echo htmlspecialchars($collapseId); ?>" data-toggle="collapse" class="ag-standings__toggle">

            <i class="fa fa-chevron-up"></i>

        </a>

        <?php endif; ?>

    </div>

    <div id="<?php echo htmlspecialchars($collapseId); ?>" class="ag-standings__wrap collapse in">

        <table class="ag-standings__table">

            <thead>

                <tr>

                    <?php if ($hasGroups): ?><th class="ag-standings__th-group"><?php echo $showClassification ? 'Grupo / Pos.' : 'Grupo'; ?></th><?php endif; ?>

                    <th class="ag-standings__th-participant">Participante</th>

                    <?php if ($showClassification && $hasGroups): ?>

                    <th class="ag-standings__th-stat" title="Pontos">P</th>

                    <th class="ag-standings__th-stat" title="Vitórias">V</th>

                    <th class="ag-standings__th-stat" title="Empates">E</th>

                    <th class="ag-standings__th-stat" title="Derrotas">D</th>

                    <?php elseif ($showClassification): ?>

                    <th class="ag-standings__th-stat ag-standings__th-stat--result">Resultado</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($standingsData as $s): ?>

                <?php

                    $position = (int) ($s['position'] ?? 0);

                    $tier = $showClassification ? arenagamer_position_tier($position) : null;

                    $rowClass = '';

                    $tierIcon = '';

                    if ($showClassification) {

                        if ($position === 1) {

                            $rowClass = 'ag-standings__row--first';

                            $tierIcon = 'fa-trophy';

                        } elseif ($position === 2) {

                            $rowClass = 'ag-standings__row--second';

                            $tierIcon = 'fa-medal';

                        } elseif ($position === 3) {

                            $rowClass = 'ag-standings__row--third';

                            $tierIcon = 'fa-medal';

                        } elseif ($position === 4 || $position === 5) {

                            $rowClass = 'ag-standings__row--qualifier';

                        }

                        if ($tier !== null) {

                            $rowClass .= ' ag-standings__row--tier-' . $tier;

                        }

                    }

                ?>

                <tr class="<?php echo trim($rowClass); ?>">

                    <?php if ($hasGroups): ?>

                    <td class="ag-standings__cell-group">

                        <div class="ag-standings__group-pos">

                            <?php if (!empty($s['groupNumber'])): ?>

                            <span class="ag-standings__group-pill">G<?php echo (int) ($s['groupNumber'] ?? 0); ?></span>

                            <?php endif; ?>

                            <?php if ($showClassification && $position > 0): ?>

                            <span class="ag-standings__position-pill<?php echo $tier !== null ? ' ag-standings__position-pill--' . htmlspecialchars($tier) : ''; ?>">

                                <?php echo $position; ?>º

                            </span>

                            <?php elseif ($showClassification): ?>

                            <span class="ag-standings__position-pill ag-standings__position-pill--empty">—</span>

                            <?php endif; ?>

                        </div>

                    </td>

                    <?php endif; ?>

                    <td class="ag-standings__cell-participant">

                        <div class="ag-standings__participant-row">

                            <?php if ($showClassification && $tierIcon !== ''): ?>

                            <span class="ag-standings__tier-icon ag-standings__tier-icon--<?php echo htmlspecialchars($tier); ?>">

                                <i class="fa <?php echo htmlspecialchars($tierIcon); ?>"></i>

                            </span>

                            <?php elseif ($showClassification): ?>

                            <span class="ag-standings__tier-icon ag-standings__tier-icon--neutral" aria-hidden="true"></span>

                            <?php endif; ?>

                            <span class="ag-standings__participant-wrap">

                                <?php

                                    $participantName = trim((string) ($s['participantName'] ?? 'A definir'));

                                    $participantLabel = arenagamer_participant_display_label($participantName !== '' ? $participantName : 'A definir');

                                ?>

                                <span class="ag-standings__participant"<?php echo $participantName !== '' && $participantLabel !== $participantName ? ' title="' . htmlspecialchars($participantName) . '"' : ''; ?>>

                                    <?php echo htmlspecialchars($participantLabel); ?>

                                </span>

                                <?php if ($showClassification && !empty($s['note']) && $hasGroups): ?>

                                <span class="ag-standings__note-badge"><?php echo htmlspecialchars($s['note']); ?></span>

                                <?php endif; ?>

                            </span>

                        </div>

                    </td>

                    <?php if ($showClassification && $hasGroups): ?>

                    <td class="ag-standings__stat ag-standings__stat--points">

                        <span class="ag-standings__points"><?php echo (int) ($s['points'] ?? 0); ?></span>

                    </td>

                    <td class="ag-standings__stat"><?php echo (int) ($s['wins'] ?? 0); ?></td>

                    <td class="ag-standings__stat"><?php echo (int) ($s['draws'] ?? 0); ?></td>

                    <td class="ag-standings__stat"><?php echo (int) ($s['losses'] ?? 0); ?></td>

                    <?php elseif ($showClassification): ?>

                    <td class="ag-standings__stat ag-standings__stat--result">

                        <?php echo !empty($s['note']) ? htmlspecialchars($s['note']) : '—'; ?>

                    </td>

                    <?php endif; ?>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

