<?php

if (file_exists(__DIR__ . '/config.php')) {
    include(__DIR__ . '/config.php');
}

$fp = fopen('repo.csv', 'r');
$columns = fgetcsv($fp);
while ($rows = fgetcsv($fp)) {
    $values = array_combine($columns, $rows);
    $repo = $values['name'];
    error_log($repo);
    for ($i = 1; ; $i ++) {
        $target = __DIR__ . "/issues/{$repo}-{$i}.json";
        if (!file_exists($target)) {
            $url = "https://api.github.com/repos/g0v/{$repo}/issues/{$i}";
            error_log($url);
            if ($key = getenv('key')) {
                $cmd = sprintf("curl -H 'Authorization: token %s' %s", $key, escapeshellarg($url));
            } else {
                $cmd = sprintf("curl %s", escapeshellarg($url));
            }
            $c = (`$cmd`);
            if (!$c) {
                break;
            }
            if (strpos($c, 'API rate limit exceeded')) {
                echo $c;
                throw new Exception("API API rate limit exceeded");
            }
            if (strpos($c, '"message": "Not Found",')) {
                break;
            }
            file_put_contents($target, $c);
        }
        $obj = json_decode(file_get_contents($target));
        if ($obj->comments) {
            $target = __DIR__ . "/issues/{$repo}-{$i}.comment-json";
            if (!file_exists($target)) {
                $url = "https://api.github.com/repos/g0v/{$repo}/issues/{$i}/comments";
                error_log($url);
                if ($key = getenv('key')) {
                    $cmd = sprintf("curl -H 'Authorization: token %s' %s", $key, escapeshellarg($url));
                } else {
                    $cmd = sprintf("curl %s", escapeshellarg($url));
                }
                $c = (`$cmd`);
                file_put_contents($target, $c);
            }
        }
    }
}
