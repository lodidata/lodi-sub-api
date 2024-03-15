<?php
namespace Logic\GameApi\Order;

/**
 * YESBINGO
 * Class YESBINGO
 * @package Logic\GameApi\Order
 */
class YESBINGOBY extends YESBINGO {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_yesbingo';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 148;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'YESBINGOBY';

    public function OrderRepair(){

    }

}

