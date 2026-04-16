<?php
$data = json_decode(file_get_contents('https://connect.gateway.nwg.se/api/jPEELCU7kORztJHwtz6Iw'), true);
print_r(array_keys($data[0]['variations'][0]));
print_r("\n\nSample Variation:\n");
print_r($data[0]['variations'][0]);
