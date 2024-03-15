<?php

namespace Logic\GameApi\Order;

/**
 * JILI电子
 * Class JILI
 * @package Logic\GameApi\Order
 */
class JILI extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_jili';

    /**
     * 类ID
     * @var int
     */
    public $game_id = 62;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'JILI';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'JILI', 'JILIBY','JILILIVE'";


    public $game_type_field = 'GameCategoryId';

    public $platformTypes = [
        1 => ['id' => 62, 'game' => 'GAME', 'type' => 'JILI'],
        5 => ['id' => 63, 'game' => 'BY', 'type' => 'JILIBY'],
        8 => ['id' => 101, 'game' => 'LIVE', 'type' => 'JILILIVE'],
        2 => ['id' => 110, 'game' => 'QP', 'type' => 'JILIQP'],
    ];
}

