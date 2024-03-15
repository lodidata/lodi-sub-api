<?php
/**
 * SA限红
 */

$gameClass = \Logic\GameApi\GameApi::getApi('SA', 0);
$data = $gameClass->QueryBetLimit();
var_dump($data);
echo 'success';