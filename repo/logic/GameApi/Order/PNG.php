<?php
namespace Logic\GameApi\Order;

/**
 * PNG电子
 * Class PNG
 * @package Logic\GameApi\Order
 */
class PNG extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_png';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 68;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'PNG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'PNG'";

    /**
     * @var string 一级类类型GAME,LIVE,BY,TABLE,QP,SPORT,ESPORTS,ARCADE,SABONG
     */
    public $gameBigType = 'GAME';

}

