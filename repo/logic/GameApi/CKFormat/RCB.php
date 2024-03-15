<?php
namespace Logic\GameApi\CKFormat;

/**
 * RCB
 * Class RCB
 */
class RCB extends AWC {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 80;
    /**
     * PP电子订单表
     * @var string
     */
    public $order_table = 'game_order_rcb';

    protected $game_name = 'Horsebook';

    public function getAllGameList()
    {
        return [];
    }
}