<?php

namespace Logic\GameApi\CKFormat;


class PG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 76;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_pg';

    protected $table_fields = [
        'gameCode' => 'gameCode',
    ];
}