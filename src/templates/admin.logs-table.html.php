<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
    <li class="breadcrumb-item"><a href="/admin/logs">Logs</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?=$type?></li>
  </ol>
</nav>

<h1><?= ucfirst($type) ?></h1>

<?php
  $baseUrl = '/admin/logs/' . $type . ($filter ? '/' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') : '');
  $from    = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
  $to      = min($page * $perPage, $total);
?>

<p class="text-muted small">
  Showing <?= $from ?>–<?= $to ?> of <?= $total ?> entries (latest first)
</p>

<?php if($total_pages > 1): ?>
<nav aria-label="Log pagination" class="mb-2">
  <ul class="pagination pagination-sm">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page - 1 ?>">« Prev</a>
    </li>
    <?php
      $window = 2;
      $start  = max(1, $page - $window);
      $end    = min($total_pages, $page + $window);
      if($start > 1): ?><li class="page-item disabled"><span class="page-link">1</span></li><?php endif;
      if($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
      for($p = $start; $p <= $end; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= $baseUrl ?>?page=<?= $p ?>"><?= $p ?></a>
        </li>
      <?php endfor;
      if($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif;
      if($end < $total_pages): ?><li class="page-item"><a class="page-link" href="<?= $baseUrl ?>?page=<?= $total_pages ?>"><?= $total_pages ?></a></li><?php endif;
    ?>
    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page + 1 ?>">Next »</a>
    </li>
  </ul>
</nav>
<?php endif; ?>

<pre><code><?php foreach($logs as $log):
    echo htmlspecialchars($log, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    endforeach; ?></code></pre>

<?php if($total_pages > 1): ?>
<nav aria-label="Log pagination" class="mt-2">
  <ul class="pagination pagination-sm">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page - 1 ?>">« Prev</a>
    </li>
    <li class="page-item disabled">
      <span class="page-link">Page <?= $page ?> of <?= $total_pages ?></span>
    </li>
    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $baseUrl ?>?page=<?= $page + 1 ?>">Next »</a>
    </li>
  </ul>
</nav>
<?php endif; ?>
