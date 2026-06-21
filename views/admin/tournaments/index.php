<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$currentView = $view ?? 'all';
$viewLabels = [
    'all'         => 'Todos',
    'my-managed'  => 'Gerenciar',
    'my-created'  => 'Criados por mim',
    'my-joined'   => 'Participando',
];
$clientNames = $client_names ?? [];
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-items-center tw-justify-between">
                    <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-0">
                        <i class="fa fa-trophy"></i> ArenaGamer - Torneios
                    </h4>
                    <?php if (staff_can('create', 'arenagamer')): ?>
                    <a href="<?php echo admin_url('arenagamer/tournament'); ?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Novo Torneio
                    </a>
                    <?php endif; ?>
                </div>
                <hr />
            </div>
        </div>

        <ul class="nav nav-tabs mbot15">
            <?php foreach ($viewLabels as $key => $label): ?>
            <li class="<?php echo $currentView === $key ? 'active' : ''; ?>">
                <a href="<?php echo admin_url('arenagamer/tournaments?view=' . $key); ?>">
                    <?php echo $label; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (!empty($api_error)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Erro na API:</strong> <?php echo htmlspecialchars($api_error); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($response['data']['content']) && !empty($response['data']['content'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped dt-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Slug</th>
                                <th>Cliente</th>
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
                                <td><?php
                                    $cid = $t['clientUserId'] ?? null;
                                    echo arenagamer_tournament_client_badge($t, $cid ? ($clientNames[$cid] ?? null) : null);
                                ?></td>
                                <td><span class="label label-default"><?php echo htmlspecialchars(arenagamer_tournament_type_label($t['type'] ?? '')); ?></span></td>
                                <td><?php echo isset($t['format']) ? htmlspecialchars(arenagamer_tournament_format_label($t['format'])) : '—'; ?></td>
                                <td><?php echo arenagamer_status_badge($t['status']); ?></td>
                                <td><?php
                                    $count = isset($t['participantCount']) ? $t['participantCount'] : 0;
                                    $limit = isset($t['participantsLimit']) ? $t['participantsLimit'] : '—';
                                    echo $count . ' / ' . $limit;
                                ?></td>
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

                <?php
                $pagination = arenagamer_pagination_meta($response);
                if ($pagination['totalPages'] > 1):
                ?>
                <div class="text-center">
                    <ul class="pagination">
                        <?php for ($i = 0; $i < $pagination['totalPages']; $i++): ?>
                        <li class="<?php echo ($pagination['number'] == $i) ? 'active' : ''; ?>">
                            <a href="<?php echo admin_url('arenagamer/tournaments?view=' . $currentView . '&page=' . $i); ?>"><?php echo $i + 1; ?></a>
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
<?php init_tail(); ?>
