<?php
namespace Logic\GameApi\Order;

/**
 * AWS
 * Class AWS
 * @package Logic\GameApi\Order
 */
class AWSTAB extends AWS {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_aws';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 107;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'AWSTAB';


    public function OrderRepair(){

    }
}

