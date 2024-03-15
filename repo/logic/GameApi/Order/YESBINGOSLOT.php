<?php
namespace Logic\GameApi\Order;

/**
 * YESBINGO
 * Class YESBINGO
 * @package Logic\GameApi\Order
 */
class YESBINGOSLOT extends YESBINGO {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_yesbingo';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 149;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'YESBINGOSLOT';

    public function OrderRepair(){

    }

}

