<?php

exec('vendor\bin\phpstan analyse -v --error-format=json', $output, $resultCode);
$jsonString = implode("\n", $output);
file_put_contents('phpstan_errors_v.json', $jsonString);

$json = json_decode($jsonString, true);
if (! $json || ! isset($json['files'])) {
    echo "Invalid JSON.\n";
    exit;
}
$files = [];
foreach ($json['files'] as $file => $data) {
    $files[basename($file)] = count($data['messages']);
}
arsort($files);
foreach ($files as $file => $count) {
    echo str_pad($file, 40).' | '.$count." errors\n";
}
