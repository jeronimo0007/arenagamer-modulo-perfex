<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$labelClass = (string) ($label_class ?? 'control-label');
$helpClass = (string) ($help_class ?? 'help-block');
$showTiebreaker = !empty($show_tiebreaker);
?>
<div class="panel_s mtop10 mbot15">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-gavel"></i> Regras</h4>
    </div>
    <div class="panel-body">
        <div class="form-group<?php echo $showTiebreaker ? '' : ' mbot0'; ?>">
            <label for="rules" class="<?php echo htmlspecialchars($labelClass); ?>">Regras do torneio</label>
            <textarea name="rules" id="rules" class="form-control" rows="3"><?php echo htmlspecialchars($t['rules'] ?? ''); ?></textarea>
            <p class="<?php echo htmlspecialchars($helpClass); ?> text-muted mbot0">
                Preenchidas automaticamente ao escolher o jogo (se houver modelo de regras).
            </p>
        </div>
        <?php if ($showTiebreaker): ?>
        <div class="form-group mbot0">
            <label for="tiebreaker_rules" class="<?php echo htmlspecialchars($labelClass); ?>">Regras de desempate</label>
            <textarea name="tiebreaker_rules" id="tiebreaker_rules" class="form-control" rows="2"><?php echo htmlspecialchars($t['tiebreakerRules'] ?? ''); ?></textarea>
        </div>
        <?php endif; ?>
    </div>
</div>
