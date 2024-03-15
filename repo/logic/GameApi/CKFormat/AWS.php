<?php

namespace Logic\GameApi\CKFormat;

/**
 * AWC游戏聚合平台
 * AE电子
 * Class AWS
 */
class AWS extends AWC {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 106;
    /**
     * AE电子订单表
     * @var string
     */
    public $order_table = 'game_order_aws';

    public $field_category_name = 'gameType';
    public $field_category_value = "'SLOT'";
}