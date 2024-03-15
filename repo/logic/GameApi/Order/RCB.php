<?php
namespace Logic\GameApi\Order;

/**
 * RCB
 * Class RCB
 * @package Logic\GameApi\Order
 */
class RCB extends AWC {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_rcb';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 80;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'RCB';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'RCB'";

    public $gameBigType = 'SABONG';

}

