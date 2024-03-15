<?php

namespace Logic\GameApi\Format;

class TCG
{
    /**
     * 分类ID
     * @var int
     */
    public $game_id = 144;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_tcg';

    public $game_type = 'LOTTO';

    //注单状态
    protected $resultStatus = [
        '1' => 'order.pass',
        '2' => 'order.lose',
        '3' => 'order.cancel',
        '4' => 'order.draw',
    ];

    /**
     * 将订单状态 转换为前端可以查看的
     * @param float $profit 盈亏
     * @param string $gameEndTime 游戏结束时间
     * @return array
     */
    public function parseRecordsState(float $profit, string $gameEndTime)
    {
        global $app;
        $ci = $app->getContainer();
        $result = [];
        if ($gameEndTime == null || strlen($gameEndTime) == 0) {
            $result['state'] = $ci->lang->text("state.created");
        } else if ($profit > 0) {
            $result['state'] = $ci->lang->text("state.winning");
        } else {
            $result['state'] = $ci->lang->text("state.close");
        }
        // 模式
        $result['mode'] = $ci->lang->text("Third party");
        return $result;
    }

    /**
     * 获取全部游戏列表
     */
    public function getAllGameList()
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $list = $redis->hGet('game_list_format', $this->game_id);
        if (!is_null($list)) {
            return json_decode($list, true);
        }
        $list = \DB::connection('slave')->table('game_3th')
            ->where('game_3th.game_id', '=', $this->game_id)
            ->select(['id as game_id', 'game_name', 'kind_id', 'game_img'])->orderBy('sort')->get()->toArray();
        $redis->hSet('game_list_format', $this->game_id, json_encode($list, JSON_UNESCAPED_UNICODE));
        $redis->expire('game_list_format', 84600);
        return $list;
    }

    /**
     * 过滤条件详情
     * @param bool $isShowState
     * @return array
     */
    public function filterDetail(bool $isShowState)
    {
        global $app;
        $ci = $app->getContainer();
        // 过滤彩种
        $filter = [];
        $data = self::getAllGameList();
        $filter[] = ['key' => '', 'name' => $ci->lang->text('All')];
        foreach ($data ?? [] as $v) {
            $v = (array)$v;
            $filter[] = [
                // id
                'key' => (string)$v['kind_id'],
                // 游戏名
                'name' => $v['game_name'],
            ];
        }
        if ($isShowState) {
            // 状态
            $state = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            $result[] = [
                'title' => $ci->lang->text('State'),
                'category' => 'state',
                'list' => $state,
            ];
        }
        $result[] = [
            'title' => $ci->lang->text('Game type'),
            'category' => 'kind_id',
            'list' => $filter,
        ];
        return $result;
    }

    /**
     * 查看订单记录
     * @param $condition
     * @return mixed [type]            [description]
     */
    private function getRecord($condition)
    {
        //  插入查询
        $table = \DB::connection('slave')->table($this->order_table);

        // 状态
        if (isset($condition['state'])) {
            switch ($condition['state']) {
                case 'winning':
                    $table->where('netPNL', '>', '0');
                    break;
                case 'lose':
                    $table->where('netPNL', '<=', '0');
                    break;
                default:
                    break;
            }
        }
        // last_id 筛选
        isset($condition['last_id']) && $table->where('id', '>', $condition['last_id']);
        // id 筛选
        isset($condition['id']) && $table->where('id', $condition['id']);
        // 遊戲類型
        isset($condition['kind_id']) && $table->where('gameCode', $condition['kind_id']);
        //期号
        isset($condition['lottery_number']) && $table->where('numero', $condition['lottery_number']);
        //来源
        isset($condition['order_origin']) && $table->where('device', $condition['order_origin']);

        // 订单筛选
        if (isset($condition['order_number'])) {
            $table->where('orderNum', $condition['order_number']);
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $table->where('betTime', '>=', $condition['start_time']);
            } else {
                $table->where('betTime', '>=', date('Y-m-d'));
            }
            if (isset($condition['end_time']) && $condition['end_time']) {
                $table->where('betTime', '<=', $condition['end_time']);
            } else {
                $table->where('betTime', '<=', date('Y-m-d H:i:s'));
            }
        }

        //查用户
        if (isset($condition['user_name'])) {
            $user_id = \DB::connection('slave')->table('user')->where('name', $condition['user_name'])->value('id');
            $table->where('user_id', $user_id);
        } elseif (isset($condition['user_agent_ids'])) {
            $table->whereIn('user_id', $condition['user_agent_ids']);
        } elseif (isset($condition['user_id'])) {
            $table->where('user_id', intval($condition['user_id']));
        }

        // 统计相关,只要几个简单字段
        if (isset($condition['statistics'])) {
            // 统计
            $select = [
                // 总下注
                'all_bet' => \DB::raw("sum(betAmount) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(betAmount) as total_available_bet"),
                //派彩金额
                'send_prize' => \DB::raw("sum(netPNL) as total_send_prize"),
                // 用户数量
                'user_id' => \DB::raw("count(DISTINCT user_id) as total_user_count"),
                //总数
                'order_count' => \DB::raw("count(0) as total_order_count"),
            ];
        } else {
            // 常规操作
            $select = [
                // 订单详细id
                'id' => 'id',
                // 订单号
                'order_number' => 'orderNum as order_number',
                // 用户id
                'user_id' => 'user_id',
                // 投注额
                'betAmount' => 'betAmount',
                // 有效投注
                'validBet' => 'actualBetAmount as validAmount',
                // 中奖金额
                'winAmount' => 'winAmount',
                //盈亏
                'profit' => 'netPNL as profit',
                //投注时间
                'gameStartDate' => 'betTime as gameStartDate',
                // 游戏结算
                'gameEndDate' => 'settlementTime as gameEndDate',
                //游戏代码
                'kind_id' => 'gameCode as kind_id',
                //来源，1:pc, 2:h5, 3:移动端
                'origin' => 'device as origin',
                //期号
                'lottery_number' => 'numero as lottery_number',
                //彩种
                'lottery_name' => 'remark as lottery_name',
                //注数
                'bet_num' => 'betNum as bet_num',
                //倍数
                'times' => 'multiple as times',
                //状态
                'state' => 'betStatus as state',
                //赔率
                'odds' => 'playBonus as odds',
                //开奖号
                'open_code' => 'winningNumber as open_code',
                //玩法ID
                'play_id' => 'playId as play_id',
                //玩法
                'play_name' => 'playName as play_name',
                //玩法组
                'play_group' => 'gameGroupName as play_group',
                //选号
                'play_number' => 'bettingContent as play_number',
            ];

            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'betTime';
                switch ($condition['orderby']) {
                    case 'pay_money':
                        $order_field = 'betAmount';
                        break;
                    case 'CellScore':
                        $order_field = 'validBet';
                        break;
                    case 'Profit':
                        $order_field = 'profit';
                        break;
                }
                $table->orderBy($order_field, isset($condition['orderbyasc']) && $condition['orderbyasc'] == 'asc' ? 'asc' : 'desc');
            }
        }

        $table = $table->select(array_values($select));
        return $table;
    }

    /**
     * 查询订单详情
     * @param $condition
     * @return mixed
     */
    public function getRecordInfo($condition)
    {
        $table = $this->getRecord($condition);
        return $table->first();
    }

    /**
     * 查询订单记录
     * @param  [type]  $condition [description]
     * @param integer $page [页]
     * @param integer $pageSize [每页几项]
     * @return mixed [type]            [description]
     */
    public function getRecords($condition, $page = 1, $pageSize = 20)
    {
        $table = $this->getRecord($condition);
        return $table->forPage($page, $pageSize)->get();
    }

    /**
     * 查询全部订单
     * @param $condition
     * @return mixed
     */
    public function getRecordAll($condition)
    {
        $table = $this->getRecord($condition);
        return $table->get();
    }

    /**
     * 统计信息
     * @param array $condition
     * @return array
     */
    public function statistics(array $condition)
    {
        $data = $this->getRecordInfo($condition);
        $result = [
            'all_bet' => bcmul($data->total_bet, 100, 0),
            'cell_score' => bcmul($data->total_available_bet, 100, 0),
            'user_count' => $data->total_user_count,
            'send_prize' => bcmul($data->total_send_prize, 100, 0),
            'total_count' => $data->total_order_count,
        ];
        return $result;
    }


    /**
     * 订单列表
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function records($condition, $page = 1, $pageSize = 20)
    {
        global $app;
        $userLogic = new \Logic\User\User($app->getContainer());
        // 订单记录
        $records = $this->getRecords($condition, $page, $pageSize);
        $data = $records->toArray();
        $result = [];

        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = ($v['play_group']? ($v['play_group'] . '-') :'') . ($v['lottery_name'] ?? '-');
            // 座位号
            $result[$k]['TableID'] = $v['lottery_number'];
            //盈亏
            $result[$k]['profit'] = $v['profit'] ? bcmul($v['profit'], 100, 0) : 0;
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = bcmul($v['betAmount'], 100, 0);
            // 显示用户名
            $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['gameStartDate'];

            // 模式
            $result[$k]['mode'] = $app->getContainer()->lang->text("Third party");
            //状态
            $result[$k]['state'] = $app->getContainer()->lang->text($this->resultStatus[$v['state']]) ?: $app->getContainer()->lang->text("Unsettled");

        }
        return $result;

    }


    /**
     * 订单列表 -> admin
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function recordsAdmin($condition, $page = 1, $pageSize = 20)
    {
        global $app;
        $userLogic = new \Logic\User\User($app->getContainer());
        // 订单记录
        $records = $this->getRecords($condition, $page, $pageSize);
        $data = $records->toArray();
        // 列表详情
        $isShowUsername = isset($condition['user_agent_ids']);
        $result = [];

        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 显示用户名
            $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            // 投注时间
            $result[$k]['created'] = $v['gameStartDate'];
            // 来源
            $result[$k]['origin'] = $v['origin']=='MTH' ? 'h5' : 'web';
            // 彩种
            $result[$k]['lottery_name'] = ($v['play_group']? ($v['play_group'] . '-') :'') . ($v['lottery_name'] ?? '-');
            //期号
            $result[$k]['lottery_number'] = $v['lottery_number'];
            // 玩法id
            $result[$k]['play_name'] = $v['play_name'];
            // 玩法名
            $result[$k]['play_id'] = $v['play_id']??0;
            //赔率
            $result[$k]['odds'] = $v['odds'];
            //注数/倍数
            $result[$k]['bet_num'] = $v['bet_num'];
            //单注金额
            $result[$k]['one_money'] = isset($v['one_money']) ? bcmul($v['one_money'], 100, 0) : bcmul($v['betAmount'], 100, 0);
            //总投注金额
            $result[$k]['pay_money'] = bcmul($v['betAmount'], 100, 0);
            //开奖号码
            $result[$k]['open_code'] = $v['open_code']?? '-';
            //派奖金额
            $result[$k]['p_money'] = $v['winAmount'] ? bcmul($v['winAmount'], 100, 0) : 0;
            //选号
            $result[$k]['play_number'] = $v['play_number'];
            //倍数
            $result[$k]['times'] = $v['times'] ?? 1;
            //输赢
            $result[$k]['lose_earn'] = bcmul($v['profit'], 100, 0);
            // 模式
            $result[$k]['mode'] = $app->getContainer()->lang->text("Third party");
            //状态
            $result[$k]['state2'] = $app->getContainer()->lang->text($this->resultStatus[$v['state']]) ?: $app->getContainer()->lang->text("Unsettled");
        }
        return $result;
    }

}