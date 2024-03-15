<?php

namespace Logic\GameApi\Format;


class BTI
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
        if ($isShowState) {
            // 状态
            $state = OrderRecordCommon::TYPE_TARGET_STATE['default'];
            $result[] = [
                'title' => $ci->lang->text('State'),
                'category' => 'state',
                'list' => $state,
            ];
        }
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
                    $table->where('PL', '>', '0');
                    break;
                case 'lose':
                    $table->where('PL', '<=', '0');
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
            $table->where('PurchaseID', $condition['order_number']);
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $table->where('CreationDate', '>=', $condition['start_time']);
            } else {
                $table->where('CreationDate', '>=', date('Y-m-d'));
            }

            if (isset($condition['end_time']) && $condition['end_time']) {
                $table->where('BetSettledDate', '<=', $condition['end_time']);
            } else {
                $table->where('BetSettledDate', '<=', date('Y-m-d H:i:s'));
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
                'all_bet' => \DB::raw("sum(TotalStake) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(ValidStake) as total_available_bet"),
                // 用户数量
                'user_id' => \DB::raw("count(DISTINCT user_id) as total_user_count"),
                //派彩金额
                'send_prize' => \DB::raw("sum(PL) as total_send_prize"),
                //总数
                'order_count' => \DB::raw("count(0) as total_order_count"),
            ];
        } else {
            // 常规操作
            $select = [
                // 订单详细id
                'id' => 'id',
                // 游戏局号列表 基本定义为 订单号
                'order_number' => 'PurchaseID as order_number',
                // 用户id
                'user_id' => 'user_id',
                // 下注金额
                'betAmount' => 'TotalStake as betAmount',
                //有效下注金额
                'validBet' => 'ValidStake as validBet',
                // 游戏结束时间列表
                'gameDate' => 'BetSettledDate as gameDate',
                'BetDate' => 'CreationDate as BetDate',
                'selection' => 'Selections',
                'game_name' => 'BetTypeName as game_name',
                'BetOdds' => 'OddsInUserStyle as BetOdds',
                'oddsFormat' => 'OddsStyleOfUser',
                'Result' => 'Status as Result',
                'PL' => 'PL',
                'BetTypeName' => 'BetTypeName',
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
            }

            //排序默认结算时间
            if (isset($condition['orderby'])) {
                $order_field = 'gameDate';
                switch ($condition['orderby']) {
                    case 'pay_money':
                        $order_field = 'TotalStake';
                        break;
                    case 'CellScore':
                        $order_field = 'ValidStake';
                        break;
                    case 'Profit':
                        $order_field = 'PL';
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
            'all_bet' => $data->total_bet,
            'cell_score' => $data->total_available_bet,
            'user_count' => $data->total_user_count,
            'send_prize' => $data->total_send_prize,
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
            // 游戏名
            $result[$k]['game_name'] = $v['game_name'];
            // 座位号
            $result[$k]['TableID'] = '-';
            // 玩法名
            $result[$k]['play_name'] = 'BTI';
            // 输赢
            $result[$k]['profit'] = $v['PL'];
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = $v['betAmount'];
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['gameDate'];
            // 描述
            $result[$k] += self::parseRecordsState($v['PL'], $v['gameDate']);
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
            $result[$k]['game_name'] = $v['game_name'];
            // 桌子号 > 椅子号
            $result[$k]['TableID'] = '-';
            // 总投注金额
            $result[$k]['pay_money'] = $v['betAmount'];
            // 有效投注
            $result[$k]['CellScore'] = $v['validBet'];
            // 派奖/输赢
            $result[$k]['Profit'] = $v['PL'];
            // 抽水
            $result[$k]['Revenue'] = '-';
            // 结果
            $result[$k]['outcome'] = self::getResultDetail($v['Selections']);
            $result[$k]['desc'] = self::getBetDetail($v['Selections']);
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
            'game_name' => $data->game_name,
            // 期号
            'sub_game_name' => '',
            // 投注金额 转为真实金额
            'pay_money' => $data->betAmount,
            // 中奖金额
            'send_money' => $data->PL,
        ];
        $result += self::parseRecordsState($data->PL, $data->gameDate);
        // 方案详情
        $detail = [
            [
                'key' => $ci->lang->text("Play name") . ':',
                'value' => $data->BetTypeName,
            ],
            [
                'key' => $ci->lang->text('Note No') . ':',
                'value' => $data->order_number
            ],
            [
                'key' => $ci->lang->text('Bet amount') . ':',
                'value' => bcdiv($data->betAmount, 100, 2)
            ],
            [
                'key' => $ci->lang->text('Profit and loss amount') . ':',
                'value' => bcdiv($data->PL, 100, 2)
            ],
            [
                'key' => $ci->lang->text('Bet time') . ':',
                'value' => $data->BetDate
            ],
            [
                'key' => $ci->lang->text('Settlement time') . ':',
                'value' => $data->gameDate
            ],
            [
                'key' => $ci->lang->text('Betting Odds') . ':',
                'value' => $data->BetOdds
            ],
            [
                'key' => $ci->lang->text('Note results') . ':',
                'value' => $data->Result
            ],
        ];
        // 投注详情
        $code_list = self::getResultDetail($data->Selections);
        foreach ($code_list as $item) {
            $detail[] = $item;
        }
        // 返回内容
        $result['detail'] = $detail;
        return $result;
    }

    /** 解析比赛结果
     * @param $item
     * @return array
     */
    private function getResultDetail($item)
    {
        global $app;
        $ci = $app->getContainer();
        $decode_list = json_decode($item, true);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            $return_list[] = ['key' => $ci->lang->text('Types of bets') . ':', 'value' => $decode['BetType']];
            $return_list[] = ['key' => $ci->lang->text('union') . ':', 'value' => $decode['LeagueName']];
            $return_list[] = ['key' => $ci->lang->text('Home team') . ':', 'value' => $decode['HomeTeam']];
            $return_list[] = ['key' => $ci->lang->text('Away team') . ':', 'value' => $decode['AwayTeam']];
            $return_list[] = ['key' => $ci->lang->text('Event start time') . ':', 'value' => $decode['EventDate']];
            $return_list[] = ['key' => $ci->lang->text('Final result (home team)') . ':', 'value' => $decode['Score']];

        }
        return $return_list;
    }

    /**
     * 查看投注详情
     */
    private function getBetDetail($item)
    {
        global $app;
        $ci = $app->getContainer();
        $decode_list = json_decode($item, true);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            foreach ($decode as $k1 => $v1) {
                $return_list[] = ['key' => $ci->lang->text($k1) . ':', 'value' => $v1];
            }
        }
        return $return_list;
    }

}