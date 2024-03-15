<?php

namespace Logic\GameApi\CKFormat;


class AT extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 88;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_at';

    protected $field_category_name = 'gameType';
    protected $field_category_value = "'slot'";
    protected $table_fields = [
        'orderNumber' => 'order_number',
        'userName' => 'player',
        'betAmount' => 'bet',
        'validAmount' => 'validBet',
        'profit' => 'result',
        'whereTime' => 'createdAt',
        'startTime' => 'createdAt',
        'endTime' => 'updatedAt',
        'gameCode' => 'productId',
        'roundId' => 'setId',
    ];

}