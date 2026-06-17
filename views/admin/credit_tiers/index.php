<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-money"></i> ArenaGamer - Tiers de Créditos
                </h4>
                <hr />
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (isset($tiers['data']) && !empty($tiers['data'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped dt-table">
                        <thead>
                            <tr>
                                <th>Mín. Participantes</th>
                                <th>Máx. Participantes</th>
                                <th>Custo (Créditos)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tiers['data'] as $tier): ?>
                            <tr>
                                <td><?php echo $tier['minParticipants']; ?></td>
                                <td><?php echo $tier['maxParticipants']; ?></td>
                                <td><strong><?php echo number_format($tier['creditCost'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Nenhum tier encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_foot(); ?>
</body>
</html>
