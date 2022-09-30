<?php

$fp = fopen('repo.csv', 'r');
while ($rows = fgetcsv($fp)) {
    list($repo) = $rows;

    error_log($repo);

    system("rm -rf repo");
    system("git clone https://github.com/g0v/{$repo} repo");
    system("php check-repo.php > outputs/{$repo}.csv");
}
