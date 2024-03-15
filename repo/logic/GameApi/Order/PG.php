<?php
namespace Logic\GameApi\Order;

/**
 * PG电子
 * Class PG
 * @package Logic\GameApi\Order
 */
class PG extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_pg';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 76;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'PG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'PG'";

}

