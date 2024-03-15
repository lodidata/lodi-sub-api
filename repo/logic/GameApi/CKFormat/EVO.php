<?php
/**
 * User: ny
 * Date: 2019-05-04
 * Time: 16:01
 * Des :
 */

namespace Logic\GameApi\CKFormat;

/**
 * EVO真人
 * Class EVO
 */
class EVO extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 67;
    /**
     * JILI订单表
     * @var string
     */
    public $order_table = 'game_order_evo';

    public $table_fields = [
        'gameCode' => 'gameCode',
    ];


}