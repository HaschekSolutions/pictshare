<h2>Admin Panel</h2>
<?php if (!$_SESSION['admin']) { ?>
    <form method="post" action="/admin">
        <div class="input-group mb-3">
            <input type="password" class="form-control" name="password" placeholder="Password" aria-label="Password" aria-describedby="btn-addn">
            <button class="btn btn-outline-secondary" type="submit" id="btn-addn">Login</button>
        </div>
    </form>
<?php } ?>
<?php if ($_SESSION['admin']) { ?>
    <div class="alert alert-success" role="alert">You are logged in as admin</div>
    <form method="post" action="/admin">
        <button type="submit" name="logout" class="btn btn-danger">Logout</button>
    </form>

    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link" aria-current="page" href="/admin/stats">Stats</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/admin/logs">Logs</a>
        </li>
    </ul>

<?php } ?>