<?php

namespace Logic\GameApi\CKFormat;

class YESBINGO extends CKFORMAT {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 147;
    /**
     * AE电子订单表
     * @var string
     */
    public $order_table = 'game_order_yesbingo';

    public $field_category_name = 'game_menu_id';
    public $field_category_value = "147";
    protected $table_fields = [
        'gameCode' => 'gameCode',
        'endTime' => 'lastModifyTime',
    ];
}