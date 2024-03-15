<?php
namespace Logic\GameApi\Order;

/**
 * JOKER 游戏接口
 */

class JOKER extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_joker';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 59;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'JOKER';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'JOKER', 'JOKERBY', 'JOKERLIVE'";

}

