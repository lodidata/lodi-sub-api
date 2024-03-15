<?php
/**
 * User: ny
 * Date: 2019-05-04
 * Time: 16:01
 * Des :
 */

namespace Logic\GameApi\Format;

/**
 * AWC游戏聚合平台
 * SV388
 * Class SV388
 * @package Logic\GameApi\Format
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


}