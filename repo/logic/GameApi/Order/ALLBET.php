<?php
namespace Logic\GameApi\Order;

/**
 * ALLBET真人
 * Class ALLBET
 * @package Logic\GameApi\Order
 */
class ALLBET extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_allbet';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 134;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'ALLBET';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'ALLBET'";

    /**
     * @var string 一级类类型GAME,LIVE,BY,TABLE,QP,SPORT,ESPORTS,ARCADE,SABONG
     */
    public $gameBigType = 'LIVE';
}

