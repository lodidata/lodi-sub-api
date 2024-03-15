<?php

namespace Logic\GameApi\CKFormat;


class HB extends CKFORMAT
{
    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 128;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_hb';
    protected $field_category_name = 'GameTypeId';
    protected $field_category_value = 11;

    protected $table_fields = [
        'orderNumber' => 'GameInstanceId',
        'userName' => 'Username',
        'betAmount' => 'Stake',
        'validAmount' => 'Stake',
        'profit' => '(Payout-Stake)',
        'whereTime' => 'DtCompleted',
        'startTime' => 'DtStarted',
        'endTime' => 'DtCompleted',
        'gameCode' => 'GameKeyName',
    ];

}