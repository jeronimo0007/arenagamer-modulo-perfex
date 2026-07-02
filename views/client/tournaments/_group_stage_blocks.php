<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$blocks = is_array($group_stage_blocks ?? null) ? $group_stage_blocks : [];
$canManage = !empty($can_manage);
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$advancePerGroup = max(0, (int) ($advance_per_group ?? 0));
$showMatches = !empty($show_matches);
$tournament = is_array($tournament ?? null) ? $tournament : [];
if (isset($show_classification)) {
    $showClassification = !empty($show_classification);
} elseif (!empty($tournament)) {
    $showClassification = arenagamer_tournament_shows_classification($tournament);
} else {
    $showClassification = true;
}

if (empty($blocks)) {
    return;
}
?>
<div class="ag-group-stage">
    <div class="row ag-group-stage__grid">
        <?php foreach ($blocks as $block): ?>
        <?php
            $groupNumber = (int) ($block['number'] ?? 0);
            $standings = is_array($block['standings'] ?? null) ? $block['standings'] : [];
            $matches = is_array($block['matches'] ?? null) ? $block['matches'] : [];
            $participantCount = count($standings);
            $finishedMatches = 0;
            foreach ($matches as $gm) {
                if (in_array($gm['status'] ?? '', ['COMPLETED', 'WALKOVER'], true)) {
                    $finishedMatches++;
                }
            }
            $qualifiers = [];
            if ($showClassification) {
                foreach ($standings as $sq) {
                    $sqPos = (int) ($sq['position'] ?? 0);
                    if ($sqPos < 1) {
                        continue;
                    }
                    if ($advancePerGroup > 0 && $sqPos > $advancePerGroup) {
                        continue;
                    }
                    if ($advancePerGroup === 0 && $sqPos > 5) {
                        continue;
                    }
                    $sqTier = arenagamer_position_tier($sqPos);
                    if ($sqTier === null) {
                        continue;
                    }
                    $qualifiers[] = [
                        'label' => $sqPos . 'º',
                        'tier'  => $sqTier,
                        'name'  => trim((string) ($sq['participantName'] ?? '')),
                    ];
                }
            }
        ?>
        <div class="col-sm-12 col-lg-6 col-xl-4 ag-group-stage__col">
            <article class="ag-group-stage__block">
                <header class="ag-group-stage__header">
                    <div class="ag-group-stage__header-main">
                        <span class="ag-group-stage__icon"><i class="fa fa-users"></i></span>
                        <div>
                            <h6 class="ag-group-stage__title">Grupo <?php echo $groupNumber; ?></h6>
                            <?php if ($participantCount > 0): ?>
                            <span class="ag-group-stage__subtitle"><?php echo $participantCount; ?> participante<?php echo $participantCount === 1 ? '' : 's'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ag-group-stage__header-aside">
                        <?php if (!empty($qualifiers)): ?>
                        <div class="ag-group-stage__qualifiers" title="Melhores colocados do grupo">
                            <?php foreach ($qualifiers as $qualifier): ?>
                            <span class="ag-rank-badge ag-rank-badge--<?php echo htmlspecialchars($qualifier['tier']); ?>"
                                  title="<?php echo htmlspecialchars($qualifier['name'] !== '' ? $qualifier['name'] : $qualifier['label']); ?>">
                                <?php echo htmlspecialchars($qualifier['label']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($matches)): ?>
                        <span class="ag-group-stage__progress"><?php echo $finishedMatches; ?>/<?php echo count($matches); ?> jogos</span>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if (!empty($standings)): ?>
                <section class="ag-group-stage__section">
                    <h6 class="ag-group-stage__section-title">
                        <i class="fa <?php echo $showClassification ? 'fa-list-ol' : 'fa-users'; ?>"></i>
                        <?php echo $showClassification ? 'Classificação' : 'Participantes'; ?>
                    </h6>
                    <div class="ag-group-stage__table-wrap">
                        <table class="ag-group-stage__table">
                            <thead>
                                <tr>
                                    <th class="ag-group-stage__th-participant">Participante</th>
                                    <?php if ($showClassification): ?>
                                    <th class="ag-group-stage__th-stat" title="Pontos">P</th>
                                    <th class="ag-group-stage__th-stat" title="Vitórias">V</th>
                                    <th class="ag-group-stage__th-stat" title="Empates">E</th>
                                    <th class="ag-group-stage__th-stat" title="Derrotas">D</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standings as $s): ?>
                                <?php
                                    $position = (int) ($s['position'] ?? 0);
                                    $tier = $showClassification ? arenagamer_position_tier($position) : null;
                                    $rowClass = '';
                                    $tierIcon = '';
                                    if ($showClassification) {
                                        if ($position === 1) {
                                            $rowClass = 'ag-group-stage__row--first';
                                            $tierIcon = 'fa-trophy';
                                        } elseif ($position === 2) {
                                            $rowClass = 'ag-group-stage__row--second';
                                            $tierIcon = 'fa-medal';
                                        } elseif ($position === 3) {
                                            $rowClass = 'ag-group-stage__row--third';
                                            $tierIcon = 'fa-medal';
                                        } elseif ($position === 4 || $position === 5) {
                                            $rowClass = 'ag-group-stage__row--qualifier';
                                        }
                                        if ($tier !== null) {
                                            $rowClass .= ' ag-group-stage__row--tier-' . $tier;
                                        }
                                    }
                                ?>
                                <tr class="<?php echo trim($rowClass); ?>">
                                    <td class="ag-group-stage__cell-participant">
                                        <div class="ag-group-stage__participant-row">
                                            <?php if ($showClassification && $tierIcon !== ''): ?>
                                            <span class="ag-group-stage__tier-icon ag-group-stage__tier-icon--<?php echo htmlspecialchars($tier); ?>">
                                                <i class="fa <?php echo htmlspecialchars($tierIcon); ?>"></i>
                                            </span>
                                            <?php elseif ($showClassification): ?>
                                            <span class="ag-group-stage__tier-icon ag-group-stage__tier-icon--neutral" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <span class="ag-group-stage__participant-wrap">
                                                <?php
                                                    $participantName = trim((string) ($s['participantName'] ?? ''));
                                                    $participantLabel = arenagamer_participant_display_label($participantName);
                                                ?>
                                                <span class="ag-group-stage__participant"<?php echo $participantName !== '' && $participantLabel !== $participantName ? ' title="' . htmlspecialchars($participantName) . '"' : ''; ?>>
                                                    <?php echo htmlspecialchars($participantLabel); ?>
                                                </span>
                                                <?php if ($showClassification && !empty($s['note'])): ?>
                                                <span class="ag-group-stage__note-badge"><?php echo htmlspecialchars($s['note']); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <?php if ($showClassification): ?>
                                    <td class="ag-group-stage__stat ag-group-stage__stat--points">
                                        <span class="ag-group-stage__points"><?php echo (int) ($s['points'] ?? 0); ?></span>
                                    </td>
                                    <td class="ag-group-stage__stat"><?php echo (int) ($s['wins'] ?? 0); ?></td>
                                    <td class="ag-group-stage__stat"><?php echo (int) ($s['draws'] ?? 0); ?></td>
                                    <td class="ag-group-stage__stat"><?php echo (int) ($s['losses'] ?? 0); ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($showMatches && !empty($matches)): ?>
                <section class="ag-group-stage__section ag-group-stage__section--matches">
                    <h6 class="ag-group-stage__section-title"><i class="fa fa-gamepad"></i> Jogos</h6>
                    <div class="ag-group-stage__matches">
                        <?php foreach ($matches as $match): ?>
                        <?php
                            $home = arenagamer_bracket_participant_row($match, 'home', $standingMap);
                            $away = arenagamer_bracket_participant_row($match, 'away', $standingMap);
                            $homeId = $match['homeParticipantId'] ?? null;
                            $awayId = $match['awayParticipantId'] ?? null;
                            $matchStatus = $match['status'] ?? '';
                            $bothDefined = !empty($homeId) && !empty($awayId);
                            $isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);
                            $scheduled = !empty($match['scheduledAt'])
                                ? arenagamer_format_date($match['scheduledAt'], 'd/m H:i')
                                : '';
                        ?>
                        <div class="ag-group-stage__game<?php echo $isFinished ? ' ag-group-stage__game--done' : ''; ?>">
                            <div class="ag-group-stage__game-actions">
                                <span class="ag-group-stage__game-meta">
                                    <?php if ($scheduled !== ''): ?>
                                    <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($scheduled); ?>
                                    <?php else: ?>
                                    <?php echo arenagamer_status_badge($matchStatus); ?>
                                    <?php endif; ?>
                                </span>
                                <?php if ($canManage && $bothDefined && !$isFinished): ?>
                                <button type="button" class="ag-group-stage__result-btn" data-toggle="modal" data-target="#result-modal-<?php echo (int) $match['id']; ?>" title="Registrar resultado" aria-label="Registrar resultado">
                                    <i class="fa fa-trophy"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="ag-group-stage__team<?php echo $home['tierClass']; ?><?php echo $home['winner'] ? ' ag-group-stage__team--winner' : ''; ?><?php echo $home['empty'] ? ' ag-group-stage__team--empty' : ''; ?>">
                                <?php if ($home['winner']): ?><i class="fa fa-trophy ag-group-stage__trophy"></i><?php endif; ?>
                                <span class="ag-group-stage__team-name"><?php echo htmlspecialchars($home['label']); ?></span>
                                <?php if ($home['score'] !== null): ?>
                                <span class="ag-group-stage__team-score"><?php echo (int) $home['score']; ?></span>
                                <?php endif; ?>
                                <?php echo arenagamer_render_participant_rank_badge($home); ?>
                            </div>
                            <div class="ag-group-stage__vs">vs</div>
                            <div class="ag-group-stage__team<?php echo $away['tierClass']; ?><?php echo $away['winner'] ? ' ag-group-stage__team--winner' : ''; ?><?php echo $away['empty'] ? ' ag-group-stage__team--empty' : ''; ?>">
                                <?php if ($away['winner']): ?><i class="fa fa-trophy ag-group-stage__trophy"></i><?php endif; ?>
                                <span class="ag-group-stage__team-name"><?php echo htmlspecialchars($away['label']); ?></span>
                                <?php if ($away['score'] !== null): ?>
                                <span class="ag-group-stage__team-score"><?php echo (int) $away['score']; ?></span>
                                <?php endif; ?>
                                <?php echo arenagamer_render_participant_rank_badge($away); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (empty($standings) && empty($matches)): ?>
                <div class="ag-group-stage__empty">
                    <i class="fa fa-inbox"></i>
                    <span>Aguardando dados do grupo</span>
                </div>
                <?php endif; ?>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
</div>
