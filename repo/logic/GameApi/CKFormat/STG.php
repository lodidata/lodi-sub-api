<?php

namespace Logic\GameApi\CKFormat;


class STG extends CKFORMAT
{

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 115;
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
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_stg';

    /**
     * 注单结果
     */
    const API_RESULT_TYPE = [
        '0' => 'draw',
        '1' => 'New',
        '2' => 'Winner',
        '3' => 'Lost',
        '4' => 'Return',
        '5' => 'Half-return',
        '6' => 'Half-win',
        '7' => 'Rejected',
        '8' => 'Initial Return',
        '9' => 'Initial Half Return',
        '10' => 'Initial Half Won'
    ];

    protected $table_fields = [
        'orderNumber' => 'OrderNumber',
        'userName' => 'ClientID',
        'betAmount' => 'Amount*100',
        'validAmount' => 'UsedAmount*100',
        'profit' => 'profit*100',
        'whereTime' => 'DateUpdated',
        'startTime' => 'FillDate',
        'endTime' => 'DateUpdated',
    ];

    protected $table_fields_ext = [
        'BetDetail' => 'BetDetail'
    ];

    protected $game_name = 'STG';

    protected $is_sport = true;

    /**
     * 获取全部游戏列表
     */
    public function getAllGameList()
    {
        return [];
    }


    /**
     * 查看投注详情
     * @param $data
     * @return array
     */
    public function getBetDetail($data)
    {
        $decode_list = json_decode($data['BetDetail']);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $return_list[] = ['key' => $this->ci->lang->text("Sheet %s", [$key]), 'value' => '=== ' . $this->ci->lang->text("Bet details") . ' ==='];
            $decode = (array)$decode;
            foreach ($decode as $k1 => $v1) {
                if($k1=='BetStakeAmount'){
                    $k1 = 'BetValue';
                }elseif($k1 == 'StakeName_en'){
                    $k1 = 'Stage';
                }elseif($k1=='BetNumber'){
                    $k1 = 'SubID';
                }elseif($k1=='EventDate'){
                    $k1 = 'Event Date';
                }elseif($k1 == 'SportName_en'){
                    $k1='betOption';
                }elseif($k1 == 'StakeName_en'){
                    $k1 = 'hdp';
                }elseif($k1 == 'EventName_en'){
                    $k1 = 'Event Name';
                }
                if($k1=='StakeStatus'){
                    $return_list[] = ['key' => $this->ci->lang->text('Status') . ':', 'value' => self::API_RESULT_TYPE[$v1]];
                }else{
                    $return_list[] = ['key' => $this->ci->lang->text($k1) . ':', 'value' => $v1];
                }
            }
        }
        return $return_list;
    }

}