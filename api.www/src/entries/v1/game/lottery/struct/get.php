<?php

use Utils\Www\Action;
use Model\Lottery;
use Model\Hall;
use Model\Room;
use Model\LotteryPlayStruct;
use Model\LotteryPlayOdds;
use Model\LotteryOrder;

return new class extends Action
{
    const TITLE = "GET 获取玩法结构";
    const HINT = "开发的技术提示信息";
    const DESCRIPTION = "获取玩法结构";
    const TAGS = "彩票";
    const QUERY = [
        "lottery_id" => "int(required) #彩票id",
      //  "hall_id"    => "int() #厅ID",
       // "origin"     => "int(required) # 来源，1:pc, 2:h5, 3:移动端",
    ];
    const SCHEMAS = [
        [
            'standard' => [
                [
                    'nm' => 'string #玩法名称 前三，前二',
                    'child' => [
                        [
                            'nm' => 'string #玩法名称 三中一',
                            'plid' => 'integer #玩法ID',
                            'plid_type' => 'int #玩法类型',
                            'pltx1' => 'string #玩法提示',
                            'pltx2' => 'string #玩法中奖提示',
                            'vn' => 'string #计算注数方法名称 默认valid',
                            'args' => 'array #valid参数',
                            'tags' => 'array #标签',
                            'sv' => 'array #标签选号显示数据(前端显示用)',
                            'vv' => 'array #标签选号提交数据与sv对应(提交订单用)',
                            'tp' => 'array #标签选号提示与sv对应',
                            'odds' => 'array #赔率 [3.69]',
                            'max_win' => 'int #最大可赢次数 1',
                            'buy_ball_num' => "int #最大购买球数 0不限制"
                        ]
                    ],
                ]
            ],
        ]
    ];


    public function run() {
        $rHallId = (int)$this->request->getQueryParam('hall_id', 0);
        $lotteryId = (int)$this->request->getQueryParam('lottery_id', 0);
        $origin = LotteryOrder::getOrigin($this->auth->getCurrentPlatform());
        if ($lotteryId == 0) {
            return $this->lang->set(10);
        }

        $merge = $this->redis->get(\Logic\Define\CacheKey::$perfix['lotteryPlayStruct'] . $lotteryId . '_' . $rHallId);
        //$merge = "";
        if (empty($merge)) {
            if ($rHallId == 0) {
                /**
                 * 新需求
                 * hall_level = 4 为PC厅赔率，hall_level = 5 为传统厅赔率
                 * 新需求PC与传统不做区分，PC直接使用传统的赔率
                 */

                /*
                $hall = Hall::where('lottery_id', $lotteryId)
                            ->where('is_pc', 1)
                            ->where('hall_level', $origin == 1 ? 4 : 5)
                            ->first();
                */

                $hall = Hall::where('lottery_id', $lotteryId)
                            ->where('is_pc', 1)
                            ->where('hall_level', 5)
                            ->first();

                $hallId = $hall['id'];
            } else {
                $hall = Hall::where('id', $rHallId)
                            ->first();
                $hallId = $rHallId;
            }

            if (empty($hall)) {
                return $this->lang->set(61);
            }

            $lottery = Lottery::where('id', $lotteryId)
                              ->select(['pid','all_bet_max','per_bet_max'])
                              ->first()
                              ->toArray();

            if (empty($lottery)) {
                return $this->lang->set(61);
            }

            if ($hall['hall_level'] == 4 || $hall['hall_level'] == 5) {
                $struct = LotteryPlayStruct::where('lottery_pid', $lottery['pid'])
                                           ->where('open', 1)
                                           ->whereIn('model', ['标准', '快捷'])
                                           ->orderBy('sort', 'desc')
                                           ->get()
                                           ->toArray();
            } else if ($hall['hall_level'] == 6) {
                $struct = LotteryPlayStruct::where('lottery_pid', $lottery['pid'])
                                           ->where('open', 1)
                                           ->whereIn('model', ['直播'])
                                           ->orderBy('sort', 'desc')
                                           ->get()
                                           ->toArray();
            } else {
                $struct = LotteryPlayStruct::where('lottery_pid', $lottery['pid'])
                                           ->where('open', 1)
                                           ->whereIn('model', ['聊天'])
                                           ->orderBy('sort', 'desc')
                                           ->get()
                                           ->toArray();
            }

            if (empty($struct)) {
                return $this->lang->set(62);
            }

            $struct = $this->toStruct($struct);

            $odds = LotteryPlayOdds::where('lottery_id', $lotteryId)
                                   ->where('hall_id', $hallId)
                                   ->get()
                                   ->toArray();

            if (empty($odds)) {
                return $this->lang->set(63);
            }

            $merge = $this->merge($lotteryId, $hallId, $struct, $odds, $hall['is_pc']);
            if (!empty($merge)) {
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['lotteryPlayStruct'] . $lotteryId . '_' . $rHallId, 30, json_encode($merge, JSON_UNESCAPED_UNICODE));
            }
        } else {
            $merge = json_decode($merge, true);
        }
        return $merge;
    }

    protected function toStruct($data) {
        $lists = [];
        $listsChat = [];
        foreach ($data as $key => $val) {
            $lists[$val['play_id']] = [
                'name'         => [$val['model'], $val['group'], $val['name']],
                'tags'         => json_decode($val['tags'], true),
                'pltx1'        => $val['play_text1'],
                'pltx2'        => $val['play_text2'],
                'vn'           => $val['valid'],
                'args'         => json_decode($val['args'], true) ? json_decode($val['args'], true) : null,
                'buy_ball_num' => (int)$val['buy_ball_num'],
            ];

            $listsChat[] = [
                'plid'         => $val['play_id'],
                'name'         => [$val['model'], $val['group'], $val['name']],
                'tags'         => json_decode($val['tags'], true),
                'pltx1'        => $val['play_text1'],
                'pltx2'        => $val['play_text2'],
                'vn'           => $val['valid'],
                'args'         => json_decode($val['args'], true) ? json_decode($val['args'], true) : null,
                'buy_ball_num' => (int)$val['buy_ball_num'],
            ];
        }

        $struct = ['standard' => [], 'fast' => [], 'chat' => [], 'vedio' => []];//标准，快捷，直播，聊天
        $maps = ['standard' => [], 'fast' => [], 'chat' => [], 'vedio' => []];
        // 生成第一层数据
        foreach ($lists as $key => $val) {
            if ($val['name'][0] == '标准') {
                $maps['standard'][] = $val['name'][1];
            } else if ($val['name'][0] == '快捷') {
                $maps['fast'][] = $val['name'][1];
            } else if ($val['name'][0] == '直播') {
                $maps['vedio'][] = $val['name'][1];
            } else if ($val['name'][0] == '聊天') {
                $maps['chat'][] = $val['name'][1];
            }
        }

        foreach (array_unique($maps['standard']) as $val) {
            $struct['standard'][] = ['nm' => $val];
        }

        foreach (array_unique($maps['fast']) as $val) {
            $struct['fast'][] = ['nm' => $val];
        }

        foreach (array_unique($maps['vedio']) as $val) {
            $struct['vedio'][] = ['nm' => $val];
        }

        // 生成第二层数据，标准
        foreach ($struct['standard'] as $key => $val) {
            $node = [];
            foreach ($lists as $key2 => $val2) {
                if ($key2 == 0) {
                    continue;
                }

                if ($val2['name'][0] == '标准' && $val2['name'][1] == $val['nm']) {
                    $structType = $this->getStructType($key2);
                    $node[] = [
                        'nm'           => $val2['name'][2],
                        'plid'         => $key2,
                        'plid_type'    => $structType['plid_type'],
                        'max_win'      => $structType['max_win'],
                        'tags'         => $val2['tags'],
                        'pltx1'        => $val2['pltx1'],
                        'vn'           => $val2['vn'],
                        'args'         => $val2['args'],
                        'pltx2'        => $val2['pltx2'],
                        'buy_ball_num' => (int)$val2['buy_ball_num'],
                    ];
                }
            }


            $val['child'] = $node;
            $struct['standard'][$key] = $val;
        }

        //快捷
        foreach ($struct['fast'] as $key => $val) {
            $node = [];
            foreach ($lists as $key2 => $val2) {
                if ($key2 == 0) {
                    continue;
                }

                if ($val2['name'][0] == '快捷' && $val2['name'][1] == $val['nm']) {
                    $structType = $this->getStructType($key2);
                    $node[] = [
                        'nm'           => $val2['name'][2],//玩法名称
                        'plid'         => $key2,//玩法id
                        'plid_type'    => $structType['plid_type'],//玩法类型
                        'max_win'      => $structType['max_win'],//最大可赢次数
                        'tags'         => $val2['tags'],//玩法标签
                        'pltx1'        => $val2['pltx1'],//玩法描述1
                        'vn'           => $val2['vn'],//计算注数方法名称
                        'args'         => $val2['args'],//valid参数
                        'pltx2'        => $val2['pltx2'],//玩法描述2
                        'buy_ball_num' => (int)$val2['buy_ball_num'],//最大购买球数
                    ];
                }
            }
            $val['child'] = $node;
            $struct['fast'][$key] = $val;
        }

        //直播
        foreach ($struct['vedio'] as $key => $val) {
            $node = [];
            foreach ($lists as $key2 => $val2) {
                if ($key2 == 0) {
                    continue;
                }
                if ($val2['name'][0] == '直播' && $val2['name'][1] == $val['nm']) {
                    $structType = $this->getStructType($key2);
                    $node[] = [
                        'nm'           => $val2['name'][2],
                        'plid'         => $key2,
                        'plid_type'    => $structType['plid_type'],
                        'tags'         => $val2['tags'],
                        'pltx1'        => $val2['pltx1'],
                        'vn'           => $val2['vn'],
                        'args'         => $val2['args'],
                        'pltx2'        => $val2['pltx2'],
                        'buy_ball_num' => (int)$val2['buy_ball_num'],
                    ];
                }
            }
            $val['child'] = $node;
            $struct['vedio'][$key] = $val;
        }

        $node = [];
        foreach ($listsChat as $key2 => $val2) {
            $structType = $this->getStructType($val2['plid']);
            if ($val2['name'][0] == '聊天') {
                $node = [
                    'nm'           => $val2['name'][2],
                    'plid'         => $val2['plid'],
                    'plid_type'    => $structType['plid_type'],
                    'max_win'      => $structType['max_win'],
                    'tags'         => $val2['tags'],
                    'pltx1'        => $val2['pltx1'],
                    'vn'           => $val2['vn'],
                    'args'         => $val2['args'],
                    'pltx2'        => $val2['pltx2'],
                    'buy_ball_num' => (int)$val2['buy_ball_num'],
                ];
                $struct['chat'][] = $node;
            }
        }

        // if (isset($config['mode']) && $config['mode'] == 'dev') {
        //     $struct['fields'] = [
        //         'standard' => '标准玩法',
        //         'fast' => '快捷玩法',
        //         'chat' => '聊天',
        //         'child' => 'array 子节点',
        //         'nm' => 'string 玩法名称',
        //         'plid' => 'integer 玩法ID',
        //         'pltx1' => 'string 玩法提示',
        //         'pltx2' => 'string 玩法提示',
        //         'vn' => 'string 计算注数方法名称',
        //         'args' => 'array valid参数',
        //         'tags' => 'array 标签',
        //         'sv' => 'array 标签选号显示数据(前端显示用)',
        //         'vv' => 'array 标签选号提交数据与sv对应(提交订单用)',
        //         'tp' => 'array 标签选号提示与sv对应',
        //     ];
        // }
        if (empty($struct['standard'])) {
            unset($struct['standard']);
        }

        if (empty($struct['fast'])) {
            unset($struct['fast']);
        }

        if (empty($struct['chat'])) {
            unset($struct['chat']);
        }

        if (empty($struct['vedio'])) {
            unset($struct['vedio']);
        }
        return $struct;
    }

    protected function merge($lotteryId, $hallId, $struct, $odds) {
        $playIdOdds = [];
        foreach ($odds as $k => $v) {
            if (!isset($playIdOdds[$v['play_id']])) {
                $playIdOdds[$v['play_id']] = [];
            }
            $playIdOdds[$v['play_id']][$v['name']] = $v['odds'];
        }
        if (isset($struct['standard'])) {
            foreach ($struct['standard'] as $k1 => $v1) {
                foreach ($v1['child'] as $k2 => $v2) {
                    $haveOdds = false;
                    foreach ($v2['tags'] as $k3 => $v3) {

                        $odds = [];
                        $v3['odds'] = [];
                        foreach ($v3['vv'] as $k4 => $v4) {
                            if (isset($playIdOdds[$v2['plid']]) && isset($playIdOdds[$v2['plid']][$v4])) {
                                $haveOdds = true;
                                break;
                            }
                        }

                        if ($haveOdds) {
                            foreach ($v3['vv'] as $k4 => $v4) {
                                $odds[] = $playIdOdds[$v2['plid']][$v4];
                            }
                            $v3['odds'] = $odds;
                        }
                        $v2['tags'][$k3] = $v3;
                    }

                    if (!$haveOdds) {
                        $v2['odds'] = array_values($playIdOdds[$v2['plid']]);
                    }

                    $v1['child'][$k2] = $v2;
                }

                $struct['standard'][$k1] = $v1;
            }
        }

        if (isset($struct['fast'])) {
            foreach ($struct['fast'] as $k1 => $v1) {
                foreach ($v1['child'] as $k2 => $v2) {
                    $haveOdds = false;
                    foreach ($v2['tags'] as $k3 => $v3) {

                        $odds = [];
                        $v3['odds'] = [];
                        foreach ($v3['vv'] as $k4 => $v4) {
                            if (isset($playIdOdds[$v2['plid']]) && isset($playIdOdds[$v2['plid']][$v4])) {
                                $haveOdds = true;
                                break;
                            }
                        }

                        if ($haveOdds) {
                            foreach ($v3['vv'] as $k4 => $v4) {
                                $odds[] = $playIdOdds[$v2['plid']][$v4];
                            }
                            $v3['odds'] = $odds;
                        }
                        $v2['tags'][$k3] = $v3;
                    }

                    if (!$haveOdds) {
                        $v2['odds'] = array_values($playIdOdds[$v2['plid']]);
                    }

                    $v1['child'][$k2] = $v2;
                }

                $struct['fast'][$k1] = $v1;
            }
        }

        if (isset($struct['chat'])) {
            foreach ($struct['chat'] as $k1 => $v1) {

                $haveOdds = false;
                foreach ($v1['tags'] as $k3 => $v3) {

                    $odds = [];
                    $v3['odds'] = [];
                    foreach ($v3['vv'] as $k4 => $v4) {
                        if (isset($playIdOdds[$v1['plid']]) && isset($playIdOdds[$v1['plid']][$v4])) {
                            $haveOdds = true;
                            break;
                        }
                    }

                    if ($haveOdds) {
                        foreach ($v3['vv'] as $k4 => $v4) {
                            $odds[] = $playIdOdds[$v1['plid']][$v4];
                        }
                        $v3['odds'] = $odds;
                    }
                    $v1['tags'][$k3] = $v3;
                }

                if (!$haveOdds) {
                    $v1['odds'] = array_values($playIdOdds[$v1['plid']]);
                }

                $struct['chat'][$k1] = $v1;
            }
        }
        if (isset($struct['vedio'])) {
            foreach ($struct['vedio'] as $k1 => $v1) {
                foreach ($v1['child'] as $k2 => $v2) {
                    $haveOdds = false;
                    foreach ($v2['tags'] as $k3 => $v3) {

                        $odds = [];
                        $v3['odds'] = [];
                        foreach ($v3['vv'] as $k4 => $v4) {
                            if (isset($playIdOdds[$v2['plid']]) && isset($playIdOdds[$v2['plid']][$v4])) {
                                $haveOdds = true;
                                break;
                            }
                        }

                        if ($haveOdds) {
                            foreach ($v3['vv'] as $k4 => $v4) {
                                $odds[] = $playIdOdds[$v2['plid']][$v4];
                            }
                            $v3['odds'] = $odds;
                        }
                        $v2['tags'][$k3] = $v3;
                    }

                    if (!$haveOdds) {
                        $v2['odds'] = array_values($playIdOdds[$v2['plid']]);
                    }

                    $v1['child'][$k2] = $v2;
                }

                $struct['vedio'][$k1] = $v1;
            }
        }
        $struct['struct_ver'] = (int)$this->redis->get(\Logic\Define\CacheKey::$perfix['lotteryPlayStructVer'] . $lotteryId . '_' . $hallId);
        return $struct;
    }

    /**
     * 计算最高盈利额,参数type奖金的计算类别，play_type为玩法id，type:算法类型，maxWin:最高中奖注数，data为数据,countObj计算用到的一些参数
     * 单注奖金=注数单注金额赔率*倍数
     * 单注金额=注数单注金额倍数
     * 最高盈利额=单注奖金*N(中奖注数)-单注金额
     * play_type=1 最高中奖一注
     * play_type=2 取所有选项中最高的赔率计算
     * play_type=3 多级奖金计算，如时时彩直选组合
     * play_type=4 根据最高中奖注数N计算
     * play_type=5 取每行最高赔率计算
     * play_type=6 取每行最高赔率两个
     * play_type=7 所有结果组合后，取赔率和最高的一组（3个赔率一组）
     * play_type=8 幸运28大小单双和时时彩百家乐这类特殊计算，所有结果组合后，取赔率和最高的一组
     * play_type=9 如时时彩快捷的大小单双，N行每行一号共组一注，取每行最高赔率两个
     * play_type=10 六合彩的三中二和中二特玩法
     * @var array
     */
    public $PlayTypes = [
        2 => [31, 32, 36, 37, 38, 41, 42, 46, 47, 48, 51, 52, 56, 57, 58, 60, 61, 64, 65, 152, 153, 154, 155, 156, 454, 607, 608, 826, 827, 516, 518, 519, 520, 513],
        3 => [2, 11, 12, 16, 18, 29, 40, 50, 70, 71, 72, 73, 74, 75, 76, 79, 77, 78, 79, 514, 601, 602, 611, 807, 808, 809, 810, 815, 816, 817, 824, 512, 515, 521, 534, 535, 536, 537, 538, 539, 540, 541, 542, 543, 544,545, 534, 547, 548],
        4 => [69, 451, 822],
        5 => [159, 67, 825, 455],
        6 => [68, 80, 614, 823, 452],
        7 => [609, 453],
        8 => [151, 10, 511, 517, 528, 529, 530, 531, 532, 533],
        9 => [81, 82, 83, 84],
        10=> [546, 549, 550, 551, 552, 553, 554],
    ];

    public $max_wins = [
        2   => 5, 11 => 5, 12 => 2, 16 => 4, 18 => 4, 29 => 3, 40 => 3, 50 => 3, 70 => 3, 71 => 3,
        72  => 3, 73 => 3, 74 => 3, 75 => 3, 76 => 4, 77 => 6, 78 => 10, 79 => 10, 601 => 3, 602 => 3,
        611 => 3, 807 => 5, 808 => 10, 809 => 10, 810 => 5, 815 => 10, 816 => 10, 817 => 5, 824 => 3,
        512 => 6, 514 => 6, 515 => 7, 521 => 6, 534 => 252, 535 => 210, 536 => 120, 537 => 165, 538 => 220,
        539 => 286, 540 => 252, 541 => 210, 542 => 120, 543 => 165, 544 => 220, 546 => 286,545 => 286,
        547 => 20, 548 => 15
    ];

    /**
     * @param int $plid 玩法ID
     * @return array
     */
    public function getStructType($plid) {
        $plid_type = 1;
        foreach ($this->PlayTypes as $k => $v) {
            if (in_array($plid, $v)) {
                $plid_type = $k;
                break;
            }
        }
        $max_win = $this->max_wins[$plid] ?? 1;
        return ['plid_type' => $plid_type, 'max_win' => $max_win];
    }
};