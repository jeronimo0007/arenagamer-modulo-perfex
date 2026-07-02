<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$match = is_array($match ?? null) ? $match : [];
if (empty($match)) {
    return;
}

$standingMap = is_array($standing_map ?? null) ? $standing_map : [];
$canManage = !empty($can_manage);
$cardClass = trim((string) ($card_class ?? 'ag-match-card'));
$showReschedule = !empty($show_reschedule) && $canManage;

$home = arenagamer_bracket_participant_row($match, 'home', $standingMap, ['context' => $context ?? 'group']);
$away = arenagamer_bracket_participant_row($match, 'away', $standingMap, ['context' => $context ?? 'group']);
$homeId = $match['homeParticipantId'] ?? null;
$awayId = $match['awayParticipantId'] ?? null;
$matchStatus = $match['status'] ?? '';
$bothDefined = !empty($homeId) && !empty($awayId);
$isFinished = in_array($matchStatus, ['COMPLETED', 'WALKOVER', 'CANCELLED'], true);
$scheduled = !empty($match['scheduledAt'])
    ? arenagamer_format_date($match['scheduledAt'], 'd/m H:i')
    : '';

$metaParts = [];
if (!empty($meta_label)) {
    $metaParts[] = (string) $meta_label;
}
if ($scheduled !== '') {
    $metaParts[] = $scheduled;
}
$metaText = implode(' · ', $metaParts);
?>
<div class="<?php echo htmlspecialchars($cardClass); ?><?php echo $isFinished ? ' ag-match-card--done' : ''; ?>">
    <div class="ag-match-card__actions">
        <span class="ag-match-card__meta">
            <?php if ($metaText !== ''): ?>
            <?php if ($scheduled !== '' && strpos($metaText, $scheduled) !== false): ?>
            <i class="fa fa-clock-o"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($metaText); ?>
            <?php elseif (!$isFinished): ?>
            <?php echo arenagamer_status_badge($matchStatus); ?>
            <?php else: ?>
            <i class="fa fa-check-circle"></i> Finalizada
            <?php endif; ?>
        </span>
        <?php if ($canManage && $bothDefined && !$isFinished): ?>
        <button type="button" class="ag-match-card__result-btn" data-toggle="modal" data-target="#result-modal-<?php echo (int) $match['id']; ?>" title="Registrar resultado" aria-label="Registrar resultado">
            <i class="fa fa-trophy"></i>
        </button>
        <?php endif; ?>
        <?php if ($showReschedule): ?>
        <button type="button" class="ag-match-card__reschedule-btn" data-toggle="modal" data-target="#reschedule-modal-<?php echo (int) $match['id']; ?>" title="Reagendar" aria-label="Reagendar">
            <i class="fa fa-calendar"></i>
        </button>
        <?php endif; ?>
    </div>
    <div class="ag-match-card__team<?php echo $home['tierClass']; ?><?php echo $home['winner'] ? ' ag-match-card__team--winner' : ''; ?><?php echo $home['empty'] ? ' ag-match-card__team--empty' : ''; ?>">
        <?php if ($home['winner']): ?><i class="fa fa-trophy ag-match-card__trophy"></i><?php endif; ?>
        <span class="ag-match-card__team-name"><?php echo htmlspecialchars($home['label']); ?></span>
        <?php if ($home['score'] !== null): ?>
        <span class="ag-match-card__team-score"><?php echo (int) $home['score']; ?></span>
        <?php endif; ?>
        <?php echo arenagamer_render_participant_rank_badge($home); ?>
    </div>
    <div class="ag-match-card__vs">vs</div>
    <div class="ag-match-card__team<?php echo $away['tierClass']; ?><?php echo $away['winner'] ? ' ag-match-card__team--winner' : ''; ?><?php echo $away['empty'] ? ' ag-match-card__team--empty' : ''; ?>">
        <?php if ($away['winner']): ?><i class="fa fa-trophy ag-match-card__trophy"></i><?php endif; ?>
        <span class="ag-match-card__team-name"><?php echo htmlspecialchars($away['label']); ?></span>
        <?php if ($away['score'] !== null): ?>
        <span class="ag-match-card__team-score"><?php echo (int) $away['score']; ?></span>
        <?php endif; ?>
        <?php echo arenagamer_render_participant_rank_badge($away); ?>
    </div>
</div>
