<?php
namespace Logic\GameApi\CKFormat;

/**
 * SBO体育
 * Class SBO
 */
class SBO extends CKFORMAT
{
    /**
     * 赔率种类
     */
    const API_ODDS_TYPE = [
        'M' => 'Malay odds',
        'H' => 'Hong Kong odds',
        'E' => 'Euro odds',
        'I' => 'Indonesia odds'
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
        'running' => 'running',
    ];

    /**
     * 分类ID
     * @var int
     */
    protected $game_id = 72;
    /**
     * 订单表
     * @var string
     */
    protected $order_table = 'game_order_sbo';

    protected $table_fields = [
        'orderNumber' => 'refNo',
        'userName' => 'username',
        'betAmount' => 'stake*100',
        'validAmount' => 'stake*100',
        'profit' => 'winlost*100',
        'whereTime' => 'orderTime',
        'startTime' => 'orderTime',
        'endTime' => 'settleTime',
        'gameName' => 'sportsType',
    ];

    protected $table_fields_ext = [
        'oddsStyle' => 'oddsStyle',
        'status' => 'status',
        'subBet' => 'subBet',
    ];

    public function getAllGameList()
    {
        return [];
    }

    protected function getExtDetail($data)
    {
        return [
            [
                'key' => $this->ci->lang->text('Types of odds') . ':',
                'value' => $this->ci->lang->text(self::API_ODDS_TYPE[$data['oddsStyle']])
            ],
            [
                'key' => $this->ci->lang->text('Award time') . ':',
                'value' => $data['send_time'] ? $data['send_time'] : $this->ci->lang->text("No award")
            ]
        ];
    }

    /**
     * 比赛结果
     * @param $data
     * @return array
     */
    protected function getResultDetail($data)
    {
        $decode_list = json_decode($data['subBet'], true);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            $return_list[] = ['key' => $this->ci->lang->text('Note results') . ':', 'value' => self::API_ODDS_STATE_TYPE[$decode['status']]];
            $return_list[] = ['key' => $this->ci->lang->text('match') . ':', 'value' => $decode['match']];
            $return_list[] = ['key' => $this->ci->lang->text('Home team score (first half)') . ':', 'value' => $decode['htScore']];
            $return_list[] = ['key' => $this->ci->lang->text('Away team score (second half)') . ':', 'value' => $decode['ftScore']];

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
        $decode_list = json_decode($data['subBet'], true);
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