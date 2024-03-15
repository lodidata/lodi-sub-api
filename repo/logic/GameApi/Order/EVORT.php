<?php

namespace Logic\GameApi\Order;

/**
 * EVORT
 * Class
 * @package Logic\GameApi\Order
 */
class EVORT extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_evort';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 138;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'EVORT';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'EVORT'";

    public $gameBigType = 'SLOT'; //一级类型
}

