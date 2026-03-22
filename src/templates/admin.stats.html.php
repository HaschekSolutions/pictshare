<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stats</li>
  </ol>
</nav>

<h1>Stats</h1>

<?php if($built_at > 0): ?>
  <p class="text-muted small">Last updated: <?=round((time()-$built_at)/60)?> minute(s) ago</p>
<?php else: ?>
  <div class="alert alert-warning">Redis unavailable — stats may load slowly.</div>
<?php endif; ?>

<div class="mb-3">
  <input
    type="text"
    name="q"
    id="stats-search"
    class="form-control"
    placeholder="Search hash, filename, IP, MIME…"
    hx-get="/admin/stats/data"
    hx-trigger="keyup changed delay:300ms"
    hx-target="#stats-tbody"
    hx-swap="innerHTML"
    hx-include="[name='sort'],[name='dir']"
  >
</div>

<input type="hidden" name="sort" id="current-sort" value="uploaded">
<input type="hidden" name="dir"  id="current-dir"  value="desc">

<div class="table-responsive">
<table class="table table-hover">
  <thead>
    <tr>
      <?php
        $cols = [
          'hash'              => 'Hash',
          'views'             => 'Views',
          'original_filename' => 'Original Filename',
          'mime'              => 'MIME type',
          'uploaded'          => 'Created at',
          'size'              => 'Size',
          'ip'                => 'Uploader IP',
        ];
        foreach($cols as $key => $label):
      ?>
      <th scope="col">
        <a href="#" class="sort-link text-decoration-none text-body" data-sort="<?=$key?>">
          <?=$label?> <span class="sort-arrow" data-col="<?=$key?>"></span>
        </a>
      </th>
      <?php endforeach; ?>
      <?php if(defined('LOG_VIEWS') && LOG_VIEWS==true): ?>
        <th scope="col">Views log</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody
    id="stats-tbody"
    hx-get="/admin/stats/data"
    hx-trigger="load"
    hx-swap="innerHTML"
  >
    <tr><td colspan="<?=defined('LOG_VIEWS')&&LOG_VIEWS?8:7?>">Loading&hellip;</td></tr>
  </tbody>
</table>
</div>

<script>
document.querySelectorAll('.sort-link').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    var col    = this.dataset.sort;
    var sortEl = document.getElementById('current-sort');
    var dirEl  = document.getElementById('current-dir');
    var curDir = dirEl.value;

    dirEl.value  = (sortEl.value === col && curDir === 'desc') ? 'asc' : 'desc';
    sortEl.value = col;

    // Update arrow indicators
    document.querySelectorAll('.sort-arrow').forEach(function(el) {
      el.textContent = '';
    });
    document.querySelector('.sort-arrow[data-col="' + col + '"]').textContent =
      dirEl.value === 'asc' ? ' ▲' : ' ▼';

    // Trigger HTMX request manually, including search query
    var q = document.getElementById('stats-search').value;
    htmx.ajax('GET', '/admin/stats/data?sort=' + sortEl.value + '&dir=' + dirEl.value + '&q=' + encodeURIComponent(q), {
      target: '#stats-tbody',
      swap: 'innerHTML'
    });
  });
});
</script>
