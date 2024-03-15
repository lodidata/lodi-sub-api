<?php

namespace Logic\GameApi\Format;


class STG
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
        return \DB::connection('slave')->table('game_3th')
            ->where('game_3th.game_id', '=', $this->game_id)
            ->select(['id as game_id', 'game_name', 'kind_id'])->orderBy('sort')->get()->toArray();
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
        /*$data = self::getAllGameList();
        $filter[] = ['key' => '', 'name' => $ci->lang->text('All')];
        foreach ($data ?? [] as $v) {
            $v = (array)$v;
            $filter[] = [
                // id
                'key' => (string)$v['kind_id'],
                // 游戏名
                'name' => $v['game_name'],
            ];
        }*/
        if ($isShowState) {
            // 状态
            $state = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            $result[] = [
                'title' => $ci->lang->text('State'),
                'category' => 'state',
                'list' => $state,
            ];
        }
        /*$result[] = [
            'title' => $ci->lang->text('Game type'),
            'category' => 'kind_id',
            'list' => $filter,
        ];*/
        $result[] = [];
        return $result;
    }

    /**
     * 查看订单记录
     * @param $condition
     * @return mixed [type]            [description]
     */
    private function getRecord($condition)
    {
        $table = \DB::connection('slave')->table($this->order_table);


        // 状态
        if (isset($condition['state'])) {
            switch ($condition['state']) {
                case 'winning':
                    $table->where('profit', '>', '0');
                    break;
                case 'lose':
                    $table->where('profit', '<=', '0');
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
            $table->where('OrderNumber', $condition['order_number']);
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $table->where('DateUpdated', '>=', $condition['start_time']);
            } else {
                $table->where('DateUpdated', '>=', date('Y-m-d'));
            }

            if (isset($condition['end_time']) && $condition['end_time']) {
                $table->where('DateUpdated', '<=', $condition['end_time']);
            } else {
                $table->where('DateUpdated', '<=', date('Y-m-d H:i:s'));
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
                'all_bet' => \DB::raw("sum(Amount) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(UsedAmount) as total_available_bet"),
                // 用户数量
                'user_id' => \DB::raw("count(DISTINCT user_id) as total_user_count"),
                //派彩金额
                'send_prize' => \DB::raw("sum(profit) as total_send_prize"),
                //总数
                'order_count' => \DB::raw("count(0) as total_order_count"),
            ];
        } else {
            // 常规操作
            $select = [
                // 订单详细id
                'id' => 'id',
                // 游戏局号列表 基本定义为 订单号
                'order_number' => 'OrderNumber as order_number',
                // 用户id
                'user_id' => 'user_id',
                // 中奖金额
                'winLoss' => 'WinAmount as winLoss',
                // 下注金额
                'betAmount' => 'Amount as betAmount',
                //有效下注金额
                'validBet' => 'UsedAmount as validBet',
                // 游戏结束时间列表
                'gameDate' => 'DateUpdated as gameDate',
                'BetDate' => 'FillDate as BetDate',
            ];

            if (isset($condition['info']) || isset($condition['admin_info'])) {
                //投注赔率
                //$select['BetOdds'] = 'odds as BetOdds';
                //赔率样式
               // $select['oddsFormat'] = 'oddsFormat';
               // $select['Result'] = 'Result';
                //$select['BetInfo'] = 'BetInfo';
                //$select['BetResult'] = 'BetResult';
               //$select['betType'] = 'betType';
                //$select['BetPos'] = 'BetPos';
                // 投注内容
                $select['BetDetail'] = 'BetDetail';
            }

            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'gameDate';
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
        return $table->paginate($pageSize, ['*'], 'page', $page);
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
        $ci = $app->getContainer();
        $userLogic = new \Logic\User\User($app->getContainer());
        // 订单记录
        $records = self::getRecords($condition, $page, $pageSize);
        $data = $records->toArray()['data'];
        // 列表详情
        $isShowUsername = isset($condition['user_agent_ids']);
        $result = [];
        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = 'STG';
            // 座位号
            $result[$k]['TableID'] = '-';
            // 玩法名
            $result[$k]['play_name'] = 'STG';
            // 输赢
            $result[$k]['profit'] = bcmul($v['winLoss'], 100, 0);
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = bcmul($v['betAmount'], 100, 0);
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['gameDate'];
            // 描述
            $result[$k] += self::parseRecordsState(bcmul($v['winLoss'], 100, 0), $v['gameDate']);
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
        $data = $records->toArray()['data'];
        $result = [];
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
            $result[$k]['GameStartTime'] = $v['BetDate'];
            // 结算时间
            $result[$k]['GameEndTime'] = $v['gameDate'];
            // 游戏名
            $result[$k]['game_name'] = 'STG';
            // 桌子号 > 椅子号
            $result[$k]['TableID'] = '-';
            // 总投注金额
            $result[$k]['pay_money'] = bcmul($v['betAmount'], 100, 0);
            // 有效投注
            $result[$k]['CellScore'] = bcmul($v['validBet'], 100, 0);
            // 派奖/输赢
            $result[$k]['Profit'] = bcmul($v['winLoss'], 100, 0);
            // 抽水
            $result[$k]['Revenue'] = '-';
            // 结果
            // 结果
            $result[$k]['outcome'] = self::getResultDetail($v['BetDetail']);
            $result[$k]['desc'] = self::getBetDetail($v['BetDetail']);
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
        $result = [
            // logo
            'logo' => '',
            // 彩种名
            'game_name' => 'STG',
            // 期号
            'sub_game_name' => '',
            // 投注金额 转为真实金额
            'pay_money' => bcmul($data->betAmount, 100, 0),
            // 中奖金额
            'send_money' => bcmul($data->winLoss, 100, 0),
        ];
        $result += self::parseRecordsState(bcmul($data->winLoss, 100, 0), $data->gameDate);
        // 方案详情
        $detail = [
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
                'value' => $data->winLoss
            ],
            [
                'key' => $ci->lang->text('Bet time') . ':',
                'value' => $data->BetDate
            ],
            [
                'key' => $ci->lang->text('Settlement time') . ':',
                'value' => $data->gameDate
            ]
        ];
        $bet_list = self::getBetDetail($data->BetDetail);
        foreach ($bet_list as $item) {
            $detail[] = $item;
        }
        // 返回内容
        $result['detail'] = $detail;
        return $result;
    }

    /**
     * 比赛结果
     */
    private function getResultDetail($item)
    {
        global $app;
        $ci = $app->getContainer();
        $decode_list = json_decode($item);
        $return_list = [];
        /*foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            if (isset($decode['odds']) && isset($decode['detailResult'])) {
                $return_list = [
                    ['key' => 'odds', 'value' => $decode['odds']],
                    ['key' => 'detailResult', 'value' => $decode['detailResult']],
                ];
            }

        }*/
        return $return_list;
    }

    /**
     * 查看投注详情
     */
    private function getBetDetail($item)
    {
        global $app;
        $ci = $app->getContainer();
        $decode_list = json_decode($item);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $return_list[] = ['key' => $ci->lang->text("Sheet %s", [$key]), 'value' => '=== ' . $ci->lang->text("Bet details") . ' ==='];
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
                    $return_list[] = ['key' => $ci->lang->text('Status') . ':', 'value' => self::API_RESULT_TYPE[$v1]];
                }else{
                    $return_list[] = ['key' => $ci->lang->text($k1) . ':', 'value' => $v1];
                }
            }
        }
        return $return_list;
    }

}