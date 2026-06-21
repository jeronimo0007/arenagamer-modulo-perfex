<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$isEdit = !empty($is_edit) && !empty($t['slug']);
$formAction = $isEdit
    ? admin_url('arenagamer/tournament_edit/' . $t['slug'])
    : admin_url('arenagamer/tournament');
$presetsData = arenagamer_api_data($presets ?? null, []);
$authUser = $auth_user ?? null;
$userType = is_array($authUser) ? ($authUser['userType'] ?? 'STAFF') : 'STAFF';
$isStaff = $userType === 'STAFF';
$isContact = $userType === 'CONTACT';
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <i class="fa fa-trophy"></i> <?php echo htmlspecialchars($title); ?>
                </h4>
                <a href="<?php echo admin_url('arenagamer/tournaments'); ?>" class="btn btn-default btn-xs">
                    <i class="fa fa-arrow-left"></i> Voltar
                </a>
                <hr />

                <?php if (!empty($api_error)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Erro na API:</strong> <?php echo htmlspecialchars($api_error); ?>
                </div>
                <?php endif; ?>

                <?php echo form_open($formAction, ['enctype' => 'multipart/form-data']); ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="name" class="control-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required
                                   value="<?php echo htmlspecialchars($t['name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="game_name" class="control-label">Nome do jogo</label>
                            <input type="text" name="game_name" id="game_name" class="form-control" maxlength="100"
                                   placeholder="Ex.: Counter-Strike 2"
                                   value="<?php echo htmlspecialchars($t['gameName'] ?? ''); ?>">
                            <p class="help-block">Nome do jogo que será disputado no torneio.</p>
                        </div>

                        <div class="form-group">
                            <label for="description" class="control-label">Descrição</label>
                            <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($t['description'] ?? ''); ?></textarea>
                        </div>

                        <?php if ($isStaff): ?>
                        <div class="form-group">
                            <label for="client_user_id" class="control-label">Cliente</label>
                            <select name="client_user_id" id="client_user_id" class="form-control selectpicker" data-none-selected-text="Torneio da plataforma" data-live-search="true">
                                <option value="">Torneio da plataforma</option>
                                <?php foreach (($clients ?? []) as $client): ?>
                                <?php $clientId = is_object($client) ? $client->userid : ($client['userid'] ?? 0); ?>
                                <?php $company = is_object($client) ? $client->company : ($client['company'] ?? ''); ?>
                                <option value="<?php echo (int) $clientId; ?>" <?php echo (int) ($t['clientUserId'] ?? 0) === (int) $clientId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($company ?: ('Cliente #' . $clientId)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-block">Opcional. Deixe vazio para criar um torneio da plataforma.</p>
                        </div>
                        <?php elseif ($isContact): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-building"></i>
                            <strong>Vinculado à sua empresa</strong>
                            <?php if (!empty($authUser['clientUserId'])): ?>
                            — Cliente #<?php echo (int) $authUser['clientUserId']; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type" class="control-label">Tipo</label>
                                    <select name="type" id="type" class="form-control selectpicker">
                                        <?php foreach (arenagamer_tournament_type_options() as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo ($t['type'] ?? 'SINGLE_ELIMINATION') === $type ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(arenagamer_tournament_type_label($type)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="format" class="control-label">Formato</label>
                                    <select name="format" id="format" class="form-control selectpicker">
                                        <?php foreach (arenagamer_tournament_format_options() as $format): ?>
                                        <option value="<?php echo $format; ?>" <?php echo ($t['format'] ?? 'SOLO') === $format ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(arenagamer_tournament_format_label($format)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="visibility" class="control-label">Visibilidade</label>
                                    <select name="visibility" id="visibility" class="form-control selectpicker">
                                        <?php foreach (arenagamer_tournament_visibility_options() as $visibility): ?>
                                        <option value="<?php echo $visibility; ?>" <?php echo ($t['visibility'] ?? 'PUBLIC') === $visibility ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(arenagamer_tournament_visibility_label($visibility)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="participants_limit" class="control-label">Limite de participantes</label>
                                    <input type="number" min="2" name="participants_limit" id="participants_limit" class="form-control"
                                           value="<?php echo (int) ($t['participantsLimit'] ?? 20); ?>"
                                           <?php echo $isEdit ? 'readonly' : ''; ?>>
                                    <?php if ($isEdit): ?>
                                    <p class="help-block">Não pode ser alterado após a criação do torneio.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="min_participants" class="control-label">Mín. participantes</label>
                                    <input type="number" min="2" name="min_participants" id="min_participants" class="form-control"
                                           value="<?php echo (int) ($t['minParticipants'] ?? 2); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="preset_id" class="control-label">Preset / Jogo</label>
                                    <select name="preset_id" id="preset_id" class="form-control selectpicker" data-none-selected-text="Nenhum">
                                        <option value="">Nenhum</option>
                                        <?php foreach ($presetsData as $p): ?>
                                        <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) ($t['presetId'] ?? 0) === (int) $p['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['gameName'] ?? ('Preset #' . $p['id'])); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="help-block">Ao selecionar ou trocar o preset, os campos do jogo são preenchidos automaticamente.</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="entry_fee_credits" class="control-label">Taxa de entrada (créditos)</label>
                                    <input type="number" step="0.01" min="0" name="entry_fee_credits" id="entry_fee_credits" class="form-control"
                                           value="<?php echo htmlspecialchars($t['entryFeeCredits'] ?? '0'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fee_percentage" class="control-label">Taxa (%)</label>
                                    <input type="number" step="0.01" min="0" name="fee_percentage" id="fee_percentage" class="form-control"
                                           value="<?php echo htmlspecialchars($t['feePercentage'] ?? '0'); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="prize_type" class="control-label">Tipo de prêmio</label>
                                    <select name="prize_type" id="prize_type" class="form-control selectpicker">
                                        <?php foreach (arenagamer_tournament_prize_type_options() as $prizeType): ?>
                                        <option value="<?php echo $prizeType; ?>" <?php echo ($t['prizeType'] ?? 'AUTOMATIC') === $prizeType ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(arenagamer_tournament_prize_type_label($prizeType)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="groups_count" class="control-label">Grupos</label>
                                    <input type="number" min="0" name="groups_count" id="groups_count" class="form-control"
                                           value="<?php echo htmlspecialchars($t['groupsCount'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="teams_per_group" class="control-label">Times por grupo</label>
                                    <input type="number" min="0" name="teams_per_group" id="teams_per_group" class="form-control"
                                           value="<?php echo htmlspecialchars($t['teamsPerGroup'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="advance_per_group" class="control-label">Avançam por grupo</label>
                                    <input type="number" min="0" name="advance_per_group" id="advance_per_group" class="form-control"
                                           value="<?php echo htmlspecialchars($t['advancePerGroup'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="best_of" class="control-label">Melhor de (MD)</label>
                                    <input type="number" min="1" name="best_of" id="best_of" class="form-control"
                                           value="<?php echo htmlspecialchars($t['bestOf'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_date_fields', [
                            'tournament' => $t,
                            'is_create'  => !$isEdit,
                        ]); ?>

                        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_media_fields', [
                            'tournament' => $t,
                            'presets'    => $presets ?? null,
                        ]); ?>

                        <div class="form-group">
                            <label for="rules" class="control-label">Regras</label>
                            <textarea name="rules" id="rules" class="form-control" rows="3"><?php echo htmlspecialchars($t['rules'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="tiebreaker_rules" class="control-label">Regras de desempate</label>
                            <textarea name="tiebreaker_rules" id="tiebreaker_rules" class="form-control" rows="2"><?php echo htmlspecialchars($t['tiebreakerRules'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check"></i> <?php echo $isEdit ? 'Salvar alterações' : 'Criar Torneio'; ?>
                        </button>
                    </div>
                </div>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_preset_autofill', [
    'presets' => $presets ?? null,
]); ?>
<?php init_tail(); ?>
