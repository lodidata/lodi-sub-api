<?php

namespace Logic\GameApi\CKFormat;

/**
 * BNG电子
 * Class BNG
 */
class BNG extends CKFORMAT
{

    public $game_id = 99;
    public $order_table = "game_order_bng";
    public $table_fields = [
        'orderNumber' => 'round',
        'userName' => 'username',
        'betAmount' => 'betAmount',
        'validAmount' => 'betAmount',
        'profit' => 'income',
        'whereTime' => 'gameDate',
        'startTime' => 'gameDate',
        'endTime' => 'gameDate',
        'gameCode' => 'game_id',
        'gameName' => 'game_name'
    ];
}
