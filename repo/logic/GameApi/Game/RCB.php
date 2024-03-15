<?php
namespace Logic\GameApi\Game;
/**
 * AWC游戏聚合平台
 * 游戏平台-HORSEBOOK
 * RCB赛马
 * Class RCB
 * @package Logic\GameApi\Game
 */
class RCB extends AWC {
    protected $orderTable = 'game_order_rcb';
    protected $platfrom = 'HORSEBOOK';
    protected $orderType = 'RCB';
    protected $gameType = 'LIVE';
    protected $gameOrderType = 'SABONG';
    protected $game_id = 80;
}

