<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$content = arenagamer_paginated_content($response ?? null);
$pagination = arenagamer_pagination_meta($response ?? null);
$totalPages = (int) ($pagination['totalPages'] ?? 0);
$currentPage = (int) ($pagination['number'] ?? 0);
$activeFilter = strtoupper(trim((string) ($filter ?? '')));
?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="tw-font-semibold mbot15"><i class="fa fa-globe"></i> Torneios públicos</h4>
        <a href="<?php echo arenagamer_client_url(); ?>" class="btn btn-default btn-xs mbot15">&larr; Voltar</a>

        <?php if (!empty($api_error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($api_error); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs mbot15">
            <?php foreach (($filters ?? arenagamer_public_tournament_filters()) as $key => $label): ?>
            <?php
            $params = [];
            if ($key !== '') {
                $params['filter'] = $key;
            }
            $url = arenagamer_client_url('public_tournaments') . ($params ? '?' . http_build_query($params) : '');
            $isActive = ($key === '' && $activeFilter === '') || ($key !== '' && $key === $activeFilter);
            ?>
            <li class="<?php echo $isActive ? 'active' : ''; ?>">
                <a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($label); ?></a>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if (empty($content)): ?>
        <p class="text-muted">Nenhum torneio encontrado para este filtro.</p>
        <?php else: ?>
        <div class="row">
            <?php foreach ($content as $tournament): ?>
            <?php
            $cover = (string) ($tournament['coverImageUrl'] ?? '');
            $gameImage = arenagamer_tournament_game_image_url($tournament);
            $logoImage = arenagamer_tournament_logo_image_url($tournament);
            $avatarImage = $logoImage !== '' ? $logoImage : $gameImage;
            ?>
            <div class="col-md-6 col-lg-4 mbot20">
                <div class="panel_s" style="overflow:hidden;">
                    <?php if ($cover !== ''): ?>
                    <div style="height:140px;background:url('<?php echo htmlspecialchars($cover); ?>') center/cover no-repeat;"></div>
                    <?php endif; ?>
                    <div class="panel-body">
                        <div class="media">
                            <?php if ($avatarImage !== ''): ?>
                            <div class="media-left">
                                <img src="<?php echo htmlspecialchars($avatarImage); ?>" alt="" class="img-circle" style="width:48px;height:48px;object-fit:cover;">
                                <?php if ($logoImage !== '' && $gameImage !== '' && $logoImage !== $gameImage): ?>
                                <img src="<?php echo htmlspecialchars($gameImage); ?>" alt="" class="img-circle mtop5" style="width:32px;height:32px;object-fit:cover;">
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="media-body">
                                <h5 class="media-heading bold mtop0">
                                    <?php echo htmlspecialchars($tournament['name'] ?? ''); ?>
                                    <?php echo arenagamer_status_badge($tournament['status'] ?? ''); ?>
                                </h5>
                                <p class="text-muted mbot5">
                                    <?php echo htmlspecialchars(arenagamer_tournament_game_name($tournament)); ?>
                                </p>
                            </div>
                        </div>
                        <p class="mbot5">
                            <small>
                                Inscritos: <?php echo (int) ($tournament['participantCount'] ?? 0); ?> / <?php echo (int) ($tournament['participantsLimit'] ?? 0); ?>
                                <br>Início: <?php echo arenagamer_format_date($tournament['startDate'] ?? '', 'd/m/Y H:i'); ?>
                                <?php if (!empty($tournament['registrationOpensAt'])): ?>
                                <br>Abertura inscrições: <?php echo arenagamer_format_date($tournament['registrationOpensAt'], 'd/m/Y H:i'); ?>
                                <?php endif; ?>
                            </small>
                        </p>
                        <div class="mbot10">
                            <?php if (!empty($tournament['youtubeUrl'])): ?>
                            <a href="<?php echo htmlspecialchars($tournament['youtubeUrl']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-xs">
                                <i class="fa fa-youtube-play"></i> YouTube
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($tournament['twitchUrl'])): ?>
                            <a href="<?php echo htmlspecialchars($tournament['twitchUrl']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-xs">
                                <i class="fa fa-twitch"></i> Twitch
                            </a>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo arenagamer_client_url('tournament_detail/' . ($tournament['slug'] ?? '')); ?>" class="btn btn-primary btn-sm btn-block">
                            Ver detalhes
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 0; $i < $totalPages; $i++): ?>
                <?php
                $pageParams = ['page' => $i];
                if ($activeFilter !== '') {
                    $pageParams['filter'] = $activeFilter;
                }
                $pageUrl = arenagamer_client_url('public_tournaments') . '?' . http_build_query($pageParams);
                ?>
                <li class="<?php echo $i === $currentPage ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($pageUrl); ?>"><?php echo $i + 1; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
