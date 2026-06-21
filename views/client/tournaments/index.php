<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$currentView = $view ?? 'my-managed';
$viewLabels = [
    'my-managed' => 'Gerenciar',
    'my-created' => 'Criados por mim',
    'my-joined'  => 'Participando',
];
$tournaments = arenagamer_paginated_content($response ?? null);
$pagination = arenagamer_pagination_meta($response ?? null);
$totalPages = (int) ($pagination['totalPages'] ?? 0);
$currentPage = (int) ($pagination['number'] ?? 0);
?>
<div class="panel_s">
    <div class="panel-body">
        <div class="tw-flex tw-items-center tw-justify-between mbot15">
            <h4 class="tw-font-semibold tw-mb-0"><i class="fa fa-trophy"></i> Torneios</h4>
            <a href="<?php echo arenagamer_client_url('tournament'); ?>" class="btn btn-primary btn-sm">
                <i class="fa fa-plus"></i> Novo
            </a>
        </div>

        <ul class="nav nav-tabs mbot15">
            <?php foreach ($viewLabels as $key => $label): ?>
            <li class="<?php echo $currentView === $key ? 'active' : ''; ?>">
                <a href="<?php echo arenagamer_client_url('tournaments?view=' . $key); ?>"><?php echo $label; ?></a>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($api_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($tournaments)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Participantes</th>
                        <th>Criado em</th>
                        <th>Início</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $t): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($t['name']); ?></strong><br>
                            <?php echo arenagamer_tournament_client_badge($t, $client_name ?? null); ?>
                        </td>
                        <td><?php echo arenagamer_status_badge($t['status']); ?></td>
                        <td><?php echo (int) ($t['participantCount'] ?? 0); ?> / <?php echo (int) ($t['participantsLimit'] ?? 0); ?></td>
                        <td><?php echo !empty($t['createdAt']) ? arenagamer_format_date($t['createdAt']) : '—'; ?></td>
                        <td><?php echo !empty($t['startDate']) ? arenagamer_format_date($t['startDate']) : '—'; ?></td>
                        <td>
                            <a href="<?php echo arenagamer_client_url('tournament_detail/' . $t['slug']); ?>" class="btn btn-default btn-xs">Ver</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="row">
            <div class="col-md-6">
                <p class="text-muted mtop10 mbot0"><?php echo arenagamer_pagination_info_text($pagination); ?></p>
            </div>
            <div class="col-md-6 text-right">
                <nav>
                    <ul class="pagination tw-mb-0">
                        <?php for ($i = 0; $i < $totalPages; $i++): ?>
                        <?php
                        $pageUrl = arenagamer_client_url('tournaments') . '?' . http_build_query([
                            'view' => $currentView,
                            'page' => $i,
                        ]);
                        ?>
                        <li class="<?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a href="<?php echo htmlspecialchars($pageUrl); ?>"><?php echo $i + 1; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <p class="text-muted text-center">Nenhum torneio encontrado.</p>
        <?php endif; ?>
    </div>
</div>
