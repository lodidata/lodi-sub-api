<?php

namespace Logic\GameApi\CKFormat;


class TF extends CKFORMAT
{

    protected $game_type = 'TF';
    protected $game_id = 78;
    protected $order_table = 'game_order_tf';
    //会员下的盘
    protected $oddsStyle = [
        'malay' => 'Malay odds',
        'hongkong' => 'HongKong odds',
        'euro' => 'Euro odds',
        'indo' => 'Indonesia odds'
    ];
    //注单结果
    protected $resultStatus = [
        'WIN' => 'order.pass',
        'LOSS' => 'order.loss',
        'DRAW' => 'order.draw',
        'CANCELLED' => 'order.cancel',
        'NULL' => ''
    ];
    //注单下注状况
    protected $ticketType = [
        'db' => 'ticket_type.db',//早盘
        'live' => 'ticket_type.live' //滚球
    ];

    protected $table_fields = [
        'orderNumber' => 'order_id',
        'userName' => 'member_code',
        'betAmount' => 'amount*100',
        'validAmount' => 'amount*100',
        'profit' => 'earnings*100',
        'whereTime' => 'settlement_datetime',
        'startTime' => 'date_created',
        'endTime' => 'settlement_datetime',
    ];
}