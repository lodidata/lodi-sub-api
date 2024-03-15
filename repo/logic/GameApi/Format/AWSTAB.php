<?php
/**
 * User: ny
 * Date: 2019-05-04
 * Time: 16:01
 * Des :
 */

namespace Logic\GameApi\Format;

/**
 * AWS-TABLE
 * Class AWS
 * @package Logic\GameApi\Format
 */
class AWSTAB extends AWS {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 107;
    /**
     * AE电子订单表
     * @var string
     */
    public $order_table = 'game_order_aws';

    public $game_table_type = "'TABLE','EGAME'";
}