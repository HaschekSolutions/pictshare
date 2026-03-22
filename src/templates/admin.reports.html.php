<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
        <li class="breadcrumb-item active" aria-current="page">Reports</li>
    </ol>
</nav>

<?php
//sort reports by $report['status'] and then by $report['timestamp']
usort($reports, function($a, $b) {
return $b['timestamp'] - $a['timestamp'];
});
?>

<div class="row">
    <div class="col-md-6">
        <?php foreach ($reports as $report): if($report['status'] != 'open') continue; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Report ID: <?php echo htmlspecialchars($report['id']); ?></h5>
                    <p class="card-text">Timestamp: <?php echo date('Y-m-d H:i:s', $report['timestamp']); ?></p>
                    <p class="card-text">Hashes:
                        <?php foreach ($report['hashes'] as $hash): ?>
                            <a href="<?php echo URL . $hash; ?>" target="_blank"><?php echo htmlspecialchars($hash); ?></a> 
                            <?php if(file_exists(getDataDir().DS.$hash.DS.$hash)):?>
                                <a href="/admin/reports/delete/<?=$hash ?>" class="text-danger">Delete</a><br>
                            <?php else: ?>
                                <span class="text-muted">(File already deleted)</span><br>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </p>
                    <p class="card-text">Note: <?php echo htmlspecialchars($report['note']); ?></p>
                    <a href="/admin/reports/status/resolved/<?=$report['id']?>" class="btn btn-primary">Resolve Report</a>
                </div>
            </div>
            
        <?php endforeach; ?>
    </div>
</div>