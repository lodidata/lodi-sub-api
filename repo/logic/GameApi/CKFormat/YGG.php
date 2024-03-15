<?php

namespace Logic\GameApi\CKFormat;

/**
 * YGG电子
 * Class YGG
 * @package Logic\GameApi\Format
 */
class YGG extends CKFORMAT
{

    public $game_id = 108;
    public $order_table = "game_order_ygg";
    public $game_type = 'YGG';

    protected $table_fields = [
        'orderNumber' => 'reference',
        'userName' => 'loginname',
        'betAmount' => 'amount*100',
        'validAmount' => 'amount*100',
        'profit' => 'profit*100',
        'whereTime' => 'createTime',
        'startTime' => 'createTime',
        'endTime' => 'createTime',
        'gameCode' => 'DCGameID',
        'gameName' => 'gameName'
    ];
}
