<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$items = arenagamer_paginated_content($response ?? null);
$pagination = arenagamer_pagination_meta($response ?? null);
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-address-book"></i> ArenaGamer - Contatos
                </h4>
                <hr />
            </div>
        </div>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>Erro na API:</strong> <?php echo htmlspecialchars($api_error); ?>
        </div>
        <?php endif; ?>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (!empty($items)): ?>
                <div class="table-responsive">
                    <table class="table table-striped dt-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Role</th>
                                <th>Email Verificado</th>
                                <th>ID Cliente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $contact): ?>
                            <tr>
                                <td><?php echo $contact['id']; ?></td>
                                <td><?php echo arenagamer_user_type_badge($contact['userType'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(trim(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? ''))); ?></td>
                                <td><?php echo htmlspecialchars($contact['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($contact['phoneNumber'] ?? '—'); ?></td>
                                <td><?php echo arenagamer_role_badge($contact['role'] ?? ''); ?></td>
                                <td>
                                    <?php echo !empty($contact['emailVerified']) ?
                                        '<span class="text-success"><i class="fa fa-check"></i></span>' :
                                        '<span class="text-danger"><i class="fa fa-times"></i></span>'; ?>
                                </td>
                                <td><?php echo $contact['clientUserId'] ?? '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($pagination['totalPages'] > 1): ?>
                <div class="text-center">
                    <ul class="pagination">
                        <?php for ($i = 0; $i < $pagination['totalPages']; $i++): ?>
                        <li class="<?php echo ($pagination['number'] == $i) ? 'active' : ''; ?>">
                            <a href="<?php echo admin_url('arenagamer/contacts?page=' . $i); ?>"><?php echo $i + 1; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <p class="text-muted text-center">Nenhum contato encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
