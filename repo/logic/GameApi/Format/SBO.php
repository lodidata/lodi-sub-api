<?php
namespace Logic\GameApi\Format;

/**
 * SBO体育
 * Class SBO
 * @package Logic\GameApi\Format
 */
class SBO
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


    /**
     * 将订单状态 转换为前端可以查看的
     * @param string $resultFlag 状态，这个订单比较特殊
     * @return array
     */
    public function parseRecordsState($resultFlag)
    {
        global $app;
        $ci = $app->getContainer();
        $result = [];
        if ($resultFlag == null || strlen($resultFlag) == 0) {
            $result['state'] = $ci->lang->text("state.created");
        } else {
            $result['state'] = $ci->lang->text(self::API_ODDS_STATE_TYPE[$resultFlag]);
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
    public function filterDetail(bool $isShowState, $condition = [])
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
        // 操作的表名
        //  插入查询
        $table = \DB::connection('slave')->table($this->order_table);


        // last_id 筛选
        isset($condition['last_id']) && $table->where('id', '>', $condition['last_id']);
        // id 筛选
        isset($condition['id']) && $table->where('id', $condition['id']);
        // 订单筛选
        if (isset($condition['order_number'])) {
            $table->where('refNo', $condition['order_number']);
        }else{
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
                'all_bet' => \DB::raw("sum(stake) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(stake) as total_available_bet"),
                // 用户数量
                'user_id' => \DB::raw("count(DISTINCT user_id) as total_user_count"),
                //派彩金额
                'send_prize' => \DB::raw("sum(winlost) as total_send_prize"),
                //总数
                'order_count' => \DB::raw("count(0) as total_order_count"),
            ];

        } else {
            // 常规操作
            $select = [
                // 订单详细id
                'id' => 'id',
                // 游戏局号列表 基本定义为 订单号
                'order_num' => 'refNo as order_number',
                // 用户id
                'user_id' => 'user_id',
                'game_name' => 'sportsType as game_name',
                // 有效下注
                'CellScore' => 'stake as availableBet',
                // 总下注
                'AllBet' => 'stake as bet',
                // 盈亏
                'profit' => 'winlost as profit',
                // 派彩时间
                'gameDate' => 'settleTime as send_time',
                // 补充字段：订单状态
                'status' => 'status',
                // oddsStyle:赔率种类 1 香港盘；2 欧洲盘；3 印尼盘；4 马来西亚盘
                'oddsStyle' => 'oddsStyle',
                // orderTime:下注时间
                'orderTime' => 'orderTime',
                // 投注内容
                'subBet' => 'subBet',
            ];
            // 详情
            if (isset($condition['info'])) {
                # 注单详情
                $select['betDetail'] = 'subBet as betDetail';
                // 注单可赢金额
                $select['winlost'] = 'winlost';
                # 是否开奖
                //$select['Settle'] = 'Settle';
            }
        }

        // 状态
        if (isset($condition['state'])) {
            switch ($condition['state']) {
                case 'winning':
                    $table->where('winlost', '>', '0');
                    break;
                case 'lose':
                    $table->where('winlost', '<=', '0');
                    break;
                default:
                    break;
            }

            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'orderTime';
                switch ($condition['orderby']) {
                    case 'CellScore':
                        $order_field = 'availableBet';
                        break;
                    case 'pay_money':
                        $order_field = 'bet';
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
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = $v['game_name'];
            // 订单号
            $result[$k]['sub_game_name'] = $ci->lang->text('SBO');
            // 赔率种类
            $result[$k]['play_name'] = $ci->lang->text(self::API_ODDS_TYPE[$v['oddsStyle']]);
            // 输赢
            $result[$k]['profit'] = bcmul($v['profit'], 100, 2);
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = bcmul($v['bet'], 100, 2);
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = date('Y-m-d H:i:s', strtotime($v['orderTime']));//GMT(UTC+0)日期转为北京时间
            // 描述
            $result[$k] += self::parseRecordsState($v['status']);
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
        $ci = $app->getContainer();
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
            $result[$k]['GameStartTime'] = date('Y-m-d H:i:s', strtotime($v['orderTime']));//GMT(UTC+0)日期转为北京时间
            // 结算时间
            $result[$k]['GameEndTime'] = $v['send_time'];
            // 游戏名
            $result[$k]['game_name'] = $v['game_name'];
            // 桌子号 > 椅子号
            $result[$k]['TableID'] = $ci->lang->text('SBO');
            // 总投注金额
            $result[$k]['pay_money'] = bcmul($v['bet'], 100, 2);
            // 有效投注
            $result[$k]['CellScore'] = bcmul($v['availableBet'], 100, 2);
            // 派奖/输赢
            $result[$k]['Profit'] = bcmul($v['profit'], 100, 2);
            // 抽水
            $result[$k]['Revenue'] = '-';
            // 结果
            $result[$k]['outcome'] = self::getResultDetail($v['subBet']);
            $result[$k]['desc'] = self::getBetDetail($v['subBet']);

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
            'game_name' => $ci->lang->text('SBO'),
            // 球种
            'sub_game_name' => $data->game_name,
            // 投注金额 转为真实金额
            'pay_money' => (string)($data->bet),
            // 中奖金额
            'send_money' => (string)($data->profit > 0 ? $data->profit : 0),
        ];
        $result += self::parseRecordsState($data->status);
        // 方案详情
        $detail = [
            [
                'key' => $ci->lang->text("Play name") . ':',
                'value' => $data->game_name
            ],
            [
                'key' => $ci->lang->text("Event start time") . ':',
                'value' => date('Y-m-d H:i:s', strtotime($data->orderTime))
            ],
            [
                'key' => $ci->lang->text('Note No') . ':',
                'value' => $data->order_number
            ],
            [
                'key' => $ci->lang->text('Types of odds') . ':',
                'value' => $ci->lang->text(self::API_ODDS_TYPE[$data->oddsStyle])
            ],
            [
                'key' => ($data->status == 'lose' ? $ci->lang->text('Profit and loss amount') : $ci->lang->text('Winnable amount')) . ':',
                'value' => $data->profit
            ],
            [
                'key' => $ci->lang->text('Bet amount') . ':',
                'value' => $data->bet
            ],
            [
                'key' => $ci->lang->text('Note time') . ':',
                'value' => date('Y-m-d H:i:s', strtotime($data->orderTime))
            ],
            [
                'key' => $ci->lang->text('Award time') . ':',
                'value' => $data->send_time ? $data->send_time : $ci->lang->text("No award")
            ]
        ];
        // 投注详情
        $code_list = self::getResultDetail($data->subBet);
        foreach ($code_list as $item) {
            $detail[] = $item;
        }
        $bet_list = self::getBetDetail($data->subBet);
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
        $decode_list = json_decode($item, true);
        $return_list = [];
        foreach ($decode_list as $key => $decode) {
            $decode = (array)$decode;
            $return_list[] = ['key' => $ci->lang->text('Note results') . ':', 'value' => self::API_ODDS_STATE_TYPE[$decode['status']]];
            $return_list[] = ['key' => $ci->lang->text('match') . ':', 'value' => $decode['match']];
            $return_list[] = ['key' => $ci->lang->text('Home team score (first half)') . ':', 'value' => $decode['htScore']];
            $return_list[] = ['key' => $ci->lang->text('Away team score (second half)') . ':', 'value' => $decode['ftScore']];

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