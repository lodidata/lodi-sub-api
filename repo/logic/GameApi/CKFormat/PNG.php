<?php

namespace Logic\GameApi\CKFormat;


class PNG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 68;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_png';

    protected $table_fields = [
        'gameCode' => 'gameCode',
    ];
}