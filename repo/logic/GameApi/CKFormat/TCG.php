<?php
namespace Logic\GameApi\CKFormat;

/**
 * TCG彩票
 * Class TCG
 */
class TCG extends TCP
{
    public $game_id = 144;
    public $order_table = "game_order_tcg";

    protected $table_fields = [
        // 订单号
        'orderNumber' => 'orderNum',
        //账号
        'userName' => 'username',
        //单注金额
        'one_money' => 'betAmount*100',
        // 投注额
        'betAmount' => 'betAmount*100',
        // 有效投注
        'validAmount' => 'actualBetAmount*100',
        // 中奖金额
        'winAmount' => 'winAmount*100',
        //盈亏
        'profit' => 'netPNL*100',
        //搜索时间
        'whereTime' => 'betTime',
        //投注时间
        'startTime' => 'betTime',
        // 游戏结算
        'endTime' => 'settlementTime',
        //游戏代码
        'gameCode' => 'gameCode',
        //来源，1:pc, 2:h5, 3:移动端
        'origin' => 'device',
        //期号
        'lottery_number' => 'numero',
        //彩种
        'lottery_name' => 'remark',
        //注数
        'bet_num' => 'betNum',
        //倍数
        'times' => 'multiple',
        //状态
        'state' => 'betStatus',
        //赔率
        'odds' => 'playBonus',
        //开奖号
        'open_code' => 'winningNumber',
        //玩法ID
        'play_id' => 'playId',
        //玩法
        'play_name' => 'playName',
        //玩法组
        'play_group' => 'gameGroupName',
        //选号
        'play_number' => 'bettingContent',
    ];

    /**
     * 终端显示
     * @param $origin
     * @return mixed|string
     */
    protected function getOrigin($origin)
    {
        return $origin=='MTH' ? 'h5' : 'web';
    }

    /**
     * 注单内容转换
     * @param $data
     * @return mixed
     */
    protected function getBetCountent($data)
    {
        return $data['play_number'];
    }

    /**
     * 注单状态
     * @param $status
     * @return mixed
     */
    protected function resultStatus($status)
    {
        $statusArr = [
            '1' => 'order.pass',
            '2' => 'order.lose',
            '3' => 'order.cancel',
            '4' => 'order.draw',
        ];

        return $statusArr[$status];
    }
}