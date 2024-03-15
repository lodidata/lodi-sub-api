<?php

namespace Logic\GameApi\CKFormat;

/**
 * AWC游戏聚合平台
 * SV388
 * Class SV388
 */
class SV388 extends AWC {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 79;
    /**
     * PP电子订单表
     * @var string
     */
    public $order_table = 'game_order_sv388';

    protected $game_name = 'SV388';

    public function getAllGameList()
    {
        return [];
    }
}