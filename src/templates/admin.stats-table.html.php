<?php if(empty($rows)): ?>
<tr><td colspan="<?=defined('LOG_VIEWS')&&LOG_VIEWS?8:7?>">No uploads found.</td></tr>
<?php else: ?>
<?php foreach($rows as $row): ?>
<tr>
  <td><a href="/<?=htmlspecialchars($row['hash'])?>"><?=htmlspecialchars($row['hash'])?></a></td>
  <td><?=(int)$row['views']?></td>
  <td><?=htmlspecialchars($row['original_filename']??'')?></td>
  <td><?=htmlspecialchars($row['mime']??'')?></td>
  <td><?=$row['uploaded']?date('Y-m-d H:i',(int)$row['uploaded']):''?></td>
  <td><?=number_format((int)($row['size']??0))?></td>
  <td><?=htmlspecialchars($row['ip']??'')?></td>
  <?php if(defined('LOG_VIEWS')&&LOG_VIEWS==true): ?>
    <td><a class="btn btn-secondary btn-sm" href="/admin/logs/views/<?=htmlspecialchars($row['hash'])?>">View logs</a></td>
  <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php endif; ?>
<?php
  // Pagination row — all current params carried through so state is preserved
  $cols = defined('LOG_VIEWS')&&LOG_VIEWS ? 8 : 7;
  $base = '/admin/stats/data?sort='.urlencode($sort).'&dir='.urlencode($dir).'&q='.urlencode($q);
?>
<tr>
  <td colspan="<?=$cols?>">
    <?php if($total_pages > 1): ?>
      <?php if($page > 1): ?>
        <a hx-get="<?=htmlspecialchars($base.'&page='.($page-1))?>" hx-target="#stats-tbody" hx-swap="innerHTML"
           href="<?=htmlspecialchars($base.'&page='.($page-1))?>" class="btn btn-outline-secondary btn-sm me-2">&laquo; Prev</a>
      <?php endif; ?>
      Page <?=$page?> of <?=$total_pages?> (<?=$total?> total)
      <?php if($page < $total_pages): ?>
        <a hx-get="<?=htmlspecialchars($base.'&page='.($page+1))?>" hx-target="#stats-tbody" hx-swap="innerHTML"
           href="<?=htmlspecialchars($base.'&page='.($page+1))?>" class="btn btn-outline-secondary btn-sm ms-2">Next &raquo;</a>
      <?php endif; ?>
    <?php else: ?>
      <?=$total?> total
    <?php endif; ?>
  </td>
</tr>
