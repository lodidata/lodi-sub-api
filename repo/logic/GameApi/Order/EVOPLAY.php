<?php

namespace Logic\GameApi\Order;

/**
 * EVOPLAY
 * Class EVOPLAY
 * @package Logic\GameApi\Order
 */
class EVOPLAY extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_evoplay';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 125;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'EVOPLAY';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'EVOPLAY', 'EVOPLAYTAB', 'EVOPLAYLIVE'";


    public $game_type_field = 'game_id';

    public $platformTypes = [
        125 => ['id' => 125, 'game' => 'GAME', 'type' => 'EVOPLAY'],
        126 => ['id' => 126, 'game' => 'TABLE', 'type' => 'EVOPLAYTAB'],
        127 => ['id' => 127, 'game' => 'LIVE', 'type' => 'EVOPLAYLIVE'],
    ];
}

