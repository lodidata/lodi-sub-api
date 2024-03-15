<?php

namespace Logic\GameApi\Order;

/**
 * RSG电子
 * Class JILI
 * @package Logic\GameApi\Order
 */
class RSG extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_rsg';

    /**
     * 类ID
     * @var int
     */
    public $game_id = 150;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'RSG';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'RSG', 'RSGBY'";


    public $game_type_field = 'game_menu_id';

    public $platformTypes = [
        150 => ['game' => 'GAME', 'type' => 'RSG'],
        151 => ['game' => 'BY', 'type' => 'RSGBY'],
    ];
}