<?php
namespace Logic\GameApi\CKFormat;

/**
 * AWS-TABLE
 * Class AWS
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

    public $field_category_value = "'TABLE','EGAME'";
}