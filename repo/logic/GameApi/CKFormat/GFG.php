<?php
namespace Logic\GameApi\CKFormat;

/**
 * GFG捕鱼
 * Class GFG
 */
class GFG extends CKFORMAT
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 136;
    /**
     * PP电子订单表
     * @var string
     */
    public $order_table = 'game_order_gfg';
    protected $field_category_name = 'game_menu_id';
    protected $field_category_value = 136;

    protected $table_fields = [
        'gameCode' => 'gameCode',
    ];

}