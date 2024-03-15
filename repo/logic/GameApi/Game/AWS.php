<?php
namespace Logic\GameApi\Game;
/**
 * AWC游戏聚合平台
 * 游戏平台-AWS
 * Class AWS
 * @package Logic\GameApi\Game
 */
class AWS extends AWC {
    protected $orderTable = 'game_order_aws';
    protected $platfrom = 'AWS';
    protected $orderType = 'AWS';
    protected $gameType = 'SLOT';
    protected $gameOrderType = 'GAME';
    protected $game_id = 106;
}

