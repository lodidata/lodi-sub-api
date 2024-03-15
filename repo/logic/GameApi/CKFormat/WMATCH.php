<?php

namespace Logic\GameApi\CKFormat;


class WMATCH extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 140;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_wmatch';
    public $field_category_name = 'gameTypeId';
    public $field_category_value = "140";
    protected $table_fields = [
        'orderNumber' => 'roundId',
        'userName' => 'externalUserId',
        'betAmount' => 'totalBetAmount',
        'validAmount' => 'totalBetAmount',
        'profit' => 'totalWinAmount-totalBetAmount',
        'whereTime' => 'roundEndTime',
        'startTime' => 'roundEndTime',
        'endTime' => 'roundEndTime',
        'gameCode' => 'gameIdentify',
    ];

}