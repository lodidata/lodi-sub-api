<?php

namespace Logic\GameApi\CKFormat;


class BGBY extends CKFORMAT
{

    protected $game_id = 139;
    protected $order_table = 'game_order_bg';
    protected $field_category_name = 'gameCategory';
    protected $field_category_value = "'BY'";

    public $table_fields = [
        'orderNumber' => 'orderId',
        'userName' => 'loginId',
        'betAmount' => 'bAmount*100',
        'validAmount' => 'validBet*100',
        'profit' => 'payment*100',
        'whereTime' => 'orderTime',
        'startTime' => 'orderTime',
        'endTime' => 'lastUpdateTime',
        'gameCode' => 'gameId',
    ];

}