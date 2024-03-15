<?php
namespace Logic\GameApi\Order;

/**
 * PP电子
 * Class PP
 * @package Logic\GameApi\Order
 */
class PP extends AbsOrder {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_pp';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 64;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'PP';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'PP','PPBY','PPLIVE'";

    public $game_type_field = 'dataType';

    public $platformTypes = [
        'Slot' => ['id' => 64, 'game' => 'GAME', 'type' => 'PP'],
        'BY' => ['id' => 65, 'game' => 'BY', 'type' => 'PPBY'],
        'ECasino' => ['id' => 66, 'game' => 'LIVE', 'type' => 'PPLIVE'],
    ];
}

