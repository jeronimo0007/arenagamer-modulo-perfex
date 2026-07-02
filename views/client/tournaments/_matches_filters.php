<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$pagination = is_array($matches_pagination ?? null) ? $matches_pagination : [];
$baseUrl = (string) ($matches_base_url ?? '');
$activeView = (string) ($match_view ?? 'pending');
$totalPages = (int) ($pagination['totalPages'] ?? 1);
$currentPage = (int) ($pagination['number'] ?? 0);
?>
<?php if ($baseUrl !== ''): ?>
<ul class="nav nav-pills mbot10">
    <?php foreach (arenagamer_tournament_matches_filter_options() as $option): ?>
    <?php $url = arenagamer_tournament_matches_filter_url($baseUrl, $option['id'], 0); ?>
    <li class="<?php echo $activeView === $option['id'] ? 'active' : ''; ?>">
        <a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($option['label']); ?></a>
    </li>
    <?php endforeach; ?>
</ul>
<?php if ($totalPages > 1): ?>
<div class="row mbot10">
    <div class="col-md-6">
        <p class="text-muted mbot0"><?php echo arenagamer_pagination_info_text($pagination); ?></p>
    </div>
    <div class="col-md-6 text-right">
        <nav>
            <ul class="pagination tw-mb-0">
                <?php for ($i = 0; $i < $totalPages; $i++): ?>
                <?php $pageUrl = arenagamer_tournament_matches_filter_url($baseUrl, $activeView, $i); ?>
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
<?php endif; ?>
