<?php
namespace Logic\GameApi\CKFormat;


class PP extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 64;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_pp';

    protected $field_category_name = 'dataType';
    protected $field_category_value = "'Slot'";

    protected $table_fields = [
        'gameCode' => 'gameCode',
    ];
}