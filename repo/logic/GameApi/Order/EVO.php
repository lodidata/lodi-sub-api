<?php

namespace Logic\GameApi\Order;

/**
 * EVO真人
 * Class
 * @package Logic\GameApi\Order
 */
class EVO extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_evo';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 67;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'EVO';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'EVO'";

    public $gameBigType = 'LIVE'; //一级类型
}

