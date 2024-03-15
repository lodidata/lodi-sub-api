<?php
namespace Logic\GameApi\CKFormat;


class JOKER extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 59;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_joker';

    protected $field_category_name = 'game_id';
    protected $field_category_value = 59;

    protected $table_fields = [
        'gameCode' => 'gameCode',
        'gameRoundId' => 'RoundID',
    ];
}