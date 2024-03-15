<?php

namespace Logic\GameApi\CKFormat;


class NG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 137;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_ng';
    protected $table_fields = [
        'orderNumber' => 'roundId',
        'userName' => 'playerNativeId',
        'betAmount' => 'amount*100',
        'validAmount' => 'amount*100',
        'profit' => '(earn-amount)*100',
        'whereTime' => 'created',
        'startTime' => 'created',
        'endTime' => 'updated',
        'gameName' => 'gameName',
        'gameCode' => 'gameCode',
    ];

}