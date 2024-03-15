<?php

namespace Logic\GameApi\CKFormat;


class EVOPLAY extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 125;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_evoplay';
    protected $field_category_name = 'game_id';
    protected $field_category_value = 125;

    public $table_fields = [
        'gameCode' => 'gameCode',
    ];

}