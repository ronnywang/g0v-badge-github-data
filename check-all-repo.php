<?php

$fp = fopen('repo.csv', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);
    $repo = $values['name'];
    if (file_exists("outputs/{$repo}.csv")) {
        continue;
    }
    if ($values['size'] > 1024000) {
        continue;
    }

    error_log($repo);

    system("rm -rf repo");
    system("git clone https://github.com/g0v/{$repo} repo");
    system("php check-repo.php > outputs/{$repo}.csv");
}
