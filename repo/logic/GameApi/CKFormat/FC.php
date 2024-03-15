<?php
namespace Logic\GameApi\CKFormat;


class FC extends CKFORMAT
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 93;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_fc';

    protected $field_category_name = "gametype";
    /**
     * @var int 2电子
     */
    protected $field_category_value = "2,7";

    protected $table_fields = [
        'orderNumber' => 'recordID',
        'userName' => 'account',
        'betAmount' => 'bet*100',
        'validAmount' => 'bet*100',
        'profit' => '(winlose+jppoints)*100',
        'whereTime' => 'bdate',
        'startTime' => 'bdate',
        'endTime' => 'bdate',
        'gameCode' => 'gameID',
    ];

}