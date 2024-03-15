<?php
/**
 * User: ny
 * Date: 2019-05-04
 * Time: 16:01
 * Des :
 */

namespace Logic\GameApi\CKFormat;

/**
 * EVORT
 * Class EVORT
 */
class EVORT extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 138;
    /**
     * EVORT订单表
     * @var string
     */
    public $order_table = 'game_order_evort';

    protected $table_fields = [
        'gameCode' => 'gameCode'
    ];

}