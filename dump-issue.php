<?php

$accounts = [];
$records = [];
$titles = [];

foreach (glob("issues/*") as $f) {
    $obj = json_decode(file_get_contents($f));

    if (preg_match('#([^/]+)-(\d+)\.json#', $f, $matches)) {
        $repo = $matches[1];
        $id = intval($matches[2]);
        $time = strtotime($obj->created_at);

        $titles[$repo . '-' . $id] = $obj->title;

        $records[] = [$repo, 'create', $obj->user->login, $time, $id];
    } elseif (preg_match('#([^/]+)-(\d+)\.comment-json#', $f, $matches)) {
        $repo = $matches[1];
        $id = intval($matches[2]);

        foreach ($obj as $comment) {
            $time = strtotime($comment->created_at);
            $records[] = [$repo, 'comment', $comment->user->login, $time, $id];
        }
    }
}

usort($records, function($a, $b){ 
    return $a[3] - $b[3];
});

$levels = [10, 50, 100, 200, 300, 400, 500, 1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000];
$output_id = fopen('github-id.csv', 'w');
fputcsv($output_id, ['uid', 'name', 'hash_ids']);
$output_badge = fopen('github-badge.jsonl', 'w');

$users = new StdClass;

$add_trophy = function($obj) use ($users, $output_id, $output_badge, &$accounts, $titles){
    list($user, $time, $brief, $extra) = $obj;

    if (!array_key_exists($user , $accounts)) {
        $accounts[$user] = true;
        fputcsv($output_id, [$user, $user, md5('github://' . $user . 'g0vg0v')]);
    }

    $extra['title'] = $titles[$extra['repo'] . '-' . $extra['id']];
    $extra['url'] = "https://github.com/g0v/{$extra['repo']}/issues/{$extra['id']}";

        
    fputs($output_badge, json_encode([
        'uid' => $user,
        'time' => date('Y-m-d', $time),
        'brief' => $brief,
        'extra' => $extra,
    ], JSON_UNESCAPED_UNICODE) . "\n");
};

foreach ($records as $record) {
    //$records[] = [$repo, 'comment', $comment->user->login, $time, [$id]];
    list($repo, $action, $user, $time, $issue_id) = $record;

    if (!property_exists($users, $user)) {
        $users->{$user} = new StdClass;
        $users->{$user}->repos = [];
        $users->{$user}->create = 0;
        $users->{$user}->comment = 0;
    }

    if (!array_key_exists($repo, $users->{$user}->repos)) {
        $users->{$user}->repos[$repo] = [
            'create' => 0,
            'comment' => 0,
        ];
        $c = count($users->{$user}->repos);
        if (in_array($c, $levels)) {
            $add_trophy([$user, $time, sprintf("參與 %d 個 repo 的 issue", $c), ['repo' => $repo, 'id' => $issue_id]]);
        }
    }
    $users->{$user}->repos[$repo][$action] ++;
    $c = $users->{$user}->repos[$repo][$action];
    if (in_array($c, $levels)) {
        if ($action == 'create') {
            $add_trophy([$user, $time, sprintf("在 %s 建立了 %d 個 issue", $repo, $c), ['repo' => $repo, 'id' => $issue_id]]);
        } else {
            $add_trophy([$user, $time, sprintf("在 %s 回應了 %d 個 issue", $repo, $c), ['repo' => $repo, 'id' => $issue_id]]);
        }
    }

    $users->{$user}->{$action} ++;
    $c = $users->{$user}->{$action};
    if (in_array($c, $levels)) {
        if ($action == 'create') {
            $add_trophy([$user, $time, sprintf("建立了 %d 個 issue", $c), ['repo' => $repo, 'id' => $issue_id]]);
        } else {
            $add_trophy([$user, $time, sprintf("回應了 %d 個 issue", $c), ['repo' => $repo, 'id' => $issue_id]]);
        }
    }
}
