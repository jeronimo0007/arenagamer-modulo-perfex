<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$teamsData = arenagamer_api_data($teams ?? null, []);
$teamSettings = is_array($team_settings ?? null) ? $team_settings : arenagamer_team_settings_local();
$editTeam = is_array($edit_team ?? null) ? $edit_team : null;
$isEdit = !empty($editTeam['id']);
$formAction = $isEdit
    ? arenagamer_client_url('team_edit/' . (int) $editTeam['id'])
    : arenagamer_client_url('teams');
$t = $editTeam ?? [];
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-users"></i> Meus Times</h4>
        <a href="<?php echo arenagamer_client_url(); ?>" class="btn btn-default btn-xs mbot15">&larr; Voltar</a>

        <div class="alert alert-info">
            <strong>Regras atuais:</strong>
            máximo <?php echo (int) ($teamSettings['maxOwnedTeamsPerContact'] ?? 1); ?> time(s) como dono,
            participação em até <?php echo (int) ($teamSettings['maxParticipatedTeamsPerContact'] ?? 3); ?> time(s).
            <?php if (!empty($teamSettings['unlimitedTournamentsPerTeam'])): ?>
            Cada time pode participar de quantos torneios desejar.
            <?php elseif (!empty($teamSettings['maxTournamentsPerTeam'])): ?>
            Limite de <?php echo (int) $teamSettings['maxTournamentsPerTeam']; ?> torneio(s) por time.
            <?php endif; ?>
        </div>

        <h5 class="bold"><?php echo $isEdit ? 'Editar time' : 'Criar time'; ?></h5>
        <?php echo form_open($formAction, ['enctype' => 'multipart/form-data']); ?>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($t['name'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Tag</label>
                    <input type="text" name="tag" class="form-control" value="<?php echo htmlspecialchars($t['tag'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Logo do time</label>
                    <?php if (!empty($t['logoUrl'])): ?>
                    <div class="mbot5"><img src="<?php echo htmlspecialchars($t['logoUrl']); ?>" alt="" class="img-thumbnail" style="max-height:64px;"></div>
                    <?php endif; ?>
                    <input type="url" name="logo_url" class="form-control mbot5" placeholder="https://..." value="<?php echo htmlspecialchars($t['logoUrl'] ?? ''); ?>">
                    <input type="file" name="logo_file" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>YouTube</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($t['youtubeUrl'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Instagram</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($t['instagramUrl'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Twitch</label>
                    <input type="url" name="twitch_url" class="form-control" value="<?php echo htmlspecialchars($t['twitchUrl'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Outras redes sociais</label>
                    <input type="url" name="other_social_url" class="form-control" value="<?php echo htmlspecialchars($t['otherSocialUrl'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Alteração de regra</label>
            <textarea name="rules_change" class="form-control" rows="3" placeholder="Descreva alterações de regras internas do time"><?php echo htmlspecialchars($t['rulesChange'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-check"></i> <?php echo $isEdit ? 'Salvar alterações' : 'Criar time'; ?>
        </button>
        <?php if ($isEdit): ?>
        <a href="<?php echo arenagamer_client_url('teams'); ?>" class="btn btn-default">Cancelar</a>
        <?php endif; ?>
        <?php echo form_close(); ?>

        <hr>

        <?php if (!empty($teamsData)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th></th><th>Nome</th><th>Tag</th><th>Membros</th><th>Redes</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($teamsData as $team): ?>
                    <tr>
                        <td>
                            <?php if (!empty($team['logoUrl'])): ?>
                            <img src="<?php echo htmlspecialchars($team['logoUrl']); ?>" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:4px;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($team['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($team['tag'] ?? '—'); ?></td>
                        <td><?php echo (int) ($team['memberCount'] ?? 0); ?></td>
                        <td>
                            <?php if (!empty($team['youtubeUrl'])): ?><i class="fa fa-youtube-play text-danger" title="YouTube"></i><?php endif; ?>
                            <?php if (!empty($team['instagramUrl'])): ?><i class="fa fa-instagram text-muted" title="Instagram"></i><?php endif; ?>
                            <?php if (!empty($team['twitchUrl'])): ?><i class="fa fa-twitch" title="Twitch"></i><?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo arenagamer_client_url('team_edit/' . (int) ($team['id'] ?? 0)); ?>" class="btn btn-default btn-xs">
                                <i class="fa fa-pencil"></i> Editar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">Nenhum time cadastrado.</p>
        <?php endif; ?>
    </div>
</div>
