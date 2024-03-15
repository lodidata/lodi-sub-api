<?php

namespace Logic\GameApi\CKFormat;

class QT extends CKFORMAT
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 132;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_qt';

    protected $field_category_name = 'gameCategory';
    protected $field_category_value = "'SLOT'";

    protected $table_fields = [
        'orderNumber' => 'gameProviderRoundId',
        'userName' => 'playerId',
        'betAmount' => 'totalBet*100',
        'validAmount' => 'totalBet*100',
        'profit' => '(totalPayout-totalBet)*100',
        'whereTime' => 'completed',
        'startTime' => 'completed',
        'endTime' => 'completed',
        'gameCode' => 'gameId',
    ];
}