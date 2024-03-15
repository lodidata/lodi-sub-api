<?php

use Utils\Www\Action;
use Model\Lottery;
use Model\Hall;
use Model\LotteryInfo;

return new class extends Action
{
    const TITLE = 'GET 获取指定彩票info';
    const HINT = '开发的技术提示信息';
    const DESCRIPTION = '获取指定彩票info';
    const TAGS = "彩票";
    const QUERY = [
        'id'      => 'int(required) #彩票id',
        /*'hall_id' => 'int() #厅id',
        'origin'  => 'int() # 来源，1:pc, 2:h5, 3:移动端',
        'refresh' => 'int()# 刷新玩法信息  重新从表里读取',*/
    ];
    const SCHEMAS = [
            'id'                 => 'int(required) #彩票id',
            'pid'                => 'int(required) #彩票父id',
            'name'               => 'string(required) #彩票名称',
            'today_count'        => 'int(required) #今天总期数',
            'now_count'          => 'int(required) #今天还剩期数',
            'standard_type_auth' => 'string() #基础玩法',
            'fast_type_auth'     => 'string() #快速玩法',
            'on_sell'            => 'int() #是否开售(1:开售,0:禁售)',
            'state'              => 'set[standard, fast, auto, enabled] #集合(standard:启用标准, fast:启用快速, auto:自动派奖, enabled:有效)',
            'bet_money'          => 'int() #标准投注设定金额默认100分',
            'curperiod'          => [
                'now'            => 'datetime(required) #当前时间',
                'start_time'     => 'datetime(required) #开始时间',
                'end_time'       => 'datetime(required) #结束时间',
                'lottery_number' => 'int(required) #期号',
            ],
            'history_results'    => [
                [
                    'lottery_number' => 'int(required)#期号',
                    'end_time'       => 'datetime(required)#彩票结束时间',
                    'period_result'  => 'string() #开奖结果',
                    'period_code'    => 'string() #开奖号码',
                    'period_code_part'=> 'string() #开奖号码前千万位',
                    'state'          => 'set[enabled, stop, valid, open]() #集合(send: 已派奖, stop:停售, open:已开奖, valid:有效)',
                ]
            ],
            'limit'              => [
                'min_bet' => 'int(required) #最低限额 单位分',
                'one_bet' => 'int(required) #单注最高 单位分',
                'per_max' => 'int(required) #总注最高 单位分',
            ],
            'struct_ver'         => 'int(required) #结构版本号',
            'current_time'  => 'date(required) #当前时间 2022-01-27 10:29:30'
    ];


    public function run() {
        $id = $this->request->getQueryParam('id', 0);
        $hallId = $this->request->getQueryParam('hall_id', 0);

        if ($this->auth->isPcPlatform()) {
            $origin = 1;
        } else if ($this->auth->isH5Platform()) {
            $origin = 2;
        } else {
            $origin = 3;
        }

        if ($hallId == 0) {
            if ($origin == 1) {
                $hallId = Hall::where('lottery_id', $id)
                              ->where('is_pc', 1)
                              ->where('hall_level', 4)
                              ->value('id');
            } else {
                $hallId = Hall::where('lottery_id', $id)
                              ->where('is_pc', 1)
                              ->where('hall_level', 5)
                              ->value('id');
            }
        }

        $id = intval($id);
        $hallId = intval($hallId);

        return $this->getLotteryInfo($id, $hallId);
    }

    /**
     * 彩票info
     *
     * @param int $id 彩票ID
     * @param int $hallId 厅ID
     *
     * @return 返回相应的彩票详情
     * @todo 加上盘口选择
     */
    protected function getLotteryInfo($id, $hallId) {
        $hall = Hall::where('id', $hallId)
                    ->select(['type', 'min_bet'])
                    ->first();

        if (!$hall) {
            return false;
        }

        $type_name = $hall->type;

        $info = Lottery::where('id', $id)
                       ->select([
                           'id',
                           'state',
                           'all_bet_max',
                           'per_bet_max',
                           'name',
                           'switch',
                           'pid',
                           'start_delay'
                       ])
                       ->first()
                       ->toArray();

        if (empty($info)) {
            return false;
        }

        $info['bet_money'] = 100;

        if ($info['pid'] == 0 || empty($info)) {
            return false;
        }

        //获取彩种延期开盘时间
        $peroidCount = LotteryInfo::getCachePeriodCount($id);
        $info['curperiod'] = LotteryInfo::getCacheCurrentPeriod($id);
        $info['curperiod'] = $info['curperiod'] ? $info['curperiod'] : null;
        $info['on_sell'] = isset($info['curperiod']['lottery_number']) ? 1 : 0;

        //开盘延迟
        if(is_array($info['curperiod']) && isset($info['curperiod']['start_time'])) {
            $info['curperiod']['start_time'] = $info['curperiod']['start_time'] + $info['start_delay'];
        }

        if ($id == 52) {
            $info['today_count'] = 3;
        } else {
            $info['today_count'] = isset($peroidCount['today_count']) ? $peroidCount['today_count'] : 0;
        }

        $info['now_count'] = isset($peroidCount['now_count']) ? $peroidCount['now_count'] : 0;
        $info['sale_time'] = $this->saleTime($id);
        $info['type'] = $type_name;

        /**
         * 获取 url GET 的 cache 参数
         * 不传入默认 cache=1 从缓存获取
         * 对实时性要求比较高的可以 url 传入 cache=0，则直接从取数据库获取数据
         */
        if ($this->request->getQueryParam('cache', true)) {
            $info['history_results'] = LotteryInfo::getCacheHistory($id);
        } else {
            $info['history_results'] = \DB::table('lottery_info')
                                          ->select(['lottery_number', 'end_time', 'period_result', 'end_time', 'period_code', 'period_code_part', 'official_time', 'state', 'pid'])
                                          ->where('lottery_type', $id)
                                          ->where('updated_at', '>', time() - 24 * 3600)
                                          ->whereRaw('`period_code` IS NOT NULL AND `period_code` != \'\'')
                                          ->whereRaw('FIND_IN_SET(\'open\', `state`)')
                                          ->orderBy('end_time', 'DESC')
                                          ->take(10)
                                          ->get()
                                          ->toArray();

            $info['history_results'] = array_map(function ($row) {
                $row->now = time();
                return $row;
            }, $info['history_results']);
        }

        $lotterCommon = new  \Logic\Lottery\Common();
        foreach ($info['history_results'] as $key => $val) {
            $info['history_results'][$key] = $val = (array)$val;

            if ($val['period_code'] && $id == 52) {
                $period_code_arr = explode(',', $val['period_code']);
                $sx = [];
                foreach ($period_code_arr as $v) {
                    $sx[] = $lotterCommon->getSx($v);
                }
                $info['history_results'][$key]['sx'] = implode(',', $sx);

            } else {
                $info['history_results'][$key]['sx'] = null;
            }
        }
        !empty($info['history_results']) && $info['history_results'] = current($info['history_results']);
        $info['limit'] = [
            'min_bet' => $hall->min_bet,
            'one_bet' => $info['all_bet_max'],
            'per_max' => $info['per_bet_max'],
        ];

        if (!$hallId) {
            $info['limit']['min_bet'] = 100;
        }

        $info["struct_ver"]   = (int)$this->redis->get(\Logic\Define\CacheKey::$perfix['lotteryPlayStructVer'] . $id . '_' . $hallId);
        $info["next_periods"] = LotteryInfo::getCacheNextPeriods($id, 500);

        if($info['curperiod']){
            $info['curperiod']['start_time'] = date('Y-m-d H:i:s', $info['curperiod']['start_time'] );
            $info['curperiod']['insert_number_end_time']  = date('Y-m-d H:i:s', $info['curperiod']['end_time'] + 90 );
            $info['curperiod']['end_time']   = date('Y-m-d H:i:s', $info['curperiod']['end_time'] );
            $info['curperiod']['now']        = date('Y-m-d H:i:s', $info['curperiod']['now'] );
        }

        if($info['history_results']){
            $info['history_results']['official_time'] = date('Y-m-d H:i:s', $info['history_results']['official_time'] );
            $info['history_results']['end_time']      = date('Y-m-d H:i:s', $info['history_results']['end_time'] );
            $info['history_results']['now_time']      = date('Y-m-d H:i:s', $info['history_results']['now_time'] );
        }
        foreach($info["next_periods"] ?? [] as $k => $v){
            $info["next_periods"][$k]['end_time']             = date('Y-m-d H:i:s', $v['end_time']);
            $info["next_periods"][$k]['insert_number_end_time'] = date('Y-m-d H:i:s', $v['end_time'] + 90);
            $info["next_periods"][$k]['short_lottery_number'] = (int)substr($v['lottery_number'],-3);
        }
        //删除数组第一个 他跟当前期一样
        !empty($info['next_periods']) && array_shift($info['next_periods']);

        unset($info['all_bet_max']);
        unset($info['per_bet_max']);
        //服务器当前时间
        $info['current_time'] = date('Y-m-d H:i:s');

        return $info;
    }

    protected function saleTime($id) {
        $jnd28 = $this->jnd28();
        $saleTime = [
            2   => '9:00-23:55', //北京幸运28
            3   => '7:00-5:00', //韩国幸运28
            4   => '7:00-23:55', //台湾幸运28
            6   => '9:28-22:28', //广西快三
            7   => '8:30-22:10', //江苏快三
            8   => '8:40-22:00', //安徽快三
            9   => '8:20-21:33', //吉林快三
            11  => '7:30-03:10', //重庆时时彩
            13  => '9:00-23:00', //天津时时彩
            14  => '10:00-2:00', //新疆时时彩
            15  => '9:00-20:30', //排列五
            16  => '24小时', //分分彩
            17  => '7:00-23:55', //五分彩
            18  => '7:00-5:00', //1.5分彩
            19  => '24小时', //三分彩
            21  => '9:00-20:30', //福彩3D
            22  => '9:00-20:30', //排列三
            23  => '10:30-21:30', //上海时时乐
            25  => '9:00-23:00', //江西11选5
            26  => '8:25-22:05', //江苏11选5
            27  => '9:00-23:00', //广东11选5
            28  => '8:25-22:55', //山东11选5
            29  => '8:30-22:00', //安徽11选5
            30  => '8:50-23:50', //上海11选5
            32  => '8:53-22:53', //天津快乐十分
            33  => '9:00-23:00', //广东快乐十分
            34  => '9:00-23:00', //湖南快乐十分
            35  => '9:00-23:00', //重庆快乐十分
            40  => '9:10-23:30', //北京赛车
            42  => '9:00-23:55', //北京快乐8
            43  => $jnd28[43], //加拿大幸运28
            44  => $jnd28[44], //加拿大快乐8
            45  => '13:03-04:08', //幸运飞艇
            52  => '每周二、周四、周六/21:30开奖', //香港六合彩
            99  => '24小时', //菲律宾时时彩
            100 => '24小时', //菲律宾三分彩
            101 => '24小时', //菲律宾1.5分彩
            102 => '24小时', //菲律宾五分彩
            103 => '24小时', //菲律宾幸运28
            104 => '24小时', //菲律宾幸运28
            105 => '24小时', //吉隆坡幸运28
            106 => '24小时', //极速赛车
            107 => '24小时', //极速赛车
            108 => '24小时', //极速赛车
            109 => '24小时', //极速赛车
        ];

        return isset($saleTime[$id]) ? $saleTime[$id] : '';
    }

    public function jnd28(){
        //夏令时
        $re = [];
        if(\Utils\Utils::isDst()) {
            $re[43] = date('w') == 1 ? '21:00-18:59' : '20:00-18:59'; //加拿大幸运28
            $re[44] = date('w') == 1 ? '21:00-18:59' : '20:00-18:59'; //加拿大快乐8
        }else {  //冬令时
            $re[43] = date('w') == 1 ? '22:00-19:59' : '21:00-19:59'; //加拿大幸运28
            $re[44] = date('w') == 1 ? '22:00-19:59' : '21:00-19:59'; //加拿大快乐8
        }
        return $re;
    }
};
