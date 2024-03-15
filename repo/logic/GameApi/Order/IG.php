<?php
namespace Logic\GameApi\Order;

/**
 * IG 游戏接口
 */

class IG extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_ig';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 103;

    public $game_type = 'IG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'IG','IGSABONG','IGSMALL','IGSLOT'";

    public $gameBigType = 'QP';
}

