<?php

namespace Logic\GameApi\CKFormat;

/**
 * BSG
 */
class BSG extends CKFORMAT {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 124;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_bsg';

    public $table_fields = [
        'orderNumber' => 'order_number',
        'userName' => 'username',
        'betAmount' => 'bet_amount',
        'validAmount' => 'bet_amount',
        'profit' => 'income',
        'whereTime' => 'bettime',
        'startTime' => 'bettime',
        'endTime' => 'bettime',
        'gameCode' => 'game_id',
    ];
}