<?php

if (!file_exists(__DIR__ . '/config.php')) {
    throw new Exception('需要 config.php');
}
$secret = getenv('secret');
$accounts = [];
$records = [];

$commits = [];

foreach (glob("outputs/*") as $f) {
    $fp = fopen($f, 'r');
    preg_match('#/([^/]*)\.csv$#', $f, $matches);
    $repo = $matches[1];

    while ($line = fgets($fp)) {
        $o = json_decode($line);
        if (!$o) {
            continue;
        }
        list($name, $email) = $o->author;
        if (!array_key_exists($email, $accounts)) {
            $accounts[$email] = [
                'names' => [],
                'email' => $email,
            ];
        }
        if ($name) {
            if (!array_key_exists($name, $accounts[$email]['names'])) {
                $accounts[$email]['names'][$name] = 0;
            }
            $accounts[$email]['names'][$name] ++;
        }
        $commits[$repo . '-' . $o->commit] = $o->message;
        $records[] = [$email, $o->date, $repo, $o->commit];
    }
}

usort($records, function($a, $b){ 
    return $a[1] - $b[1];
});

$levels = [10, 50, 100, 200, 300, 400, 500, 1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000];
$output = fopen('stat.csv', 'w');
fputcsv($output, ['帳號', '時間', '頻道', '成就']);
$output_id = fopen('github-id.csv', 'w');
fputcsv($output_id, ['uid', 'name', 'hash_ids']);
$output_badge = fopen('github-badge.jsonl', 'w');

$users = new StdClass;

$add_trophy = function($obj) use ($secret, $users, $output, $output_id, $output_badge, &$accounts, $commits){
    list($email, $time, $brief, $extra) = $obj;
    $userid = crc32($email . $secret);

    if (array_key_exists($email, $accounts)) {
        $names = $accounts[$email]['names'];
        arsort($names);
        $name = array_keys($names)[0];
        fputcsv($output_id, [$userid, $name, md5($email . 'g0vg0v')]);
        unset($accounts[$email]);
    }

    $title = $commits[$extra['repo'] . '-' . $extra['commit']];
    $url = "https://github.com/g0v/{$extra['repo']}/commit/{$extra['commit']}";
    $extra['title'] = $title;
    $extra['url'] = $url;
    fputs($output_badge, json_encode([
        'uid' => $userid,
        'time' => date('Y-m-d', $time),
        'brief' => $brief,
        'extra' => $extra,
    ], JSON_UNESCAPED_UNICODE) . "\n");
};

foreach ($records as $record) {
    //$records[] = [$email, $o->date, $repo, $o->commit];
    list($email, $time, $repo, $commit) = $record;

    if (!property_exists($users, $email)) {
        $users->{$email} = new StdClass;
        $users->{$email}->repos = [];
        $users->{$email}->commit = 0;
    }

    if (!array_key_exists($repo, $users->{$email}->repos)) {
        $users->{$email}->repos[$repo] = 0;
        $c = count($users->{$email}->repos);
        if (in_array($c, $levels)) {
            $add_trophy([$email, $time, sprintf("參與 %d 個 repo", $c), ['repo' => $repo, 'commit' => $commit]]);
        }
    }
    $users->{$email}->repos[$repo] ++;
    $c = $users->{$email}->repos[$repo];
    if (in_array($c, $levels)) {
        $add_trophy([$email, $time, sprintf("在 %s 送出 %d 個 commit", $repo, $c), ['repo' => $repo, 'commit' => $commit]]);
    }

    $users->{$email}->commit ++;
    $c = $users->{$email}->commit;
    if (in_array($c, $levels)) {
        $add_trophy([$email, $time, sprintf("送出 %d 個 commit", $c), ['repo' => $repo, 'commit' => $commit]]);
    }
}
