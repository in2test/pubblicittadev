<?php
$data = json_decode(file_get_contents('https://connect.gateway.nwg.se/api/jPEELCU7kORztJHwtz6Iw'), true);
print_r(array_keys($data[0]));
print_r(isset($data[0]['productVariations']) ? 'Has productVariations' : 'No productVariations');
