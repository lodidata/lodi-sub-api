<?php
namespace Logic\GameApi\Order;

/**
 * VIVO真人
 * Class VIVO
 * @package Logic\GameApi\Order
 */
class VIVO extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_vivo';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 133;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'VIVO';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'VIVO'";

    /**
     * @var string 一级类类型GAME,LIVE,BY,TABLE,QP,SPORT,ESPORTS,ARCADE,SABONG
     */
    public $gameBigType = 'LIVE';

    public function OrderRepair(){

    }
}

