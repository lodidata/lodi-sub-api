<?php

namespace Logic\GameApi\CKFormat;


class BTI extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 135;

    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_bti';

    protected $table_fields = [
        'orderNumber' => 'PurchaseID',
        'userName' => 'MerchantCustomerID',
        'betAmount' => 'TotalStake',
        'validAmount' => 'ValidStake',
        'profit' => 'PL',
        'whereTime' => 'CreationDate',
        'startTime' => 'CreationDate',
        'endTime' => 'BetSettledDate',
        'gameName' => 'BetTypeName',
    ];

    protected $table_fields_ext = [
        'Selections' => 'Selections',
        'BetOdds' => 'OddsInUserStyle as BetOdds',
        'Result' => 'Status as Result',
    ];


    protected function getExtDetail($data)
    {
        return [
            [
                'key' => $this->ci->lang->text('Betting Odds') . ':',
                'value' => $data['BetOdds']
            ],
            [
                'key' => $this->ci->lang->text('Note results') . ':',
                'value' => $data['Result']
            ],
        ];
    }

    /** 解析比赛结果
     * @param $data
     * @return array
     */
    protected function getResultDetail($data)
    {
        $decode_list = json_decode($data['Selections'], true);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            $return_list[] = ['key' => $this->ci->lang->text('Types of bets') . ':', 'value' => $decode['BetType']];
            $return_list[] = ['key' => $this->ci->lang->text('union') . ':', 'value' => $decode['LeagueName']];
            $return_list[] = ['key' => $this->ci->lang->text('Home team') . ':', 'value' => $decode['HomeTeam']];
            $return_list[] = ['key' => $this->ci->lang->text('Away team') . ':', 'value' => $decode['AwayTeam']];
            $return_list[] = ['key' => $this->ci->lang->text('Event start time') . ':', 'value' => $decode['EventDate']];
            $return_list[] = ['key' => $this->ci->lang->text('Final result (home team)') . ':', 'value' => $decode['Score']];
        }
        return $return_list;
    }

    /**
     * 查看投注详情
     * @param $data
     * @return array
     */
    protected function getBetDetail($data)
    {
        $decode_list = json_decode($data['Selections'], true);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            foreach ($decode as $k1 => $v1) {
                $return_list[] = ['key' => $this->ci->lang->text($k1) . ':', 'value' => $v1];
            }
        }
        return $return_list;
    }
}