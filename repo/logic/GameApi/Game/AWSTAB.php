<?php
namespace Logic\GameApi\Game;
/**
 * AWC游戏聚合平台
 * 游戏平台-AWS
 * Class AWSTAB
 * @package Logic\GameApi\Game
 */
class AWSTAB extends AWS {
    protected $orderTable = 'game_order_aws';
    protected $platfrom = 'AWS';
    protected $orderType = 'AWSTAB';
    protected $gameType = 'TABLE';
    protected $game_id = 107;
}

