<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-history"></i> ArenaGamer - Auditoria
                </h4>
                <hr />
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (isset($response['data']['content']) && !empty($response['data']['content'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ação</th>
                                <th>Entidade</th>
                                <th>ID Entidade</th>
                                <th>Ator</th>
                                <th>Detalhes</th>
                                <th>IP</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($response['data']['content'] as $audit): ?>
                            <tr>
                                <td><?php echo $audit['id']; ?></td>
                                <td><span class="label label-default"><?php echo htmlspecialchars($audit['action'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars($audit['entityType'] ?? ''); ?></td>
                                <td><?php echo $audit['entityId'] ?? '—'; ?></td>
                                <td>
                                    <?php
                                    $actor = ($audit['actorType'] ?? '') . ' #' . ($audit['actorId'] ?? '—');
                                    echo htmlspecialchars($actor);
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars(arenagamer_audit_display_message($audit)); ?></td>
                                <td><code><?php echo htmlspecialchars($audit['ipAddress'] ?? ''); ?></code></td>
                                <td><?php echo arenagamer_format_date($audit['createdAt'] ?? '', 'd/m/Y H:i:s'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $auditPagination = arenagamer_pagination_meta($response ?? null);
                if ($auditPagination['totalPages'] > 1):
                ?>
                <div class="text-center">
                    <ul class="pagination">
                        <?php for ($i = 0; $i < $auditPagination['totalPages']; $i++): ?>
                        <li class="<?php echo ($auditPagination['number'] == $i) ? 'active' : ''; ?>">
                            <a href="<?php echo admin_url('arenagamer/audits?page=' . $i); ?>"><?php echo $i + 1; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <p class="text-muted text-center">Nenhum registro de auditoria encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
