<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2">
                    <?php if (staff_can('create', 'arenagamer')): ?>
                    <a href="<?php echo admin_url('arenagamer/preset'); ?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Preset
                    </a>
                    <?php endif; ?>
                </div>
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-gamepad"></i> ArenaGamer - Presets / Jogos
                </h4>
                <hr />
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php $presetsData = arenagamer_api_data($presets ?? null, []); ?>
                <?php if (!empty($presetsData)): ?>
                <div class="table-responsive">
                    <table class="table table-striped dt-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagem</th>
                                <th>Jogo</th>
                                <th>Plataforma</th>
                                <th>Tamanho Time</th>
                                <th>Mín. Jogadores</th>
                                <th>Máx. Jogadores</th>
                                <th>Status</th>
                                <?php if (staff_can('edit', 'arenagamer')): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presetsData as $p): ?>
                            <?php $thumb = arenagamer_preset_game_image_url($p); ?>
                            <tr>
                                <td><?php echo (int) $p['id']; ?></td>
                                <td>
                                    <?php if ($thumb !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" class="img-thumbnail" style="max-width:40px;max-height:40px;object-fit:cover;">
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($p['gameName'] ?? '—'); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['platform'] ?? '—'); ?></td>
                                <td><?php echo $p['teamSize'] ?? '—'; ?></td>
                                <td><?php echo $p['minPlayersPerTeam'] ?? '—'; ?></td>
                                <td><?php echo $p['maxPlayersPerTeam'] ?? '—'; ?></td>
                                <td>
                                    <?php echo !empty($p['active']) ?
                                        '<span class="label label-success">Ativo</span>' :
                                        '<span class="label label-danger">Inativo</span>'; ?>
                                </td>
                                <?php if (staff_can('edit', 'arenagamer')): ?>
                                <td>
                                    <a href="<?php echo admin_url('arenagamer/preset/' . (int) $p['id']); ?>" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i> Editar
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Nenhum preset encontrado.</p>
                <?php if (staff_can('create', 'arenagamer')): ?>
                <p class="text-center">
                    <a href="<?php echo admin_url('arenagamer/preset'); ?>" class="btn btn-primary mtop10">
                        <i class="fa fa-plus"></i> Criar primeiro preset
                    </a>
                </p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
