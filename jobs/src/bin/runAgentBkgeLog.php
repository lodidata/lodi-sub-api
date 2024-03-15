<?php

use Logic\Set\SystemConfig;
use Model\GameMenu;
use Logic\User\Bkge;

$ci = $app->getContainer();

$limit = 1000;
$offset = 0;
$bkge_settle_type = 1;
$data = [];
while(1) {
    $data = \DB::table('agent_loseearn_bkge')
                ->selectRaw("proportion_list, bet_amount, bet_amount_list, loseearn_amount_list,loseearn_amount, user_name, user_id, agent_name, active_user_num, fee_amount, bkge_time, date")
                ->where('status',1)
                ->where('type', 1)
                ->where('bkge','!=','0.00')
                ->offset($offset)
                ->limit($limit)
                ->get();
    $data && $data = $data->toArray();

    if(empty($data)) {
        break;
    }

    $bkge = new Bkge($ci);
    if($data){
        $agent_profit_loss      = $bkge->getAgentProfitLoss();
        $game_list              = GameMenu::getGameTypeList();
        $count_self             = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_calculation_self'] ?? 0;  //1:统计自身，0:不统计

        $bkge_list = [];
        foreach ($game_list as $vv){
            $bkge_list[$vv] = 0;
        }

        //返佣条件
        $profit_condition       = $bkge->getProfitCondition($bkge_settle_type);
        if(!$profit_condition) throw new \Exception("没有设置返佣条件:{$bkge_settle_type}");

        $data_num = count($data);

        for($i=0; $i<$data_num; $i++) {
            $v                  = (array)$data[$i];

            //占成是根据是否统计自身的盈亏计算的  周结算和月结算会重新计算
            $proportion_list    = json_decode($v['proportion_list'],true) ?? [];
            if($count_self){
                $loseearn_amount_for_count = $v['loseearn_amount'] ?? 0;
            }else{
                $loseearn_amount_for_count = $v['sub_loseearn_amount'] ?? 0;
            }

            $bet_amount_list = json_decode($v['bet_amount_list'],true);

            $bkge_update_data   = [];
            $bkge               = 0;
            $sub_agent_data     = [];
            $memo               = ''; //备注

                //一级代理 出现过user_name不存在的bug
                if(!$v['agent_name'] && $v['user_name']){
                    //获取直属下级代理数据
                    $sub_agent_data = \DB::table('agent_loseearn_bkge')
                        ->selectRaw("bet_amount, bet_amount_list, sub_loseearn_amount_list, proportion_list, loseearn_amount, fee_amount, user_name, user_id")
                        ->where('date',$v['date'])
                        ->where('agent_name',$v['user_name'])
                        ->get();

                    if($sub_agent_data){
                        $sub_agent_data          = $sub_agent_data->toArray();
                        //循环直属代理
                        $sub_agent_data_num = count($sub_agent_data);
                        for($j=0;$j<$sub_agent_data_num;$j++){
                            $sub_info            = (array)$sub_agent_data[$j];
                            $sub_loseearn_list   = json_decode($sub_info['sub_loseearn_amount_list'],true);
                            $sub_proportion_list = json_decode($sub_info['proportion_list'],true);
                            $sub_bet_amount_list = json_decode($sub_info['bet_amount_list'],true);

                            //计算各个游戏佣金
                            $bkgeLogData = [];
                            foreach ($game_list as $key => $game_type) {
                                //游戏盈亏为负
                                $subLoseearnList = $sub_loseearn_list[$game_type] ?? 0;
                                if ($subLoseearnList == 0) continue;
                                //结算盈亏参与比
                                if (isset($agent_profit_loss[$game_type])) {
                                    if ($agent_profit_loss[$game_type]['is_take'] == 1) {
                                        $profit_loss_ratio = bcdiv($agent_profit_loss[$game_type]['ratio'], 100, 2);
                                    } else {
                                        $profit_loss_ratio = 0;
                                    }
                                } else {
                                    $profit_loss_ratio = 1;
                                }

                                //总返佣 = 电子返佣+真人返佣+捕鱼返佣+体育返佣.....
                                //直属下级盈亏（不包含直属下级自身盈亏，是直属下级的直属下级盈亏）
                                //电子返佣 = 参与比*(代理返佣比-直属代理返佣比)*(直属下级电子盈亏-直属下级电子盈亏/自身和直属下级总盈亏*自身和直属下级总成本)
                                $sub_agent_game_proportion = ($proportion_list[$game_type] ?? 0) - ($sub_proportion_list[$game_type] ?? 0);
                                //代理返佣比-直属代理返佣比 小于0就跳过
                                if($sub_agent_game_proportion <= 0 || $profit_loss_ratio ==0) continue;
                                $sub_game_bkge             = $profit_loss_ratio * $sub_agent_game_proportion / 100 * ($subLoseearnList - $sub_bet_amount_list[$game_type] / $sub_info['bet_amount'] * $sub_info['fee_amount']);
                                //佣金为负
                                if ($sub_game_bkge == 0) continue;
                                $bkge_list[$game_type]     = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);
                                $bkge                      = bcadd($bkge, $sub_game_bkge, 2);

                                //记录用户不同游戏类型的返佣记录
                                $bkgeLogData[$key]['user_id'] = $sub_info['user_id'];
                                $bkgeLogData[$key]['user_name'] = $sub_info['user_name'];
                                $bkgeLogData[$key]['agent_id'] = $v['user_id'];
                                $bkgeLogData[$key]['agent_name'] = $v['user_name'];
                                $bkgeLogData[$key]['bkge_money'] = bcmul($sub_game_bkge, 100, 0);
                                $bkgeLogData[$key]['game_type'] = $game_type;
                                $bkgeLogData[$key]['date'] = date('Y-m-d', strtotime($v['bkge_time']));
                            }
                            if(!empty($bkgeLogData)) {
                                \DB::table('agent_bkge_log')->insert($bkgeLogData);
                            }
                            unset($sub_agent_data[$j]);
                        }
                    }
                }

            unset($data[$i]);
        }
    }

    $offset += $limit;
}

