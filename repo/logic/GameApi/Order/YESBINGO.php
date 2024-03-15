<?php
namespace Logic\GameApi\Order;

/**
 * YESBINGO
 * Class YESBINGO
 * @package Logic\GameApi\Order
 */
class YESBINGO extends AbsOrder
{

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_yesbingo';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 147;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'YESBINGO';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'YESBINGO', 'YESBINGOBY','YESBINGOSLOT'";


    public $game_type_field = 'game_menu_id';

    public $platformTypes = [
        147 => ['id' => 147, 'game' => 'BINGO', 'type' => 'YESBINGO'],
        148 => ['id' => 148, 'game' => 'BY', 'type' => 'YESBINGOBY'],
        149 => ['id' => 149, 'game' => 'GAME', 'type' => 'YESBINGOSLOT'],
    ];

}

