<?php
namespace Logic\GameApi\CKFormat;

class JILI extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 62;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_jili';

    protected $field_category_name = 'GameCategoryId';

    protected $field_category_value = 1;//电子

    protected $table_fields = [
        'gameCode' => 'gameCode',
        'endTime' => 'SettlementTime',
    ];

}