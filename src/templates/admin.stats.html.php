<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stats</li>
  </ol>
</nav>

<h1>Stats</h1>

<div class="table-responsive">
<table class="table table-hover" data-toggle="table" data-search="true">
  <thead>
    <tr>
      <th scope="col" data-sortable="true">Hash</th>
      <th scope="col" data-sortable="true">Views</th>
      <th scope="col" data-sortable="true">Original Filename</th>
      <th scope="col" data-sortable="true">MIME type</th>
      <th scope="col" data-sortable="true">Created at</th>
      <th scope="col" data-sortable="true">Uploader IP</th>
      <?php if(defined('LOG_VIEWS') && LOG_VIEWS==true): ?>
        <th scope="col" data-sortable="true">List views</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach($stats['hashes'] as $hash => $data): ?>
    <tr>
      <th scope="row"><a href="/<?=$hash?>"><?=$hash?></a></th>
      <td><?=$data['views']?:0?></td>
      <td><?=$data['metadata']['original_filename']?></td>
      <td><?=$data['metadata']['mime']?></td>
      <td><?=$data['metadata']['uploaded']?date("Y-m-d H:i",$data['metadata']['uploaded']):''?></td>
      <td><?=$data['metadata']['ip']?></td>
      <?php if(defined('LOG_VIEWS') && LOG_VIEWS==true): ?>
        <td scope="col" data-sortable="true"><a class="btn btn-secondary" href="/admin/logs/views/<?=$hash?>">View logs</a></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>