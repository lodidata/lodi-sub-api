<?php
namespace Logic\GameApi\CKFormat;

/**
 * SA真人
 * Class SA
 */
class SA extends CKFORMAT {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 73;
    /**
     * PP电子订单表
     * @var string
     */
    public $order_table = 'game_order_sa';

    protected $game_name = 'SA';

    public function getAllGameList()
    {
        return [];
    }

}