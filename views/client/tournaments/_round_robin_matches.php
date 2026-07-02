<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$allMatches = is_array($all_matches ?? null) ? $all_matches : [];
$standingMap = is_array($participant_standing_map ?? null) ? $participant_standing_map : [];
$canManage = !empty($can_manage);
$baseUrl = (string) ($matches_base_url ?? '');

$allRoundRobinMatches = arenagamer_collect_round_robin_matches($allMatches);

if (empty($allRoundRobinMatches)) {
    return;
}

$roundRobinPage = arenagamer_round_robin_matches_page_from_request();
$roundRobinList = arenagamer_paginate_array(
    $allRoundRobinMatches,
    $roundRobinPage,
    arenagamer_round_robin_matches_page_size()
);
$matches = $roundRobinList['items'];
$pagination = $roundRobinList['pagination'];
$totalPages = (int) ($pagination['totalPages'] ?? 1);
$currentPage = (int) ($pagination['number'] ?? 0);
?>
<section class="ag-matches-section ag-matches-section--round-robin">
    <h5 class="ag-tournament-section-title"><i class="fa fa-refresh"></i> Jogos — pontos corridos</h5>

    <?php if ($totalPages > 1 && $baseUrl !== ''): ?>
    <div class="row mbot10">
        <div class="col-md-6">
            <p class="text-muted mbot0"><?php echo arenagamer_pagination_info_text($pagination); ?></p>
        </div>
        <div class="col-md-6 text-right">
            <nav>
                <ul class="pagination tw-mb-0">
                    <?php for ($i = 0; $i < $totalPages; $i++): ?>
                    <?php $pageUrl = arenagamer_tournament_detail_url($baseUrl, ['rr_page' => $i]); ?>
                    <li class="<?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <a href="<?php echo htmlspecialchars($pageUrl); ?>"><?php echo $i + 1; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php elseif (!empty($pagination['totalElements'])): ?>
    <p class="text-muted mbot10"><?php echo arenagamer_pagination_info_text($pagination); ?></p>
    <?php endif; ?>

    <div class="ag-matches-grid">
        <?php foreach ($matches as $match): ?>
        <?php $this->load->view('../../modules/arenagamer/views/client/tournaments/_match_card', [
            'match'        => $match,
            'standing_map' => $standingMap,
            'can_manage'   => $canManage,
            'meta_label'   => !empty($match['matchNumber']) ? 'Jogo #' . (int) $match['matchNumber'] : 'Pontos corridos',
            'context'      => 'group',
        ]); ?>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1 && $baseUrl !== ''): ?>
    <div class="row mtop10">
        <div class="col-md-12 text-right">
            <nav>
                <ul class="pagination tw-mb-0">
                    <?php for ($i = 0; $i < $totalPages; $i++): ?>
                    <?php $pageUrl = arenagamer_tournament_detail_url($baseUrl, ['rr_page' => $i]); ?>
                    <li class="<?php echo $i === $currentPage ? 'active' : ''; ?>">
                        <a href="<?php echo htmlspecialchars($pageUrl); ?>"><?php echo $i + 1; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</section>
