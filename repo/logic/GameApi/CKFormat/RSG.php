<?php
namespace Logic\GameApi\CKFormat;

class RSG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 150;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_rsg';

    protected $field_category_name = 'game_menu_id';

    protected $field_category_value = 150; //电子

    protected $table_fields = [
        'gameCode' => 'GameId', //游戏码
        'orderNumber' => 'SequenNumber', //单号
        'userName' => 'UserId', //账号
        'betAmount' => 'BetAmt', //投注
        'validAmount' => 'BetAmt', //实际投注
        'profit' => 'WinAmt-BetAmt', //盈亏
        'whereTime' => 'PlayTime', //搜索时间
        'startTime' => 'PlayTime', //投注时间
        'endTime' => 'PlayTime', //结算时间
    ];

}