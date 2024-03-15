<?php

namespace Logic\GameApi\CKFormat;


class SGMK extends CKFORMAT {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 81;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_sgmk';
    protected $field_category_name = 'categoryId';
    /**
     * @var string 游戏类型 SM AD FH BC
     */
    protected $field_category_value = "'SM'";

    protected $table_fields = [
        'orderNumber' => 'ticketId',
        'userName' => 'acctId',
        'betAmount' => 'betAmount*100',
        'validAmount' => 'betAmount*100',
        'profit' => 'winLoss*100',
        'whereTime' => 'ticketTime',
        'startTime' => 'ticketTime',
        'endTime' => 'ticketTime',
        'gameCode' => 'gameCode',
        'gameRoundId' => 'roundId',
    ];

    protected $user_account_is_upper = true;

}