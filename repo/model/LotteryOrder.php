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
use Utils\Utils;

class LotteryOrder extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'lottery_order';

    public $timestamps = false;

    protected $fillable = [
        // 'id',
        'user_id',
        'user_name',
        'order_number',
        'lottery_id',
        'lottery_number',
        'play_id',
        'pay_money',
        'play_group',
        'play_name',
        'play_number',
        'odds',
        'bet_num',
        'chase_number',
        'chase_amount',
        'one_money',
        'hall_id',
        'room_id',
        'origin',
        'times',
        'state',
        'marks',
        // 'created',
        // 'updated',
        'odds_ids',
        'settle_odds',
        'win_bet_count',
        'rake_back',
        'open_code',
        'send_money',
        'lose_earn',
        'tags',
    ];


    public static function boot() {

        parent::boot();

        static::creating(
            function ($obj) {
                $obj->odds = is_array($obj->odds) ? json_encode($obj->odds, JSON_UNESCAPED_UNICODE) : $obj->odds;
                $obj->settle_odds = is_array($obj->settle_odds) ? json_encode(
                    $obj->settle_odds, JSON_UNESCAPED_UNICODE
                ) : $obj->settle_odds;
                $obj->odds_ids = is_array($obj->odds_ids) ? json_encode(
                    $obj->odds_ids, JSON_UNESCAPED_UNICODE
                ) : $obj->odds_ids;
            }
        );

        // static::updating(function ($obj) {
        //     $obj->win_bet_count = is_array($obj->win_bet_count) ? json_encode($obj->win_bet_count, JSON_UNESCAPED_UNICODE) : $obj->win_bet_count;
        // });
    }

    // public static function createOrder($order) {
    //     $order2 = []; 
    //     foreach ($order as $key => $value) {
    //         $temp = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    //         $order2[$key] = $temp;
    //     }
    //     return self::create($order2);
    // }

    public static function generateOrderNumber($rand = 999999999, $length = 9) {
        return intval(date('ndhis')) . str_pad(mt_rand(1, $rand), $length, '0', STR_PAD_LEFT);
    }


    /**
     * 将订单状态 转换为前端可以查看的
     * @param $state
     * @param $profit
     * @return array
     */
    public static function parseRecordsState($state,$profit) {
        global $app;
        $ci = $app->getContainer();
        $result = [];
        if (strstr($state, 'winning')) {
            if ($profit > 0){
                $result['state'] = $ci->lang->text("state.winning");
            }else{
                $result['state'] = $ci->lang->text("state.close");
            }
        } else if (strstr($state, 'open')) {
            $result['state'] = $ci->lang->text("state.close");
        } else if (strstr($state, 'canceled')) {
            $result['state'] = $ci->lang->text("state.canceled");
        } else {
            // strstr($state, 'created') or 其他
            $result['state'] = $ci->lang->text("state.created");
        }
        // 模式
        if (strstr($state, 'std')) {
            $result['mode'] = $ci->lang->text("state.std");
        } else if (strstr($state, 'video')) {
            $result['mode'] = $ci->lang->text("state.video");
        } else if (strstr($state, 'chat')) {
            $result['mode'] = $ci->lang->text("state.chat");
        } else if (strstr($state, 'fast')) {
            $result['mode'] = $ci->lang->text("state.fast");
        }
        return $result;
    }

    /**
     * 查看订单记录
     * @param $condition
     * @return mixed [type]            [description]
     */
    private static function getRecord($condition) {
        global $app;
        $table = DB::table('lottery_order')
            ->leftjoin('lottery', 'lottery.id', '=', 'lottery_order.lottery_id')
            ->leftjoin('send_prize', 'send_prize.order_number', '=', 'lottery_order.order_number')
          //  ->leftjoin('hall', 'hall.id', '=', 'lottery_order.hall_id')
           // ->leftjoin('room', 'room.id', '=', 'lottery_order.room_id')
            ->orderBy('lottery_order.id', 'desc');

        // 状态
        if (isset($condition['state'])) {
            switch ($condition['state']) {
                case 'winning':
                    $table->whereRaw("find_in_set('winning', lottery_order.state)");
                    break;
                case 'lose':
                    $table->whereRaw(
                        "find_in_set('open', lottery_order.state) AND !find_in_set('winning', lottery_order.state) "
                    );
                    break;
                default:
                    $table->whereRaw("!FIND_IN_SET('open',lottery_order.state)");
                    break;
            }
        }

        isset($condition['id']) &&  $table->where('lottery_order.id', $condition['id']);
        isset($condition['lottery_number']) && $table->where('lottery_order.lottery_number', $condition['lottery_number']);
        isset($condition['order_number']) && $table->where('lottery_order.order_number', $condition['order_number']);
        isset($condition['lottery_id']) && $table->where('lottery_order.lottery_id', $condition['lottery_id']);
        isset($condition['start_time']) && $table->where('lottery_order.created', '>=', $condition['start_time']);
        isset($condition['end_time']) && $table->where('lottery_order.created', '<=', $condition['end_time']);
        //isset($condition['room_id']) && $table->where('lottery_order.room_id', $condition['room_id']);
        // 加入会员 信息
        if (isset($condition['notInTags']) || isset($condition['user_agent_ids']) || isset($condition['user_name'])) {
            $table->leftJoin('user', 'user.id', '=', 'lottery_order.user_id');
        }
        // 排除测试和试玩用户
        if (isset($condition['notInTags'])) {
            $notInTags = $app->getContainer()->get('settings')['websize']['notInTags'];
            $table->whereNotIn(
                'user.tags', $notInTags
            );
        }

        // 代理数据
        if (isset($condition['user_agent_ids'])){
            $table->whereIn('lottery_order.user_id', $condition['user_agent_ids']);
        }else{
            if (!isset($condition['info']) && !isset($condition['admin_info'])) {
                // 非详情时，才进行 user_id 筛选
                isset($condition['user_id']) && $table->where('lottery_order.user_id', $condition['user_id']);
            }
        }
        // user_name 筛选
        isset($condition['user_name']) && $table->where('user.name', $condition['user_name']);

        $select = [
            'id' => 'lottery_order.id',
            'hall_id' => 'lottery_order.hall_id',
            'room_id' => 'lottery_order.room_id',
            'created' => 'lottery_order.created',
            'order_number' => \DB::raw("concat(lottery_order.order_number,'') as order_number "),
            'user_name' => 'lottery_order.user_name',
            'lottery_id' => 'lottery_order.lottery_id',
            'origin' => 'lottery_order.origin',
            'lottery_number' => 'lottery_order.lottery_number',
            'lottery_name' => DB::raw('lottery.name AS lottery_name'),
            'pid' => 'lottery.pid',
            // 玩法
            'play_group' => 'lottery_order.play_group',
            // 玩法细则
            'lottery_order' => 'lottery_order.play_name',
            'pay_money' => 'lottery_order.pay_money',
            'bet_num' => 'lottery_order.bet_num',
            'one_money' => 'lottery_order.one_money',
            'times' => 'lottery_order.times',
            'odds' => 'lottery_order.odds',
            'win_bet_count' => 'lottery_order.win_bet_count',
            'play_number' => 'lottery_order.play_number',
            'send_money' => DB::raw('send_prize.money as send_money'),
            'play_id' => 'lottery_order.play_id',
           // 'hall_name' => 'hall.hall_name',
         //   'hall_level' => 'hall.hall_level',
        //    'room_name' => 'room.room_name',
            'state' => 'lottery_order.state',
            'user_id' => 'lottery_order.user_id',
            'open_img' => 'lottery.open_img',
            'alias' => 'lottery.alias',
        ];

        if (isset($condition['info'])) {
            $select['period_code'] = DB::raw(
                'lottery_info.period_code'
            );
            $select['period_result'] = DB::raw(
                'lottery_info.period_result'
            );

            $table->leftJoin('lottery_info' ,function ($join) {
                $join->on('lottery_info.lottery_number', '=', 'lottery_order.lottery_number');
                $join->on('lottery_info.lottery_type', '=', 'lottery_order.lottery_id');
            });
        }

        //$table = $table->where('chase_number', '<=', 0);
        $table = $table->select(array_values($select));
        return $table;
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
     * 查询订单列表
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
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getGame($condition, $page = 1, $pageSize = 20) {
        $table = DB::table('lottery_order')->leftjoin(
            'send_prize', function ($join) {
            $join->on('lottery_order.order_number', '=', 'send_prize.order_number');
            $join->on('lottery_order.user_id', '=', 'send_prize.user_id');
        }
        )->join('lottery', 'lottery.id', '=', 'lottery_order.lottery_id')
            ->groupBy('lottery_order.id')
            ->selectRaw(
                'lottery.name, count(lottery_order.id) as bet_times, sum(lottery_order.pay_money) as bet_money, sum(send_prize.pay_money) as valid_bet, sum(earn_money) as money'
            );

        isset($condition['start_time']) &&
        $table = $table->where('lottery_order.created', '>=', $condition['start_time']);
        isset($condition['end_time']) && $table = $table->where('lottery_order.created', '<=', $condition['end_time']);
        isset($condition['user_id']) && $table = $table->where('lottery_order.user_id', $condition['user_id']);
        isset($condition['lottery_id']) && $table = $table->where('lottery_order.lottery_id', $condition['lottery_id']);
        return $table->paginate($pageSize, ['*'], 'page', $page);
    }

    public static function getOrigin($pl) {
        $pls = ['pc' => 1, 'h5' => 2, 'android' => 3, 'ios' => 3];
        return isset($pls[$pl]) ? $pls[$pl] : $pls['pc'];
    }

    /**
     * 打印完整的sql (\Illuminate\Database\Eloquent\Model 打印对于预处理的sql解析,参数绑定并未填充)
     *  抓包mysql的预处理,会有2次TCP包?
     * @TODO 这个方法暂放这里,后续应该移到BaseModel
     */
    public static function getLastSql() {
        $db = DB::connection();
        $db->enableQueryLog();
        $queryLog = $db->getQueryLog();
        //        var_dump($queryLog); exit;
        $sql_format = str_replace('?', '%s', $queryLog[0]['query']);
        $sql = self::_sprintf_array($sql_format, $queryLog[0]['bindings']);
        return $sql;
    }

    protected static function _sprintf_array($format, $arr) {
        return call_user_func_array('sprintf', array_merge((array)$format, $arr));
    }


    /**
     * 获取彩票投注模式
     * @param string $state
     * @param int $hall_level
     * @param string $hall_name
     * @return string
     */
    private static function cpBettingModel(string $state, int $hall_level, string $hall_name) {
        global $app;
        $ci = $app->getContainer();
        if (strstr($state, 'fast')) {
            $tmp = $ci->lang->text("state.fast");
        } else if (strstr($state, 'std')) {
            $tmp = $ci->lang->text("state.std");
        } else {
            $tmp = '';
        }
        //模式那栏  4是PC  5是传统  PC和传统是统一的
        switch ($hall_level) {
            case 4:
            case 5:
                return $ci->lang->text("Traditional model")."<{$tmp}>";
                break;
            case 6:
                return $ci->lang->text("Live mode");
                break;
            default:
                return $ci->lang->text("Room mode")."<{$hall_name}>";
        }

    }

    /**
     * 获取彩票开奖号码
     * @param string $alias
     * @param string $period_code
     * @param string $period_result
     * @return array
     */
    private static function cpLotteryNumber(string $alias, string $period_code, string $period_result) {
        // 图片域名
        global $app;
        $cp_domain_url = $app->getContainer()->get('settings')['upload']['dsn']['local']['domain'] . "/lottery/%s.png";
        // 正常逻辑
        $result = [];
        $code_list = explode(",", $period_code);
        foreach ($code_list as $pos => $code) {
            switch ($alias) {
                case 'SC':
                    $img = sprintf("ic_car%s_indicator", $code);
                    $result[] = [
                        'img' => sprintf($cp_domain_url, $img)
                    ];
                    break;
                case 'XYRB':
                    $result[] = [
                        'title' => $code,
                        'img' => sprintf($cp_domain_url, 'guide_point_yellow')
                    ];
                    break;
                case 'LHC':
                    $lhc = \LotteryPlay\lhc\LHCResult::getNumberInfo($code);
                    $pos == count($code_list) - 1 && $result[] = ['title' => '+'];
                    $result[] = [
                        'title' => $code,
                        'img' => sprintf($cp_domain_url, 'guide_point_' . $lhc['color']),
                        'bottom_img' => sprintf($cp_domain_url, $lhc['en'])
                    ];
                    break;
                default:
                    $result[] = [
                        'title' => $code,
                        'img' => sprintf($cp_domain_url, 'guide_point_yellow')
                    ];
                    break;
            }
        }
        // 幸运28 加入结果
        if ($alias == 'XYRB') {
            $newResult = [];
            foreach ($result as $key => $value){
                $newResult[] = $value;
                if ($key == count($result) - 1){
                    $newResult[] = ['title' => '='];
                }else{
                    $newResult[] = ['title' => '+'];
                }
            }
            // 结果
            $number = intval($period_result);
            if ($number == 0 || $number == 13 || $number == 14 || $number == 27) {
                $img = 'guide_point_black';
            } else if ($number % 3 == 0) {
                $img = 'guide_point_red';
            } else if ($number % 3 == 1) {
                $img = 'guide_point_green';
            } else if ($number % 3 == 2) {
                $img = 'guide_point_blue';
            } else {
                $img = 'guide_point_yellow';
            }

            $newResult[] = [
                'title' => $period_result,
                'img' => sprintf($cp_domain_url, $img)
            ];
            // 赋值
            $result = $newResult;
        }
        return $result;
    }


    /**
     * 订单列表
     * @param $condition
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public static function records($condition, $page = 1, $pageSize = 20) {
        global $app;
        $ci = $app->getContainer();
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
            $result[$k]['order_number'] = strval($v['order_number']);
            // 游戏名
            $result[$k]['game_name'] = $v['lottery_name'];
            $result[$k]['created'] = $v['created'];
            // 订单号
            $result[$k]['sub_game_name'] = (string)$v['lottery_number'];
            // 输赢
            $profit = ($v['send_money'] - $v['pay_money']) / 100.0;
            $result[$k]['profit'] =   (string)($profit);
            //$result[$k]['profit'] = (string)($v['send_money']/100).$ci->lang->text("RMB");
            // 玩法名
//            $result[$k]['play_name'] = $v['play_group'] . ' > ' . $v['play_name'];
            $result[$k]['play_name'] = $v['play_name'].'>{'.Utils::subtext($v['play_number'],6).'}';
            // 投注金额 以分为单位
            $result[$k]['pay_money'] = (string)($v['pay_money'] / 100.0);
            // 显示用户名
            if ($isShowUsername) {
                $result[$k]['user_name'] = $v['user_name'];
            }
            // item 类型
            $result[$k]['type_name'] = $condition['type_name'];
            //PC 传统二合一
            if(strpos($v['hall_name'],'PC') !== false){
                $v['hall_name'] = $v['room_name'] = $ci->lang->text("Traditional");
            }
            $result[$k]['hall_room'] = $v['hall_name'] == $v['room_name'] ? $v['hall_name'] : $v['hall_name'].$v['room_name'];
            // 描述
            $result[$k] += LotteryOrder::parseRecordsState($v['state'],$v['send_money']);
        }
        return array($result, $records->total());
    }



    /**
     * 自营彩票
     * @param $condition
     * @return array
     */
    public static function detail($condition) {
        global $app;
        $ci = $app->getContainer();
        $logic = new \LotteryPlay\Logic();
        $data = self::getRecordInfo($condition);
        if (!isset($data)){
            return [];
        }
        $result = [
            // pid
            'pid' => $data->pid,
            // logo
            'logo' => $data->open_img,
            // 彩种名
            'game_name' => $data->lottery_name,
            // 期号
            'sub_game_name' => $data->lottery_number . $ci->lang->text("issue"),
            // 投注金额 转为真实金额
            'pay_money' => (string)($data->pay_money / 100.0) ,
            // 中奖金额
            'send_money' => (string)($data->send_money / 100.0) ,
        ];
        // 订单状态
        $result += self::parseRecordsState($data->state,$data->send_money);
        // 投注信息
        $play_numbers_list = $logic->getPretty($data->pid, $data->play_id, $data->play_number);
        $play_numbers = '';
        foreach ($play_numbers_list as $pos => $item) {
            if (strlen($play_numbers) > 0){
                $play_numbers .= "\n";
            }
            $play_numbers .= $item['title'] . " : " . join(',', $item['value']);
        }
        // 赔率信息
        $temp_odds = '';
        $odds = json_decode($data->odds, true);
        foreach ($odds as $k => $v) {
            if (strlen($temp_odds) > 0){
                $temp_odds .= "\n";
            }
            $temp_odds .= $k . ' : ' . $v;
        }
        // 开奖号码
        if ($data->period_code == null || $data->period_result == null){
            $open_code = [
                'key' => $ci->lang->text("open code").':',
                'value' => '#'.$ci->lang->text("state.created").'#'
            ];
        }else{
            $code_number = self::cpLotteryNumber($data->alias, $data->period_code, $data->period_result);
            $open_code = [
                'key' => $ci->lang->text("open code").':',
                'list' => $code_number
            ];
        }
        // 投注模式
        $betting_model = self::cpBettingModel($data->state, $data->hall_level, $data->hall_name);
        // 方案详情
        $detail = [
            [
                'key' => $ci->lang->text('Betting mode').':',
                'value' => $betting_model
            ],
            [
                'key' => $ci->lang->text('Note No').':',
                'value' => $data->order_number
            ],
            // 开奖信息
            $open_code,
            // 其他
            [
                'key' => $ci->lang->text('Play name').':',
                'value' => $data->play_group . ' > ' . $data->play_name
            ],
            [
                'key' => $ci->lang->text('Betting content').':',
                'value' => $play_numbers
            ],
            [
                'key' => $ci->lang->text('Betting Odds').':',
                'value' => $temp_odds
            ],
            [
                'key' => $ci->lang->text('Number of bets').':',
                'value' => $data->bet_num .  $ci->lang->text('note')
            ],
            [
                'key' => $ci->lang->text('Single note amount').':',
                'value' => ($data->one_money / 100)
            ],
            [
                'key' => $ci->lang->text('Betting multiple').':',
                'value' => $data->times .  $ci->lang->text('multiple')
            ],
            [
                'key' => $ci->lang->text('Bet time').':',
                'value' => $data->created
            ],
        ];
        $result['detail'] = $detail;
        return $result;
    }

    /**
     * 获取彩票 过滤条件
     * @param bool $isShowState
     * @return array
     */
    public static function filterDetail(bool $isShowState){
        global $app;
        $ci = $app->getContainer();
        // 过滤彩种
        $filter = [];
        $data = \Model\Lottery::getAllChildLottery() ?? [];
        $filter[] = ['key' => '', 'name' => $ci->lang->text('All')];
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
            $state = OrderRecordCommon::TYPE_TARGET_STATE['CPZG'];
            $result[] = [
                'title' => $ci->lang->text('State'),
                'category' => 'state',
                'list' => $state,
            ];
        }
        $result[] = [
            'title' => $ci->lang->text('Lottery Type'),
            'category' => 'lottery_id',
            'list' => $filter,
        ];
        return $result;
    }
}
