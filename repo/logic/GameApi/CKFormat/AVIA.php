<?php

namespace Logic\GameApi\CKFormat;


class AVIA extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 96;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_avia';

    /**
     * 赔率种类
     */
    const API_ODDS_TYPE = [
        'MY' => 'Malay odds',
        'HK' => 'Hong Kong odds',
        'EU' => 'Euro odds',
        'IN' => 'Indonesia odds',
    ];
    /**
     * 订单状态
     */
    const API_ODDS_STATE_TYPE = [
        'None' => 'created',
        'Win' => 'pass',
        'Lose' => 'lose',
        'Revoke' => 'refund',
        'Cancel' => 'cancel',
    ];
    /**
     * 注单类型
     */
    const API_ORDER_TYPE = [
        'Single' => 'Single',
        'Combo' => 'Combo',
        'Smart' => 'Smart',
        'Anchor' => 'Anchor',
        'VisualSport' => 'VisualSport',
    ];

    protected $table_fields = [
        'orderNumber' => 'OrderID',
        'userName' => 'UserName',
        'betAmount' => 'BetAmount*100',
        'validAmount' => 'BetMoney*100',
        'profit' => 'Money*100',
        'whereTime' => 'CreateAt',
        'startTime' => 'CreateAt',
        'endTime' => 'RewardAt',
        'gameName' => 'Type',
    ];

    protected $table_fields_ext = [
        'Status' => 'Status',
        //赔率样式
        'OddsType' => 'OddsType',
        //赔率
        'Odds' => 'Odds',
        //详情
        'Details' => 'Details'
    ];


    protected function getExtDetail($data)
    {
        return [
            [
                'key' => $this->ci->lang->text('Status') . ':',
                'value' => $this->ci->lang->text(self::API_ODDS_STATE_TYPE[$data['Status']])
            ],
            [
                'key' => $this->ci->lang->text("Play name") . ':',
                'value' => $this->ci->lang->text(self::API_ODDS_TYPE[$data['OddsType']]),
            ],
            [
                'key' => $this->ci->lang->text('odds') . ':',
                'value' => $data['Odds']
            ],
        ];
    }


    public function getBetDetail($data)
    {
        $detail = json_decode($data['detail'], true);
        $return_list = [];
        foreach ($detail as $item) {
            foreach ($item as $key => $val) {
                if ($key == 'DetailID') {
                    $return_list[] = ['key' => $this->ci->lang->text('SubID') . ':', 'value' => $val];
                } elseif ($key == 'Category') {
                    $return_list[] = ['key' => $this->ci->lang->text('Game name') . ':', 'value' => $val];
                } elseif ($key == 'LeagueID') {
                    $return_list[] = ['key' => $this->ci->lang->text('LeagueID') . ':', 'value' => $val];
                } elseif ($key == 'League') {
                    $return_list[] = ['key' => $this->ci->lang->text('league') . ':', 'value' => $val];
                } elseif ($key == 'Match') {
                    $return_list[] = ['key' => $this->ci->lang->text('match') . ':', 'value' => $val];
                } elseif ($key == 'Bet') {
                    $return_list[] = ['key' => $this->ci->lang->text('game_market_name') . ':', 'value' => $val];
                } elseif ($key == 'Content') {
                    $return_list[] = ['key' => $this->ci->lang->text('Betting content') . ':', 'value' => $val];
                } elseif ($key == 'Anchor') {
                    $return_list[] = ['key' => $this->ci->lang->text('Anchor') . ':', 'value' => $val];
                } elseif ($key == 'Code') {
                    $return_list[] = ['key' => $this->ci->lang->text('Game name') . ':', 'value' => $val];
                } elseif ($key == 'Index') {
                    $return_list[] = ['key' => $this->ci->lang->text('avia.Index') . ':', 'value' => $val];
                } elseif ($key == 'Player') {
                    $return_list[] = ['key' => $this->ci->lang->text('Play name') . ':', 'value' => $val];
                }
            }
        }
        return $return_list;
    }
}