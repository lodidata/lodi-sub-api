<?php

namespace Logic\GameApi\CKFormat;
/**
 * ALLBET真人
 * Class ALLBET
 * @package Logic\GameApi\Format
 */
class ALLBET extends CKFORMAT {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 134;
    /**
     * PP电子订单表
     * @var string
     */
    public $order_table = 'game_order_allbet';

    protected $table_fields = [
        'endTime' => 'gameEndTime',
        'validAmount' => 'validAmount',
    ];

    protected $game_name = 'ALLBET';


    /**
     * 获取全部游戏列表
     */
    public function getAllGameList(){
        return [];
    }
}