<?php
$total = (int) ($total ?? 0);
$page = (int) ($page ?? 1);
$perPage = (int) ($perPage ?? 10);
$totalPages = max(1, (int) ($totalPages ?? ceil($total / max(1, $perPage))));
$params = $params ?? [];
$link = static function (int $target) use ($baseUrl, $params, $perPage): string {
    return base_url($baseUrl . '?' . http_build_query(array_merge($params, ['page' => $target, 'per_page' => $perPage])));
};
$first = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
$last = min($page * $perPage, $total);
if ($totalPages > 1 || $total > 0):
?>
<div class="mk-pagination">
    <div class="mk-pagination-size">Menampilkan
        <select onchange="window.location.href=this.value" aria-label="Data per halaman">
            <?php foreach ([10, 30, 50, 100] as $size): ?><option value="<?= esc($link(1) . '&per_page=' . $size) ?>" <?= $size === $perPage ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
        </select> data per halaman
    </div>
    <?php if ($totalPages > 1): ?><ul class="pagination mb-0">
        <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= esc($link(1)) ?>">«</a></li>
        <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= esc($link(max(1, $page - 1))) ?>">‹</a></li>
        <?php $start = max(1, min($page - 2, $totalPages - 4)); $end = min($totalPages, $start + 4); for ($p = $start; $p <= $end; $p++): ?><li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= esc($link($p)) ?>"><?= $p ?></a></li><?php endfor; ?>
        <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= esc($link(min($totalPages, $page + 1))) ?>">›</a></li>
        <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= esc($link($totalPages)) ?>">»</a></li>
    </ul><?php endif; ?>
    <div class="mk-pagination-total">Menampilkan <?= $first ?> - <?= $last ?> dari <?= $total ?> data</div>
</div>
<?php endif; ?>
<script>
(function () {
    const key = 'pagination-scroll-position';
    window.addEventListener('pagehide', function () {
        sessionStorage.setItem(key, String(window.scrollY));
    });
    window.addEventListener('pageshow', function () {
        const saved = sessionStorage.getItem(key);
        if (saved !== null) {
            sessionStorage.removeItem(key);
            requestAnimationFrame(function () {
                window.scrollTo(0, parseInt(saved, 10) || 0);
            });
        }
    });
})();
</script>
