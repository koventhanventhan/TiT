<?php
$str = '"[\"ICT\",\"arts\",\"drama\"]"';
$decoded1 = json_decode($str, true);
var_dump($decoded1);
var_dump(is_string($decoded1));

$decoded2 = json_decode($decoded1, true);
var_dump($decoded2);
var_dump(is_array($decoded2));
