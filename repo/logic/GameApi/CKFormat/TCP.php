<?php
namespace Logic\GameApi\CKFormat;

/**
 * 彩票类CK查询公共类
 * Class TCP
 * @package Logic\GameApi\CKFormat
 */
class TCP extends CKFORMAT
{
    /**
     * 查看订单记录
     * @param array $condition
     * @return mixed
     */
    public function getRecord($condition)
    {
        $default_fields = [
            'orderNumber' => 'OCode',//单号
            'userName' => 'Username',//账号
            'betAmount' => 'betAmount',//投注
            'validAmount' => 'validAmount',//实际投注
            'winAmount' => 'winAmount', //派奖
            'profit' => 'income',//盈亏
            'whereTime' => 'gameDate',//搜索时间
            'startTime' => 'gameDate',//投注时间
            'endTime' => 'gameDate',//结算时间
            'gameCode' => "''", //游戏码
            'origin' => "''", //终端web h5
            'lottery_number' => "''", //期号
            'lottery_name' => "''", //彩种
            'bet_num' => "''",//注数
            'times' => "''",//倍数
            'state' => "''",//状态
            'odds' => "''",//赔率
            'open_code' => "''",//开奖号
            'play_id' => "''", //玩法ID
            'play_name' => "''",//玩法
            'play_group' => "''", //玩法组
            'play_number' => "''",//选号
            'one_money' => "''" //单注金额
        ];

        $fields = array_merge($default_fields, $this->table_fields);

        $where = [];
        $tid = $this->ci->get('settings')['app']['tid'];
        $where[] = "tid=" . $tid;
        if(isset($this->field_category_name) && $this->field_category_name){
            $where[] = $this->field_category_name . " IN (" . $this->field_category_value. ")";
        }

        // 状态
        if (isset($condition['state'])) {
            $where[] = $fields['profit'] . (($condition['state']=='winning') ? '>' : '<=') . '0';
        }
        // last_id 筛选
        isset($condition['last_id']) && $where[] = 'id' . '>' . $condition['last_id'];
        // id 筛选
        isset($condition['id']) && $where[]= 'id' . '=' . $condition['id'];

        // KindID 筛选
        isset($condition['kind_id']) && $where[]= $fields['gameCode'] . "='" . $condition['kind_id'] . "'";

        //期号
        isset($condition['lottery_number'])  && $where[]= 'lottery_number' . "='" . $condition['lottery_number'] . "'";
        //来源
        isset($condition['order_origin'])  && $where[]= 'origin' . "='" . $condition['order_origin'] . "'";

        // 订单筛选
        if (isset($condition['order_number'])) {
            $where[]= $fields['orderNumber'] . "='" . $condition['order_number']."'";
        } else {
            // 订单时间过滤
            if (isset($condition['start_time']) && $condition['start_time']) {
                $where[] = $fields['whereTime'] . ">='" . $condition['start_time'] . "'";
            } else {
                $where[] = $fields['whereTime'] . ">='" . date('Y-m-d') . "'";
            }

            if (isset($condition['end_time']) && $condition['end_time']) {
                $where[] = $fields['whereTime'] . "<='" . $condition['end_time'] . "'";
            } else {
                $where[] = $fields['whereTime'] . "<='" . date('Y-m-d H:i:s') . "'";
            }
        }

        //查用户
        if (isset($condition['user_name'])) {
            $user_account = $this->getGameAccountByUserName($condition['user_name']);
            $where[] = $fields['userName'] . "='" . $user_account . "'";
        } elseif (isset($condition['user_id'])) {
            $user_account = $this->getGameAccountByUserId($condition['user_id']);
            $where[] = $fields['userName'] . "='" . $user_account . "'";
        }


        // 统计相关,只要几个简单字段
        if (isset($condition['statistics'])) {
            // 统计
            $select = [
                // 总下注
                'all_bet' => "sum(".$fields['betAmount'].") as total_bet",
                // 有效投注
                'cell_score' => "sum(".$fields['validAmount'].") as total_available_bet",
                // 用户数量
                'Username' => "count(DISTINCT ".$fields['userName'].") as total_user_count",
                //派彩金额
                'send_prize' => "sum(".$fields['profit'].") as total_send_prize",
                //总数
                'order_count' => "count(0) as total_order_count"
            ];

        } else {
            // 常规操作
            $select = [
                'id' => 'id',
                //订单号
                'order_number' => $fields['orderNumber'] . ' as order_number',
                // 用户id
                'user_account' => $fields['userName'] . ' as user_account',
                // 盈亏
                'income' => $fields['profit'] . ' as income',
                //单金额
                'one_money' => $fields['one_money'] . ' as one_money',
                // 下注
                'gameBetAmount' => $fields['betAmount'] . ' as gameBetAmount',
                //有效下注
                'validAmount' => $fields['validAmount'] . ' as gameValidAmount',
                // 中奖金额
                'winAmount' => $fields['winAmount'] . ' as gameWinAmount',
                // 开始时间
                'GameStartTime' => $fields['startTime'] . ' as GameStartTime',
                //结算时间
                'GameEndTime' => $fields['endTime'] . ' as GameEndTime',
                //游戏代码
                'gameCode' => $fields['gameCode'] . ' as kind_id',
                //来源，1:pc, 2:h5, 3:移动端
                'origin' => $fields['origin'] . ' as origin',
                //期号
                'lottery_number' => $fields['lottery_number'] . ' as lottery_number',
                //彩种
                'lottery_name' => $fields['lottery_name'] . ' as lottery_name',
                //注数
                'bet_num' => $fields['bet_num'] . ' as bet_num',
                //倍数
                'times' => $fields['times'] . ' as times',
                //状态
                'state' => $fields['state'] . ' as state',
                //赔率
                'odds' => $fields['odds'] . ' as odds',
                //开奖号
                'open_code' => $fields['open_code'] . ' as open_code',
                //玩法ID
                'play_id' => $fields['play_id'] . ' as play_id',
                //玩法
                'play_name' => $fields['play_name'] . ' as play_name',
                //玩法组
                'play_group' => $fields['play_group'] . ' as play_group',
                //选号
                'play_number' => $fields['play_number'] . ' as play_number',
            ];

            //TODO 扩展字段查询
            if(!empty($this->table_fields_ext)){
                $select = array_merge($select, $this->table_fields_ext);
            }
        }
        $field = join(',', array_values($select));
        $where = join(' and ', array_values($where));
        $sql = "SELECT {$field} FROM {$this->order_table} WHERE {$where}";

        //是否排序
        if (!isset($condition['statistics'])) {
            //排序默认结算时间
            if (isset($condition['orderby'])) {
                $order_field = $fields['whereTime'];
                switch ($condition['orderby']) {
                    case 'CellScore':
                        $order_field = 'gameValidAmount';
                        break;
                    case 'pay_money':
                        $order_field = 'gameBetAmount';
                        break;
                    case 'Profit':
                        $order_field = 'income';
                        break;
                }
                $sql .= " ORDER BY {$order_field} " . (isset($condition['orderbyasc']) && $condition['orderbyasc'] == 'asc' ? 'asc' : 'desc');
            }
        }
        return $sql;
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
        // 订单记录
        $data = self::getRecords($condition, $page, $pageSize);
        $result = [];

        foreach ($data ?? [] as $k => $v) {
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 游戏名
            $result[$k]['game_name'] = ($v['play_group']? ($v['play_group'] . '-') :'') . ($v['lottery_name'] ?? '-');
            // 座位号
            $result[$k]['TableID'] = $v['lottery_number'];
            // 玩法名
            $result[$k]['play_name'] = $v['play_name'];
            // 盈亏
            $result[$k]['profit'] = $v['income'];
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = $v['gameBetAmount'];
            // 显示用户名
            $result[$k]['user_name'] = $this->getGameUserNameByAccount($v['user_account']);
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 创建时间
            $result[$k]['create_time'] = $v['GameStartTime'];
            // 模式
            $result[$k]['mode'] = $this->ci->lang->text("Third party");
            //状态
            $result[$k]['state'] = $this->ci->lang->text($this->resultStatus($v['state'])) ?: $this->ci->lang->text("Unsettled");
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
        $data = self::getRecords($condition, $page, $pageSize);
        $result = [];

        foreach ($data ?? [] as $k => $v){
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = $v['order_number'];
            // 会员账号
            $result[$k]['user_name'] = $this->getGameUserNameByAccount($v['user_account']);

            // 投注时间
            $result[$k]['created'] = $v['GameStartTime'];
            // 来源
            $result[$k]['origin'] = $this->getOrigin($v['origin']);
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
            $result[$k]['one_money'] = $v['one_money'];
            //总投注金额
            $result[$k]['pay_money'] = $v['gameBetAmount'];
            //开奖号码
            $result[$k]['open_code'] = $v['open_code']?? '-';
            //派奖金额
            $result[$k]['p_money'] = $v['gameWinAmount'];
            //选号
            $result[$k]['play_number'] = $this->getBetCountent($v);
            //倍数
            $result[$k]['times'] = $v['times'] ?? 1;
            //输赢
            $result[$k]['lose_earn'] = $v['income'];
            // 模式
            $result[$k]['mode'] = $this->ci->lang->text("Third party");
            //状态
            $result[$k]['state2'] = $this->ci->lang->text($this->resultStatus($v['state'])) ?: $this->ci->lang->text("Unsettled");
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
        return [];
    }

    /**
     * 终端显示 web pc h3
     * @param $orgin
     * @return mixed
     */
    protected function getOrigin($orgin)
    {
        return $orgin;
    }

    /**
     * 注单内容转换
     * @param $data
     * @return mixed
     */
    protected function getBetCountent($data)
    {
        return $data;
    }

    /**
     * 注单状态
     * @param $status
     * @return mixed
     */
    protected function resultStatus($status)
    {
        return $status;
    }
}