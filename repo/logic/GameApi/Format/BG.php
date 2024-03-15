<?php

namespace Logic\GameApi\Format;


class BG
{

    protected $game_type = 'BG';
    protected $game_id = 112;
    protected $order_table = 'game_order_bg';
    protected $gameCategory = 'LIVE';

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
     * 过滤条件详情
     * @param bool $isShowState
     * @return array
     */
    public function filterDetail(bool $isShowState)
    {
        global $app;
        // 过滤彩种
        if ($isShowState) {
            // 状态
            $state = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            $result[] = [
                'title' => $app->getContainer()->lang->text("State"),
                'category' => 'state',
                'list' => $state,
            ];
        }
        $result[] = [];
        return $result;
    }

    /**
     * 获取全部游戏列表
     */
    public function getAllGameList() {
        global $app;
        $redis = $app->getContainer()->redis;
        $list = $redis->hGet('game_list_format', $this->game_id);
        if(!is_null($list)){
            return json_decode($list, true);
        }
        $list = \DB::connection('slave')->table('game_3th')
            ->where('game_3th.game_id', '=', $this->game_id)
            ->select(['id as game_id', 'game_name', 'kind_id','game_img'])->orderBy('sort')->get()->toArray();
        $redis->hSet('game_list_format', $this->game_id, json_encode($list, JSON_UNESCAPED_UNICODE));
        $redis->expire('game_list_format', 84600);
        return $list;
    }

    /**
     * 查看订单记录
     * @param $condition
     * @return mixed [type]            [description]
     */
    private function getRecord($condition)
    {
        //  插入查询
        $table = \DB::table($this->order_table)->where('gameCategory', $this->gameCategory);


        // 状态
        if (isset($condition['state'])) {
            switch ($condition['state']) {
                case 'winning':
                    $table->where('payment', '>', '0');
                    break;
                case 'lose':
                    $table->where('payment', '<=', '0');
                    break;
                default:
                    break;
            }
        }
        // last_id 筛选
        isset($condition['last_id']) && $table->where('id', '>', $condition['last_id']);
        // id 筛选
        isset($condition['id']) && $table->where('id', $condition['id']);
        // 订单筛选
        if (isset($condition['order_number'])) {
            $table->where('orderId', $condition['order_number']);
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $table->where('orderTime', '>=', $condition['start_time']);
            } else {
                $table->where('orderTime', '>=', date('Y-m-d'));
            }

            if (isset($condition['end_time']) && $condition['end_time']) {
                $table->where('orderTime', '<=', $condition['end_time']);
            } else {
                $table->where('orderTime', '<=', date('Y-m-d H:i:s'));
            }
        }

        //查用户
        if (isset($condition['user_name'])) {
            $user_id = \DB::table('user')->where('name', $condition['user_name'])->value('id');
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
                'all_bet' => \DB::raw("sum(bAmount) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(validBet) as total_available_bet"),
                //派彩金额
                'send_prize' => \DB::raw("sum(payment) as total_send_prize"),
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
                //订单号
                'order_number' => 'orderId as order_number',
                // 用户id
                'user_id' => 'user_id',
                // 返还金额
                'winLoss' => 'aAmount as winLoss',
                // 下注金额
                'betAmount' => 'bAmount as betAmount',
                //有效下注金额
                'validBet' => 'validBet',
                //盈利
                'profit' => 'payment as profit',
                // 下注时间
                'gameStartTime' => 'orderTime as gameStartTime',
                //结算时间
                'gameEndTime' => 'lastUpdateTime as gameEndTime',
                //游戏Id
                'kind_id' => 'gameId as kind_id',
                //游戏名称
                'gameName' => 'gameName',
            ];

            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'orderTime';
                switch ($condition['orderby']) {
                    case 'pay_money':
                        $order_field = 'betAmount';
                        break;
                    case 'CellScore':
                        $order_field = 'validBet';
                        break;
                    case 'Profit':
                        $order_field = 'winLoss';
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
        $table = self::getRecord($condition);
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
        $table = self::getRecord($condition);
        return $table->forPage($page, $pageSize)->get();
    }

    /**
     * 查询全部订单
     * @param $condition
     * @return mixed
     */
    public function getRecordAll($condition)
    {
        $table = self::getRecord($condition);
        return $table->get();
    }

    /**
     * 统计信息
     * @param array $condition
     * @return array
     */
    public function statistics(array $condition)
    {
        $data = self::getRecordInfo($condition);
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
        $records = self::getRecords($condition, $page, $pageSize);
        $data = $records->toArray();
        // 列表详情
        $isShowUsername = isset($condition['user_agent_ids']);
        $result = [];

        //游戏列表
        $gameList = $this->getAllGameList();
        $games = [];
        foreach ($gameList as $value){
            $value = (array)$value;
            $games[$value['kind_id']] = $value['game_name'];
        }

        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = $v['gameName']?: ($games[$v['kind_id']]?? 'BG');
            // 座位号
            $result[$k]['roundId'] = '';
            // 玩法名
            $result[$k]['play_name'] = 'BG';
            // 输赢
            $result[$k]['profit'] = bcmul($v['profit'], 100, 2);
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = bcmul($v['betAmount'], 100, 2);
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['gameStartTime'];
            // 描述
            $result[$k] += self::parseRecordsState($v['profit'], $v['gameStartTime']);
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
        $records = self::getRecords($condition, $page, $pageSize);
        $data = $records->toArray();
        $result = [];
        //游戏列表
        $gameList = $this->getAllGameList();
        $games = [];
        foreach ($gameList as $value){
            $value = (array)$value;
            $games[$value['kind_id']] = $value['game_name'];
        }
        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 用户id
            $result[$k]['user_id'] = $v['user_id'];
            // 订单号0
            $result[$k]['order_number'] = $v['order_number'];
            // 会员账号
            $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            // 投注时间
            $result[$k]['GameStartTime'] = $v['gameStartTime'];
            // 结算时间
            $result[$k]['GameEndTime'] = $v['gameEndTime'];
            // 游戏名
            $result[$k]['game_name'] =  $v['gameName']?: ($games[$v['kind_id']]?? 'BG');
            // 桌子号 > 椅子号
            $result[$k]['TableID'] = '';
            // 总投注金额
            $result[$k]['pay_money'] = bcmul($v['betAmount'], 100, 0);
            // 有效投注
            $result[$k]['CellScore'] = bcmul($v['betAmount'], 100, 0);
            // 派奖/输赢
            $result[$k]['Profit'] = bcmul($v['profit'], 100, 0);
            // 抽水
            $result[$k]['Revenue'] = '-';
        }
        return $result;
    }


    /**
     * 订单详情
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public function detail($condition)
    {
        global $app;
        $ci = $app->getContainer();
        $data = self::getRecordInfo($condition);
        if (!isset($data)) {
            return [];
        }

        //游戏列表
        $gameList = $this->getAllGameList();
        $game_img = $game_name = '';
        foreach ($gameList as $value){
            $value = (array)$value;
            if($data->kind_id == $value['kind_id']){
                $game_img = $value['game_img'];
                $game_name = $value['game_name'];
                break;
            }
        }

        $result = [
            // logo
            'logo' =>  $game_img,
            // 彩种名
            'game_name' => $data->gameName ?: $game_name,
            // 期号
            'sub_game_name' => '',
            // 投注金额 转为真实金额
            'pay_money' => bcmul($data->betAmount, 100, 0),
            // 中奖金额
            'send_money' => bcmul($data->profit, 100, 0),
        ];
        $result += self::parseRecordsState(bcmul($data->profit, 100, 0), $data->gameStartTime);
        // 方案详情
        $detail = [
            [
                'key' => $ci->lang->text("Play name") . ':',
                'value' => $data->gameName,
            ],
            [
                'key' => $ci->lang->text('Note No') . ':',
                'value' => $data->order_number
            ],
            [
                'key' => $ci->lang->text('Bet amount') . ':',
                'value' => $data->betAmount
            ],
            [
                'key' => $ci->lang->text('Profit and loss amount') . ':',
                'value' => $data->profit
            ],
            [
                'key' => $ci->lang->text('Game start time') . ':',
                'value' => $data->gameStartTime
            ],
            [
                'key' => $ci->lang->text('update time') . ':',
                'value' => $data->gameEndTime
            ],
        ];
        //游戏详情
        /* $gameInfo = $this->getBetDetail($this->platform, $data->gameInfo);
         if($gameInfo){
             foreach ($gameInfo as $key => $value){
                 $detail[][$key] = $value;
             }
         }*/
        $result['detail'] = $detail;
        return $result;
    }
}