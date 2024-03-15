<?php

namespace Logic\GameApi\CKFormat;


class PB extends CKFORMAT
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 114;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_pb';

    protected $table_fields = [
        'orderNumber' => 'wagerId',
        'userName' => 'loginId',
        'betAmount' => 'stake*100',
        'validAmount' => 'stake*100',
        'profit' => 'winLoss*100',
        'whereTime' => 'settleDateFm',
        'startTime' => 'wagerDateFm',
        'endTime' => 'settleDateFm',
        'gameName' => 'sport',
    ];

    protected $table_fields_ext = [
        'BetOdds' => 'odds as BetOdds',
        'oddsFormat' =>'oddsFormat',
        'Result' => 'result as Result',
    ];

    /**
     * 赔率种类
     */
    const API_ODDS_TYPE = [
        '4' => 'Malay odds',
        '2' => 'Hong Kong odds',
        '1' => 'Euro odds',
        '3' => 'Indonesia odds',
        '0' => 'USA odds',
    ];

    /**
     * 注单结果
     */
    const API_RESULT_TYPE = [
        '0' => 'draw',
        '1' => 'all win',
        '2' => 'all lose',
        '3' => 'win half',
        '4' => 'lose half',
        'DRAW' => 'draw',
        'WIN' => 'win',
        'LOSE' => 'lose',
        'winHalf' => 'win half',
        'loseHalf' => 'lose half',
    ];

    protected function getExtDetail($data)
    {
        return [
            [
                'key' => $this->ci->lang->text("Play name") . ':',
                'value' => $this->ci->lang->text(self::API_ODDS_TYPE[$data['oddsFormat']]),
            ],
            [
                'key' => $this->ci->lang->text('Betting Odds') . ':',
                'value' => $data['BetOdds']
            ],
            [
                'key' => $this->ci->lang->text('Note results') . ':',
                'value' => $data['Result'] ? $this->ci->lang->text(self::API_RESULT_TYPE[$data['Result']]): ''
            ],
        ];
    }
}