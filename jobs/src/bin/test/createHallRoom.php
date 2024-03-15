<?php

/**
 * 根据彩票分类生成大厅，房间，赔率
 */

$pid = isset($argv[2]) ? $argv[2] : 0;
$lottery_id = isset($argv[3]) ? intval($argv[3]) : 0;
if($pid <= 0){
    exit('非法输入');
}

$hallLevelArray = [
   /* '1' => '回水厅',
    '2' => '保本厅',
    '3' => '高赔率厅',
    '4' => 'PC房',*/
    '5' => '传统',
    //'6' => '直播',
];
$roomArray = [
    1 => [
        1 => 'VIP房1',
        2 => 'VIP房2',
        3 => 'VIP房3',
        4 => 'VIP房4',
    ],
    2 => [
        1 => 'VIP房1',
        2 => 'VIP房2',
        3 => 'VIP房3',
        4 => 'VIP房4',
    ],
    3 => [
        1 => 'VIP房1',
        2 => 'VIP房2',
        3 => 'VIP房3',
        4 => 'VIP房4',
    ],
    4 => [
        5 => 'PC房'
    ],
    5 => [
        5 => '传统'
    ],
];

echo 'pid:'.$pid.'--lottery_id:'.$lottery_id.PHP_EOL;
//分类
$lotterydb = DB::table('lottery')
    ->whereRaw("FIND_IN_SET('enabled', state)")
    ->whereRaw("FIND_IN_SET('standard', state)")
    ->where('pid', $pid);
if($lottery_id){
    $lotterydb->where('id', $lottery_id);
}
$lotteryArray = $lotterydb->get(['id','pid','name','alias'])->toArray();
try{
    if($lotteryArray){
        createHall($lotteryArray, $hallLevelArray, $roomArray);
        createHotLottery($lotteryArray);
    }
    echo 'success';
}catch (\Exception $e){
    print_r($e->getMessage());
}






//生成大厅
function createHall($lotteryArray, $hallLevelArray, $roomArray){
    foreach ($lotteryArray as $lottery){
        $lottery = (array)$lottery;
        foreach ($hallLevelArray as $levelKey => $levelName){
            $data = [
                'hall_level'    => $levelKey,
                'lottery_id'    => $lottery['id'],
                'hall_name'     => $levelName,
                'rebet_config'  => '',
                'rebet_desc'    => 'VIP',
                'min_bet'       => '200',
                'max_bet'       => '10000000',
                'min_balance'   => '3.00',
                'is_pc'         => '0',
                'rebot_min'     => '200',
                'rebot_max'     => '1000',
                'rebot_list'    => 'rebot',
                'rebet_condition' => '[{"type":"betting_gt","value":"1","checked":true}]',
                'type'          => $lottery['alias'],
                'per_max'       => '1000000',
                'rebot_way'     => '{"type":"betting","data":{"status":"percentage","value":["100,100000000;0.2;0"]}}',
                'rebet_ceiling' => '100000',
            ];
            try{
               $res = DB::table('hall')->updateOrInsert(['hall_level' => $levelKey, 'lottery_id' => $lottery['id']], $data);
               if($res){
                   //生成房间
                   $hall_id = DB::table('hall')->max('id');
                   echo '生成房间号hall_id:'.$hall_id.PHP_EOL;
                   if(isset($roomArray[$levelKey])){
                       foreach($roomArray[$levelKey] as $room_id => $room_name){
                           $data2 = [
                               'lottery_id' => $lottery['id'],
                               'hall_id'    => $hall_id,
                               'room_level' => $room_id,
                               'room_name'  => $room_name,
                               'number'     => 100,
                           ];
                           try{
                               DB::table('room')->insert($data2);
                           }catch (\Exception $e){
                               throw $e;
                           }

                       }
                   }

                   //生成赔率配置
                   $user_leavel = [1,2,3,4,5,6,7,8];
                   foreach($user_leavel as $k => $l){
                        $data3 = [
                            'user_level_id' => $l,
                            'hall_id'       => $hall_id,
                            'rebet_desc'    => $data['rebet_desc'],
                            'rebet_condition' => $data['rebet_condition'],
                            'rebet_ceiling' => $data['rebet_ceiling'],
                            'rebot_way'     => $data['rebot_way'],
                            'game3th_id'    => 0,
                            'status_switch' => 1,
                        ];
                        try{
                            DB::table('rebet_config')->insert($data3);
                        }catch (\Exception $e){
                            throw $e;
                        }
                   }

                   //生成赔率
                   $baseOdds = DB::table('lottery_play_base_odds')->get()->toArray();
                   foreach ($baseOdds as $odds){
                       $odds = (array)$odds;
                       //生成大厅对应赔率lottery_play_odds
                       unset($odds['id'],$odds['created'],$odds['updated']);
                       $odds['hall_id'] = $hall_id;
                       $odds['lottery_id'] = $lottery['id'];
                       try{
                           DB::table('lottery_play_odds')->insert($odds);
                       }catch (\Exception $e){
                           throw $e;
                       }

                        //生成限制赔率
                       if($odds['play_sub_id'] == 0){
                           unset($odds['odds'],$odds['max_odds'],$odds['play_sub_id']);
                           $odds['max_betting'] = 20000000;
                           try{
                                DB::table('lottery_play_limit_odds')->insert($odds);
                           }catch (\Exception $e){
                               throw $e;
                           }
                       }
                   }
               }

            }catch (\Exception $e){
                throw $e;
            }
        }
    }
}

//生成热门彩票
function createHotLottery($lotteryArray)
{
    $type = [
        [
            'type' => 'chat',
            'state' => 0,
        ],
        [
            'type' => 'standard',
            'state' => 1,
        ],
    ];
    $timeType = [1,2];

    foreach($lotteryArray as $sort => $lottery){
        $lottery = (array)$lottery;
        foreach ($type as $tp){
            foreach ($timeType as $k =>$v){
                $sort++;
                $data = [
                    'lottery_id' => $lottery['id'],
                    'type'       => $tp['type'],
                    'state'      => $tp['state'],
                    'timeType'   => $v,
                    'sort'       => $sort
                ];
                try{
                    DB::table('hot_lottery')->insert($data);
                }catch (\Exception $e){
                    throw $e;
                }
            }
        }

    }
}
