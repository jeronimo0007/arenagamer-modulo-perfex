<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit = !empty($preset) && !empty($preset['id']);
$formUrl = $isEdit ? admin_url('arenagamer/preset/' . (int) $preset['id']) : admin_url('arenagamer/preset');
$preset = is_array($preset) ? $preset : [];
$iconUrl = (string) ($preset['iconUrl'] ?? '');
$gameImageUrl = arenagamer_preset_game_image_url($preset);
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-gamepad"></i> <?php echo htmlspecialchars($title); ?>
                </h4>
                <a href="<?php echo admin_url('arenagamer/presets'); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-arrow-left"></i> Voltar
                </a>
                <hr />

                <?php if (!empty($api_error)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Erro na API:</strong> <?php echo htmlspecialchars($api_error); ?>
                </div>
                <?php endif; ?>

                <?php echo form_open($formUrl, ['enctype' => 'multipart/form-data']); ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="game_name" class="control-label">Nome do jogo <span class="text-danger">*</span></label>
                            <input type="text" name="game_name" id="game_name" class="form-control" required maxlength="100"
                                   value="<?php echo htmlspecialchars($preset['gameName'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="platform" class="control-label">Plataforma</label>
                            <input type="text" name="platform" id="platform" class="form-control" maxlength="100"
                                   placeholder="Ex.: PC, PlayStation, Xbox"
                                   value="<?php echo htmlspecialchars($preset['platform'] ?? ''); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="team_size" class="control-label">Tamanho do time</label>
                                    <input type="number" min="1" name="team_size" id="team_size" class="form-control"
                                           value="<?php echo (int) ($preset['teamSize'] ?? 1); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_players_per_team" class="control-label">Mín. jogadores/time</label>
                                    <input type="number" min="1" name="min_players_per_team" id="min_players_per_team" class="form-control"
                                           value="<?php echo (int) ($preset['minPlayersPerTeam'] ?? 1); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_players_per_team" class="control-label">Máx. jogadores/time</label>
                                    <input type="number" min="1" name="max_players_per_team" id="max_players_per_team" class="form-control"
                                           value="<?php echo (int) ($preset['maxPlayersPerTeam'] ?? 1); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ícone do preset</label>
                                    <?php if ($iconUrl !== ''): ?>
                                    <div class="mbot10">
                                        <img src="<?php echo htmlspecialchars($iconUrl); ?>" alt="Ícone" class="img-thumbnail" style="max-height:72px;">
                                    </div>
                                    <?php endif; ?>
                                    <input type="url" name="icon_url" class="form-control mbot5"
                                           placeholder="https://..."
                                           value="<?php echo htmlspecialchars($iconUrl); ?>">
                                    <input type="file" name="icon_file" class="form-control" accept="image/*">
                                    <small class="text-muted">Ícone pequeno usado em listagens.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Imagem do jogo</label>
                                    <?php if ($gameImageUrl !== ''): ?>
                                    <div class="mbot10">
                                        <img src="<?php echo htmlspecialchars($gameImageUrl); ?>" alt="Imagem do jogo" class="img-thumbnail" style="max-height:72px;">
                                    </div>
                                    <?php endif; ?>
                                    <input type="url" name="game_image_url" class="form-control mbot5"
                                           placeholder="https://..."
                                           value="<?php echo htmlspecialchars($preset['gameImageUrl'] ?? ''); ?>">
                                    <input type="file" name="game_image_file" class="form-control" accept="image/*">
                                    <small class="text-muted">Usada ao preencher torneios a partir deste preset.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="rules_template" class="control-label">Modelo de regras</label>
                            <textarea name="rules_template" id="rules_template" class="form-control" rows="6"
                                      placeholder="Texto padrão de regras para torneios deste jogo"><?php echo htmlspecialchars($preset['rulesTemplate'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="scoring_script" class="control-label">Script de pontuação</label>
                            <textarea name="scoring_script" id="scoring_script" class="form-control" rows="4"
                                      placeholder="Opcional — lógica de pontuação"><?php echo htmlspecialchars($preset['scoringScript'] ?? ''); ?></textarea>
                        </div>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="active" value="1"
                                <?php echo (!$isEdit || !empty($preset['active'])) ? 'checked' : ''; ?>>
                            <label for="active">Preset ativo</label>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <a href="<?php echo admin_url('arenagamer/presets'); ?>" class="btn btn-default">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?php echo $isEdit ? 'Salvar alterações' : 'Criar preset'; ?>
                        </button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
