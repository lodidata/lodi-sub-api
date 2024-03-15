<?php

namespace Logic\GameApi\CKFormat;

/**
 * IG电子
 * Class IG
 */
class IG extends CKFORMAT
{
    public $game_id = 102;
    public $order_table = "game_order_ig";
    protected $field_category_name = 'game_menu_id';
    protected $field_category_value = 102;
    protected $table_fields = [
        'gameCode' => 'gameCode',
    ];
}
