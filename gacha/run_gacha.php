<?php
require_once('./gachaBase.php');
require_once('./gachaHistoryConsideration.php');

use Sample\GachaBase;
use Sample\GachaHistoryConsideration;

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


$history = array("1" => 1, "20" => 2);

$init_time = microtime_float();
//$gacha = new GachaBase("gacha_data_1.json");
$gacha = new GachaHistoryConsideration("gacha_data_1.json", $history);

$start_time = microtime_float();
$result = array();
for ($i = 0; $i<10000; $i++) {
  $r = $gacha->draw(11);
  foreach ($r as $id) {
    @$result[$id] ++;
  }
}
$end_time = microtime_float();
ksort($result);

echo "init: $init_time\n";
echo "start: $start_time\n";
echo "end: $end_time\n";
echo "total: " . ($end_time - $start_time) . "\n";

$rate = array();
$total = 0;
foreach ($result as $key => $value) {
  $total += $value;
}
foreach ($result as $key => $value) {
  $rate[$key] = round($value / $total, 5);
}

echo "total draw: $total\n";
foreach ($result as $key => $value) {
  echo "$key: $value ({$rate[$key]} %)\n";
}
