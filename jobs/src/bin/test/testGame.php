<?php

$game_type = $argv[3];
$uid = $argv[2];
$active = $argv[4];

$game = Logic\GameApi\GameApi::getApi(strtoupper($game_type), $uid);
switch ($active) {
    case 'checkMoney': $res = $game->checkMoney();break;
    case 'getUrl': $res = $game->getJumpUrl();break;
    case 'getAccount': $res = $game->getGameAccount();break;
    case 'getBalance': $res = $game->getThirdBalance();break;
    case 'rollIn': $res = $game->rollInThird();break;
    case 'rollOut': $res = $game->rollOutThird();break;
    case 'quite': $res = $game->quitChildGame();break;
    case 'getOrder': $res = $game->synchronousChildData();break;
}
print_r($res);
exit;

