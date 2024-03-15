<?php

namespace Logic\GameApi\Format;

/**
 * BSG
 */
class BSG {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 124;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_bsg';

    /**
     * 将订单状态 转换为前端可以查看的
     * @param float  $profit 盈亏
     * @param string $gameEndTime 游戏结束时间
     * @return array
     */
    public function parseRecordsState(float $profit, string $gameEndTime) {
        global $app;
        $ci     = $app->getContainer();
        $result = [];
        if($gameEndTime == null || strlen($gameEndTime) == 0) {
            $result['state'] = $ci->lang->text("state.created");
        } else if($profit > 0) {
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
    public function filterDetail(bool $isShowState) {
        global $app;
        $ci = $app->getContainer();
        // 过滤彩种
        $filter   = [];
        $filter[] = ['key' => '', 'name' => $ci->lang->text('All')];
        if($isShowState) {
            // 状态
            $state    = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            $result[] = [
                'title'    => $ci->lang->text('State'),
                'category' => 'state',
                'list'     => $state,
            ];
        }
        $result[] = [
            'title'    => $ci->lang->text('Game type'),
            'category' => 'kind_id',
            'list'     => $filter,
        ];
        return $result;
    }

    /**
     * 查看订单记录
     * @param $condition
     * @return mixed [type]            [description]
     */
    private function getRecord($condition) {
        $table = \DB::connection('slave')->table($this->order_table);

        // 状态
        if(isset($condition['state'])) {
            switch($condition['state']) {
                case 'winning':
                    $table->where('income', '>', '0');
                    break;
                case 'lose':
                    $table->where('income', '<=', '0');
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
        if(isset($condition['order_number'])) {
            $table->where('order_number', $condition['order_number']);
        } else {
            // 订单时间过滤
            if(isset($condition['start_time']) && $condition['start_time']) {
                $table->where('bettime', '>=', $condition['start_time']);
            } else {
                $table->where('bettime', '>=', date('Y-m-d'));
            }

            if(isset($condition['end_time']) && $condition['end_time']) {
                $table->where('bettime', '<=', $condition['end_time']);
            } else {
                $table->where('bettime', '<=', date('Y-m-d H:i:s'));
            }
        }

        //查用户
        if(isset($condition['user_name'])) {
            $user_id = \DB::connection('slave')->table('user')->where('name', $condition['user_name'])->value('id');
            $table->where('user_id', $user_id);
        } elseif(isset($condition['user_agent_ids'])) {
            $table->whereIn('user_id', $condition['user_agent_ids']);
        } elseif(isset($condition['user_id'])) {
            $table->where('user_id', intval($condition['user_id']));
        }

        // 统计相关,只要几个简单字段
        if(isset($condition['statistics'])) {
            // 统计
            $select = [
                // 总下注
                'all_bet'    => \DB::raw("sum(bet_amount) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(bet_amount) as total_available_bet"),
                // 用户数量
                'user_id'    => \DB::raw("count(DISTINCT user_id) as total_user_count"),
                //派彩金额
                'send_prize' => \DB::raw("sum(income) as total_send_prize"),
                //总数
                'order_count' => \DB::raw("count(0) as total_order_count"),
            ];
        } else {
            // 常规操作
            $select = [
                // 订单详细id
                'id'            => 'id',
                //订单号
                'order_number'  => 'order_number',
                // 用户id
                'user_id'       => 'user_id',
                // 返还金额
                'winLoss'       => 'win_amount as winLoss',
                // 下注金额
                'betAmount'     => 'bet_amount',
                //有效下注金额
                'validBet'      => 'bet_amount as validbet',
                //盈利
                'profit'        => 'income',
                // 下注时间
                'gameStartTime' => 'bettime',
                //游戏代码
                'gameCode'      => 'game_id as kind_id',
            ];


            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'bettime';
                switch($condition['orderby']) {
                    case 'pay_money':
                    case 'CellScore':
                        $order_field = 'bet_amount';
                        break;
                    case 'profit':
                        $order_field = 'income';
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
    public function getRecordInfo($condition) {
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
    public function getRecords($condition, $page = 1, $pageSize = 20) {
        $table = self::getRecord($condition);
        return $table->forPage($page, $pageSize)->get();
    }

    /**
     * 查询全部订单
     * @param $condition
     * @return mixed
     */
    public function getRecordAll($condition) {
        $table = self::getRecord($condition);
        return $table->get();
    }

    /**
     * 统计信息
     * @param array $condition
     * @return array
     */
    public function statistics(array $condition) {
        $data   = self::getRecordInfo($condition);
        $result = [
            'all_bet'    => $data->total_bet,
            'cell_score' => $data->total_available_bet,
            'user_count' => $data->total_user_count,
            'send_prize' => $data->total_send_prize,
            'total_count' => $data->total_order_count,
        ];
        return $result;
    }

    /**
     * 订单列表
     * @param     $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function records($condition, $page = 1, $pageSize = 20) {
        global $app;
        $ci   = $app->getContainer();
        $userLogic = new \Logic\User\User($app->getContainer());
        // 订单记录
        $records = self::getRecords($condition, $page, $pageSize);
        $data    = $records->toArray();
        // 列表详情
        $isShowUsername = isset($condition['user_agent_ids']);
        $result         = [];
        //游戏列表
        $gameList = $this->getAllGameList();
        $games = [];
        foreach ($gameList as $value){
            $value = (array)$value;
            $games[$value['kind_id']] = $value['game_name'];
        }

        foreach($data ?? [] as $k => $v) {
            $v                = (array)$v;
            $result[$k]       = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = $games[$v['kind_id']] ?? '-';
            // 玩法名
            $result[$k]['play_name'] = '-';
            // 输赢
            $result[$k]['profit'] = $v['income'];
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = $v['bet_amount'];
            // 显示用户名
            if($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['bettime'];
            // 描述
            $result[$k] += self::parseRecordsState($v['income'], $v['bettime']);
        }
        return $result;
    }

    /**
     * 订单列表 -> admin
     * @param     $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function recordsAdmin($condition, $page = 1, $pageSize = 20) {
        global $app;
        $ci   = $app->getContainer();
        $userLogic = new \Logic\User\User($app->getContainer());
        $records   = self::getRecords($condition, $page, $pageSize);
        $data      = $records->toArray();
        $result    = [];
        $gameList = $this->getAllGameList();
        $games = [];
        foreach ($gameList as $value){
            $value = (array)$value;
            $games[$value['kind_id']] = $value['game_name'];
        }

        foreach($data ?? [] as $k => $v) {
            $v                = (array)$v;
            $result[$k]       = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 用户id
            $result[$k]['user_id'] = $v['user_id'];
            // 订单号0
            $result[$k]['order_number'] = $v['order_number'];
            // 会员账号
            $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            // 投注时间
            $result[$k]['GameStartTime'] = $v['bettime'];
            // 结算时间
            $result[$k]['GameEndTime'] = $v['bettime'];
            // 游戏名
            $result[$k]['game_name'] = $games[$v['kind_id']] ?? '-';
            // 总投注金额
            $result[$k]['pay_money'] = $v['bet_amount'];
            // 有效投注
            $result[$k]['CellScore'] = $v['bet_amount'];
            // 派奖/输赢
            $result[$k]['Profit'] = $v['income'];
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
    public function detail($condition) {
        global $app;
        $ci   = $app->getContainer();
        $data = self::getRecordInfo($condition);
        if(!isset($data)) {
            return [];
        }

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
            'logo'          => $game_img,
            // 彩种名
            'game_name'     =>$game_name,
            // 投注金额 转为真实金额
            'pay_money'     => $data->bet_amount,
            // 中奖金额
            'send_money'    => $data->income,
        ];
        $result += self::parseRecordsState($data->income, $data->bettime);
        // 方案详情
        $detail = [
            [
                'key'   => $ci->lang->text("Play name") . ':',
                'value' => $game_name,
            ],
            [
                'key'   => $ci->lang->text('Note No') . ':',
                'value' => $data->order_number
            ],
            [
                'key'   => $ci->lang->text('Bet amount') . ':',
                'value' => bcdiv($data->bet_amount,100,2)
            ],
            [
                'key'   => $ci->lang->text('Profit and loss amount') . ':',
                'value' => bcdiv($data->income,100,2)
            ],
            [
                'key'   => $ci->lang->text('Game start time') . ':',
                'value' => $data->bettime
            ],
            [
                'key'   => $ci->lang->text('update time') . ':',
                'value' => $data->bettime
            ],
        ];
        $result['detail'] = $detail;
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
}