<?php

namespace Logic\GameApi\CKFormat;

/**
 * AWC游戏聚合平台
 */
class AWC extends CKFORMAT
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 0;
    /**
     * AE电子订单表
     * @var string
     */
    public $order_table = '';
    public $field_category_name = '';
    public $field_category_value = '';

    public $table_fields = [
        'orderNumber' => 'platformTxId',
        'userName' => 'userId',
        'betAmount' => 'betAmount*100',
        'validAmount' => 'betAmount*100',
        'profit' => '(winAmount-betAmount)*100',
        'whereTime' => 'betTime',
        'startTime' => 'betTime',
        'endTime' => 'updateTime',
        'gameCode' => 'gameCode',
        'roundId' => "roundId",
        'gameName' => 'gameName'
    ];
}