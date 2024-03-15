<?php
namespace Logic\GameApi\Order;
use Logic\Logic;

/**
 * 单个表游戏类型模型
 *
 * Class AbsOrder
 * @package Logic\GameApi\Order
 */
class AbsOrder extends Logic
{
    /**
     * @var string 统计中间表
     */
    public $order_middle_table = 'order_game_user_middle';

    /**
     * 订单表
     * @var string
     */
    public $order_table = '';

    /**
     * 类ID
     * @var int
     */
    public $game_id = 0;

    /**
     * 游戏类型 game_menu表 type字段
     * @var string
     */
    public $game_type = '';

    /**
     * 游戏类型组JILI,JILIBY,JILIQP,JILILIVE
     * @var string
     */
    public $game_types = "''";

    /**
     * @var string 一级类类型GAME,LIVE,BY,TABLE,QP,SPORT,ESPORTS,ARCADE,SABONG
     */
    public $gameBigType = 'GAME';

    /**
     * @var string 游戏类型区分字段
     */
    public $game_type_field = '';

    /**
     * @var array 同注单多游戏类型
     */
    public $platformTypes = [

    ];


    /**
     * 经营报表详情中的详情统计
     * @param $sdate
     * @param $edate
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getOrderGroupUser($sdate, $edate, $page = 1, $size = 20)
    {
        $query = \DB::table($this->order_middle_table .' as o')
            ->leftJoin('user', 'user_id', 'user.id')
            ->where('o.date', '>=', $sdate)
            ->where('o.date', '<=', $edate)
            ->where('o.play_id', $this->game_id)
            ->groupBy('o.user_id');
        $total = clone $query;
        $data = $query->forPage($page, $size)->get([
            'o.user_id',
            'user.name AS user_name',
            \DB::raw('sum(o.num) bet_count'),
            \DB::raw('sum(o.bet) bet'),
            \DB::raw('sum(o.bet) valid_bet'),
            \DB::raw('sum(o.profit) win_loss'),
            \DB::raw('sum(o.send_money) send_prize'),
        ])->toArray();
        $attr = [
            'total' => $total->pluck('user_id')->count(),
            'number' => $page,
            'size' => $size,
        ];
        return ['attr' => $attr, 'data' => $data];
    }

    /**
     * orders补单
     */
    public function OrderRepair()
    {
        $sdate = date('Y-m-d', strtotime("-1 day"));
        $edate = $sdate . ' 23:59:59';
        $sql = "SELECT user_id,OCode,gameCode,gameDate,betAmount,winAmount,income";
        if($this->game_type_field){
            $sql .=",{$this->game_type_field}";
        }
        $sql .= " FROM {$this->order_table} WHERE gameDate >= '{$sdate}' AND gameDate <= '{$edate}' AND OCode NOT IN( SELECT order_number FROM orders WHERE orders.game_type IN ({$this->game_types}) AND orders.date = '{$sdate}') ;";
        echo $sql;
        echo PHP_EOL;
        $data = \DB::select($sql);
        foreach ($data as $value) {
            $value = (array)$value;
            //多分类游戏
            if($this->game_type_field){
                $tmp = [
                    'user_id' => $value['user_id'],
                    'game' =>  $this->platformTypes[$value[$this->game_type_field]]['game'],
                    'order_number' => $value['OCode'],
                    'game_type' => $this->platformTypes[$this->game_type_field]['type'],
                    'type_name' => $this->lang->text($this->platformTypes[$value[$this->game_type_field]]['type']),
                    'game_id' => $this->platformTypes[$value[$this->game_type_field]]['id'],
                    'bet' => $value['betAmount'],
                    'profit' => $value['income'],
                    'date' => $value['gameDate'],
                ];
            }else{
                $tmp = [
                    'user_id' => $value['user_id'],
                    'game' => $this->gameBigType,
                    'order_number' => $value['OCode'],
                    'game_type' => $this->game_type,
                    'type_name' => $this->lang->text($this->game_type),
                    'game_id' => $this->game_id,
                    'bet' => $value['betAmount'],
                    'profit' => $value['income'],
                    'date' => $value['gameDate'],
                ];
            }

            \Utils\MQServer::send(\Logic\GameApi\GameApi::$synchronousOrderCallback, $tmp);
        }
        echo count($data) . PHP_EOL;
    }

    /**
     * 超管游戏校验统计
     * @param $sdate
     * @param $edate
     * @return mixed
     */
    public function orderOverPipe($sdate, $edate)
    {
        $query = \DB::table($this->order_middle_table)
            ->where('date', '>=', $sdate)
            ->where('date', '<=', $edate)
            ->where('play_id', $this->game_id);

        $res = $query->groupBy('date')->get([
            \DB::raw("date"),
            \DB::raw('sum(num) count'),
            \DB::raw('sum(betAmount) bet'),//下注金额
            \DB::raw('sum(betAmount) valid_bet'),//有效投注金额
            \DB::raw('sum(income) win_loss'),//派彩金额
        ])->toArray();
        $data['list'] = $res;
        $data['game_name'] = $this->lang->text($this->game_type);
        $data['game_type'] = $this->game_type;
        $data['user_prefix'] = $this->getUserPrefix();
        return $data;
    }

    /**
     * 游戏数据汇总
     * @param $sdate
     * @param $edate
     * @return array
     */
    public function querySumOrder($sdate, $edate)
    {
        $query = \DB::table($this->order_middle_table)
            ->where('date', '>=', $sdate)
            ->where('date', '<=', $edate)
            ->where('play_id', $this->game_id);
        $res = (array)$query->get([
            \DB::raw('sum(num) count'),
            \DB::raw('sum(betAmount) bet'),//下注金额
            \DB::raw('sum(betAmount) valid_bet'),//有效投注金额
            \DB::raw('sum(income) win_loss'),//派彩金额
        ])->first();

        return $res;
    }


    /**
     * 获取用户游戏统计
     * @param $sdate
     * @param $edate
     * @param $gameCode
     * @return array
     */
    public function queryUserSumOrder($sdate, $edate, $gameCode)
    {
        $query = \DB::table($this->order_table)
            ->where('gameCode', $gameCode)
            ->where('gameDate', '>=', $sdate)
            ->where('gameDate', '<=', $edate)
            ->groupBy('user_id');

        $res = (array)$query->get([
            \DB::raw('user_id'),
            \DB::raw('sum(betAmount) bet'),//下注金额
            \DB::raw('sum(betAmount) valid_bet'),//有效投注金额
            \DB::raw('sum(income) win_loss'),//盈亏金额
        ])->toArray();

        return $res;
    }

    public function getUserPrefix()
    {
        return 'game' . $this->ci->get('settings')['app']['tid'] . 'tes';
    }
}