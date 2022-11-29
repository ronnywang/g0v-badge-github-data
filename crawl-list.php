<?php

$columns = [
    'name', 'description', 'created_at', 'updated_at', 'pushed_at', 'size', 'stargazers_count', 'watchers_count', 'language', 'forks_count', 'forks', 'open_issues',
];

$output = fopen('php://output', 'w');
fputcsv($output, $columns);
for ($p = 1; true; $p ++) {
    $url = "https://api.github.com/orgs/g0v/repos?page=" . $p;
    $cmd = sprintf("curl %s", escapeshellarg($url));
    $records = json_decode(`$cmd`);
    if (!count($records)) {
        break;
    }
    foreach ($records as $record) {
        fputcsv($output, array_map(function($c) use ($record) { return $record->{$c}; }, $columns));
    }
}
