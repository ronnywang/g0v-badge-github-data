<?php

ini_set('memory_limit', '4g');
$parse_message = function($message){
    $obj = new StdClass;
    $obj->diff = [];
    $lines = explode("\n", $message);
    while (count($lines)) {
        $line = array_shift($lines);
        if (preg_match('#^commit ([a-f0-9]+)#', $line, $matches)) {
            $obj->commit = $matches[1];
        } else if (strpos($line, 'Merge:') === 0) {
        } else if (preg_match('#Author: ([^<]+) <([^>]+)>#', $line, $matches)) {
            $obj->author = [$matches[1], $matches[2]];
        } else if (preg_match('#Date: (.*)#', $line, $matches)) {
            $obj->date = strtotime($matches[1]);
        } else if (trim($line) == '') {
        } else if (strpos($line, '    ') === 0) {
            if (!property_exists($obj, 'message')) {
                $obj->message = '';
            }
            $obj->message .= ltrim($line) . "\n";
        } else if (preg_match('#^(M|A|D|MM)\s+(.*)$#', $line, $matches)) {
            $obj->diff[] = [$matches[1], $matches[2]];
        } elseif (preg_match('#^R099#', $line)) {
            continue;
        } else {
            var_dump($line);
            exit;
        }
    }
    $obj->message = rtrim($obj->message);
    return $obj;
};

$handle_commit = function($obj) use ($parse_message){
    error_log($obj->commit);
    $cmd = ("git --git-dir=repo/.git show --name-status {$obj->commit}");
    $message = `$cmd`;
    $obj = $parse_message($message);
    echo json_encode($obj, JSON_UNESCAPED_UNICODE) . "\n";
};


$fp = popen('git --git-dir=repo/.git log --reflog', 'r');
$message = null;
while ($line = fgets($fp)) {
    if (preg_match('#^commit ([a-f0-9]+)#', $line, $matches)) {
        if (!is_null($message)) {
            $obj = $parse_message($message);
            $handle_commit($obj);
        }
        $message = '';
    }
    $message .= $line;
}

if ($message) {
    $obj = $parse_message($message);
    $handle_commit($obj);
}
