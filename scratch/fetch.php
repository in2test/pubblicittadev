<?php

ini_set('memory_limit', '512M');

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ],
]);
$response = file_get_contents('https://connect.gateway.nwg.se/api/jPEELCU7kORztJHwtz6Iw', false, $context);
$data = json_decode($response, true);
file_put_contents('output.json', json_encode(array_slice($data, 0, 1), JSON_PRETTY_PRINT));
