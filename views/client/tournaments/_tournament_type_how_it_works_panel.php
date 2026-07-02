<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = is_array($tournament ?? null) ? $tournament : [];
$howItWorks = arenagamer_tournament_how_it_works_for_detail($t);

if ($howItWorks === null || empty($howItWorks['description'])) {
    return;
}

$wrapperClass = trim((string) ($wrapper_class ?? 'ag-tournament-how-it-works mtop15 mbot15'));
$collapseId = (string) ($collapse_id ?? 'arenagamer-how-it-works');
$showToggle = !isset($show_toggle) || $show_toggle;
?>
<div class="<?php echo htmlspecialchars($wrapperClass); ?>">
    <div class="panel_s mbot0">
        <div class="panel-heading">
            <h4 class="panel-title mtop0 mbot0">
                <i class="fa <?php echo htmlspecialchars($howItWorks['icon']); ?>"></i> Como funciona
                <?php if ($showToggle): ?>
                <a href="#<?php echo htmlspecialchars($collapseId); ?>" data-toggle="collapse" class="pull-right" style="font-size:12px;">
                    <i class="fa fa-chevron-up"></i>
                </a>
                <?php endif; ?>
            </h4>
        </div>
        <div id="<?php echo htmlspecialchars($collapseId); ?>" class="panel-body collapse in">
            <p class="text-muted mtop0 mbot10">
                <strong><?php echo htmlspecialchars($howItWorks['title']); ?></strong>
                — <?php echo htmlspecialchars($howItWorks['description']); ?>
            </p>
            <?php if (!empty($howItWorks['extra'])): ?>
            <p class="mbot10">
                <span class="label label-primary"><?php echo htmlspecialchars($howItWorks['extra']); ?></span>
            </p>
            <?php endif; ?>
            <?php if (!empty($howItWorks['tip'])): ?>
            <p class="text-muted mbot0">
                <small><i class="fa fa-lightbulb-o"></i> <?php echo htmlspecialchars($howItWorks['tip']); ?></small>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>
