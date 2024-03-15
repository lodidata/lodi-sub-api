<?php
/**
 * Created by PhpStorm.
 * User:
 * Date: 2018/3/22
 * Time: 14:47
 */

namespace Model;

use DB;
use Logic\GameApi\Format\OrderRecordCommon;

class LotteryChaseOrder extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'lottery_chase_order';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'chase_type',
        'chase_number',
        'hall_id',
        'hall_name',
        'room_id',
        'room_name',
        'lottery_id',
        'lottery_name',
        'play_group',
        'play_name',
        'current_bet',
        'increment_bet',
        'complete_periods',
        'sum_periods',
        'reward',
        'profit',
        'mode',
        'state',
        'tags',
        'created',
        'updated',
        'origin',
    ];

    /**
     * 将订单状态 转换为前端可以查看的
     * @param $state
     * @return array
     */
    public static function parseRecordsState($state) {
        $states = ['complete' => '已结束', 'cancel' => '已撤单', 'underway' => '进行中'];
        return $states[$state];
    }

    /**
     * 查看订单记录
     * @param $condition
     * @return mixed [type]            [description]
     */
    private static function getRecord($condition) {
        $table_name = 'lottery_chase_order';
        $query = DB::table($table_name)
            ->where('tags', '!=', 7)
            ->orderBy('id', 'desc');

        // 状态
        if (isset($condition['state'])) {
            $state = $condition['state'];
            $state = $state == 'created' ? 'underway' : $state;
            $query->where('state', $state);
        }
        isset($condition['id']) && $query->where('id', $condition['id']);
        isset($condition['order_number']) && $query->where('chase_number', $condition['order_number']);

        isset($condition['lottery_id']) && $query->where('lottery_id', $condition['lottery_id']);
        isset($condition['start_time']) && $query->where('created', '>=', $condition['start_time']);
        isset($condition['end_time']) && $query->where('created', '<=', $condition['end_time'] . ' 23:59:59');
        // 加入会员 信息
        if (isset($condition['user_name'])) {
            $query->leftJoin('user', 'user.id', '=', $table_name .'.user_id');
        }
        // user_name 筛选
        isset($condition['user_name']) && $query->where('user.name', $condition['user_name']);
        // 代理数据
        if (isset($condition['user_agent_ids'])){
            $query->whereIn($table_name . '.user_id', $condition['user_agent_ids']);
        }else{
            if (!isset($condition['info']) && !isset($condition['admin_info'])) {
                // 非详情时，才进行 user_id 筛选
                isset($condition['user_id']) && $query->where('user_id', $condition['user_id']);
            }
        }

        $select = [
            // id
            'id' => $table_name . '.id',
            'user_id' => $table_name . '.user_id',
            // 玩法
            'lottery_name' => $table_name . '.lottery_name',
            // 玩法
            'play_group' => $table_name . '.play_group',
            'play_name' => $table_name . '.play_name',
            // 追号编号
            'chase_number' => $table_name . '.chase_number',
            // 当前期
            'complete_periods' => $table_name . '.complete_periods',
            // 总期数
            'sum_periods' => $table_name . '.sum_periods',
            // 累计下注
            'increment_bet' => $table_name . '.increment_bet',
            // 输赢
            'profit' => $table_name . '.profit',
            // 状态
            'state' => $table_name . '.state',
            'hall_name' => $table_name . '.hall_name',
            'room_name' => $table_name . '.room_name',
        ];
        // 显示代理 需要的用户名
        if (isset($condition['user_agent_ids'])){
            $select += [
                // 状态
                'user_name' => $table_name . '.user_name'
            ];
        }
        // 显示详情
        if (isset($condition['info'])) {
            $query->leftjoin('lottery', 'lottery.id', '=', $table_name . '.lottery_id');
            $select += [
                'pid'         => 'lottery.pid',
                'open_img'    => 'lottery.open_img',
                'play_id'     => $table_name . '.play_id',
                // fast:快速玩法, std标准玩法, chat聊天玩法 , video 视频玩法
                'mode'        => $table_name . '.mode',
                // 注数
                'bet_num'     => $table_name . '.bet_num',
                // 单注金额
                'one_money'   => $table_name . '.one_money',
                // 倍率
                'times'       => $table_name . '.times',
                // 创建时间
                'created'     => $table_name . '.created',
                // 追号类型 1为中奖不停止 2为中奖停止
                'chase_type'  => $table_name . '.chase_type',
            ];
        }
        return $query->select(array_values($select));
    }


    /**
     * 查询订单详情
     * @param $condition
     * @return mixed
     */
    public static function getRecordInfo($condition) {
        $table = self::getRecord($condition);
        return $table->first();
    }

    /**
     * 查询订单记录
     *
     * @param  [type]  $condition [description]
     * @param  integer $page [description]
     * @param  integer $pageSize [description]
     * @return mixed [type]            [description]
     */
    public static function getRecords($condition, $page = 1, $pageSize = 20) {
        $table = self::getRecord($condition);
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 订单列表
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function records($condition, $page = 1, $pageSize = 20) {
        // 订单记录
        $records = self::getRecords($condition, $page, $pageSize);
        $data = $records->toArray()['data'];
        // 其他
        $isShowUsername = isset($condition['user_agent_ids']);
        $result = [];
        foreach ($data ?? [] as $k => $v) {
            $v = (array)$v;
            $result[$k] = isset($result[$k]) ? $result[$k] : [];
            $result[$k]['id'] = $v['id'];
            // 订单号
            $result[$k]['order_number'] = strval($v['chase_number']);
            // 游戏名
            $result[$k]['game_name'] = $v['lottery_name'];
            // 子标题 已追期/总期
            $result[$k]['sub_game_name'] = "{$v['complete_periods']}期/{$v['sum_periods']}期";
            // 玩法名
            $result[$k]['play_name'] = $v['play_group'] . ' > ' . $v['play_name'];
            // 输赢
            $result[$k]['profit'] = sprintf("%s元", (string)($v['profit'] / 100.0));
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = sprintf("下注%s元", (string)($v['increment_bet'] / 100.0));
            //PC 传统二合一
            if(strpos($v['hall_name'],'PC') !== false){
                $v['hall_name'] = $v['room_name'] = '传统';
            }
            $result[$k]['hall_room'] = $v['hall_name'] == $v['room_name'] ? $v['hall_name'] : $v['hall_name'].$v['room_name'];

            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $v['user_name'];
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            // 描述
            $result[$k]['state'] = self::parseRecordsState($v['state']);
        }
        return array($result, $records->total());
    }

    /**
     * 自营彩票 - 追号
     * @param $condition
     * @return array
     */
    public static function detail($condition) {
        $logic = new \LotteryPlay\Logic();
        $info = LotteryChaseOrder::getRecordInfo($condition);
        $sub = LotteryChaseOrderSub::getRecords($condition);
        // 订单追号 以目前的设计都一致，直接取第一单就可以了
        if (isset($sub) && count($sub) < 1) {
            return [];
        } else {
            $sub_info = $sub[0];
        }
        // 总共中奖金额
        $send_money = 0;
        foreach ($sub as $item) {
            $send_money += $item->send_money;
        }
        // 模式
        if (strstr($info->mode, 'fast')) {
            $betting_model = '快捷';
        } else {
            // if (strstr($info->mode, 'std'))
            $betting_model = '标准';
        }
        // 投注信息
        $play_numbers_list = $logic->getPretty($info->pid, $info->play_id, $sub_info->play_number);
        $play_numbers = '';
        foreach ($play_numbers_list as $item) {
            if (strlen($play_numbers) > 0){
                $play_numbers .= "\n";
            }
            $play_numbers .= $item['title'] . " : " . join(',', $item['value']);
        }
        // 赔率信息
        $temp_odds = '';
        foreach (json_decode($sub_info->odds, true) as $k => $v) {
            if (strlen($temp_odds) > 0){
                $temp_odds .= "\n";
            }
            $temp_odds .= $k . ' : ' . $v;
        }
        $detail = [
            [
                'key' => '投注模式:',
                'value' => "传统模式<{$betting_model}>"
            ],
            [
                'key' => '追号订号:',
                'value' => $info->chase_number
            ],
            [
                'key' => '玩法名称:',
                'value' => $info->play_group . ' > ' . $info->play_name
            ],
            [
                'key' => '投注内容:',
                'value' => $play_numbers
            ],
            [
                'key' => '投注赔率:',
                'value' => $temp_odds
            ],
            [
                'key' => '投注注数:',
                'value' => $info->bet_num . '注'
            ],
            [
                'key' => '单注金额:',
                'value' => ($info->one_money / 100) . '元'
            ],
            [
                'key' => '投注倍数:',
                'value' => $info->times . '倍'
            ],
            [
                'key' => '投注时间:',
                'value' => $info->created
            ],
        ];
        $result = [
            // pid
            'pid' => $info->pid,
            // logo
            'logo' => $info->open_img,
            // 彩种名
            'game_name' => $info->lottery_name,
            // 状态
            'sub_game_name' => LotteryChaseOrder::parseRecordsState($info->state),
            // 投注金额 转为真实金额
            'pay_money' => (string)($info->increment_bet / 100.0) . '元',
            // 中奖金额
            'send_money' => (string)($send_money / 100.0) . '元',
            // 追期
            'chase_progress' => "{$info ->complete_periods}期/{$info ->sum_periods}期",
            // 追号停止
            'chase_type' => $info->chase_type
        ];
        $result['detail'] = $detail;
        $result['chase_list'] = $sub;
        return $result;
    }


    /**
     * 过滤条件详情
     * @param bool $isShowState
     * @return array
     */
    public static function filterDetail(bool $isShowState){
        // 过滤彩种
        $filter = [];
        $data = \Model\Lottery::getAllChildLottery() ?? [];
        $filter[] = ['key' => '', 'name' => '全部'];
        foreach ($data ?? [] as $v) {
            $v = (array)$v;
            $filter[] = [
                // id
                'key' => (string)$v['id'],
                // 游戏名
                'name' => $v['name'],
            ];
        }
        // 显示状态
        if ($isShowState){
            $state = OrderRecordCommon::TYPE_TARGET_STATE['CPZH'];
            $result[] = [
                'title' => '状态',
                'category' => 'state',
                'list' => $state,
            ];
        }
        $result[] = [
            'title' => '彩种类型',
            'category' => 'lottery_id',
            'list' => $filter,
        ];
        return $result;
    }
}

