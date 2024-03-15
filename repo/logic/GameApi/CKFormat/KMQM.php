<?php

namespace Logic\GameApi\CKFormat;


class KMQM extends CKFORMAT
{

    protected $game_type = 'KMQM';
    protected $game_id = 77;
    public $order_table = "game_order_kmqm";
    protected $table_fields = [
        'orderNumber' => 'ugsbetid',
        'userName' => 'userid',
        'betAmount' => 'riskamt*100',
        'validAmount' => 'riskamt*100',
        'profit' => 'winloss*100',
        'whereTime' => 'betupdatedon',
        'startTime' => 'beton',
        'endTime' => 'betupdatedon',
        'gameRoundId' => 'roundid',
        'gameName' => 'gamename',
    ];
}