<?php

namespace Logic\GameApi\CKFormat;


class DS88 extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 100;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_ds88';
    protected $game_name = 'DS88';
    protected $is_sport = true;

    protected $table_fields = [
        'orderNumber' => 'slug',
        'userName' => 'account',
        'betAmount' => 'bet_amount*100',
        'validAmount' => 'valid_amount*100',
        'profit' => 'net_income*100',
        'whereTime' => 'settled_at',
        'startTime' => 'bet_at',
        'endTime' => 'settled_at',
    ];

    protected $table_fields_ext = [
        'outcome' => 'side as outcome',
        'desc' => 'result as desc',
    ];

    public function getAllGameList()
    {
        return [];
    }

    protected function getResultDetail($data)
    {
        return $data['outcome'];
    }

    protected function getBetDetail($data)
    {
        return $data['desc'];
    }
}