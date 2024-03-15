<?php
namespace Logic\GameApi\CKFormat;

/**
 * CQ9电子
 * Class CQ9
 */
class CQ9 extends CKFORMAT
{
    public $game_id = 74;
    public $order_table = "game_order_cq9";

    protected $field_category_name = 'gametype';
    protected $field_category_value = "'slot'";

    protected $table_fields = [
        'orderNumber' => 'round',
        'userName' => 'account',
        'betAmount' => 'bet',
        'validAmount' => 'validbet',
        'profit' => '(win-bet)',
        'whereTime' => 'createtime',
        'startTime' => 'createtime',
        'endTime' => 'createtime',
        'gameRoundId' => 'tableid',
        'gameCode' => 'gamecode',
    ];
}