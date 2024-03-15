<?php

namespace Logic\GameApi\CKFormat;

/**
 * WM视讯
 */
class WM extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 104;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_wm';

    protected $table_fields = [
        'orderNumber' => 'order_number',
        'userName' => 'user',
        'betAmount' => 'bet*100',
        'validAmount' => 'validbet*100',
        'profit' => 'winLoss*100',
        'whereTime' => 'betTime',
        'startTime' => 'betTime',
        'endTime' => 'settime',
        'gameRoundId' => 'tableId',
        'gameCode' => 'gid',
        'gameName' => 'gname'
    ];
}