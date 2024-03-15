<?php

namespace Logic\GameApi\CKFormat;

/**
 * FACHAI
 * Class FACHAI
 */
class FACHAI extends CKFORMAT
{
    public $game_id = 171;
    public $order_table = "game_order_fachai";

    protected $field_category_name = 'gameType';
    protected $field_category_value = "'SLOT'";

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