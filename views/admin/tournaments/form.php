<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$isEdit = !empty($is_edit) && !empty($t['slug']);
$formAction = $isEdit
    ? admin_url('arenagamer/tournament_edit/' . $t['slug'])
    : admin_url('arenagamer/tournament');
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

                <?php
                $this->load->view('../../modules/arenagamer/views/client/tournaments/_basic_info_fields', [
                    'tournament'  => $t,
                    'presets'     => $presets ?? null,
                    'search_url'  => admin_url('arenagamer/search_presets'),
                    'label_class' => 'control-label',
                    'help_class'  => 'help-block',
                ]);
                ?>

                        <?php if ($isStaff): ?>
                        <div class="panel_s mtop10 mbot15">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="fa fa-building"></i> Cliente</h4>
                            </div>
                            <div class="panel-body">
                        <div class="form-group mbot0">
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
                            <p class="help-block mbot0">Opcional. Deixe vazio para criar um torneio da plataforma.</p>
                        </div>
                            </div>
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

                <?php
                $this->load->view('../../modules/arenagamer/views/client/tournaments/_tournament_config_fields', [
                    'tournament'              => $t,
                    'is_edit'                 => $isEdit,
                    'tournament_systems'      => $tournament_systems ?? null,
                    'tournament_type_options' => $tournament_type_options ?? null,
                    'tournament_type_labels'  => $tournament_type_labels ?? null,
                    'default_participants_limit' => (int) ($t['participantsLimit'] ?? 20),
                    'participants_solo_help' => $isEdit ? 'Não pode ser alterado após a criação do torneio.' : '',
                    'participants_team_help' => $isEdit ? 'Não pode ser alterado após a criação do torneio.' : 'Número máximo de equipes inscritas.',
                    'participants_help_text' => $isEdit ? 'Não pode ser alterado após a criação do torneio.' : '',
                    'show_best_of'         => true,
                    'use_selectpicker'     => true,
                    'label_class'          => 'control-label',
                    'help_class'           => 'help-block',
                ]);

                $this->load->view('../../modules/arenagamer/views/client/tournaments/_prize_fee_fields', [
                    'tournament'               => $t,
                    'use_selectpicker'         => true,
                    'allows_entry_fee_by_plan' => true,
                    'label_class'              => 'control-label',
                    'help_class'               => 'help-block',
                ]);

                $this->load->view('../../modules/arenagamer/views/client/tournaments/_date_fields', [
                    'tournament' => $t,
                    'is_create'  => !$isEdit,
                ]);

                $this->load->view('../../modules/arenagamer/views/client/tournaments/_media_fields', [
                    'tournament' => $t,
                    'presets'    => $presets ?? null,
                ]);

                $this->load->view('../../modules/arenagamer/views/client/tournaments/_rules_fields', [
                    'tournament'      => $t,
                    'show_tiebreaker' => true,
                    'label_class'     => 'control-label',
                    'help_class'      => 'help-block',
                ]);
                ?>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check"></i> <?php echo $isEdit ? 'Salvar alterações' : 'Criar Torneio'; ?>
                        </button>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_preset_autofill', [
    'presets' => $presets ?? null,
]); ?>
<?php init_tail(); ?>
