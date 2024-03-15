<?php

namespace Logic\GameApi\CKFormat;

/**
 * DS88DJ
 * Class DS88DJ
 */
class DS88DJ extends CKFORMAT
{
    public $game_id = 184;
    public $order_table = "game_order_ds88dj";

    protected $field_category_name = 'gameType';
    protected $field_category_value = "'SABONG'";

    protected $table_fields = [
        'orderNumber' => 'orderNumber',
        'userName'    => 'userId',
        'betAmount'   => 'betAmount*100',
        'validAmount' => 'betAmount*100',
        'profit'      => 'profit*100',
        'whereTime'   => 'betEndTime',
        'startTime'   => 'betTime',
        'endTime'     => 'betEndTime',
        'gameCode'    => 'gameCode',
    ];
}