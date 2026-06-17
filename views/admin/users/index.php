<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-users"></i> ArenaGamer - Usuários
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
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Ativo</th>
                                <th>Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($response['data']['content'] as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : '—'; ?></td>
                                <td>
                                    <?php
                                    $role = isset($user['role']) ? $user['role'] : '';
                                    $role_class = $role === 'ADMIN' ? 'danger' : ($role === 'MANAGER' ? 'warning' : 'info');
                                    ?>
                                    <span class="label label-<?php echo $role_class; ?>"><?php echo $role; ?></span>
                                </td>
                                <td>
                                    <?php echo (isset($user['active']) && $user['active']) ?
                                        '<span class="text-success"><i class="fa fa-check"></i></span>' :
                                        '<span class="text-danger"><i class="fa fa-times"></i></span>'; ?>
                                </td>
                                <td><?php echo isset($user['createdAt']) ? date('d/m/Y', strtotime($user['createdAt'])) : '—'; ?></td>
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
                            <a href="<?php echo admin_url('arenagamer/users?page=' . $i); ?>"><?php echo $i + 1; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <p class="text-muted text-center">Nenhum usuário encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php init_foot(); ?>
</body>
</html>
