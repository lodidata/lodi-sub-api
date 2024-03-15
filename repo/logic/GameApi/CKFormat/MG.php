<?php

namespace Logic\GameApi\CKFormat;


class MG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 118;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_mg';
    protected $field_category_name = 'game_id';
    protected $field_category_value = 118;
    protected $table_fields = [
        'orderNumber' => 'betUID',
        'userName' => 'playerId',
        'betAmount' => 'betAmount',
        'validAmount' => 'betAmount',
        'profit' => 'payoutAmount-betAmount',
        'whereTime' => 'createdTime',
        'startTime' => 'createdTime',
        'endTime' => 'gameEndTime',
        'gameCode' => 'gameCode',
    ];

}