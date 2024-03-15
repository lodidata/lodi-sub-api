<?php

namespace Logic\GameApi\CKFormat;


class CG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 91;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_cg';

    protected $field_category_name = 'gameCategoryType';
    protected  $field_category_value = "'slot'";

    protected $table_fields = [
        'orderNumber' => 'SerialNumber',
        'userName' => 'ThirdPartyAccount',
        'betAmount' => 'BetMoney*100',
        'validAmount' => 'ValidBet*100',
        'profit' => '(MoneyWin-BetMoney+JackpotMoney)*100',
        'whereTime' => 'LogTime',
        'startTime' => 'LogTime',
        'endTime' => 'LogTime',
        'gameCode' => 'GameType',
    ];

}