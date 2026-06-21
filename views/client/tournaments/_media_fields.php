<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$t = $tournament ?? [];
$presetsList = $presets ?? null;
$gameImageLocked = arenagamer_tournament_game_image_locked_by_preset($t, $presetsList);
$lockedPreset = $gameImageLocked
    ? arenagamer_find_preset_by_id($presetsList, (int) ($t['presetId'] ?? 0))
    : null;
$gameImageUrl = is_array($lockedPreset)
    ? trim((string) ($lockedPreset['gameImageUrl'] ?? ''))
    : arenagamer_tournament_game_image_url($t);
$coverImageUrl = (string) ($t['coverImageUrl'] ?? '');
$logoImageUrl = arenagamer_tournament_logo_image_url($t);
?>

<div class="row">
    <div class="col-md-4">
        <div class="form-group" id="game_image_fields">
            <label>Imagem do jogo do torneio</label>
            <div class="mbot10" id="game_image_preview"<?php echo $gameImageUrl === '' ? ' style="display:none;"' : ''; ?>>
                <?php if ($gameImageUrl !== ''): ?>
                <img src="<?php echo htmlspecialchars($gameImageUrl); ?>" alt="Imagem do jogo" class="img-thumbnail" style="max-height:80px;">
                <?php endif; ?>
            </div>
            <input type="url" name="game_image_url" id="game_image_url" class="form-control mbot5"
                   placeholder="https://..."
                   value="<?php echo htmlspecialchars($gameImageLocked ? $gameImageUrl : ($t['gameImageUrl'] ?? '')); ?>"
                   <?php echo $gameImageLocked ? 'readonly' : ''; ?>>
            <input type="file" name="game_image_file" id="game_image_file" class="form-control" accept="image/*"
                   <?php echo $gameImageLocked ? 'disabled' : ''; ?>>
            <small class="text-muted" id="game_image_help">
                <?php echo $gameImageLocked
                    ? 'Definida pelo preset selecionado (não editável).'
                    : 'Se vazio, usa a imagem do preset selecionado.'; ?>
            </small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Imagem do torneio</label>
            <?php if ($logoImageUrl !== ''): ?>
            <div class="mbot10">
                <img src="<?php echo htmlspecialchars($logoImageUrl); ?>" alt="Imagem do torneio" class="img-thumbnail" style="max-height:80px;">
            </div>
            <?php endif; ?>
            <input type="url" name="logo_image_url" class="form-control mbot5"
                   placeholder="https://..."
                   value="<?php echo htmlspecialchars($t['logoImageUrl'] ?? ''); ?>">
            <input type="file" name="logo_image_file" class="form-control" accept="image/*">
            <small class="text-muted">Logo ou marca visual do torneio (além do banner e da imagem do jogo).</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Imagem de capa do torneio</label>
            <?php if ($coverImageUrl !== ''): ?>
            <div class="mbot10">
                <img src="<?php echo htmlspecialchars($coverImageUrl); ?>" alt="Capa" class="img-thumbnail" style="max-height:80px;">
            </div>
            <?php endif; ?>
            <input type="url" name="cover_image_url" class="form-control mbot5"
                   placeholder="https://..."
                   value="<?php echo htmlspecialchars($coverImageUrl); ?>">
            <input type="file" name="cover_image_file" class="form-control" accept="image/*">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Link do YouTube</label>
            <input type="url" name="youtube_url" class="form-control"
                   placeholder="https://youtube.com/..."
                   value="<?php echo htmlspecialchars($t['youtubeUrl'] ?? ''); ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Link da Twitch</label>
            <input type="url" name="twitch_url" class="form-control"
                   placeholder="https://twitch.tv/..."
                   value="<?php echo htmlspecialchars($t['twitchUrl'] ?? ''); ?>">
        </div>
    </div>
</div>
