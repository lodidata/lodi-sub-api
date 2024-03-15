<?php
namespace Logic\GameApi\Order;

/**
 * SA真人
 * Class SA
 * @package Logic\GameApi\Order
 */
class SA extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_sa';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 73;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'SA';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'SA'";

    /**
     * @var string 一级类类型GAME,LIVE,BY,TABLE,QP,SPORT,ESPORTS,ARCADE,SABONG
     */
    public $gameBigType = 'LIVE';
}

