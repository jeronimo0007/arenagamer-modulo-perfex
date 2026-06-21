<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $user = arenagamer_api_data($profile ?? null, []); ?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-user"></i> Meu Perfil</h4>
        <a href="<?php echo arenagamer_client_url(); ?>" class="btn btn-default btn-xs mbot15">&larr; Voltar</a>

        <?php if (!empty($user)): ?>
        <p class="text-muted mbot15">
            <?php echo arenagamer_user_type_badge($user['userType'] ?? ''); ?>
            <?php echo arenagamer_role_badge($user['role'] ?? ''); ?>
        </p>
        <?php endif; ?>

        <?php echo form_open(arenagamer_client_url('profile'), ['enctype' => 'multipart/form-data']); ?>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group text-center">
                    <label>Foto de perfil</label>
                    <?php if (!empty($user['avatarUrl'])): ?>
                    <div class="mbot10">
                        <img src="<?php echo htmlspecialchars($user['avatarUrl']); ?>" alt="" class="img-circle" style="width:96px;height:96px;object-fit:cover;">
                    </div>
                    <?php endif; ?>
                    <input type="url" name="avatar_url" class="form-control mbot5" placeholder="URL da foto" value="<?php echo htmlspecialchars($user['avatarUrl'] ?? ''); ?>">
                    <input type="file" name="avatar_file" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['firstName'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Sobrenome</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['lastName'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phoneNumber'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Instagram</label>
                            <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($user['instagramUrl'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>YouTube</label>
                            <input type="url" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($user['youtubeUrl'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Twitch</label>
                            <input type="url" name="twitch_url" class="form-control" value="<?php echo htmlspecialchars($user['twitchUrl'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Salvar</button>
        <?php echo form_close(); ?>
    </div>
</div>
