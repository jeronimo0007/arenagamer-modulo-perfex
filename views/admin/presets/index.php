<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-gamepad"></i> ArenaGamer - Presets / Jogos
                </h4>
                <hr />
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (isset($presets['data']) && !empty($presets['data'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped dt-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Plataforma</th>
                                <th>Tamanho Time</th>
                                <th>Tamanho Mín.</th>
                                <th>Máx. Posições</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presets['data'] as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                                <td><?php echo isset($p['platform']) ? $p['platform'] : '—'; ?></td>
                                <td><?php echo isset($p['teamSize']) ? $p['teamSize'] : '—'; ?></td>
                                <td><?php echo isset($p['minTeamSize']) ? $p['minTeamSize'] : '—'; ?></td>
                                <td><?php echo isset($p['maxPositions']) ? $p['maxPositions'] : '—'; ?></td>
                                <td>
                                    <?php echo (isset($p['active']) && $p['active']) ?
                                        '<span class="label label-success">Ativo</span>' :
                                        '<span class="label label-danger">Inativo</span>'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Nenhum preset encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_foot(); ?>
</body>
</html>
