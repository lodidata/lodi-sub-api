<?php

namespace Logic\GameApi\CKFormat;


class UG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 95;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_ug';

    /**
     * 赔率种类
     */
    const API_ODDS_TYPE = [
        'MY' => 'Malay odds',
        'HK' => 'Hong Kong odds',
        'EU' => 'Euro odds',
        'ID' => 'Indonesia odds',
        'US' => 'USA odds',
        'BU' => 'Myanmar odds'
    ];
    /**
     * 订单状态
     */
    const API_ODDS_STATE_TYPE = [
        '' => 'created',
        'draw' => 'draw',
        'pass' => 'pass',
        'lose' => 'lose',
        'refund' => 'refund',
        'cancel' => 'cancel',
        'won' => 'won',
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
        'draw' => 'draw',
        'win' => 'win',
        'lose' => 'lose',
        'winHalf' => 'win half',
        'loseHalf' => 'lose half',
    ];

    protected $table_fields = [
        'orderNumber' => 'BetID',
        'userName' => 'Account',
        'betAmount' => 'BetAmount*100',
        'validAmount' => 'Turnover*100',
        'profit' => 'Win*100',
        'whereTime' => 'BetDate',
        'startTime' => 'BetDate',
        'endTime' => 'ReportDate',
        'gameCode' => 'GameID',
    ];

    protected $table_fields_ext = [
        'BetOdds' => 'BetOdds',
        'Result' => 'Result',
        'BetInfo' => 'BetInfo'
    ];


    protected $is_sport = true;

    /**
     * 获取全部游戏列表
     */
    public function getAllGameList()
    {
        return [];
    }

    protected function getExtDetail($data)
    {
        return [
            [
                'key' => $this->ci->lang->text('Betting Odds') . ':',
                'value' => $data['BetOdds']
            ],
            [
                'key' => $this->ci->lang->text('Note results') . ':',
                'value' => $this->ci->lang->text(self::API_RESULT_TYPE[$data['Result']])
            ],
        ];
    }

    /**
     * 比赛结果
     * @param $data
     * @return array
     */
    protected function getResultDetail($data)
    {
        return $data['Result'];
    }

    /**
     * 查看投注详情
     * @param $data
     * @return array
     */
    protected function getBetDetail($data)
    {
        $decode_list = json_decode($data['BetInfo']);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $return_list[] = ['key' => $this->ci->lang->text("Sheet %s", [$key + 1]), 'value' => '=== ' . $this->ci->lang->text("Bet details") . ' ==='];
            $decode = (array)$decode;
            foreach ($decode as $k1 => $v1) {
                $return_list[] = ['key' => $this->ci->lang->text($k1) . ':', 'value' => $v1];
            }
        }
        return $return_list;
    }
}