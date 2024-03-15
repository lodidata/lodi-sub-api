<?php
namespace Logic\GameApi\Order;

/**
 * AWS
 * Class AWS
 * @package Logic\GameApi\Order
 */
class AWS extends AWC {

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_aws';
    /**
     * 类ID
     * @var int
     */
    public $game_id = 106;

    /**
     * 游戏类型
     * @var string
     */
    public $game_type = 'AWS';

    /**
     * 游戏类型组
     * @var string
     */
    public $game_types = "'AWS','AWSTAB'";

    public $platformTypes = [
        'SLOT' => ['game' => 'GAME', 'id' =>106, 'type' => 'AWS'],
        'EGAME' => ['game' => 'GAME', 'id' =>107, 'type' => 'AWSTAB'],
        'TABLE' => ['game' => 'GAME', 'id' =>107, 'type' => 'AWSTAB'],
    ];

}

