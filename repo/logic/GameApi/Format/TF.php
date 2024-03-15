<?php

namespace Logic\GameApi\Format;


class TF
{

    protected $game_type = 'TF';
    protected $game_id = 78;
    protected $order_table = 'game_order_tf';
    //会员下的盘
    protected $oddsStyle = [
        'malay' => 'Malay odds',
        'hongkong' => 'HongKong odds',
        'euro' => 'Euro odds',
        'indo' => 'Indonesia odds'
    ];
    //注单结果
    protected $resultStatus = [
        'WIN' => 'order.pass',
        'LOSS' => 'order.lose',
        'DRAW' => 'order.draw',
        'CANCELLED' => 'order.cancel',
        'NULL' => ''
    ];
    //注单下注状况
    protected $ticketType = [
        'db' => 'ticket_type.db',//早盘
        'live' => 'ticket_type.live' //滚球
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
                    $table->where('earnings', '>', '0');
                    break;
                case 'lose':
                    $table->where('earnings', '<=', '0');
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
            $table->where('order_id', $condition['order_number']);
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $table->where('date_created', '>=', $condition['start_time']);
            } else {
                $table->where('date_created', '>=', date('Y-m-d'));
            }

            if (isset($condition['end_time']) && $condition['end_time']) {
                $table->where('date_created', '<=', $condition['end_time']);
            } else {
                $table->where('date_created', '<=', date('Y-m-d H:i:s'));
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
                'all_bet' => \DB::raw("sum(amount) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(amount) as total_available_bet"),
                //派彩金额
                'send_prize' => \DB::raw("sum(earnings) as total_send_prize"),
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
                'order_num' => 'order_id',
                // 用户id
                'user_id' => 'user_id',
                // 游戏名称
                'game_name' => 'game_type_name as game_name',
                // 中奖金额
                'win' => 'earnings',
                // 总下注
                'bet' => 'amount',
                // 下注时间
                'date_created' => 'date_created',
                //结算时间
                'settlement_datetime' => 'settlement_datetime',
                //会员赔率
                'member_odds' => 'member_odds',
                'member_odds_style' => 'member_odds_style',
                'game_market_name' => 'game_market_name',
                'bet_type_name' => 'bet_type_name',
                'competition_name' => 'competition_name',
                'event_name' => 'event_name',
                'event_datetime' => 'event_datetime',
                'result_status' => 'result_status',
                'result' => 'result',
                'handicap' => 'handicap',
                'ticket_type' => 'ticket_type',
                'bet_selection' => 'bet_selection',
                'settlement_status' => 'settlement_status',
            ];

            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'date_created';
                switch ($condition['orderby']) {
                    case 'CellScore':
                    case 'pay_money':
                        $order_field = 'amount';
                        break;
                    case 'Profit':
                        $order_field = 'earnings';
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
            $result[$k]['order_number'] = $v['order_id'];
            // 游戏名
            $result[$k]['game_name'] = $v['game_name'];
            // 子标题
            $result[$k]['sub_game_name'] = $v['event_name'];
            // 玩法名
            $result[$k]['play_name'] = $ci->lang->text("game_type");
            // 输赢
            $profit = $v['earnings'] - $v['amount'];
            $result[$k]['profit'] = (string)($profit);
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = (string)(abs($v['amount']));
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['date_created'];
            // 描述
            $result[$k] += self::parseRecordsState($profit, $v['date_created']);
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
            $result[$k]['order_number'] = $v['order_id'];
            // 会员账号
            $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            // 投注时间
            $result[$k]['GameStartTime'] = $v['date_created'];
            // 结算时间
            $result[$k]['GameEndTime'] = $v['settlement_datetime'];
            // 游戏名
            $result[$k]['game_name'] = $v['game_name'];
            // 游戏类型
            $result[$k]['TableID'] = $v['event_name'];
            // 总投注金额
            $result[$k]['pay_money'] = bcmul(abs($v['amount']), 100, 0);
            // 有效投注
            $result[$k]['CellScore'] = bcmul(abs($v['amount']), 100, 0);
            // 派奖/输赢 （收入 - 支出）
            $result[$k]['Profit'] = bcmul($v['earnings'], 100, 0);
            // 抽水 这里没有
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
        // 派奖/输赢 （收入 - 支出）
        $profit = $data->earnings - $data->amount;
        $result = [
            // logo
            'logo' => '',
            // 彩种名
            'game_name' => $ci->lang->text($this->game_type),
            // 期号
            'sub_game_name' => $data->game_name,
            // 投注金额 转为真实金额
            'pay_money' => (string)(abs($data->amount)),
            // 中奖金额
            'send_money' => (string)($profit < 0 ? 0 : $profit),
        ];
        $result += self::parseRecordsState($profit, $data->date_created);
        // 方案详情
        $detail = [
            [
                'key' => $ci->lang->text("Game name") . ':',
                'value' => $data->game_name . "-" . $data->event_name
            ],
            [
                'key' => $ci->lang->text('Note No') . ':',
                'value' => $data->order_id
            ],
            [
                'key' => $ci->lang->text('Bet amount') . ':',
                'value' => bcdiv(abs($data->amount), 100, 2)
            ],
            [
                'key' => $ci->lang->text('Lottery quota') . ':',
                'value' => bcdiv($data->earnings, 100, 2)
            ],
            [
                'key' => $ci->lang->text('Profit and loss amount') . ':',
                'value' => bcdiv($profit, 100, 2)
            ],
            [
                'key' => $ci->lang->text('Bet time') . ':',
                'value' => $data->date_created ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('Settlement time') . ':',
                'value' => $data->settlement_datetime ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('Betting Odds') . ':',
                'value' => $data->member_odds ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('Types of odds') . ':',
                'value' => $ci->lang->text($this->oddsStyle[$data->member_odds_style]) ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('marketType') . ':',
                'value' => $data->bet_type_name ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('game_market_name') . ':',
                'value' => $data->game_market_name ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('competition_name') . ':',
                'value' => $data->competition_name ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('Game start time') . ':',
                'value' => $data->event_datetime ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('bet_selection') . ':',
                'value' => $data->bet_selection ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('Note status') . ':',
                'value' => $data->settlement_status ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('Note results') . ':',
                'value' => $ci->lang->text($this->resultStatus[$data->result_status]) ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('market_result') . ':',
                'value' => $data->result ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('handicap') . ':',
                'value' => $data->handicap ?: $ci->lang->text("Unsettled")
            ],
            [
                'key' => $ci->lang->text('ticket_type') . ':',
                'value' => $ci->lang->text($this->ticketType[$data->ticket_type]) ?: $ci->lang->text("Unsettled")
            ],

        ];
        $result['detail'] = $detail;
        return $result;
    }
}