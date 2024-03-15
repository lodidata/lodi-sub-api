<?php

namespace Logic\GameApi\CKFormat;

/**
 * XG视讯
 */
class XG extends CKFORMAT {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 109;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_xg';

    protected $table_fields = [
        'orderNumber' => 'WagersId',
        'userName' => 'user',
        'betAmount' => 'BetAmount*100',
        'validAmount' => 'validBetAmount*100',
        'profit' => 'PayoffAmount*100',
        'whereTime' => 'WagersTime',
        'startTime' => 'WagersTime',
        'endTime' => 'SettlementTime',
        'gameRoundId' => 'TableId',
        'gameCode' => 'GameId',
        'gameName' => 'GameType'
    ];

    public function getAllGameList()
    {
        return [];
    }

    /**
     * 游戏名称
     * @param $gameType
     * @return mixed|string
     */
    protected function getGameName($gameType) {
        $games = [
            '1' => 'Baccarat',
            '2' => 'Dice',
            '3' => 'Roulette',
            '5' => 'Dragon and tiger',
            '6' => 'Xoc Dia',
            '7' => 'Speed dice treasure',
        ];
        return $games[$gameType] ?? 'XG';
    }
}