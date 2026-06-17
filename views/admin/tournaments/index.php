<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-trophy"></i> ArenaGamer - Torneios
                </h4>
                <hr />
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (isset($response['data']['content']) && !empty($response['data']['content'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped dt-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Slug</th>
                                <th>Tipo</th>
                                <th>Formato</th>
                                <th>Status</th>
                                <th>Vagas</th>
                                <th>Taxa Entrada</th>
                                <th>Início</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($response['data']['content'] as $t): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($t['slug']); ?></code></td>
                                <td><span class="label label-default"><?php echo $t['type']; ?></span></td>
                                <td><?php echo isset($t['format']) ? $t['format'] : '—'; ?></td>
                                <td><?php echo arenagamer_status_badge($t['status']); ?></td>
                                <td><?php echo isset($t['participantsLimit']) ? $t['participantsLimit'] : '—'; ?></td>
                                <td><?php echo isset($t['entryFeeCredits']) ? number_format($t['entryFeeCredits'], 2) : '0.00'; ?></td>
                                <td><?php echo isset($t['startDate']) ? date('d/m/Y H:i', strtotime($t['startDate'])) : '—'; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('arenagamer/tournament_detail/' . $t['slug']); ?>"
                                       class="btn btn-default btn-xs" data-toggle="tooltip" title="Ver Detalhes">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (isset($response['data']['totalPages']) && $response['data']['totalPages'] > 1): ?>
                <div class="text-center">
                    <ul class="pagination">
                        <?php for ($i = 0; $i < $response['data']['totalPages']; $i++): ?>
                        <li class="<?php echo ($response['data']['number'] == $i) ? 'active' : ''; ?>">
                            <a href="<?php echo admin_url('arenagamer/tournaments?page=' . $i); ?>"><?php echo $i + 1; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="text-center mtop20">
                    <i class="fa fa-trophy fa-3x text-muted"></i>
                    <p class="text-muted mtop10">Nenhum torneio encontrado</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_foot(); ?>
</body>
</html>
