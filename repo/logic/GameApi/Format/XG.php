<?php

namespace Logic\GameApi\Format;

/**
 * XG视讯
 */
class XG {

    /**
     * 分类ID
     * @var int
     */
    public $game_id = 109;
    /**
     * 订单表
     * @var string
     */
    public $order_table = 'game_order_xg';

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
                    $table->where('PayoffAmount', '>', '0');
                    break;
                case 'lose':
                    $table->where('PayoffAmount', '<=', '0');
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
            $table->where('WagersId', $condition['order_number']);
        } else {
            // 订单时间过滤
            if(isset($condition['start_time']) && $condition['start_time']) {
                $table->where('WagersTime', '>=', $condition['start_time']);
            } else {
                $table->where('WagersTime', '>=', date('Y-m-d'));
            }

            if(isset($condition['end_time']) && $condition['end_time']) {
                $table->where('WagersTime', '<=', $condition['end_time']);
            } else {
                $table->where('WagersTime', '<=', date('Y-m-d H:i:s'));
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
                'all_bet'    => \DB::raw("sum(BetAmount) as total_bet"),
                // 有效投注
                'cell_score' => \DB::raw("sum(validBetAmount) as total_available_bet"),
                // 用户数量
                'user_id'    => \DB::raw("count(DISTINCT user_id) as total_user_count"),
                //派彩金额
                'send_prize' => \DB::raw("sum(prize_amount) as total_send_prize"),
                //总数
                'order_count' => \DB::raw("count(0) as total_order_count"),
            ];
        } else {
            // 常规操作
            $select = [
                // 订单详细id
                'id'            => 'id',
                //订单号
                'order_number'  => 'WagersId as order_number',
                // 用户id
                'user_id'       => 'user_id',
                // 返还金额
                'winLoss'       => 'PayoffAmount as winLoss',
                // 下注金额
                'betAmount'     => 'BetAmount as bet',
                //有效下注金额
                'validBet'      => 'validBetAmount as validbet',
                //盈利
                'profit'        => 'PayoffAmount as income',
                // 下注时间
                'gameStartTime' => 'WagersTime as betTime',
                //结算时间
                'gameEndTime'   => 'SettlementTime as settime',
                //游戏桌号
                'TableID'       => 'TableId as tableId',
                //游戏局号
                'roundId'       => 'Run as event',
                //游戏代码
                'gameCode'      => 'GameId as kind_id',
                //游戏类别
                'GameType'      => 'GameType',
            ];

            // 详情
            if(isset($condition['info'])) {
                // 游戏结果
                $select['result'] = 'GameResult as gameResult';
            }

            //排序默认结算时间
            if(isset($condition['orderby'])) {
                $order_field = 'WagersTime';
                switch($condition['orderby']) {
                    case 'pay_money':
                        $order_field = 'BetAmount';
                        break;
                    case 'CellScore':
                        $order_field = 'validBetAmount';
                        break;
                    case 'profit':
                        $order_field = 'PayoffAmount';
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
            'all_bet'    => bcmul($data->total_bet, 100, 0),
            'cell_score' => bcmul($data->total_available_bet, 100, 0),
            'user_count' => $data->total_user_count,
            'send_prize' => bcmul($data->total_send_prize, 100, 0),
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
        foreach($data ?? [] as $k => $v) {
            $v                = (array)$v;
            $result[$k]       = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = $ci->lang->text($this->getGameName($v['GameType']));
            // 座位号
            $result[$k]['roundId'] = $v['tableId'] . ' > ' . $v['event'];
            // 玩法名
            $result[$k]['play_name'] = '';
            // 输赢
            $result[$k]['profit'] = bcmul($v['income'], 100, 2);
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = bcmul($v['bet'], 100, 2);
            // 显示用户名
            if($isShowUsername) {
                $result[$k]['user_name'] = $userLogic->getGameUserNameById($v['user_id']);
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['betTime'];
            // 描述
            $result[$k] += self::parseRecordsState($v['income'], $v['settime']);
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
            $result[$k]['GameStartTime'] = $v['betTime'];
            // 结算时间
            $result[$k]['GameEndTime'] = $v['settime'];
            // 游戏名
            $result[$k]['game_name'] = $ci->lang->text($this->getGameName($v['GameType']));
            // 桌子号 > 椅子号
            $result[$k]['TableID'] = $v['tableId'] . ' > ' . $v['event'];
            // 总投注金额
            $result[$k]['pay_money'] = bcmul($v['bet'], 100, 0);
            // 有效投注
            $result[$k]['CellScore'] = bcmul($v['validbet'], 100, 0);
            // 派奖/输赢
            $result[$k]['Profit'] = bcmul($v['income'], 100, 0);
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

        $result = [
            // logo
            'logo'          => '',
            // 彩种名
            'game_name'     => $ci->lang->text($this->getGameName($data->GameType)),
            // 期号
            'sub_game_name' => '',
            // 投注金额 转为真实金额
            'pay_money'     => bcmul($data->bet, 100, 0),
            // 中奖金额
            'send_money'    => bcmul($data->income, 100, 0),
        ];
        $result += self::parseRecordsState(bcmul($data->income, 100, 0), $data->betTime);
        // 方案详情
        $detail = [
            [
                'key'   => $ci->lang->text("Play name") . ':',
                'value' => $ci->lang->text($this->getGameName($data->GameType)),
            ],
            [
                'key'   => $ci->lang->text('Note No') . ':',
                'value' => $data->order_number
            ],
            [
                'key'   => $ci->lang->text('Bet amount') . ':',
                'value' => $data->bet
            ],
            [
                'key'   => $ci->lang->text('Profit and loss amount') . ':',
                'value' => $data->income
            ],
            [
                'key'   => $ci->lang->text('Game start time') . ':',
                'value' => $data->betTime
            ],
            [
                'key'   => $ci->lang->text('update time') . ':',
                'value' => $data->settime
            ],
            [
                'key'   => $ci->lang->text('round id') . ':',
                'value' => $data->tableId . ' > ' . $data->event
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

    /**
     * 游戏名称
     * @param $gameType
     * @return mixed|string
     */
    private function getGameName($gameType) {
        $games = [
            '1' => 'Baccarat',
            '2' => 'Dice',
            '3' => 'Roulette',
            '5' => 'Dragon and tiger',
            '6' => 'Xoc Dia',
            '7' => 'Speed dice treasure',
        ];
        return $games[$gameType] ?? 'XG';
    }
}