<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
    <li class="breadcrumb-item"><a href="/admin/logs">Logs</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?=$type?></li>
  </ol>
</nav>

<h1><?= ucfirst($type) ?></h1>

<pre>
    <code><?php foreach($logs as $log):
    echo htmlspecialchars($log, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        endforeach; ?></code>
</pre>