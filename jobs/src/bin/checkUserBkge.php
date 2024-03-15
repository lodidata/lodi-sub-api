<?php
/**
 * 返佣
 */

use Model\GameMenu;

$date = $argv[2] ?? null;
$day        = date('d', strtotime($date));
$endDate   = date("Y-m-d", (strtotime($date)-$day*24*60*60));
$startDate = date('Y-m-01',strtotime($endDate));
$month      = date('Y-m', strtotime($startDate));
$user_id = $argv[3] ?? null;

$table_name         = 'agent_loseearn_month_bkge';
$bkge_settle_type   = 3;
$date               = $month;

$data = \DB::table($table_name)
    ->selectRaw("proportion_list, bet_amount, bet_amount_list, loseearn_amount_list,loseearn_amount, user_name, user_id, agent_name,active_user_num,fee_amount")
    ->where('date', $date)
    ->where('user_id',$user_id)
    ->get();
$data && $data = $data->toArray();

if($data){
    global $app;
    $bkge_class = new \Logic\User\Bkge($app->getContainer());
    $agent_profit_loss = $bkge_class->getAgentProfitLoss();
    $game_list         = \Model\GameMenu::getGameTypeList();

    //返佣条件
    $profit_condition       = $bkge_class->getProfitCondition($bkge_settle_type);
    if(!$profit_condition) throw new \Exception("没有设置返佣条件:{$bkge_settle_type}");

    $data_num = count($data);
    for($i=0; $i<$data_num; $i++) {
        $v                  = (array)$data[$i];
        $proportion_list    = json_decode($v['proportion_list'],true);
        $loseearn_list      = json_decode($v['loseearn_amount_list'],true);
        $bet_amount_list    = json_decode($v['bet_amount_list'],true);
        $bkge_update_data   = [];
        $bkge_list          = $bkge_class->getDefaultList($game_list);
        $bkge               = 0;
        $sub_agent_data     = [];
        $memo               = ''; //备注

        //获取满足条件的充值人数 和 新注册直属下级人数
        list($deposit_num, $register_num) = $bkge_class->CountNewUserAndDepositUser($profit_condition['recharge_min'], $v['user_id'], $startDate, $endDate);

        if($deposit_num >= $profit_condition['eff_user'] && $register_num >= $profit_condition['new_user']) {
            //一级代理  出现过 user_name不存在的bug
            if(!$v['agent_name'] && $v['user_name']){
                //获取直属下级代理数据
                $sub_agent_data = \DB::table($table_name)
                    ->selectRaw("user_name, bet_amount, bet_amount_list, sub_loseearn_amount_list, proportion_list, loseearn_amount, fee_amount")
                    ->where('date', $date)
                    ->where('agent_name',$v['user_name'])
                    ->get();

                if($sub_agent_data){
                    $sub_agent_data = $sub_agent_data->toArray();
                    //循环直属代理
                    $sub_agent_data_num = count($sub_agent_data);
                    echo "下级返佣开始\n";
                    for($j=0;$j<$sub_agent_data_num;$j++){
                        $sub_info            = (array)$sub_agent_data[$j];
                        echo "下级用户：".$sub_info['user_name']."\n";
                        $sub_loseearn_list   = json_decode($sub_info['sub_loseearn_amount_list'],true);
                        $sub_proportion_list = json_decode($sub_info['proportion_list'],true);
                        $sub_bet_amount_list = json_decode($sub_info['bet_amount_list'],true);

                        //计算各个游戏佣金
                        foreach ($game_list as $game_type) {
                            //游戏盈亏为负
                            if ($sub_loseearn_list[$game_type] == 0) continue;
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
                            if($sub_agent_game_proportion <= 0 || $sub_info['loseearn_amount'] == 0 || $profit_loss_ratio == 0) continue;
                            $sub_game_bkge = $profit_loss_ratio * $sub_agent_game_proportion / 100 * ($sub_loseearn_list[$game_type] - $sub_bet_amount_list[$game_type] / $sub_info['bet_amount'] * $sub_info['fee_amount']);
                            echo $game_type.':'.$profit_loss_ratio.' * '.$sub_agent_game_proportion.' / 100 * ('.$sub_loseearn_list[$game_type].' - '.$sub_bet_amount_list[$game_type].' / '.$sub_info['bet_amount'].' * '.$sub_info['fee_amount'].")\n";
                            echo "返佣金额：".$sub_game_bkge."\n";
                            //佣金为负
                            //if ($sub_game_bkge <= 0) continue;
                            $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);

                            $bkge                  = bcadd($bkge, $sub_game_bkge, 2);
                        }

                        unset($sub_agent_data[$j]);
                    }
                }
            }

            //计算各个游戏佣金
            echo "自身返佣:\n";
            foreach ($game_list as $game_type) {
                //游戏盈亏等于
                if ($loseearn_list[$game_type] == 0) continue;
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
                //电子返佣 = 参与比*返佣比*(电子盈亏-电子盈亏/代理总盈亏*代理总成本)
                if($v['loseearn_amount'] == 0 || $profit_loss_ratio == 0) continue;
                $sub_game_bkge = $profit_loss_ratio * ($proportion_list[$game_type] ?? 0) / 100 * ($loseearn_list[$game_type] - $bet_amount_list[$game_type] / $v['bet_amount'] * $v['fee_amount']);
                echo $game_type.':'.$profit_loss_ratio.' * '.($proportion_list[$game_type] ?? 0).' / 100 * ('.$loseearn_list[$game_type].' - '.$bet_amount_list[$game_type].' / '.$v['bet_amount'].' * '.$v['fee_amount'].")\n";
                echo "返佣金额：".$sub_game_bkge."\n";
                //佣金为负
                //if ($sub_game_bkge <= 0) continue;
                $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);

                $bkge = bcadd($bkge, $sub_game_bkge, 2);
            }
        }else{
            $memo = "deposit:{$deposit_num},register:{$register_num}";
        }
    }
}else{
    echo '没有返佣数据';
}