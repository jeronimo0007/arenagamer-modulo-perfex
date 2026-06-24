<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $t = $tournament ?? []; ?>
<div class="panel_s mtop10 mbot15">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-info-circle"></i> Informações básicas</h4>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label>Nome do torneio *</label>
            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($t['name'] ?? ''); ?>">
        </div>

        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_game_search', [
            'tournament'  => $t,
            'presets'     => $presets ?? null,
            'search_url'  => $search_url ?? arenagamer_client_url('search_presets'),
            'label_class' => $label_class ?? '',
            'help_class'  => $help_class ?? 'text-muted',
        ]); ?>

        <div class="form-group mbot0">
            <label>Descrição</label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($t['description'] ?? ''); ?></textarea>
        </div>
    </div>
</div>
