<?php
namespace Logic\GameApi\Order;

/**
 * GFG捕鱼
 * Class GFG
 * @package Logic\GameApi\Order
 */
class GFG extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_gfg';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 136;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'GFG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'GFG'";

    /**
     * @var string 一级类类型GAME,LIVE,BY,TABLE,QP,SPORT,ESPORTS,ARCADE,SABONG
     */
    public $gameBigType = 'BY';
}

