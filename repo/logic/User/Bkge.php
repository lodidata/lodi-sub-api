<?php

namespace Logic\User;

use DB;
use Logic\Funds\DealLog;
use Logic\GameApi\Common;
use Logic\Set\SystemConfig;
use Model\Admin\ActiveBkge;
use Model\Funds;
use Model\FundsDealLog;
use Model\GameMenu;
use Model\OrdersReport;
use Model\UserAgent;
use Model\UserData;
use Model\SystemConfig as SystemConfigModel;
use Model\Orders;
use phpDocumentor\Reflection\Types\Self_;
use Logic\Lottery\Rebet;

/**
 * 代理佣金模块
 */
class Bkge extends \Logic\Logic
{

    public function runData(){
        $lock = $this->redis->setnx('user_bkge_settle_supervene',1);
        $this->redis->expire('user_bkge_settle_supervene', 5*60);
        if(!$lock) return;
        $date = date('Y-m-d',strtotime('-1 day'));
        $isAllowRun = $this->redis->hget(\Logic\Define\CacheKey::$perfix['runBkge'], $date);
        // rake back
        $config = $this->ci->get('settings');
        $rakeBack = isset($config['website']['rakeBack']) ? $config['website']['rakeBack'] : false;
        if($isAllowRun || !$rakeBack) return;
        if(\DB::table('bkge')->where('day','>=',$date)->count()) return;
        $page = 1;
        $size = 500;
        while (1) {
            $data = \DB::table('order_game_user_day')->where('date', $date)
                ->groupBy('user_id')->forPage($page,$size)->get([
                    'user_id',
                    'game',
                    \DB::raw('sum(bet) AS bet'),
                ])->toArray();
            if(!$data) break;
            $page++;
            foreach ($data as $val) {
                $this->run($val->user_id, $val->bet, $val->game);
            }
        }
        //redis防重复运算
        $this->redis->del('user_bkge_settle_supervene');
        $this->redis->hset(\Logic\Define\CacheKey::$perfix['runBkge'], $date, 1);
    }
    /**
     * 执行返佣计息
     *
     * @param  $uid 订单用户ID
     * @param  $bet 昨天总投注量
     * @param  $game_type 游戏TYPE
     * @param  string $runMode test = 为测试
     *
     * @return [type]                [description]
     */
    public function run(int $uid ,int $bet, $game_type = '',$runMode = 'bkge')
    {
        $config = $this->ci->get('settings');
        $rakeBack = isset($config['website']['rakeBack']) ? $config['website']['rakeBack'] : false;
        if (!$rakeBack)
            return false;
        /*if (!\Model\Admin\GameMenu::where('type',$game_type)->value('id')) {
            $this->logger->error('返佣类型没有定义', compact('uid', 'bet', 'game_type'));
            return false;
        }*/

        if ($bet <= 0) {
            $this->logger->error('返佣数据找不到', compact('orderNumber', 'type', 'data'));
            return false;
        }
        // 查询代理数据
        $agent = \Model\UserAgent::where('user_id',$uid)->value('uid_agents');

        if (empty($agent)) {
            $this->logger->error('返佣用户不存在', compact( 'agent'));
            return false;
        }
        //自己不返佣，除开自己ID
        $agent = rtrim($agent, $uid);
        $agent = rtrim($agent, ',');

        // 解释出所有代理ids
        $agentUids = explode(',', trim($agent));

        if (empty($agent) || empty($agentUids)) {
            $this->logger->error('返佣不需要计息', compact( 'agentUids'));
            return false;
        }
        //向上取两级
        if(count($agentUids)>2){
            $newagentUids1 = array_pop($agentUids);
            $newagentUids2 = array_pop($agentUids);
            $newagentUids = [$newagentUids2, $newagentUids1];
        }else{
            $newagentUids = $agentUids;
        }
        // 查询层级用户数据
        $levelAgents = UserAgent::leftjoin('user', 'user_agent.user_id', '=', 'user.id')
            //                        ->orderBy('user_agent.id')   牵移以前的数据不能以这排序
                         ->whereIn('user_agent.user_id', $newagentUids)
                         ->selectRaw(
                             'user.wallet_id, user.name, user_agent.user_id,user_agent.uid_agent, user.auth_status'
                         )
                         ->orderByRaw(\DB::raw("FIELD(user_id,$agent)"))
                         ->get()
                         ->toArray();
        if (empty($levelAgents)) {
            $this->logger->info('返佣不需要计息', compact('levelAgents'));
            return false;
        }
        // 查询代理配置数据
        $userAgentConf = SystemConfig::getModuleSystemConfig('rakeBack');

        if (empty($userAgentConf)) {
            $this->logger->error('返佣配置不存在', compact('userAgentConf'));
            return false;
        }
        $bkge_name = \Model\User::where('id',$uid)->value('name');
        $day = date('Y-m-d',strtotime('-1 day'));
        $agentLogic = new Agent($this->ci);
        //算法 (自身返佣比例-下级返佣比例)*投注额
        $agentArrs = [];
        $agentLength = count($levelAgents);
        for ($i = 0; $i < $agentLength; $i++) {
            $levelAgents[$i] = (array)$levelAgents[$i];
            if (in_array('refuse_bkge', explode(',', $levelAgents[$i]['auth_status']))) {
                $this->logger->error('用户禁止返佣 uid:'. $levelAgents[$i]['user_id']);
                continue;
            }
           /* $bkge1 = json_decode($levelAgents[$i]['bkge_json'],true);
            if(!isset($bkge1[$game_type])) {
                $bkge1 = $agentLogic->synchroUserBkge($levelAgents[$i]['user_id'],$levelAgents[$i])['bkge'];
            }
            //自己不返佣
            if (isset($levelAgents[$i + 1])) {
                $bkge2 = json_decode($levelAgents[$i + 1]['bkge_json'],true);
                if(!isset($bkge2[$game_type])) {
                    $bkge2 = $agentLogic->synchroUserBkge($levelAgents[$i + 1]['user_id'],$levelAgents[$i + 1])['bkge'];
                }
                $tempBack = $bkge1[$game_type] - $bkge2[$game_type];
            } else
                $tempBack = $bkge1[$game_type];*/
            $bkge1 = $userAgentConf['bkge1'];
            $bkge2 = $userAgentConf['bkge2'];
            if($agentLength>1 && $i==0){
               $tempBack = $bkge2;
               $bkge1 = $bkge2;
           }else{
               $tempBack = $bkge1;
           }
            $this->logger->info("\Logic\User\Bkge", [
                    'uid'           => $uid,
                    'levelAgents'   => $levelAgents,
                    'bet'           => $bet,
                    'game_type'     => $game_type,
                    'bkge1'         => $bkge1,
                    'bkge2'         => isset($levelAgents[$i + 1]) ? $bkge2 : 0,
                    'tempBack'      => $tempBack,
                ]);
            if ($tempBack <= 0)
                continue;
            $agentArr = [];
            $agentArr['game'] = $game_type;
            $agentArr['user_id'] = $levelAgents[$i]['user_id'];
            $agentArr['user_name'] = $levelAgents[$i]['name'];
            $agentArr['bkge_uid'] = $uid;
            $agentArr['bkge_name'] = $bkge_name;
            $agentArr['wallet_id'] = $levelAgents[$i]['wallet_id'];
            $agentArr['bkge'] = bcdiv($tempBack * $bet, 100, 0);
            $agentArr['bet_amount'] = $bet;
            $agentArr['bkge_value'] = $bkge1;
            $agentArr['cur_bkge'] = $tempBack;
            $agentArr['day'] = $day;
            $agentArr['status'] = 0;
            if ($agentArr['bkge'] > 0) {
                $agentArrs[] = $agentArr;
            }
        }
        // 写入rake log
        if($runMode !== 'bkge' || count($agentArrs) <=0 ) {
            return $agentArrs;
        }
        try {
            $this->db->getConnection()->beginTransaction();
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            foreach ($agentArrs as &$val) {
                // 更新退佣总额
                \Model\UserAgent::where('user_id', $val['user_id'])->update(
                    [
                        'earn_money' => DB::raw("earn_money + {$val['bkge']}"),
                    ]
                );
                // 锁定钱包
                \Model\Funds::where('id', $val['wallet_id'])->lockForUpdate()->first();

                // 增加用户钱包
                $wallet->crease($val['wallet_id'], $val['bkge']);

                //返佣金额可直接提现
                UserData::where('user_id',$val['user_id'])->update(['free_money' => \DB::raw("free_money + {$val['bkge']}")]);

                // 查询余额
                $funds = \Model\Funds::where('id', $val['wallet_id'])->first();
                $memo = $this->lang->text("Subordinate returned Commission").":" . $val['bkge_name'];// . "（" . $val['game'] . '）';

                //流水里面添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($this->ci);
                $dmlData = $dml->getUserDmlData((int)$val['user_id']);

                // 写入流水
                \Model\FundsDealLog::create(
                    [
                        "user_id"           => $val['user_id'],
                        "username"          => $val['user_name'],
                        "deal_type"         => \Model\FundsDealLog::TYPE_AGENT_CHARGES,
                        "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                        "order_number"      => FundsDealLog::generateDealNumber(),
                        "deal_money"        => $val['bkge'],
                        "balance"           => intval($funds['balance']),
                        "memo"              => $memo,
                        "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                        'total_bet'         => $dmlData->total_bet,
                        'withdraw_bet'      => 0,
                        'total_require_bet' => $dmlData->total_require_bet,
                        'free_money'        => $dmlData->free_money,
                    ]
                );
                $val['status'] = 1;
                unset($val['wallet_id']);
                $this->logger->info(
                    "退佣计算成功", $val
                );
            }
            //插入数据
            \DB::table('bkge')->insert($agentArrs);
            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $this->logger->error("退佣计算异常:" . $e->getMessage(), compact('orderNumber', 'type', 'data'));
            return false;
        }

        return true;
    }

    public function bkgeActiveData($sdate,$edate){
        $page = 1;
        $size = 500;
        $config = $this->activeBkgeConfig();
        $day = date('Y-m-d',strtotime('-1 day'));
        foreach ($config as $con) {       //活动返佣配置
            while (1) {
                $data = \DB::table('rpt_agent_game')->leftJoin('game_menu', 'rpt_agent_game.game_type', '=', 'game_menu.type')
                    ->where('count_date', '>=', $day)
                    ->where('count_date', '<=', $day)
                    ->whereIn('game_menu.pid', $con['game_ids'])
                    ->groupBy(['rpt_agent_game.agent_id'])->forPage($page,$size)->get([
                        'rpt_agent_game.agent_id',
                        'rpt_agent_game.agent_name',
                        'game_menu.pid',
                        \DB::raw('SUM(agent_game_bet_amount)*100 AS bet'),
                        \DB::raw('SUM(agent_game_prize_amount)*100 AS prize'),
                    ])->toArray();
                if(!$data) { break;}
                $page++;
                foreach ($data as $val) {
                    $val = (array)$val;
                    $bkge_scale = 0;
                    foreach ($con['rule'] as $c) {   //规则
                        if (in_array('lottery', $con['condition_opt'])) {
                            if ($val['bet'] >= $c['min_lottery'] && $val['bet'] <= $c['max_lottery']) {
                                $bkge_scale = $c['bkge_scale'];
                            }
                        }
                        if (in_array('winloss', $con['condition_opt'])) {
                            if ($val['winloss'] >= $c['min_winloss'] && $val['winloss'] <= $c['max_winloss']) {
                                $bkge_scale = $c['bkge_scale'];
                            } else {
                                $bkge_scale = 0;
                            }
                        }
                    }
                    $remark = [
                        'condition_opt' => $con['condition_opt'],
                        'data_opt' => $con['data_opt'],
                    ];
                    $tmp = [
                        'active_bkge_name' => $con['name'],
                        'user_id' => $val['agent_id'],
                        'user_name' => $val['agent_name'],
                        'bet_amount' => $val['bet'],
                        'active_bkge' => $con['data_opt'] == 'winloss' ? $val['winloss'] * $bkge_scale / 100 : $val['bet'] * $bkge_scale / 100,
                        'prize_amount' => $val['prize'],
                        'cur_bkge' => $bkge_scale,
                        'active_bkge_id' => $con['id'],
                        'day' => $day,
                        'remark' => json_encode($remark),
                    ];
                    try {
                        \DB::table('bkge_active_log')->insert($tmp);
                    } catch (\Exception $e) {
                        $this->logger->error("【团队返佣】 bkgeActiveData ". $e->getMessage(), $tmp);
                    }
                }
            }
        }
    }

    public function bkgeActive($day = ''){
        $page = 1;
        $size = 500;
        $day = $day ? $day : date('Y-m-d',strtotime('-1 day'));
        $game = GameMenu::where('pid', 0)->get(['id', 'name'])->toArray();
        $game = array_column($game, 'name', 'id');
        while (1) {
            $data = \DB::table('bkge_active_log')->where('day','=',$day)
                ->where('active_bkge','>',0)
                ->forPage($page,$size)->get()->toArray();
            if(!$data) break;
            $page++;
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            foreach ($data as $val){
                $val = (array)$val;
                $exp_ids = UserAgent::where('uid_agent',$val['user_id'])->pluck('user_id')->toArray();
                $exp_money = \DB::table('bkge_active_log')->where('day','=',$day)
                    ->where('active_bkge_id',$val['active_bkge_id'])
                    ->where('active_bkge','>',0)
                    ->whereIn('user_id',$exp_ids)
                    ->value(\DB::raw('sum(active_bkge)'));
                $money = $val['active_bkge'] - $exp_money;
                try{
                    $this->db->getConnection()->beginTransaction();
                    // 更新退佣总额
                    \Model\UserAgent::where('user_id', $val['user_id'])->update(
                        [
                            'earn_money' => DB::raw("earn_money + {$money}"),
                        ]
                    );
                    //$wallet_id = \DB::table('user')->where('id',$val['user_id'])->value('wallet_id');
                    $wallet_id = (new Common($this->ci))->getUserInfo($val['user_id'])['wallet_id'];
                    // 锁定钱包
                    \Model\Funds::where('id', $wallet_id)->lockForUpdate()->first();

                    // 增加用户钱包
                    $wallet->crease($wallet_id, $money);

                    //返佣金额可直接提现
                    UserData::where('user_id',$val['user_id'])->update(['free_money' => \DB::raw("free_money + {$money}")]);

                    // 查询余额
                    $funds = \Model\Funds::where('id', $wallet_id)->first();
                    $memo = $this->lang->text("Commission return activity - subordinate team Commission return").":" . ($money/100);

                    //流水里面添加打码量可提余额等信息
                    $dml = new \Logic\Wallet\Dml($this->ci);
                    $dmlData = $dml->getUserDmlData((int)$val['user_id']);

                    // 写入流水
                    \Model\FundsDealLog::create(
                        [
                            "user_id"           => $val['user_id'],
                            "username"          => $val['user_name'],
                            "deal_type"         => \Model\FundsDealLog::TYPE_AGENT_CHARGES,
                            "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                            "order_number"      => FundsDealLog::generateDealNumber(),
                            "deal_money"        => $money,
                            "balance"           => intval($funds['balance']),
                            "memo"              => $memo,
                            "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                            'total_bet'         => $dmlData->total_bet,
                            'withdraw_bet'      => 0,
                            'total_require_bet' => $dmlData->total_require_bet,
                            'free_money'        => $dmlData->free_money,
                        ]
                    );
                    $t = [
                        "game" => $val['active_bkge_name'],
                        "user_id" => $val['user_id'],
                        "user_name" => $val['user_name'],
                        "bkge_uid" => 0,
                        "bkge_name" => $this->lang->text("Group Commission return activities"),
                        "bkge" => $money,
                        "bet_amount" => $val['bet_amount'],
                        "cur_bkge" => $val['cur_bkge'],
                        "day" => $day,
                    ];
                    //插入数据
                    \DB::table('bkge')->insert($t);
                    \DB::table('bkge_active_log')->where('id','=',$val['id'])->update(['status'=>1]);

                    //发送用户消息通知
                    $content = $this->lang->text("Dear customer '%s', (team back service activity meets the standard) the system has sent '%s' yuan to your account");
                    $exchange = 'user_message_send';
                    \Utils\MQServer::send($exchange,[
                        'user_id' => $val['user_id'],
                        'user_name' => $val['user_name'],
                        'title' => $this->lang->text("Group Commission return activities"),
                        'content' => vsprintf($content, [$val['user_name'],$money/100]),
                    ]);
                    $this->db->getConnection()->commit();
                }catch (\Exception $e){
                    $this->logger->error("【团队返佣】 bkgeActive ". $e->getMessage(), $val);
                    $this->db->getConnection()->rollback();
                }
            }
        }

    }


    public function activeBkgeConfig() {
        $date = date('Y-m-d H:i:s');
        $data = \Model\Admin\ActiveBkge::where('status','enabled')->where('stime','<=',$date)->where('etime','>=',$date)->orderBy('id','desc')->get([
            'id',
            'name',
            'game_ids',
            'condition_opt',
            'data_opt',
        ])->toArray();
        $rule = \DB::table('active_bkge_rule')->orderBy('min_lottery')->get([
            'active_bkge_id',
            'rule_name',
            'min_lottery',
            'max_lottery',
            'min_deposit',
            'max_deposit',
            'min_winloss',
            'max_winloss',
            'bkge_scale',
        ])->toArray();
        $rule = $this->getValues($rule,'active_bkge_id');
        foreach ($data as &$val){
            $val = (array)$val;
            $val['game_ids'] = explode(',',$val['game_ids']);
            $val['condition_opt'] = explode(',',$val['condition_opt']);
            $val['rule'] = array_values($rule[$val['id']]);
        }
        return $data;
    }

    public function getValues($data,$column){
        $res = [];
        foreach ($data as $k=>$v){
            $v = (array)$v;
            $res[$v[$column]][] = $v;
        }
        return $res;
    }

    /**
     * 新代理返佣
     */
    public function newBkgeRunData($date = null,$dateType=null){
        $time = time();
        switch($dateType){
            case "week":
                if(!empty($date)){
                    $start_date = $date;
                }else{
                    if(date('w') == 1){
                        //上周一
                        $start_date = date("Y-m-d", strtotime('last monday'));
                    }else{
                        //上周一
                        $start_date = date("Y-m-d", strtotime('-1 week last monday'));
                    }
                }

                $bkgStartTime=date('Y-m-d 00:00:00', (time() - ((date('w') == 0  ? 7 : date('w')) - 1) * 24 * 3600));
                $bkgEndTime=date('Y-m-d 23:59:59', (time() + (7 - (date('w') == 0 ?  7 : date('w'))) * 24 * 3600));

                $end_date   = date('Y-m-d', strtotime("-1 sunday",time()));
                break;
            case "month":
                if(!empty($date)){
                    $start_date = date('Y-m-01',strtotime($date));
                }else{
                    //上个月第一天
                    $start_date =  date('Y-m-01',strtotime('first day of -1 month',$time));
                }
                //每个月最后一天
                $end_date   = date('Y-m-d', strtotime("$start_date +1 month -1 day"));

                $bkgStartTime=date('Y-m-d 00:00:00', strtotime(date('Y-m', time()) . '-01 00:00:00'));
                $bkgEndTime=date('Y-m-d 23:59:59', strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00'));
                break;
            default:

                break;
        }

        $count_date = date('Ym', strtotime($start_date));
        try{
            $bkgeConfig      = \Model\Admin\ActiveBkge::where('status','enabled')->first(['new_bkge_set']);
            if(!$bkgeConfig){
                //活动不存在
                return false;
            }
            //获取状态正常 能返佣 有会员 的代理
            $uid_list = \DB::table('user_agent as ua')
                ->join('user as u','u.id','=','ua.uid_agent','inner')
                ->where('ua.uid_agent','!=', 0)
                ->where('u.state',1)
                ->whereRaw("!FIND_IN_SET('refuse_bkge',u.auth_status)")
                ->groupBy('ua.uid_agent')
                ->get(['ua.uid_agent','u.name']);
            $uid_list && $uid_list= $uid_list->toArray();

            $new_bkge_set    = json_decode($bkgeConfig->new_bkge_set, true);

            if($uid_list){
                foreach ($uid_list as $val) {
                    $res = \DB::table('new_bkge')
                              ->where('bkge_time', '>=',$bkgStartTime)
                              ->where('bkge_time', '<=',$bkgEndTime)
                              ->where('user_id',$val->uid_agent)->count();
                    //已经返过佣金了
                    if($res) {continue;}
                    $this->newBkgeRun($val->uid_agent, $start_date, $end_date, $new_bkge_set, $val->name, $count_date);
                }
            }
        }catch (\Exception $e){
            $this->logger->error("新代理返佣错:" . $e->getMessage());
            return false;
        }

    }


    /**
     * 执行返佣计息
     *
     * @param  $uid 订单用户ID
     * @param  $bet 昨天总投注量
     * @param  $game_type 游戏TYPE
     * @param  string $runMode test = 为测试
     *
     * @return [type]                [description]
     */
    public function newBkgeRun(int $uid, $beginDate, $endDate ,$newBkgeSet, $user_name, $countDate)
    {

        //关于金额的统计
        $team_money_data = \DB::table('user_agent as ua')
            ->join('rpt_user as ru','ua.user_id','=','ru.user_id','inner')
            ->where('ua.uid_agent',$uid)
            ->where('ru.count_date', '>=', $beginDate)
            ->where('ru.count_date','<=', $endDate)
            ->first([
                \DB::raw('ifnull(sum(bet_user_amount - prize_user_amount),0) winloss'),
                \DB::raw('ifnull(sum(deposit_user_amount),0) deposit_amount'),
                \DB::raw('ifnull(sum(withdrawal_user_amount),0) withdraw_amount'),
                \DB::raw('ifnull(sum(bet_user_amount),0) bet_amount'),
                \DB::raw('ifnull(sum(coupon_user_amount+return_user_amount+turn_card_user_winnings+promotion_user_winnings),0) active_amount'),
            ]);

        //rpt_user表里没数据 就返回
        if($team_money_data->winloss == 0 && $team_money_data->deposit_amount == 0 && $team_money_data->withdraw_amount == 0 && $team_money_data->bet_amount == 0 && $team_money_data->active_amount == 0){
            return;
        }
        $winloss = $team_money_data->winloss;

        //有效用户数
        $valid_user_num = $this->countValidUserNum($uid, $beginDate, $endDate ,$newBkgeSet);
        //返佣金额
        $bkge_money = 0;
        //返佣比例
        $bkge_ratio = 0;

        //有效用户大于等于 规定的人数
        if($valid_user_num >= $newBkgeSet['valid_user_num']){
            //网站输赢 大于 0
            if($winloss > 0){
                $bkge_ratio = $this->countBkgeRatio($winloss,$newBkgeSet['bkge_ratio_rule']);
                //返佣金额 = (网站输赢-网站输赢费用-活动金额-充提费用) * 返佣比例
                $real_winloss = $winloss * (1 - bcdiv($newBkgeSet['winloss_fee_ratio'],100,6)) - $team_money_data->active_amount - ($team_money_data->deposit_amount + $team_money_data->withdraw_amount) * bcdiv($newBkgeSet['deposit_withdraw_fee_ratio'],100,6);
                //网站实际输赢 大于 0
                if($real_winloss > 0){
                    $bkge_money =  bcmul($real_winloss,bcdiv($bkge_ratio,100,6),2);

                }
            }
        }

        $model_data = [
            'user_id'                    => $uid,
            'user_name'                  => $user_name,
            'bkge'                       => $bkge_money,
            'winloss'                    => $winloss,
            'winloss_fee_ratio'          => $newBkgeSet['winloss_fee_ratio'],
            'active_amount'              => $team_money_data->active_amount,
            'deposit_amount'             => $team_money_data->deposit_amount,
            'withdraw_amount'            => $team_money_data->withdraw_amount,
            'bet_amount'                 => $team_money_data->bet_amount,
            'deposit_withdraw_fee_ratio' => $newBkgeSet['deposit_withdraw_fee_ratio'],
            'valid_user_num'             => $valid_user_num,
            'bkge_value'                 => $bkge_ratio,
            'date'                       => $countDate,
            'status'                     => 1,
        ];

        try {
            $wallet    = new \Logic\Wallet\Wallet($this->ci);
            //$wallet_id = \DB::table('user')->where('id', $uid)->value('wallet_id');
            $wallet_id = (new Common($this->ci))->getUserInfo($uid)['wallet_id'];
            $this->db->getConnection()->beginTransaction();
            //插入数据
            \DB::table('new_bkge')->insert($model_data);

            if($bkge_money <= 0){
                $this->db->getConnection()->commit();
                return true;
            }
            $new_bkge_money = bcmul($bkge_money , 100);

            // 更新退佣总额
            \Model\UserAgent::where('user_id', $uid)->update(
                [
                    'earn_money' => DB::raw("earn_money + {$new_bkge_money}"),
                ]
            );
            // 增加用户钱包
            $wallet->crease($wallet_id, $new_bkge_money);

            //返佣金额可直接提现
            UserData::where('user_id',$uid)->update(['free_money' => \DB::raw("free_money + {$new_bkge_money}")]);

            // 查询余额
            $funds = \Model\Funds::where('id', $wallet_id)->first();
            $memo = $this->lang->text("Subordinate returned Commission").":new agent bkge";

            //流水里面添加打码量可提余额等信息
            $dml = new \Logic\Wallet\Dml($this->ci);
            $dmlData = $dml->getUserDmlData((int)$uid);

            // 写入流水
            \Model\FundsDealLog::create(
                [
                    "user_id"           => $uid,
                    "username"          => $user_name,
                    "deal_type"         => \Model\FundsDealLog::TYPE_AGENT_CHARGES,
                    "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                    "order_number"      => FundsDealLog::generateDealNumber(),
                    "deal_money"        => $new_bkge_money,
                    "balance"           => intval($funds['balance']),
                    "memo"              => $memo,
                    "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                    'total_bet'         => $dmlData->total_bet,
                    'withdraw_bet'      => 0,
                    'total_require_bet' => $dmlData->total_require_bet,
                    'free_money'        => $dmlData->free_money,
                ]
            );
            $this->logger->info(
                "新退佣计算成功", $model_data
            );

            $this->db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->db->getConnection()->rollback();
            $this->logger->error("新退佣计算异常:" . $e->getMessage(),$model_data);
            throw new \Exception($e->getMessage());
        }

        return true;
    }


    /**
     * 根据输赢，返回对应的返佣比例
     * @param $winloss
     * @param $bkgeRatioRule
     * @return int
     */
    public function countBkgeRatio($winloss, $bkgeRatioRule){
        $max_bkge_ratio = 0;
        $max_winloss    = 0;

        foreach ($bkgeRatioRule as $v){
            if($winloss >= $v['min_winloss'] && $winloss < $v['max_winloss']){
                return $v['bkge_scale'];
            }
            //获取最大输赢
            $v['max_winloss'] > $max_winloss && $max_winloss = $v['max_winloss'];

            //获取最大返佣比例
            $v['bkge_scale']  > $max_bkge_ratio && $max_bkge_ratio = $v['bkge_scale'];
        }
        //到这里，说明输赢不在设定的区间内
        // 如果比返佣设置的最大输赢还要大  那就返回 最大的返佣比例 否则就返回 0
        if($winloss >= $max_winloss){
            return $max_bkge_ratio;
        }

        return 0;
    }

    /**
     * 计算代理的有效用户数
     * @param $uid
     * @param $newBkgeSet
     * @param $beginDate
     * @param $endDate
     * @return mixed
     */
    public function countValidUserNum($uid, $beginDate, $endDate, $newBkgeSet){
        //有效用户数
        $sub_queary = \DB::table('user_agent as ua')->select('ru.user_id')
            ->join('rpt_user as ru','ua.user_id','=','ru.user_id','inner')
            ->where('ua.uid_agent',$uid)
            ->where('ru.count_date', '>=', $beginDate)
            ->where('ru.count_date','<=', $endDate)
            ->groupBy('ru.user_id')
            ->havingRaw("sum(ru.deposit_user_amount) >= {$newBkgeSet['valid_user_deposit']} and sum(ru.bet_user_amount) >={$newBkgeSet['valid_user_bet']}");
        $valid_user_num = \DB::table('t')->fromSUb($sub_queary, 't')
            ->first([\DB::raw( 'count(t.user_id) valid_user_num')])
            ->valid_user_num;

        return $valid_user_num;
    }

    public function unlimitedAgentBkgeRun($date=null){
        if($date){
            if(strtotime($date) >= strtotime(date('Y-m-d'))){
                return '只能发放今天以前的佣金';
            }
        }
        //默认算昨天的
        !$date && $date = date('Y-m-d',strtotime('-1 day'));

        try{
            //计算平台总投注
            $this->insertPlatEarnlose($date);

            //插入代理投注数据
            $this->insertUnlimitedAgentBkge($date);

            //计算代理佣金
            $this->countUnlimitedAgentBkge($date);

        }catch (\Exception $e){
            $this->logger->error("全民股东返佣计算异常:" . $e->getMessage());
            return $e->getMessage();
        }
        return "{$date} 新代理返佣结束";
    }

    /**
     * 插入 无限代理返佣发放记录表
     * @param $date
     */
    public function insertUnlimitedAgentBkge($date){
        $game_list = GameMenu::getGameTypeList();
        $bet_amount_sql = '';
        foreach ($game_list as $v){
            $bet_amount_sql .= " sum(if(u2.first_recharge_time > 0,ore.bet_amount_list->'$.{$v}',0)) as K{$v} ,";
        }
        $bet_amount_sql = rtrim($bet_amount_sql,',');

        //用户禁止返佣的就不返
        $agent_sql = "select a.*, ifnull(ore1.bet,0) as self_bet_amount ,ifnull(ore1.bet_amount_list,'') as self_bet_amount_list from (select ua.user_id, ua.proportion_type, ua.proportion_value, u.name user_name, ifnull(ua.uid_agent_name,'') agent_name, ifnull(ra.agent_cnt,0) agent_cnt, sum(if(u2.first_recharge_time > 0,ore.bet,0)) bet_amount,ra.deposit_agent_amount deposit_amount,
                            (ra.deposit_agent_amount - ra.withdrawal_agent_amount) revenue_amount, ra.manual_deduction_amount manual_amount,(ra.coupon_agent_amount+ra.return_agent_amount+ra.turn_card_agent_winnings+ra.promotion_agent_winnings) coupon_amount,  {$bet_amount_sql} FROM user_agent ua
                   INNER JOIN (select id pid, id cid from user union select pid, cid from child_agent) ca
                      ON ua.user_id = ca.pid
                   INNER JOIN orders_report ore
                      ON ore.user_id = ca.cid
                      AND ore.date = '{$date}'
                      INNER JOIN user u 
											on ua.user_id = u.id
											AND !find_in_set('refuse_bkge',u.auth_status)
											INNER JOIN user u2 on ca.cid= u2.id
											LEFT JOIN rpt_agent ra
											on ra.agent_id = ua.user_id
											AND ra.count_date = '{$date}'
											group by user_id ) as a left JOIN orders_report ore1
                  ON ore1.user_id = a.user_id
                  AND ore1.date = '{$date}'";

        $agent_data = \Db::select($agent_sql);

        if($agent_data){
            $proportion_config = $this->handleProportionConfig();
            $agentSettle = $this->getAgentSettle();
            $default_data = $this->getDefaultRatioAmountData();

            //平台数据
            $plat_earn_lose_data = [];
            $plat_fee_list = $default_data;
            $i=0;
            $sub_agent_data=[];

            foreach ($agent_data as &$item){
                $i++;
                $bet_amount_list = [];
                $item = (array)$item;
                $item['date']   = $date;
                $item['status'] = 0;
                //获取bet_amount_list
                foreach ($game_list as $v){
                    $bet_amount_list[$v] = bcmul($item["K{$v}"],1,2);
                    unset($item["K{$v}"]);
                }
                $amount_list = array_merge($bet_amount_list, ['deposit_amount' => $item['deposit_amount']], ['revenue_amount' => $item['revenue_amount']], ['manual_amount' => $item['manual_amount']], ['coupon_amount' => $item['coupon_amount']]);

                $item['bet_amount_list'] = json_encode($bet_amount_list);
                //自身投注占成
                if($item['agent_cnt'] >0){
                    //有下级
                    $proportion_list = $this->getAgentProportionList($proportion_config, $bet_amount_list, $item['proportion_type'], $item['proportion_value'], $game_list);
                    $self_proportion_list = $this->getAgentProportionList($proportion_config, json_decode($item['self_bet_amount_list'],true), $item['proportion_type'], $item['proportion_value'], $game_list);
                }else{
                    //普通用户占成  只要没下级  那他所有占成都按这个来算
                    $user_proportion_config = $this->handleProportionConfig(true);
                    $proportion_list = $this->getAgentProportionList($user_proportion_config, $bet_amount_list, $item['proportion_type'], $item['proportion_value'], $game_list);
                    $self_proportion_list = $this->getAgentProportionList($user_proportion_config, json_decode($item['self_bet_amount_list'],true), $item['proportion_type'], $item['proportion_value'], $game_list);
                }

                $item['proportion_list'] = json_encode($proportion_list);
                $item['self_proportion_list'] = json_encode($self_proportion_list);
                $item['bkge_list'] = json_encode([]);
                $item['self_bkge_list'] = json_encode([]);
                $item['self_bet_amount_list'] = $item['self_bet_amount_list'] ? $item['self_bet_amount_list'] : json_encode([]);
                //计算扣费
                $fee_list = $this->countFee($amount_list, $agentSettle, $default_data);
                $item['fee_list'] = json_encode($fee_list);
                $item['fee_amount'] = array_sum($fee_list);

                //平台扣费 只展示一级代理的
                if(!$item['agent_name']){
                    $plat_earn_lose_data['fee_amount'] = bcadd($plat_earn_lose_data['fee_amount'] ?? 0, $item['fee_amount'], 2);
                    $plat_fee_list = $this->addBetAmountList($plat_fee_list, $fee_list);
                }

                unset($item['proportion_type']);
                unset($item['proportion_value']);
                unset($item['coupon_amount']);
                unset($item['manual_amount']);
                unset($item['revenue_amount']);
                unset($item['deposit_amount']);

                array_push($sub_agent_data, $item);
                //每1000条插入一次
                if($i % 1000 == 0){
                    \DB::table('unlimited_agent_bkge')->insert($sub_agent_data);
                    //必须还原数组
                    $sub_agent_data = [];
                }
                unset($item);
            }
            //不足1千条的部分 插入
            \DB::table('unlimited_agent_bkge')->insert($sub_agent_data);
            unset($sub_agent_data);
            unset($item);

            //更新平台总扣款
            $plat_earn_lose_data['fee_list'] = $plat_fee_list;
            $this->updatePlatEarnlose($date, $plat_earn_lose_data,$game_list);
        }
    }

    /**
     * 求和
     * @param $betAmountList
     * @param $betAmount
     * @return mixed
     */
    public function addBetAmountList($betAmountList, $betAmount){

        foreach ($betAmountList as $k => $v){
            $betAmountList[$k] = bcadd($betAmountList[$k], $betAmount[$k]??0,2);
        }

        return $betAmountList;
    }

    public function getDefaultRatioAmountData(){
        $data = [
            'deposit_ratio_amount' => 0,
            'revenue_ratio_amount' => 0,
            'coupon_ratio_amount' => 0,
            'manual_ratio_amount' => 0,
        ];
        $game_type_list = GameMenu::getGameTypeList();

        if($game_type_list){
            foreach ($game_type_list as $v){
                $data[$v] = 0;
            }
        }

        return $data;
    }

    /**
     * 计算代理占成
     * @param $proportionConfig
     * @param $betAmount
     * @param $proportionType
     * @param $proportionValue
     * @return int
     */
    public function getAgentProportionList($proportionConfig, $betAmountList ,$proportionType, $proportionValueList, $gameList){
        $proportion_list = [];

        foreach ($gameList as $game_name){
            $proportion_list[$game_name] = 0;
        }

        $proportionValueListArray = json_decode($proportionValueList, true);

        //计算代理占成   proportionType 占成类型,1:固定,2:自动
        foreach ($proportionConfig as $k => $v){
            $bet_amount = $betAmountList[$k] ?? 0;

            foreach ($v as $val){
                if($bet_amount > $val[0] && $bet_amount <= $val[1]){
                    $proportion_list[$k] = $val[2];
                    break;
                }elseif($bet_amount > $val[1]){
                    //主要是为了超过最大值的时候 按最后一个区间算
                    $proportion_list[$k] = $val[2];
                }
            }

            //固定占成时 $proportion比固定占成少 就取固定占成
            if($proportionType == 1 && isset($proportionValueListArray[$k]) && $proportion_list[$k] < $proportionValueListArray[$k]){
                $proportion_list[$k] = $proportionValueListArray[$k];
            }

        }

        return $proportion_list;
    }

    /**
     * 获取系统占成设置
     * @return array
     */
    public function handleProportionConfig($user=false){
        if($user){
            //无下级用户时的占成
            $proportion_config = SystemConfig::getModuleSystemConfig('agent')['user_proportion'];
        }else{
            $proportion_config = SystemConfig::getModuleSystemConfig('agent')['proportion'];
        }

        $proportion_config = json_decode($proportion_config, true);
        if(!$proportion_config) throw new \Exception('系统设置agent proportion值错误');

        foreach ($proportion_config as $i => $v){
            $v = explode(';',$v);
            $new_val = [];
            foreach ($v as $k => $val){
                $val_array = explode(',',$val);
                //因为后台设置以万为单位
                $new_val[$k][0] = bcmul($val_array[0],10000);
                $new_val[$k][1] = bcmul($val_array[1],10000);
                $new_val[$k][2] = $val_array[2];

            }
            $proportion_config[$i] = $new_val;
        }

        return $proportion_config;
    }

    public function countUnlimitedAgentBkge($date){
        $data = \DB::table('unlimited_agent_bkge as uab')
            ->leftJoin('user as u','u.id','=','uab.user_id')
            ->selectRaw("uab.*,if(u.first_recharge_time>0,1,0) as is_deposit")
            ->where('uab.date',$date)
            ->where('uab.status',0)
            ->get();
        $data && $data = $data->toArray();

        if($data){
            $plat_earnlose_data = (array)\DB::table('plat_earnlose')
                ->where('date',$date)
                ->first();
            $plat_bet_amount_list = json_decode($plat_earnlose_data['bet_amount_list'], true);
            $plat_lose_earn_list  = json_decode($plat_earnlose_data['lose_earn_list'], true);

            $game_list = GameMenu::getGameTypeList();

            foreach ($data as $v){
                $v = (array)$v;
                $proportion_list = json_decode($v['proportion_list'],true);

                $bkge_update_data = [];
                $bkge_list      = $this->getDefaultList($game_list);
                $self_bkge_list = $this->getDefaultList($game_list);
                $bkge = 0;
                //自身佣金
                $self_bkge = 0;
                //如果充过值，流水才计算佣金
                if($v['is_deposit']){
                    //计算自身佣金
                    if($v['self_bet_amount'] > 0){
                        $self_bet_amount_list = json_decode($v['self_bet_amount_list'], true);
                        $self_proportion_list = json_decode($v['self_proportion_list'],true);
                        //计算自身佣金
                        foreach ($game_list as $game_type){
                            //有一个为0的 都退出
                            if($plat_bet_amount_list[$game_type] == 0 || $plat_earnlose_data['proportion'] == 0)  continue;

                            //平台分类盈利为负 也不算佣金
                            if($plat_lose_earn_list[$game_type]-$v['fee_amount'] <= 0) continue;
                            //自身分类投注流水总和/平台分类投注流水*平台分类盈利/总股份*自己流水业绩占成（用自己流水算出来的占成）
                            //最新公式 自身分类投注流水总和/平台分类投注流水*(平台分类盈利-自身成本)/总股份*自己流水业绩占成（用自己流水算出来的占成）
                            $self_game_bkge = $self_bet_amount_list[$game_type]/$plat_bet_amount_list[$game_type]*($plat_lose_earn_list[$game_type]-$v['fee_amount'])/$plat_earnlose_data['proportion']*$self_proportion_list[$game_type];

                            $bkge_list[$game_type] = bcadd($bkge_list[$game_type]??0, $self_game_bkge,2);
                            $self_bkge_list[$game_type] = bcadd($self_bkge_list[$game_type]??0, $self_game_bkge,2);

                            $bkge           = bcadd($bkge, $self_game_bkge,2);
                            $self_bkge      = bcadd($self_bkge, $self_game_bkge,2);
                        }
                    }
                }


                //获取直属下级代理
                $user_id_list =  \DB::table('user_agent')
                    ->where('uid_agent',$v['user_id'])
                    ->pluck('user_id')->toArray();

                if($user_id_list){
                    $sub_agent_data = \DB::table('unlimited_agent_bkge')
                        ->where('date',$date)
                        ->whereIn('user_id',$user_id_list)
                        ->get();
                    $sub_agent_data && $sub_agent_data = $sub_agent_data->toArray();

                    //循环直属代理 计算佣金
                    foreach ($sub_agent_data as $val){
                        $val = (array)$val;
                        $sub_proportion_list = json_decode($val['proportion_list'],true);
                        $sub_bet_amount_list = json_decode($val['bet_amount_list'],true);

                        //计算各个游戏佣金
                        foreach ($game_list as $game_type){
                            //有一个为0的 都退出
                            if($plat_bet_amount_list[$game_type] == 0 || $plat_earnlose_data['proportion'] == 0)  continue;

                            //如果下级占成比上级大 就不算这个代理的
                            if($proportion_list[$game_type] - $sub_proportion_list[$game_type] <= 0) continue;
                            //平台分类盈利-代理线成本 为负 也不算佣金
                            if($plat_lose_earn_list[$game_type]-$val['fee_amount'] <= 0) continue;
                            //自身和下级分类投注流水总和/平台分类投注流水*平台分类盈利/总股份*（上级股份占成-自己业绩占成）
                            //最新公式  自身和下级分类投注流水总和/平台分类投注流水*(平台分类盈利-代理线成本)/总股份*（上级股份占成-自己业绩占成）
                            $sub_game_bkge = $sub_bet_amount_list[$game_type]/$plat_bet_amount_list[$game_type]*($plat_lose_earn_list[$game_type]-$val['fee_amount'])/$plat_earnlose_data['proportion']*($proportion_list[$game_type] - $sub_proportion_list[$game_type]);

                            $bkge_list[$game_type] = bcadd($bkge_list[$game_type]??0, $sub_game_bkge,2);

                            $bkge           = bcadd($bkge, $sub_game_bkge,2);
                        }

                    }
                }

                //佣金大于0
                if($bkge > 0){
                    $bkge_update_data['bkge']      = $bkge;
                    $bkge_update_data['self_bkge'] = $self_bkge;
                    //结算金额 = 佣金
                    $settle_amount = $bkge;
                    //$settle_amount = bcsub($bkge, $v['fee_amount'], 2);
                    $bkge_update_data['settle_amount']  = $settle_amount > 0 ? $settle_amount : 0;
                    $bkge_update_data['bkge_list'] = json_encode($bkge_list);
                    $bkge_update_data['self_bkge_list'] = json_encode($self_bkge_list);
                    $bkge_update_data['status'] = 1;
                    $bkge_update_data['bkge_time'] = date('Y-m-d H:i:s');

                    $res = \DB::table('unlimited_agent_bkge')
                        ->where('date',$date)
                        ->where('user_id',$v['user_id'])
                        ->where('status',0)
                        ->update($bkge_update_data);

                    //更新状态成功 ，且结算金额大于0  开始发钱
                    if($res && $settle_amount > 0){
                        $order_number = $this->sendUnlimitedAgentBkge($v['user_id'], $settle_amount, $v['user_name']);
                        if($order_number){
                            \DB::table('unlimited_agent_bkge')
                                ->where('date',$date)
                                ->where('user_id',$v['user_id'])
                                ->update(['deal_log_no' => $order_number]);

                            $exchange = 'user_message_send';
                            \Utils\MQServer::send($exchange,[
                                'user_id'   => $v['user_id'],
                                'user_name' => $v['user_name'],
                                'title'     => $this->lang->text('Yesterday dividends title'),
                                'content'   => $this->lang->text('Yesterday dividends content',[$settle_amount]),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * 发放无限代理佣金
     * @param $userId
     * @param $bkge
     * @param $userName
     * @return bool|string
     */
    public function sendUnlimitedAgentBkge($userId, $bkge, $userName){
        $money = bcmul($bkge, 100);
        // 更新退佣总额
        \Model\UserAgent::where('user_id', $userId)->update(
            [
                'earn_money' => DB::raw("earn_money + {$money}"),
            ]
        );

        $wallet    = new \Logic\Wallet\Wallet($this->ci);
        //$wallet_id = \DB::table('user')->where('id', $userId)->value('wallet_id');
        $wallet_id = (new Common($this->ci))->getUserInfo($userId)['wallet_id'];
        // 增加用户钱包
        $wallet->crease($wallet_id, $money,2);

        //返佣金额可直接提现
//        UserData::where('user_id',$userId)->update(['free_money' => \DB::raw("free_money + {$money}")]);

        // 查询余额
        $funds = \Model\Funds::where('id', $wallet_id)->first();
        $memo = $this->lang->text("Refund to Shareholder Wallet");

        //流水里面添加打码量可提余额等信息
//        $dml = new \Logic\Wallet\Dml($this->ci);
//        $dmlData = $dml->getUserDmlData($userId);
        $order_number = FundsDealLog::generateDealNumber();
        // 写入流水
        $res = \Model\FundsDealLog::create(
            [
                "user_id"           => $userId,
                "username"          => $userName,
                "deal_type"         => \Model\FundsDealLog::TYPE_AGENT_CHARGES,
                "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                "order_number"      => $order_number,
                "deal_money"        => $money,
                "balance"           => intval($funds['share_balance']),
                "memo"              => $memo,
                "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => 0,
                'free_money'        => intval($funds['share_balance']),
            ]
        );
        //插入成功 返回订单号
        if($res){
            return $order_number;
        }
        return false;
    }

    /**
     * 更新平台扣款数据
     * @param $date
     * @param $plat_earnlose
     * @return int
     */
    public function updatePlatEarnlose($date, $plat_earnlose,$game_list){
        $data['fee_amount'] = $plat_earnlose['fee_amount'];
        $fee_list = $plat_earnlose['fee_list'];

        foreach ($fee_list as $k => $v){
            if(in_array($k,$game_list)){
                $data['bet_ratio_amount'] = bcadd($data['bet_ratio_amount']??0, $v,2);

            }else{
                $data[$k] = $v;
            }
        }
        $res = \DB::table('plat_earnlose')->where('date', $date)->update($data);
        return $res;
    }

    /**
     * 插入平台数据
     * @param $date
     */
    public function insertPlatEarnlose($date){
        $res_data = \DB::table('plat_earnlose')->where('date', $date)->value('id');
        if($res_data) throw new \Exception("{$date} 数据已存在");

        $plat_earnlose = [];
        $game_order_data       = $this->getGameOrderData($date);
        $deopsit_withdraw_data = $this->getDepositWithdrawData($date);

        $plat_earnlose['date']         = $date;
        $plat_earnlose['proportion']   = SystemConfig::getModuleSystemConfig('agent')['shares'];
        $plat_earnlose['bet_amount']   = $game_order_data['bet_amount'];
        $plat_earnlose['prize_amount'] = $game_order_data['prize_amount'];
        $plat_earnlose['bet_amount_list'] = $game_order_data['bet_amount_list'];
        $plat_earnlose['lose_earn_list']  = $game_order_data['lose_earn_list'];
        $plat_earnlose['lose_earn']    = bcsub($plat_earnlose['bet_amount'], $plat_earnlose['prize_amount'], 2);


        $plat_earnlose['revenue_amount'] = bcsub($deopsit_withdraw_data['deposit_amount'], $deopsit_withdraw_data['withdraw_amount'],2);

        \DB::table('plat_earnlose')->insert($plat_earnlose);

    }

    /**
     * 无限代理返佣 计算代理扣费
     * @param $amount
     * @return array
     */
    public function countFee($amount,$agentSettle=[], $data=[]){
        if($agentSettle){
            foreach ($agentSettle as $v){
                $v = (array)$v;
                $ratio = $v['proportion_value'] / 100 * $v['part_value'] / 100;
                switch ($v['type']){
                    //1:流水
                    case 1:
                        $amount_fee = $amount[$v['game_type']] * $ratio;
                        $data[$v['game_type']] = bcadd($data[$v['game_type']]??0, $amount_fee,2);
                        break;
                    //2:充值
                    case 2:
                        $amount_fee = $amount['deposit_amount'] * $ratio;
                        $data['deposit_ratio_amount'] = bcadd($data['deposit_ratio_amount'], $amount_fee,2);
                        break;
                    //3:营收
                    case 3:
                        $amount_fee = $amount['revenue_amount'] * $ratio;
                        //营收为负的 就显示0
                        $amount_fee = $amount_fee > 0 ? $amount_fee : 0;
                        $data['revenue_ratio_amount'] = bcadd($data['revenue_ratio_amount'], $amount_fee,2);
                        break;
                    //4:优惠彩金
                    case 4:
                        $amount_fee = $amount['coupon_amount'] * $ratio;
                        $data['coupon_ratio_amount'] = bcadd($data['coupon_ratio_amount'], $amount_fee,2);
                        break;
                     //5:人工扣款
                    case 5:
                        $amount_fee = $amount['manual_amount'] * $ratio;
                        $data['manual_ratio_amount'] = bcadd($data['manual_ratio_amount'], $amount_fee,2);
                        break;
                    default:

                }
            }
        }

        return $data;

    }

    public function getAgentSettle(){
        $query = \DB::table('user_agent_settle');
        $query->where('settle_status',1)
              ->where('status',1);
        $res = $query->get(['type','game_type','proportion_value','part_value']);
        $res && $res= $res->toArray();
        return $res;
    }

    public function getDefaultList($gameList=null){
        !$gameList && $gameList = GameMenu::getGameTypeList();
        $list = [];
        foreach ($gameList as $v){
            $list[$v] = 0;
        }

        return $list;
    }


    /**
     * 获取某天游戏注单额度
     * @param $date
     * @return array
     */
    public function getGameOrderData($date){
        $game_list = GameMenu::getGameTypeList();
        $bet_amount_sql = '';
        $lose_earn_sql = '';
        $bet_amount_list = [];
        $lose_earn_list = [];

        foreach ($game_list as $v){
            $bet_amount_sql .= " sum(ore.bet_amount_list->'$.{$v}') as K{$v} ,";
            $lose_earn_sql  .= " sum(ore.lose_earn_list->'$.{$v}') as Y{$v} ,";
        }
        $bet_amount_sql = rtrim($bet_amount_sql,',');
        $lose_earn_sql  = rtrim($lose_earn_sql,',');

        //没有充过值的用户不要
        $data_sql = "select ifnull(sum(ore.bet),0) bet_amount, ifnull(sum(ore.send_money),0) prize_amount,  {$bet_amount_sql} , {$lose_earn_sql}
                            FROM orders_report as ore inner join user u on u.id = ore.user_id and u.first_recharge_time > 0
                   WHERE ore.date = '{$date}'";

        $data = \Db::select($data_sql);
        $data && $data = (array)current($data);

        if($data){
            foreach ($game_list as $v){
                $bet_amount_list[$v] = bcmul($data["K{$v}"],1,2);
                unset($data["K{$v}"]);
                $lose_earn_list[$v] = bcmul($data["Y{$v}"],1,2);
                unset($data["Y{$v}"]);
            }
            $data['bet_amount_list'] = json_encode($bet_amount_list);
            $data['lose_earn_list'] = json_encode($lose_earn_list);
        };

        return $data;
    }

    /**
     * 获取某天出入款报表里的数据
     * @param $date
     * @return string
     */
    public function getDepositWithdrawData($date){

        $amount = (array)\DB::table('rpt_deposit_withdrawal_day')
            ->where('count_date',$date)
            ->first([
                'income_amount as deposit_amount',
                'withdrawal_amount as withdraw_amount',
                'coupon_amount',
                'manual_deduction_amount as manual_amount'
            ]);

        if(!$amount){
            $amount = [
                'deposit_amount' => 0,
                'withdraw_amount'=> 0,
                'coupon_amount'=> 0,
                'manual_amount'=> 0
            ];
        }

        return $amount;

    }


    /**
     * 月 盈亏返佣 发放佣金
     * @param $bkgeSettleType
     * @return string
     */
    public function agentLoseearnBkgeMonthStart(){
        $day        = date('d');
        //月结
        $end_date   = date("Y-m-d", strtotime(-$day.' day'));
        $start_date = date('Y-m-01',strtotime($end_date));
        $month      = date('Y-m', strtotime($start_date));

        try{
            $rebet = new Rebet($this->ci);

            if(!$rebet->middleOrder($end_date)){
                throw new \Exception('注单统计数据不对，请排查');
            }
            //计算平台总投注
            $this->insertAgentPlatMonthEarnlose($start_date, $end_date, $month);

            //插入代理投注数据
            $this->insertAgentLoseearnMonthBkge($start_date, $end_date, $month);

            //计算代理佣金
            $this->countAgentLoseearnMonthBkge($start_date, $end_date, $month);

            //同步一级代理盈亏占比
            $fixed_proportion_switch = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'];
            if(isset($fixed_proportion_switch) && !$fixed_proportion_switch){
                $this->userAgentProfit('month',$month);
            }


        }catch (\Exception $e){
            $this->logger->error("代理盈亏月返佣计算异常:" . $e->getMessage());
            return $e->getMessage();
        }
        return "{$month} 代理盈亏返佣结束";

    }

    /**
     * 修改代理占成 然后重新发放佣金
     * 月 盈亏返佣 发放佣金(部分发放，先去月表 修改代理占成字段，然后把需要重新返佣的status设为0)
     * @param $bkgeSettleType
     * @return string
     */
    public function SendSomeAgentLoseearnBkgeMonthStart($month){
        /*$no_user_name = \DB::table($month)
            ->where('date',$month)
            ->where('user_name','')
            ->get(['user_id'])->toArray();

        if($no_user_name){
            foreach ($no_user_name as $v){
                $v = (array)$v;
                $user_id = $v['user_id'];
                $name = \DB::table('user')->where('id',$user_id)->value('name');
                if($name){
                    \DB::table('funds_deal_log')
                        ->where('deal_type',108)
                        ->where('created','>','2022-11-02 14:00:00')
                        ->where('user_id',$user_id)
                        ->update(['username' => $name]);
                }
            }
        }
        return ;die;*/
        try{
            //重新获取占成
            $this->changeAgentLoseearnBkgeMonthProportion($month);
            //return "{$month} 设置占成结束";
            //计算代理佣金
            $this->countAgentLoseearnMonthBkge($month, true);

            //同步一级代理盈亏占比
            $this->userAgentProfit('month',$month);

        }catch (\Exception $e){
            $this->logger->error("代理盈亏月返佣计算异常:" . $e->getMessage());
            return $e->getMessage();
        }
        return "{$month} 代理盈亏返佣结束";

    }

    //重新修改月返佣表里的一些占成  条件可以随时修改
    public function changeAgentLoseearnBkgeMonthProportion($month){
        $table_name = 'agent_loseearn_month_bkge';
        $agent_list = \DB::table($table_name)
            ->where('date',$month)
            ->where('status',0)
            ->where('bkge',0)
            ->get(['user_id'])->toArray();

        if($agent_list){
            foreach ($agent_list as $v){
                $v          = (array)$v;
                $user_id    = $v['user_id'];
                $proportion = \DB::table('user_agent')->where('user_id',$user_id)->value('profit_loss_value');
                if($proportion){
                    \DB::table($table_name)
                        ->where('date',$month)
                        ->where('status',0)
                        ->where('bkge',0)
                        ->where('user_id',$user_id)
                        ->update(['proportion_list' => $proportion]);
                }
            }
        }
    }

    /**
     * 周 盈亏返佣 发放佣金
     * @param $bkgeSettleType
     * @return string
     */
    public function agentLoseearnBkgeWeekStart(){
        $days = date('w') == 0 ? 13 : date('w') + 6;
        //上周一
        $start_date = date('Y-m-d',time()-$days*86400);
        $days = date('w') == 0 ? 7 : date('w');
        //上周日
        $end_date = date('Y-m-d',time()-$days*86400);

        try{
            $rebet = new Rebet($this->ci);

            if(!$rebet->middleOrder($end_date)){
                throw new \Exception('注单统计数据不对，请排查');
            }
            //计算平台总投注
            $this->insertAgentPlatMonthEarnlose($start_date, $end_date);

            //插入代理投注数据
            $this->insertAgentLoseearnMonthBkge($start_date, $end_date);

            //计算代理佣金
            $this->countAgentLoseearnMonthBkge($start_date, $end_date);

            //同步一级代理盈亏占比
            $fixed_proportion_switch = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'];
            if(isset($fixed_proportion_switch) && !$fixed_proportion_switch){
                $this->userAgentProfit('week',$end_date);
            }


        }catch (\Exception $e){
            $this->logger->error("代理盈亏周返佣计算异常:" . $e->getMessage());
            return $e->getMessage();
        }
        return "week {$end_date} 代理盈亏返佣结束";

    }

    public function userAgentProfit($type,$date){
        $proportion_list = [];
        switch($type){
            case 'day':
                $table = 'agent_loseearn_bkge';
                break;
            case 'week':
                $table = 'agent_loseearn_week_bkge';
                break;
            case 'month':
                $table = 'agent_loseearn_month_bkge';
                break;
        }

        $bkge = DB::table($table)
            ->where('date',$date)
            ->where('bkge_time','>',0)
            ->where('agent_name','=','')
            ->get(['user_id','proportion_list']);

        if($bkge){
            foreach($bkge as &$v){
                $this->logger->debug('日期:'.$date.',类型:'.$type);
                $profit_loss = DB::table('user_agent')
                    ->where('user_id',$v->user_id)
                    ->value('profit_loss_value');

                //有的一级代理没有设置占成 用的默认占成
                if(!$profit_loss) continue;
                $profit_loss = json_decode($profit_loss,true);
                $bkge_profit = json_decode($v->proportion_list,true);

                foreach($profit_loss as $key => $val){
                    if($bkge_profit[$key] > $val){
                        $proportion_list[$key] = $bkge_profit[$key];
                    }else{
                        $proportion_list[$key] = $val;
                    }
                }

                if($proportion_list){
                    DB::table('user_agent')->where('user_id',$v->user_id)->update(['profit_loss_value'=>json_encode($proportion_list)]);
                    unset($proportion_list);
                }
                unset($v);
            }
        }
    }

    public function insertAgentPlatMonthEarnlose($startDate, $endDate, $month=null){
        if($month){
            $table_name = 'agent_plat_month_earnlose';
            $date       = $month;
        }else{
            $table_name = 'agent_plat_week_earnlose';
            $date       = $endDate;
        }

        $res_data = \DB::table($table_name)->where('date', $date)->value('id');
        if($res_data) throw new \Exception("{$date} 数据已存在");

        $plat_earnlose = [];
        $game_order_data       = $this->getAgentGameOrderWeekOrMonthData($startDate,$endDate);
        //没有数据
        if(!$game_order_data) throw new \Exception('没有数据');

        $plat_earnlose['date']         = $date;
        //$plat_earnlose['proportion']   = SystemConfig::getModuleSystemConfig('agent')['shares'];
        $plat_earnlose['bet_amount']        = bcmul($game_order_data['bet_amount'],1,2);
        $plat_earnlose['prize_amount']      = bcmul($game_order_data['prize_amount'],1,2);
        $plat_earnlose['revenue_amount']    = bcmul($game_order_data['revenue_amount'],1,2);
        $plat_earnlose['lose_earn']         = bcmul($game_order_data['lose_earn'],1,2);
        $plat_earnlose['bet_amount_list']   = $game_order_data['bet_amount_list'];
        $plat_earnlose['lose_earn_list']    = $game_order_data['lose_earn_list'];

        \DB::table($table_name)->insert($plat_earnlose);

    }


    /**
     *
     * @param $data
     */
    public function agentLoseearnBkgeWeekOrMonthSend($data,$start_date,$end_date){
        $bkge_update_data['bkge_time'] = date('Y-m-d H:i:s');
        $bkge_update_data['count_date'] = $end_date;
        $bkge_update_data['status'] = 1;
        $res = \DB::table('agent_loseearn_bkge')
            ->where('date','>=', $start_date)
            ->where('date','<=', $end_date)
            ->where('user_id',$data['user_id'])
            ->where('status',0)
            ->where('settle_amount','>',0)
            ->update($bkge_update_data);

        if($res){
            $order_number = $this->sendAgentLoseearnBkge($data['user_id'], $data['settle_amount'], $data['user_name']);
            if($order_number){
                \DB::table('agent_loseearn_bkge')
                    ->where('date','>=', $start_date)
                    ->where('date','<=', $end_date)
                    ->where('user_id',$data['user_id'])
                    ->where('status',1)
                    ->where('deal_log_no',0)
                    ->update(['deal_log_no' => $order_number]);

                $exchange = 'user_message_send';
                \Utils\MQServer::send($exchange,[
                    'user_id'   => $data['user_id'],
                    'user_name' => $data['user_name'],
                    'title'     => $this->lang->text('Yesterday dividends title'),
                    'content'   => $this->lang->text('Yesterday dividends content',[$data['settle_amount']]),
                ]);
            }
        }
    }

    /**
     * 代理盈亏返佣
     * @param null $date
     * @return string
     */
    public function agentLoseearnBkgeRun($date=null){
        if($date){
            if(strtotime($date) >= strtotime(date('Y-m-d'))){
                return '只能发放今天以前的佣金';
            }
        }
        //默认算昨天的
        !$date && $date = date('Y-m-d',strtotime('-1 day'));

        try{
            $rebet = new Rebet($this->ci);

            if(!$rebet->middleOrder($date)){
                throw new \Exception('注单统计数据不对，请排查');
            }
            //返佣结算方式   1日 2周 3月
            $bkge_settle_type       = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_settle_type'];
            //计算平台总投注
            $this->insertAgentPlatEarnlose($date);

            //插入代理投注数据
            $this->insertAgentLoseearnBkge($date);

            //日返佣 才在这里进行结算
            if($bkge_settle_type != 1) return "{$date} 代理盈亏返佣插入数据结束";

            //计算代理佣金
            $this->countAgentLoseearnBkge($date, $bkge_settle_type);

            //同步一级代理占成
            //1级默认占比打开则跑
            $fixed_proportion_switch = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'];
            if(isset($fixed_proportion_switch) && !$fixed_proportion_switch) {
                $this->userAgentProfit('day', $date);
            }

        }catch (\Exception $e){
            $this->logger->error("代理盈亏返佣计算异常:" . $e->getMessage());
            return $e->getMessage();
        }
        return "{$date} 代理盈亏返佣结束";
    }

    public function getProfitCondition($type){
        //返佣结算方式   1日 2周 3月
        switch ($type){
            case 1:
                $name = 'daily_condition';
                break;
            case 2:
                $name = 'weekly_condition';
                break;
            case 3:
                $name = 'monthly_condition';
                break;
        }
        $res = SystemConfig::getModuleSystemConfig('profit_loss')[$name];
        return $res ? json_decode($res,true) : $res;
    }

    /**
     * 获取有充值的用户id
     * @param string $beginDate
     * @param string $endDate
     * @param string|array $userId
     * @return array
     */
    public function getRechargeUser(string $beginDate, string $endDate, $userId): array
    {
        $query = DB::table('rpt_user')
            ->select(['user_id'])
            ->distinct()
            ->where('count_date', '>=', $beginDate)
            ->where('count_date', '<=', $endDate)
            ->where('deposit_user_amount', '>', 0)
            ->whereIn('user_id', (array)$userId);
//        print_r($query->getBindings());
//        die($query->toSql());
        $data = $query->get()->pluck('user_id')->toArray();
        return array_combine($data, $data);
    }

    /**
     * 代理盈亏返佣
     * @param $date
     */
    public function countAgentLoseearnBkge($date, $bkge_settle_type){
        //不是日返佣 就退出
        if($bkge_settle_type != 1) return false;

        $data = \DB::table('agent_loseearn_bkge')
                        ->selectRaw("proportion_list, bet_amount, bet_amount_list, loseearn_amount_list,loseearn_amount, user_name, user_id, agent_name, active_user_num, fee_amount")
                        ->where('date',$date)
                        ->where('status',0)
                        ->get();
        $data && $data = $data->toArray();
        $mustHasRecharge = SystemConfig::getModuleSystemConfig('profit_loss')['must_has_recharge'] ?? false;

        if($data){
            $agent_profit_loss      = $this->getAgentProfitLoss();
            $game_list              = GameMenu::getGameTypeList();
            $count_self             = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_calculation_self'] ?? 0;  //1:统计自身，0:不统计

            //返佣条件
            $profit_condition       = $this->getProfitCondition($bkge_settle_type);
            if(!$profit_condition) throw new \Exception("没有设置返佣条件:{$bkge_settle_type}");

            $data_num = count($data);
            for($i=0; $i<$data_num; $i++) {
                $v                  = (array)$data[$i];
                //占成是根据是否统计自身的盈亏计算的  周结算和月结算会重新计算
                $proportion_list    = json_decode($v['proportion_list'],true);
                if($count_self){
                    $loseearn_list_for_count   = json_decode($v['loseearn_amount_list'],true);
                    $loseearn_amount_for_count = $v['loseearn_amount'];
                }else{
                    $loseearn_list_for_count   = json_decode($v['sub_loseearn_amount_list'],true);
                    $loseearn_amount_for_count = $v['sub_loseearn_amount'];
                }

                $bet_amount_list = json_decode($v['bet_amount_list'],true);

                $bkge_update_data   = [];
                $bkge_list          = $this->getDefaultList($game_list);
                $bkge               = 0;
                $sub_agent_data     = [];
                $memo               = ''; //备注

                //获取满足条件的充值人数 和 新注册直属下级人数
                list($deposit_num, $register_num) = $this->CountNewUserAndDepositUser($profit_condition['recharge_min'], $v['user_id'], $date, $date);

                if($deposit_num >= $profit_condition['eff_user'] && $register_num >= $profit_condition['new_user']) {
                    //一级代理 出现过user_name不存在的bug
                    if(!$v['agent_name'] && $v['user_name']){
                        //获取直属下级代理数据
                        $sub_agent_data = \DB::table('agent_loseearn_bkge')
                            ->selectRaw("bet_amount, bet_amount_list, sub_loseearn_amount_list, proportion_list, loseearn_amount, fee_amount, user_name, user_id")
                            ->where('date',$date)
                            ->where('agent_name',$v['user_name'])
                            ->get();

                        if($sub_agent_data){
                            $hasRecharges = [];
                            if($mustHasRecharge) {
                                $hasRecharges = $this->getRechargeUser($date, $date, $sub_agent_data->pluck('user_id')->toArray());
                            }
                            $sub_agent_data          = $sub_agent_data->toArray();
                            //循环直属代理
                            $sub_agent_data_num = count($sub_agent_data);
                            for($j=0;$j<$sub_agent_data_num;$j++){
                                $sub_info            = (array)$sub_agent_data[$j];
                                if ($mustHasRecharge && !isset($hasRecharges[$sub_info['user_id']])) continue;
                                $sub_loseearn_list   = json_decode($sub_info['sub_loseearn_amount_list'],true);
                                $sub_proportion_list = json_decode($sub_info['proportion_list'],true);
                                $sub_bet_amount_list = json_decode($sub_info['bet_amount_list'],true);

                                //计算各个游戏佣金
                                $bkgeLogData = [];
                                foreach ($game_list as $key => $game_type) {
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
                                    if($sub_agent_game_proportion <= 0 || $profit_loss_ratio ==0) continue;
                                    $sub_game_bkge             = $profit_loss_ratio * $sub_agent_game_proportion / 100 * ($sub_loseearn_list[$game_type] - $sub_bet_amount_list[$game_type] / $sub_info['bet_amount'] * $sub_info['fee_amount']);
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
                                    $bkgeLogData[$key]['date'] = date('Y-m-d');
                                }
                                if(!empty($bkgeLogData)) {
                                    \DB::table('agent_bkge_log')->insert($bkgeLogData);
                                }
                                unset($sub_agent_data[$j]);
                            }
                        }
                    }

                    //计算各个游戏佣金
                    foreach ($game_list as $game_type) {
                        //游戏盈亏等于0
                        if ($loseearn_list_for_count[$game_type] == 0) continue;
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
                        //电子返佣 = 参与比*返佣比*(自身和直属下级电子盈亏-自身和直属下级电子盈亏/自身和直属下级总盈亏*自身和直属下级总成本)
                        if($loseearn_amount_for_count==0 || $profit_loss_ratio == 0) continue;
                        $sub_game_bkge          = $profit_loss_ratio * ($proportion_list[$game_type] ?? 0) / 100 * ($loseearn_list_for_count[$game_type] - $bet_amount_list[$game_type] / $v['bet_amount'] * $v['fee_amount']);
                        //佣金为负
                        //if ($sub_game_bkge <= 0) continue;
                        $bkge_list[$game_type]  = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);
                        $bkge                   = bcadd($bkge, $sub_game_bkge, 2);

                    }

                }else{
                    $memo = "deposit:{$deposit_num},register:{$register_num}";
                }

                //佣金不等于0
                if($bkge != 0){
                    $bkge_update_data['bkge']           = $bkge;
                    //结算金额 = 佣金
                    $settle_amount                      = $bkge;
                    //$settle_amount = bcsub($bkge, $v['fee_amount'], 2);
                    //$bkge_update_data['settle_amount']  = $settle_amount > 0 ? $settle_amount : 0;
                    $bkge_update_data['settle_amount']  = $settle_amount;
                    $bkge_update_data['bkge_list']      = json_encode($bkge_list);

                    $bkge_update_data['bkge_time']      = date('Y-m-d H:i:s');
                    $bkge_update_data['count_date']     = $date;
                    $bkge_update_data['status']         = 1;

                    $res = \DB::table('agent_loseearn_bkge')
                        ->where('date',$date)
                        ->where('user_id',$v['user_id'])
                        ->where('status',0)
                        ->update($bkge_update_data);

                    //更新状态成功  开始发钱 小于0就扣钱
                    if($res){
                        $order_number = $this->sendAgentLoseearnBkge($v['user_id'], $settle_amount, $v['user_name']);
                        if($order_number){
                            \DB::table('agent_loseearn_bkge')
                                ->where('date',$date)
                                ->where('user_id',$v['user_id'])
                                ->update(['deal_log_no' => $order_number]);

                            $exchange = 'user_message_send';
                            \Utils\MQServer::send($exchange,[
                                'user_id'   => $v['user_id'],
                                'user_name' => $v['user_name'],
                                'title'     => $this->lang->text('Yesterday dividends title'),
                                'content'   => $this->lang->text('Yesterday dividends content',[$settle_amount]),
                            ]);
                        }
                    }
                }else{
                    \DB::table('agent_loseearn_bkge')
                        ->where('date',$date)
                        ->where('user_id',$v['user_id'])
                        ->where('status',0)
                        ->update([
                            'status' => 1,
                            'memo'   => $memo
                        ]);
                }
                unset($data[$i]);
            }
        }
    }

    public function countAgentLoseearnMonthBkge($startDate, $endDate, $month = false){
        if($month){
            $table_name         = 'agent_loseearn_month_bkge';
            $bkge_settle_type   = 3;
            $date               = $month;
        }else{
            $table_name         = 'agent_loseearn_week_bkge';
            $bkge_settle_type   = 2;
            $date               = $endDate;
        }

        $data = \DB::table($table_name)
            ->selectRaw("proportion_list, bet_amount, bet_amount_list, loseearn_amount_list,loseearn_amount, user_name, user_id, agent_name, active_user_num, fee_amount")
            ->where('date', $date)
            ->where('status',0)
            ->get();
        $data && $data = $data->toArray();
        $mustHasRecharge = SystemConfig::getModuleSystemConfig('profit_loss')['must_has_recharge'] ?? false;

        if($data){
            $agent_profit_loss = $this->getAgentProfitLoss();
            $game_list         = GameMenu::getGameTypeList();

            //返佣条件
            $profit_condition       = $this->getProfitCondition($bkge_settle_type);
            if(!$profit_condition) throw new \Exception("没有设置返佣条件:{$bkge_settle_type}");

            $data_num = count($data);
            for($i=0; $i<$data_num; $i++) {
                $v                  = (array)$data[$i];
                $proportion_list    = json_decode($v['proportion_list'],true);
                $loseearn_list      = json_decode($v['loseearn_amount_list'],true);
                $bet_amount_list    = json_decode($v['bet_amount_list'],true);
                $bkge_update_data   = [];
                $bkge_list          = $this->getDefaultList($game_list);
                $bkge               = 0;
                $sub_agent_data     = [];
                $memo               = ''; //备注

                //获取满足条件的充值人数 和 新注册直属下级人数
                list($deposit_num, $register_num) = $this->CountNewUserAndDepositUser($profit_condition['recharge_min'], $v['user_id'], $startDate, $endDate);

                if($deposit_num >= $profit_condition['eff_user'] && $register_num >= $profit_condition['new_user']) {
                    //一级代理  出现过 user_name不存在的bug
                    if(!$v['agent_name'] && $v['user_name']){
                        //获取直属下级代理数据
                        $sub_agent_data = \DB::table($table_name)
                            ->selectRaw("bet_amount, bet_amount_list, sub_loseearn_amount_list, proportion_list, loseearn_amount, fee_amount, user_name, user_id")
                            ->where('date', $date)
                            ->where('agent_name',$v['user_name'])
                            ->get();

                        if($sub_agent_data){
                            $hasRecharges = [];
                            if($mustHasRecharge) {
                                $hasRecharges = $this->getRechargeUser($startDate, $endDate, $sub_agent_data->pluck('user_id')->toArray());
                            }
                            $sub_agent_data = $sub_agent_data->toArray();
                            //循环直属代理
                            $sub_agent_data_num = count($sub_agent_data);
                            for($j=0;$j<$sub_agent_data_num;$j++){
                                $sub_info            = (array)$sub_agent_data[$j];
                                if ($mustHasRecharge && !isset($hasRecharges[$sub_info['user_id']])) continue;
                                $sub_loseearn_list   = json_decode($sub_info['sub_loseearn_amount_list'],true);
                                $sub_proportion_list = json_decode($sub_info['proportion_list'],true);
                                $sub_bet_amount_list = json_decode($sub_info['bet_amount_list'],true);

                                //计算各个游戏佣金
                                $bkgeLogData = [];
                                foreach ($game_list as $key => $game_type) {
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
                                    //佣金为负
                                    //if ($sub_game_bkge <= 0) continue;
                                    $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);

                                    $bkge                  = bcadd($bkge, $sub_game_bkge, 2);

                                    //记录用户不同游戏类型的返佣记录
                                    $bkgeLogData[$key]['user_id'] = $sub_info['user_id'];
                                    $bkgeLogData[$key]['user_name'] = $sub_info['user_name'];
                                    $bkgeLogData[$key]['agent_id'] = $v['user_id'];
                                    $bkgeLogData[$key]['agent_name'] = $v['user_name'];
                                    $bkgeLogData[$key]['bkge_money'] = bcmul($sub_game_bkge, 100, 0);
                                    $bkgeLogData[$key]['game_type'] = $game_type;
                                    $bkgeLogData[$key]['date'] = date('Y-m-d');
                                }
                                if(!empty($bkgeLogData)) {
                                    \DB::table('agent_bkge_log')->insert($bkgeLogData);
                                }

                                unset($sub_agent_data[$j]);
                            }
                        }
                    }

                    //计算各个游戏佣金
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
                        //佣金为负
                        //if ($sub_game_bkge <= 0) continue;
                        $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);

                        $bkge = bcadd($bkge, $sub_game_bkge, 2);
                    }
                }else{
                    $memo = "deposit:{$deposit_num},register:{$register_num}";
                }

                //佣金大于0
                if($bkge != 0){
                    $bkge_update_data['bkge']           = $bkge;
                    //结算金额 = 佣金
                    $settle_amount                      = $bkge;
                    //$settle_amount = bcsub($bkge, $v['fee_amount'], 2);
                    $bkge_update_data['settle_amount']  = $settle_amount;
                    $bkge_update_data['bkge_list']      = json_encode($bkge_list);

                    $bkge_update_data['bkge_time']      = date('Y-m-d H:i:s');
                    $bkge_update_data['status']         = 1;

                    $res = \DB::table($table_name)
                        ->where('date',$date)
                        ->where('user_id',$v['user_id'])
                        ->where('status',0)
                        ->update($bkge_update_data);

                    //更新状态成功 开始发钱
                    if($res){
                        $order_number = $this->sendAgentLoseearnBkge($v['user_id'], $settle_amount, $v['user_name']);
                        if($order_number){
                            \DB::table($table_name)
                                ->where('date',$date)
                                ->where('user_id',$v['user_id'])
                                ->update(['deal_log_no' => $order_number]);

                            $exchange = 'user_message_send';
                            \Utils\MQServer::send($exchange,[
                                'user_id'   => $v['user_id'],
                                'user_name' => $v['user_name'],
                                'title'     => $this->lang->text('Yesterday dividends title'),
                                'content'   => $this->lang->text('Yesterday dividends content',[$settle_amount]),
                            ]);
                        }
                    }
                }else{
                    \DB::table($table_name)
                        ->where('date', $date)
                        ->where('user_id',$v['user_id'])
                        ->where('status',0)
                        ->update([
                            'memo'   => $memo,
                            'status' => 1
                        ]);
                }
                
                unset($data[$i]);
            }
        }
    }

    /**
     * 代理盈亏返佣
     * 发放佣金
     * @param $userId
     * @param $bkge
     * @param $userName
     * @return bool|string
     */
    public function sendAgentLoseearnBkge($userId, $bkge, $userName){
        $money = bcmul($bkge, 100);
        // 更新退佣总额
        \Model\UserAgent::where('user_id', $userId)->update(
            [
                'earn_money' => DB::raw("earn_money + {$money}"),
            ]
        );

        $wallet    = new \Logic\Wallet\Wallet($this->ci);
        $wallet_id = \DB::table('user')->where('id', $userId)->value('wallet_id');
        // 增加用户钱包
        $wallet->crease($wallet_id, $money,3);

        //返佣金额可直接提现
//        UserData::where('user_id',$userId)->update(['free_money' => \DB::raw("free_money + {$money}")]);

        // 查询余额
        $funds = \Model\Funds::where('id', $wallet_id)->first();
        $memo  = $this->lang->text("Refund to Shareholder Wallet");

        //流水里面添加打码量可提余额等信息
//        $dml = new \Logic\Wallet\Dml($this->ci);
//        $dmlData = $dml->getUserDmlData($userId);
        $order_number = FundsDealLog::generateDealNumber();
        // 写入流水
        $res = \Model\FundsDealLog::create(
            [
                "user_id"           => $userId,
                "username"          => $userName,
                "deal_type"         => \Model\FundsDealLog::TYPE_AGENT_CHARGES,
                "deal_category"     => \Model\FundsDealLog::CATEGORY_INCOME,
                "order_number"      => $order_number,
                "deal_money"        => $money,
                "balance"           => intval($funds['share_balance']),
                "memo"              => $memo,
                "wallet_type"       => \Model\FundsDealLog::WALLET_TYPE_PRIMARY,
                'total_bet'         => 0,
                'withdraw_bet'      => 0,
                'total_require_bet' => 0,
                'free_money'        => intval($funds['share_balance']),
            ]
        );
        //插入成功 返回订单号
        if($res){
            return $order_number;
        }
        return false;
    }

    /**
     * 代理盈亏返佣
     * 插入平台数据
     * @param $date
     */
    public function insertAgentPlatEarnlose($date){
        $res_data = \DB::table('agent_plat_earnlose')->where('date', $date)->value('id');
        if($res_data) throw new \Exception("{$date} 数据已存在");

        $plat_earnlose                      = [];
        $game_order_data                    = $this->getAgentGameOrderData($date);
        $deopsit_withdraw_data              = $this->getDepositWithdrawData($date);

        $plat_earnlose['date']              = $date;
        //$plat_earnlose['proportion']   = SystemConfig::getModuleSystemConfig('agent')['shares'];
        $plat_earnlose['bet_amount']        = $game_order_data['bet_amount'];
        $plat_earnlose['prize_amount']      = $game_order_data['prize_amount'];
        $plat_earnlose['bet_amount_list']   = $game_order_data['bet_amount_list'];
        $plat_earnlose['lose_earn_list']    = $game_order_data['lose_earn_list'];
        $plat_earnlose['lose_earn']         = bcsub($plat_earnlose['bet_amount'], $plat_earnlose['prize_amount'], 2);
        //返佣结算方式   1日 2周 3月
        $bkge_settle_type = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_settle_type'];
        if($bkge_settle_type == 1){
            //这天的佣金结算了  就把平台的status改成 1
            $plat_earnlose['status'] = 1;
        }else{
            $plat_earnlose['status'] = 0;
        }

        $plat_earnlose['revenue_amount'] = bcsub($deopsit_withdraw_data['deposit_amount'], $deopsit_withdraw_data['withdraw_amount'],2);

        \DB::table('agent_plat_earnlose')->insert($plat_earnlose);

    }

    /**
     * 代理盈亏返佣
     * 获取某天游戏注单额度
     * @param $date
     * @return array
     */
    public function getAgentGameOrderData($date){
        $game_list          = GameMenu::getGameTypeList();
        $bet_amount_sql     = '';
        $lose_earn_sql      = '';
        $bet_amount_list    = [];
        $lose_earn_list     = [];

        foreach ($game_list as $v){
            $bet_amount_sql .= " sum(ore.bet_amount_list->'$.{$v}') as K{$v} ,";
            $lose_earn_sql  .= " sum(ore.lose_earn_list->'$.{$v}') as Y{$v} ,";
        }

        $bet_amount_sql = rtrim($bet_amount_sql,',');
        $lose_earn_sql  = rtrim($lose_earn_sql,',');

        //没有充过值的用户不要
        $data_sql = "select ifnull(sum(ore.bet),0) bet_amount, ifnull(sum(ore.send_money),0) prize_amount,  {$bet_amount_sql} , {$lose_earn_sql}
                            FROM orders_report as ore inner join user u on u.id = ore.user_id and u.first_recharge_time > 0
                   WHERE ore.date = '{$date}'";

        $data = \Db::select($data_sql);
        $data && $data = (array)current($data);

        if($data){
            foreach ($game_list as $v){
                $bet_amount_list[$v] = bcmul($data["K{$v}"],1,2);
                unset($data["K{$v}"]);
                $lose_earn_list[$v]  = bcmul($data["Y{$v}"],1,2);
                unset($data["Y{$v}"]);
            }
            $data['bet_amount_list'] = json_encode($bet_amount_list);
            $data['lose_earn_list']  = json_encode($lose_earn_list);
        };

        return $data;
    }

    public function getAgentGameOrderWeekOrMonthData($startDate, $endDate){
        $game_list          = GameMenu::getGameTypeList();
        $bet_amount_sql     = '';
        $lose_earn_sql      = '';
        $bet_amount_list    = [];
        $lose_earn_list     = [];

        foreach ($game_list as $v){
            $bet_amount_sql .= " sum(bet_amount_list->'$.{$v}') as K{$v} ,";
            $lose_earn_sql  .= " sum(lose_earn_list->'$.{$v}') as Y{$v} ,";
        }

        $bet_amount_sql = rtrim($bet_amount_sql,',');
        $lose_earn_sql  = rtrim($lose_earn_sql,',');

        $data_sql = "select sum(bet_amount) bet_amount, sum(prize_amount) prize_amount, sum(lose_earn) lose_earn,  
                        sum(revenue_amount) revenue_amount, {$bet_amount_sql} , {$lose_earn_sql}
                            FROM agent_plat_earnlose 
                   WHERE date >= '{$startDate}' and date <= '{$endDate}' and status = 0 ";

        $data = \Db::select($data_sql);

        $data && $data = (array)current($data);

        if($data){
            //设为1 表示结算过了
            $res = \DB::table('agent_plat_earnlose')
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->where('status',0)
                ->update(['status' => 1]);

            //如果更新失败 就返回
            if(!$res) return;

            foreach ($game_list as $v){
                $bet_amount_list[$v] = bcmul($data["K{$v}"],1,2);
                unset($data["K{$v}"]);
                $lose_earn_list[$v]  = bcmul($data["Y{$v}"],1,2);
                unset($data["Y{$v}"]);
            }
            $data['bet_amount_list'] = json_encode($bet_amount_list);
            $data['lose_earn_list']  = json_encode($lose_earn_list);
        };

        return $data;
    }

    public function insertAgentLoseearnMonthBkge($startDate, $endDate, $month=null){
        $fixed_proportion_switch    = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'] ?? 0;  //一级代理固定占成开关  1:开，0:关
        $count_self                 = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_calculation_self'] ?? 0;  //1:统计自身，0:不统计
        $game_list          = GameMenu::getGameTypeList();
        $default_data       = $this->getAgentDefaultRatioAmountData();
        $bet_amount_sql     = '';
        $lose_earn_sql      = '';
        $lose_amount_sql    = '';
        $fee_list_sql       = '';
        $sub_loseearn_sql   = '';//直属下级
        if($month){
            $table_name      = 'agent_loseearn_month_bkge';
            $plat_table_name = 'agent_plat_month_earnlose';
            $date            = $month;
            $type            = 3;
        }else{
            $table_name      = 'agent_loseearn_week_bkge';
            $plat_table_name = 'agent_plat_week_earnlose';
            $date            = $endDate;
            $type            = 2;
        }

        foreach ($game_list as $v){
            if($count_self){
                $lose_earn_sql     .= " sum(loseearn_amount_list->'$.{$v}') as Y{$v} ,";
                $lose_amount_sql   = " sum(alb.loseearn_amount) loseearn_amount ";

            }else{
                $lose_earn_sql     .= " sum(sub_loseearn_amount_list->'$.{$v}') as Y{$v} ,";
                $lose_amount_sql   = " sum(alb.sub_loseearn_amount) loseearn_amount ";
            }
            $bet_amount_sql    .= " sum(bet_amount_list->'$.{$v}') as K{$v} ,";
            $sub_loseearn_sql  .= " sum(sub_loseearn_amount_list->'$.{$v}') as F{$v} ,";
        }

        foreach ($default_data as $k => $v){
            //盈亏扣费要重新计算 所以这里不要
            if($k == 'loseearn_ratio_amount') continue;
            $fee_list_sql  .= " sum(fee_list->'$.{$k}') as {$k} ,";
        }

        $bet_amount_sql    = rtrim($bet_amount_sql,',');
        $lose_earn_sql     = rtrim($lose_earn_sql,',');
        $sub_loseearn_sql  = rtrim($sub_loseearn_sql,',');
        $fee_list_sql      = rtrim($fee_list_sql,',');

        $data_sql = "select alb.user_id, alb.user_name, ifnull(ua.uid_agent_name,'') agent_name ,if(ua.uid_agent,1,2) proportion_type, ua.profit_loss_value,
                        max(alb.agent_cnt) agent_cnt, sum(alb.num) num,sum(alb.bet_amount) bet_amount,
                        sum(alb.dml_amount) dml_amount, {$lose_amount_sql},sum(alb.sub_loseearn_amount) sub_loseearn_amount,
                        {$bet_amount_sql} , {$lose_earn_sql},{$fee_list_sql},{$sub_loseearn_sql}
                     FROM agent_loseearn_bkge as alb
                     left join user as u ON u.id = alb.user_id   
                     left join user_agent as ua 
                         on alb.user_id = ua.user_id
                     WHERE alb.date >= '{$startDate}' and alb.date <= '{$endDate}' and alb.status = 0 AND u.agent_switch = 1 
                     group by alb.user_id";

        $agent_data = \Db::select($data_sql);

        if($agent_data){
            //设为1 表示结算过了
            $res = \DB::table('agent_loseearn_bkge')
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->where('status',0)
                ->update(['status' => 1, 'type' => $type]);

            //修改失败就返回
            if(!$res) return;

            $proportion_config  = $this->handleAgentProportionConfig();
            $agentSettle        = $this->getAgentLoseearnFee();

            //平台数据
            $plat_earn_lose_data = [];
            $plat_fee_list       = $default_data;
            $i                   = 0;
            $sub_agent_data      = [];

            foreach ($agent_data as &$item){
                $i++;
                $bet_amount_list            = [];
                $loseearn_amount_list       = [];
                $sub_loseearn_amount_list   = [];
                $fee_list = [];

                $item           = (array)$item;
                $item['date']   = $date;
                $item['status'] = 0;
                //获取 loseearn_amount_list
                foreach ($game_list as $v){
                    $bet_amount_list[$v]            = bcmul($item["K{$v}"],1,2);
                    $loseearn_amount_list[$v]       = bcmul($item["Y{$v}"],1,2);
                    $sub_loseearn_amount_list[$v]   = bcmul($item["F{$v}"],1,2);
                    unset($item["K{$v}"]);
                    unset($item["Y{$v}"]);
                    unset($item["F{$v}"]);
                }

                //获取 fee_list
                foreach ($default_data as $k => $v){
                    //盈亏扣费要重新计算 所以这里不要
                    if($k == 'loseearn_ratio_amount') continue;
                    $fee_list[$k] = bcmul($item["{$k}"],1,2);
                    unset($item["{$k}"]);
                }

                //只有盈亏的扣费需要重新计算
                $amount_list                        = ['loseearn_amount'=> $item['loseearn_amount']];

                $item['bet_amount_list']            = json_encode($bet_amount_list);
                $item['loseearn_amount_list']       = json_encode($loseearn_amount_list);
                $item['sub_loseearn_amount_list']   = json_encode($sub_loseearn_amount_list);

                $proportion_list = $this->getAgentLoseearnProportionList($proportion_config, $loseearn_amount_list, $item['proportion_type'], $item['profit_loss_value'], $game_list, $fixed_proportion_switch);

                $item['proportion_list']            = json_encode($proportion_list);
                $item['bkge_list']                  = json_encode([]);
                //计算扣费
                $fee_list['loseearn_ratio_amount']  = $this->agentLoseearnCountFee($amount_list, $agentSettle, $default_data)['loseearn_ratio_amount'];
                $item['fee_list']                   = json_encode($fee_list);
                $item['fee_amount']                 = array_sum($fee_list);

                //平台扣费 只展示一级代理的
                if(!$item['agent_name']){
                    $plat_earn_lose_data['fee_amount'] = bcadd($plat_earn_lose_data['fee_amount'] ?? 0, $item['fee_amount'], 2);
                    $plat_fee_list                     = $this->addBetAmountList($plat_fee_list, $fee_list);
                }

                unset($item['proportion_type']);
                unset($item['profit_loss_value']);

                array_push($sub_agent_data, $item);
                //每1000条插入一次
                if($i % 1000 == 0){
                    \DB::table($table_name)->insert($sub_agent_data);
                    //必须还原数组
                    $sub_agent_data = [];
                }
                unset($item);
            }
            //不足1千条的部分 插入
            \DB::table($table_name)->insert($sub_agent_data);
            unset($sub_agent_data);
            unset($item);

            //更新平台总扣款
            $plat_earn_lose_data['fee_list'] = $plat_fee_list;
            $this->updateAgentPlatMonthEarnlose($plat_table_name, $date, $plat_earn_lose_data, $game_list);
        }
    }

    /**
     * 盈亏返佣
     * 获取结算盈亏参与比
     * @return array|mixed
     */
    public function getAgentProfitLoss(){
        $ratio_list = [];
        $profit_ratio = SystemConfigModel::where('module','profit_loss')->where('key','profit_ratio')->where('state','enabled')->first();
        if(!empty($profit_ratio) && !empty($profit_ratio->value)){
            $ratio_list = json_decode($profit_ratio->value, true);
        }
        return $ratio_list;
    }

    /**
     * 盈亏返佣
     * 插入 无限代理返佣发放记录表
     * @param $date
     */
    public function insertAgentLoseearnBkge($date){
        $game_list                  = GameMenu::getGameTypeList();
        $bet_amount_sql             = '';
        $loseearn_amount_sql        = '';
        $sub_loseearn_amount_sql    = ''; //直属下级

        $fixed_proportion_switch    = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'] ?? 0;  //一级代理固定占成开关  1:开，0:关
        $count_self                 = SystemConfig::getModuleSystemConfig('rakeBack')['bkge_calculation_self'] ?? 0;  //1:统计自身，0:不统计

        foreach ($game_list as $v){
            $bet_amount_sql                    .= " sum(if(u2.first_recharge_time > 0, ore.bet_amount_list->'$.{$v}',0)) as K{$v} ,";
            $loseearn_amount_sql               .= " sum(if(u2.first_recharge_time > 0, ore.lose_earn_list->'$.{$v}',0)) as Y{$v} ,";
            $sub_loseearn_amount_sql           .= " sum(if(u2.first_recharge_time > 0  && ua.user_id != ore.user_id , ore.lose_earn_list->'$.{$v}',0)) as F{$v} ,";
        }

        $bet_amount_sql          = rtrim($bet_amount_sql,',');
        $loseearn_amount_sql     = rtrim($loseearn_amount_sql,',');
        $sub_loseearn_amount_sql = rtrim($sub_loseearn_amount_sql,',');

        //用户禁止返佣的就不返
        //proportion_type 有上级代理是 1，没有上级是 2
        $agent_sql = "select ua.user_id, 
                       if(LENGTH(ua.uid_agent_name),1,2) proportion_type,
                       ua.profit_loss_value, 
                       u.name user_name,
                       ifnull(ua.uid_agent_name,'') agent_name, 
                       ifnull(ra.agent_cnt,0) agent_cnt, 
                       sum(if(u2.first_recharge_time > 0,ore.bet,0)) bet_amount,
                       sum(if(u2.first_recharge_time > 0,ore.dml,0)) dml_amount,
                       sum(if(u2.first_recharge_time > 0,ore.num,0)) num, 
                       sum(if(ua.user_id != ru.user_id, ru.deposit_user_amount, 0)) deposit_amount, 
                       sum(if(ua.user_id != ru.user_id, ru.withdrawal_user_amount, 0)) withdrawal_amount,
                       sum(if(ua.user_id != ru.user_id, ru.deposit_user_amount - ru.withdrawal_user_amount, 0)) revenue_amount, 
                       sum(if(ua.user_id != ru.user_id, ru.manual_deduction_amount, 0)) manual_amount,
                       sum(if(ua.user_id != ru.user_id, ru.coupon_user_amount+ru.return_user_amount+ru.turn_card_user_winnings+ru.promotion_user_winnings, 0)) coupon_amount,
                       {$bet_amount_sql},{$loseearn_amount_sql},{$sub_loseearn_amount_sql}
                   FROM user_agent ua
                   INNER JOIN (select id as pid, id as cid from user union select  uid_agent as pid,  user_id as cid from user_agent) ca
                      ON ua.user_id = ca.pid
                   INNER JOIN rpt_user ru
                      on  ru.user_id    = ca.cid
                      AND ru.count_date = '{$date}'
                   LEFT JOIN orders_report ore
                      ON  ore.user_id = ca.cid
                      AND ore.date    = '{$date}'
                   INNER JOIN user u 
                        on  ua.user_id     = u.id
                        AND u.agent_switch = 1     
                        AND !find_in_set('refuse_bkge',u.auth_status)
				   INNER JOIN user u2 on ca.cid= u2.id
				   LEFT JOIN rpt_agent ra
                        on  ra.agent_id   = ua.user_id
                        AND ra.count_date = '{$date}'
				   group by user_id";

        $agent_data = \Db::select($agent_sql);

        if($agent_data){
            $proportion_config  = $this->handleAgentProportionConfig();
            $agentSettle        = $this->getAgentLoseearnFee();
            $default_data       = $this->getAgentDefaultRatioAmountData();

            //平台数据
            $plat_earn_lose_data    = [];
            $plat_fee_list          = $default_data;
            $i                      = 0;
            $sub_agent_data         = [];

            foreach ($agent_data as &$item){
                $i++;
                $bet_amount_list          = [];
                $loseearn_amount_list     = [];
                $sub_loseearn_amount_list = [];//直属下级盈亏

                $item           = (array)$item;
                $item['date']   = $date;
                $item['status'] = 0;

                //可能会有没有投注的数据  就不插入了 (这里有个小bug 就是一级代理和下级没有投注 但3级代理有投注 也应该有退佣)
                if($item['bet_amount'] <= 0) continue;
                //获取 loseearn_amount_list
                foreach ($game_list as $v){
                    $bet_amount_list[$v]            = bcmul($item["K{$v}"],1,2);
                    $loseearn_amount_list[$v]       = bcmul($item["Y{$v}"],1,2);
                    $sub_loseearn_amount_list[$v]   = bcmul($item["F{$v}"],1,2);
                    unset($item["K{$v}"]);
                    unset($item["Y{$v}"]);
                    unset($item["F{$v}"]);
                }

                $loseearn_amount                    = array_sum($loseearn_amount_list);
                $sub_loseearn_amount                = array_sum($sub_loseearn_amount_list);
                //计算自身
                if($count_self){
                    //因为 loseearn_amount 总是保存包含自身的 (这样做，主要是为了让周返和月返可以用这个数据，比如周返，月返，中途切换是否计算自身)
                    //所以计算占成的时候 得根据是否计算自身做调整
                    $loseearn_amount_list_for_count = $loseearn_amount_list;
                    $loseearn_amount_for_count      = $loseearn_amount;
                }else{
                    $loseearn_amount_list_for_count = $sub_loseearn_amount_list;
                    $loseearn_amount_for_count      = $sub_loseearn_amount;
                }

                $item['loseearn_amount']            = $loseearn_amount;
                $item['sub_loseearn_amount']        = $sub_loseearn_amount;
                $amount_list                        = array_merge(['loseearn_amount'=> $loseearn_amount_for_count], ['deposit_amount' => $item['deposit_amount']], ['withdrawal_amount' => $item['withdrawal_amount']], ['revenue_amount' => $item['revenue_amount']], ['manual_amount' => $item['manual_amount']], ['coupon_amount' => $item['coupon_amount']]);

                $item['bet_amount_list']            = json_encode($bet_amount_list);
                $item['loseearn_amount_list']       = json_encode($loseearn_amount_list);
                $item['sub_loseearn_amount_list']   = json_encode($sub_loseearn_amount_list);

                $proportion_list                    = $this->getAgentLoseearnProportionList($proportion_config, $loseearn_amount_list_for_count, $item['proportion_type'], $item['profit_loss_value'], $game_list, $fixed_proportion_switch);

                $item['proportion_list']            = json_encode($proportion_list);
                $item['bkge_list']                  = json_encode([]);

                //计算扣费
                $fee_list                           = $this->agentCountFee($amount_list, $agentSettle, $default_data);
                $item['fee_list']                   = json_encode($fee_list);
                $item['fee_amount']                 = array_sum($fee_list);

                //平台扣费 只展示一级代理的
                if(!$item['agent_name']){
                    $plat_earn_lose_data['fee_amount'] = bcadd($plat_earn_lose_data['fee_amount'] ?? 0, $item['fee_amount'], 2);
                    $plat_fee_list = $this->addBetAmountList($plat_fee_list, $fee_list);
                }

                unset($item['proportion_type']);
                unset($item['profit_loss_value']);
                unset($item['coupon_amount']);
                unset($item['manual_amount']);
                unset($item['revenue_amount']);
                unset($item['deposit_amount']);
                unset($item['withdrawal_amount']);

                array_push($sub_agent_data, $item);
                //每1000条插入一次
                if($i % 1000 == 0){
                    \DB::table('agent_loseearn_bkge')->insert($sub_agent_data);
                    //必须还原数组
                    $sub_agent_data = [];
                }
                unset($item);
            }
            //不足1千条的部分 插入
            \DB::table('agent_loseearn_bkge')->insert($sub_agent_data);
            unset($sub_agent_data);
            unset($item);

            //更新平台总扣款
            $plat_earn_lose_data['fee_list'] = $plat_fee_list;
            $this->updateAgentPlatEarnlose($date, $plat_earn_lose_data,$game_list);
        }
    }

    /**
     * 代理盈亏返佣
     * 更新平台扣款数据
     * @param $date
     * @param $plat_earnlose
     * @return int
     */
    public function updateAgentPlatEarnlose($date, $plat_earnlose,$game_list){
        $data['fee_amount'] = $plat_earnlose['fee_amount'] ?? 0;
        $fee_list = $plat_earnlose['fee_list'];

        foreach ($fee_list as $k => $v){
            if(in_array($k,$game_list)){
                $data['bet_ratio_amount'] = bcadd($data['bet_ratio_amount']??0, $v,2);

            }else{
                $data[$k] = $v;
            }
        }
        $res = \DB::table('agent_plat_earnlose')->where('date', $date)->update($data);
        return $res;
    }

    public function updateAgentPlatMonthEarnlose($tableName, $date, $plat_earnlose,$game_list){
        $data['fee_amount'] = $plat_earnlose['fee_amount'];
        $fee_list = $plat_earnlose['fee_list'];

        foreach ($fee_list as $k => $v){
            if(in_array($k,$game_list)){
                $data['bet_ratio_amount'] = bcadd($data['bet_ratio_amount']??0, $v,2);

            }else{
                $data[$k] = $v;
            }
        }
        $res = \DB::table($tableName)->where('date', $date)->update($data);
        return $res;
    }

    /**
     * 盈亏返佣
     * 获取系统占成设置
     * @return array
     */
    public function handleAgentProportionConfig(){
        $proportion_config = SystemConfig::getModuleSystemConfig('profit_loss')['proportion'];

        $proportion_config = json_decode($proportion_config, true);
        if(!$proportion_config) throw new \Exception('系统设置profit_loss proportion值错误');

        foreach ($proportion_config as $i => $v){
            $v = explode(';',$v);
            $new_val = [];
            foreach ($v as $k => $val){
                $val_array = explode(',',$val);
                //因为后台设置以万为单位
                $new_val[$k][0] = bcmul($val_array[0],10000);
                $new_val[$k][1] = bcmul($val_array[1],10000);
                $new_val[$k][2] = $val_array[2];

            }
            $proportion_config[$i] = $new_val;
        }


        return $proportion_config;
    }

    /**
     * 盈亏返佣
     * 计算代理占成
     * @param $proportionConfig
     * @param $loseearnAmountList
     * @param $proportionType
     * @param $proportionValueList
     * @return int
     */
    public function getAgentLoseearnProportionList($proportionConfig, $loseearnAmountList ,$proportionType, $proportionValueList, $gameList, $fixed_proportion_switch){
        $proportion_list = [];

        $proportionValueListArray = json_decode($proportionValueList, true);

        //计算代理占成   proportion_type 有上级代理是 1，没有上级是 2
        //有上级的取固定的  没有上级 浮动的 和固定的 哪个大取哪个
        foreach ($gameList as $game_name){
            $proportion_list[$game_name] = 0;
            //固定占成时 $proportion比固定占成少 就取固定占成
            //下级代理  或者一级代理 设定了固定占成
            if($proportionType == 1 || $fixed_proportion_switch == 1){
                $proportion_list[$game_name] = $proportionValueListArray[$game_name]??0;
                continue;
            }

            if($proportionType == 2){
                $loseearn_amount = $loseearnAmountList[$game_name] ?? 0;

                $v = $proportionConfig[$game_name]??[];
                if($v){
                    foreach ($v as $val){
                        if($loseearn_amount > $val[0] && $loseearn_amount <= $val[1]){
                            $proportion_list[$game_name] = $val[2];
                            break;
                        }elseif($loseearn_amount > $val[1]){
                            //主要是为了超过最大值的时候 按最后一个区间算
                            $proportion_list[$game_name] = $val[2];
                        }
                    }
                }

                //比固定占成小
                if(isset($proportionValueListArray[$game_name]) && $proportion_list[$game_name] < $proportionValueListArray[$game_name]){
                    $proportion_list[$game_name] = $proportionValueListArray[$game_name];
                }
            }
            
        }

        return $proportion_list;
    }

    /**
     * 盈亏返佣
     * @return array|\Illuminate\Support\Collection
     */
    public function getAgentLoseearnFee(){
        $query = \DB::table('agent_loseearn_fee');
        $query->where('settle_status',1)
            ->where('status',1);
        $res = $query->get(['type','proportion_value','part_value']);
        $res && $res= $res->toArray();
        return $res;
    }

    /**
     * 盈亏返佣
     * 计算代理扣费  1:游戏盈亏(投注-派彩),2:充值,3:取款，4:营收, 5:平台彩金, 6:平台服务(人工扣款)
     * @param $amount
     * @return array
     */
    public function agentCountFee($amount,$agentSettle=[], $data=[]){
        if($agentSettle){
            foreach ($agentSettle as $v){
                $v = (array)$v;
                $ratio = $v['proportion_value'] / 100 * $v['part_value'] / 100;
                switch ($v['type']){
                    //1:盈亏
                    case 1:
                        $amount_fee = $amount['loseearn_amount'] * $ratio;
                        $amount_fee < 0 && $amount_fee = 0;
                        $data['loseearn_ratio_amount'] = bcadd($data['loseearn_ratio_amount'], $amount_fee,2);
                        break;
                    //2:充值
                    case 2:
                        $amount_fee = $amount['deposit_amount'] * $ratio;
                        $data['deposit_ratio_amount'] = bcadd($data['deposit_ratio_amount'], $amount_fee,2);
                        break;
                    //3:取款
                    case 3:
                        $amount_fee = $amount['withdrawal_amount'] * $ratio;
                        $data['withdrawal_ratio_amount'] = bcadd($data['withdrawal_ratio_amount'], $amount_fee,2);
                        break;
                    //4:营收
                    case 4:
                        $amount_fee = $amount['revenue_amount'] * $ratio;
                        //营收为负的 就显示0
                        $amount_fee = $amount_fee > 0 ? $amount_fee : 0;
                        $data['revenue_ratio_amount'] = bcadd($data['revenue_ratio_amount'], $amount_fee,2);
                        break;
                    //5:优惠彩金
                    case 5:
                        $amount_fee = $amount['coupon_amount'] * $ratio;
                        $data['coupon_ratio_amount'] = bcadd($data['coupon_ratio_amount'], $amount_fee,2);
                        break;
                    //6:人工扣款
                    case 6:
                        $amount_fee = $amount['manual_amount'] * $ratio;
                        $data['manual_ratio_amount'] = bcadd($data['manual_ratio_amount'], $amount_fee,2);
                        break;
                    default:

                }
            }
        }

        return $data;

    }

    public function agentLoseearnCountFee($amount,$agentSettle=[], $data=[]){
        if($agentSettle){
            foreach ($agentSettle as $v){
                $v = (array)$v;
                $ratio = $v['proportion_value'] / 100 * $v['part_value'] / 100;
                switch ($v['type']){
                    //1:盈亏
                    case 1:
                        $amount_fee = $amount['loseearn_amount'] * $ratio;
                        $amount_fee < 0 && $amount_fee = 0;
                        $data['loseearn_ratio_amount'] = bcadd($data['loseearn_ratio_amount'], $amount_fee,2);
                        break;
                    default:

                }
            }
        }

        return $data;

    }

    /**
     * 代理盈亏返佣
     * @return array
     */
    public function getAgentDefaultRatioAmountData(){
        $data = [
            'loseearn_ratio_amount'     => 0,
            'deposit_ratio_amount'      => 0,
            'withdrawal_ratio_amount'   => 0,
            'revenue_ratio_amount'      => 0,
            'coupon_ratio_amount'       => 0,
            'manual_ratio_amount'       => 0,
        ];

        return $data;
    }

    /**
     * 获取累计充值大于多少元的用户
     * 获取新增直属下级数
     * @param $depositMoney
     * @param $agentId
     * @param $startDate
     * @param $endDate
     * @return array
     */
    public function CountNewUserAndDepositUser($depositMoney, $agentId, $startDate, $endDate){
        $end_time = $endDate.' 23:59:59';
        $sql = "select sum(deposit_num) depoist_num, sum(register_num) register_num from  
                (select if(sum(deposit_user_amount) >= {$depositMoney},1,0) deposit_num, if(register_time >= '{$startDate}' && register_time <= '{$end_time}',1,0) register_num   
                    from rpt_user 
                        where superior_id = {$agentId} 
                        and count_date >= '{$startDate}'
                        and count_date <= '{$endDate}'  
                    group by user_id) a";
        $data = \Db::select($sql)[0];
        $data = (array)$data;
        return [
            (int)$data['depoist_num'],
            (int)$data['register_num']
        ];
    }

    /**
     * 只是计算返佣金额
     * @param $name
     * @param $startDate
     * @param $endDate
     */
    public function onlyCountAgentLoseearnBkge($name, $startDate, $endDate)
    {
        $user_id = \Model\User::where('name', $name)->value('id');
        if (!$user_id) return $name . ' 用户不存在';

        $fixed_proportion_switch    = SystemConfig::getModuleSystemConfig('profit_loss')['fixed_proportion_switch'] ?? 0;  //一级代理固定占成开关  1:开，0:关
        $game_list = GameMenu::getGameTypeList();
        $bet_amount_sql = '';
        $loseearn_amount_sql = '';
        $sub_loseearn_amount_sql = '';//直属下级

        foreach ($game_list as $v) {
            $bet_amount_sql .= " sum(if(u2.first_recharge_time > 0,ore.bet_amount_list->'$.{$v}',0)) as K{$v} ,";
            $loseearn_amount_sql .= " sum(if(u2.first_recharge_time > 0,ore.lose_earn_list->'$.{$v}',0)) as Y{$v} ,";
            $sub_loseearn_amount_sql .= " sum(if(u2.first_recharge_time > 0 && ua.user_id != ca.cid, ore.lose_earn_list->'$.{$v}',0)) as F{$v} ,";
        }

        $bet_amount_sql = rtrim($bet_amount_sql, ',');
        $loseearn_amount_sql = rtrim($loseearn_amount_sql, ',');
        $sub_loseearn_amount_sql = rtrim($sub_loseearn_amount_sql, ',');

        //proportion_type 有上级代理是 1，没有上级是 2
        $agent_sql = "select ua.user_id, 
                       if(LENGTH(ua.uid_agent_name),1,2) proportion_type,
                       ua.profit_loss_value, 
                       u.name user_name,
                       ifnull(ua.uid_agent_name,'') agent_name, 
                       ifnull(ra.agent_cnt,0) agent_cnt, 
                       sum(if(u2.first_recharge_time > 0,ore.bet,0)) bet_amount,
                       sum(if(u2.first_recharge_time > 0,ore.dml,0)) dml_amount,
                       sum(if(u2.first_recharge_time > 0,ore.num,0)) num, 
                       ifnull(sum(ru.deposit_user_amount),0) deposit_amount, 
                       ifnull(sum(ru.withdrawal_user_amount),0) withdrawal_amount,
                       ifnull(sum((ru.deposit_user_amount - ru.withdrawal_user_amount)),0) revenue_amount, 
                       ifnull(sum(ru.manual_deduction_amount),0) manual_amount,
                       ifnull(sum((ru.coupon_user_amount+ru.return_user_amount+ru.turn_card_user_winnings+ru.promotion_user_winnings)),0) coupon_amount,
                       sum(if(u2.first_recharge_time > 0 and ru.bet_user_amount > 0,1,0)) active_user_num,
                       {$bet_amount_sql},{$loseearn_amount_sql},{$sub_loseearn_amount_sql}
                    FROM user_agent ua
                   INNER JOIN (select id as pid, id as cid from user union select  uid_agent as pid,  user_id as cid from user_agent) ca
                      ON ua.user_id = ca.pid
                      INNER JOIN rpt_user ru
											on ru.user_id = ca.cid
											
											AND ru.count_date >= '{$startDate}'
                                            AND ru.count_date <= '{$endDate}'
                   LEFT JOIN orders_report ore
                      ON ore.user_id = ca.cid
                      AND ore.date >= '{$startDate}'
                      AND ore.date <= '{$endDate}'
                      INNER JOIN user u 
											on ua.user_id = u.id
											AND u.agent_switch = 1     
											AND !find_in_set('refuse_bkge',u.auth_status)
											INNER JOIN user u2 on ca.cid= u2.id
											LEFT JOIN rpt_agent ra
											on ra.agent_id = ua.user_id
											AND ra.count_date >= '{$startDate}'
											AND ra.count_date <= '{$endDate}'
											
											where ua.user_id={$user_id}";

        $agent_data = \Db::select($agent_sql);

        if ($agent_data) {
            $proportion_config = $this->handleAgentProportionConfig();
            $agentSettle = $this->getAgentLoseearnFee();
            $default_data = $this->getAgentDefaultRatioAmountData();

            foreach ($agent_data as &$item) {
                $bet_amount_list = [];
                $loseearn_amount_list = [];
                $sub_loseearn_amount_list = [];//直属下级盈亏

                $item = (array)$item;
                $item['status'] = 0;
                //获取 loseearn_amount_list
                foreach ($game_list as $v) {
                    $bet_amount_list[$v] = bcmul($item["K{$v}"], 1, 2);
                    $loseearn_amount_list[$v] = bcmul($item["Y{$v}"], 1, 2);
                    $sub_loseearn_amount_list[$v] = bcmul($item["F{$v}"], 1, 2);
                    unset($item["K{$v}"]);
                    unset($item["Y{$v}"]);
                    unset($item["F{$v}"]);
                }
                $loseearn_amount = array_sum($loseearn_amount_list);
                $item['loseearn_amount'] = $loseearn_amount;
                $amount_list = array_merge(['loseearn_amount' => $loseearn_amount], ['deposit_amount' => $item['deposit_amount']], ['withdrawal_amount' => $item['withdrawal_amount']], ['revenue_amount' => $item['revenue_amount']], ['manual_amount' => $item['manual_amount']], ['coupon_amount' => $item['coupon_amount']]);

                $sub_loseearn_amount = array_sum($sub_loseearn_amount_list);
                $item['bet_amount_list'] = json_encode($bet_amount_list);
                $item['loseearn_amount_list'] = json_encode($loseearn_amount_list);
                $item['sub_loseearn_amount'] = $sub_loseearn_amount;
                $proportion_list = $this->getAgentLoseearnProportionList($proportion_config, $loseearn_amount_list, $item['proportion_type'], $item['profit_loss_value'], $game_list, $fixed_proportion_switch);

                $item['proportion_list'] = json_encode($proportion_list);
                $item['bkge_list'] = json_encode([]);
                //计算扣费
                var_dump($amount_list,$agentSettle,$default_data);
                $fee_list = $this->agentCountFee($amount_list, $agentSettle, $default_data);
                $item['fee_list'] = json_encode($fee_list);
                $item['fee_amount'] = array_sum($fee_list);

                unset($item['proportion_type']);
                unset($item['profit_loss_value']);
                unset($item['coupon_amount']);
                unset($item['manual_amount']);
                unset($item['revenue_amount']);
                unset($item['deposit_amount']);
                unset($item['withdrawal_amount']);

                if ($item) {
                    $agent_profit_loss = $this->getAgentProfitLoss();
                    $game_list = GameMenu::getGameTypeList();

                    //返佣配置活跃人数
                    $active_number = SystemConfig::getModuleSystemConfig('profit_loss')['active_number'] ?? 0;

                    $v = $item;
                    $proportion_list = json_decode($v['proportion_list'], true);
                    $loseearn_list = json_decode($v['loseearn_amount_list'], true);
                    $bkge_update_data = [];
                    $bkge_list = $this->getDefaultList($game_list);
                    $bkge = 0;
                    //盈亏金额小于0不返,结算金额，返佣金额为0 , 代理活跃人数大于等于配置活跃人数才返佣
                    if (bccomp($v['loseearn_amount'], 0, 2) > 0 && bccomp($v['active_user_num'], $active_number) >= 0) {
                        //计算各个游戏佣金 出现过user_name不存在的bug
                        if (!$v['agent_name'] && $v['user_name']) {
                            //获取直属下级代理数据
                            $sub_agent_data = \DB::table('agent_loseearn_bkge')
                                ->selectRaw("user_name,sub_loseearn_amount_list, proportion_list, loseearn_amount, fee_amount")
                                ->where('date', '>=', $startDate)
                                ->where('date', '<=', $endDate)
                                ->where('agent_name', $v['user_name'])
                                ->get();

                            if ($sub_agent_data) {
                                $sub_agent_data = $sub_agent_data->toArray();
                                //循环直属代理
                                foreach ($sub_agent_data as &$sub_info) {
                                    $sub_info = (array)$sub_info;
                                    $sub_loseearn_list = json_decode($sub_info['sub_loseearn_amount_list'], true);
                                    $sub_proportion_list = json_decode($sub_info['proportion_list'], true);
                                    $sub_bkge = 0;
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
                                        if ($sub_agent_game_proportion <= 0) continue;
                                        $sub_game_bkge = $profit_loss_ratio * $sub_agent_game_proportion / 100 * ($sub_loseearn_list[$game_type] - $sub_loseearn_list[$game_type] / $sub_info['loseearn_amount'] * $sub_info['fee_amount']);
                                        $fee = $sub_loseearn_list[$game_type] / $sub_info['loseearn_amount'] * $sub_info['fee_amount'];
                                        $fee = bcdiv($fee,1,2);
                                        $sub_game_bkge = bcdiv($sub_game_bkge,1,2);
                                        //佣金为负
                                        if ($sub_game_bkge == 0) continue;
                                        $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);
                                        var_dump($sub_info['user_name']." {$game_type}: ".$sub_game_bkge." 输赢：{$sub_loseearn_list[$game_type]} 费用：{$fee} ");
                                        $bkge = bcadd($bkge, $sub_game_bkge, 2);
                                        $sub_bkge = bcadd($sub_bkge, $sub_game_bkge, 2);
                                    }
                                    if($sub_bkge > 0){
                                        var_dump($sub_info['user_name']." 佣金: ".$sub_bkge);
                                    }

                                    unset($sub_info);
                                }
                            }
                        }
                        $self_bkge = 0;
                        //计算各个游戏佣金
                        foreach ($game_list as $game_type) {
                            //游戏盈亏为负
                            if ($loseearn_list[$game_type] <= 0) continue;
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
                            //电子返佣 = 参与比*返佣比*(自身和直属下级电子盈亏-自身和直属下级电子盈亏/自身和直属下级总盈亏*自身和直属下级总成本)
                            $sub_game_bkge = $profit_loss_ratio * ($proportion_list[$game_type] ?? 0) / 100 * ($loseearn_list[$game_type] - $loseearn_list[$game_type] / $v['loseearn_amount'] * $v['fee_amount']);
                            $fee = $loseearn_list[$game_type] / $v['loseearn_amount'] * $v['fee_amount'];
                            $fee = bcdiv($fee,1,2);
                            $sub_game_bkge = bcdiv($sub_game_bkge,1,2);
                            //佣金为负
                            if ($sub_game_bkge <= 0) continue;
                            $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);
                            var_dump("自身及直属下级 {$game_type}: ".$sub_game_bkge." 输赢 ： {$loseearn_list[$game_type]} 费用：{$fee}");
                            $bkge = bcadd($bkge, $sub_game_bkge, 2);
                            $self_bkge = bcadd($self_bkge, $sub_game_bkge, 2);

                        }
                        if($self_bkge > 0){
                            var_dump("自身及直属下级 佣金: ".$self_bkge);
                        }

                    }
                }
                var_dump($bkge);
            }
        }
        }

    /**
     * 计算一个用户的返佣金额
     */
    public function onlyCountOneAgentLoseearnBkge($userName, $startDate, $endDate, $month = false){
        if($month){
            $table_name         = 'agent_loseearn_month_bkge';
            $bkge_settle_type   = 3;
            $date               = $month;
        }else{
            $table_name         = 'agent_loseearn_week_bkge';
            $bkge_settle_type   = 2;
            $date               = $endDate;
        }

        $data = \DB::table($table_name)
            ->selectRaw("proportion_list, bet_amount, bet_amount_list, loseearn_amount_list,loseearn_amount, user_name, user_id, agent_name,active_user_num,fee_amount")
            ->where('date', $date)
            ->where('user_name',$userName)
            ->get();
        $data && $data = $data->toArray();

        if($data){
            $agent_profit_loss = $this->getAgentProfitLoss();
            $game_list         = GameMenu::getGameTypeList();

            //返佣条件
            $profit_condition       = $this->getProfitCondition($bkge_settle_type);
            if(!$profit_condition) throw new \Exception("没有设置返佣条件:{$bkge_settle_type}");

            $data_num = count($data);
            for($i=0; $i<$data_num; $i++) {
                $v                  = (array)$data[$i];
                $proportion_list    = json_decode($v['proportion_list'],true);
                $loseearn_list      = json_decode($v['loseearn_amount_list'],true);
                $bet_amount_list    = json_decode($v['bet_amount_list'],true);
                $bkge_update_data   = [];
                $bkge_list          = $this->getDefaultList($game_list);
                $bkge               = 0;
                $sub_agent_data     = [];
                $memo               = ''; //备注

                //获取满足条件的充值人数 和 新注册直属下级人数
                list($deposit_num, $register_num) = $this->CountNewUserAndDepositUser($profit_condition['recharge_min'], $v['user_id'], $startDate, $endDate);

                if($deposit_num >= $profit_condition['eff_user'] && $register_num >= $profit_condition['new_user']) {
                    //一级代理  出现过 user_name不存在的bug
                    if(!$v['agent_name'] && $v['user_name']){
                        //获取直属下级代理数据
                        $sub_agent_data = \DB::table($table_name)
                            ->selectRaw("bet_amount, bet_amount_list, sub_loseearn_amount_list, proportion_list, loseearn_amount, fee_amount")
                            ->where('date', $date)
                            ->where('agent_name',$v['user_name'])
                            ->get();

                        if($sub_agent_data){
                            $sub_agent_data = $sub_agent_data->toArray();
                            //循环直属代理
                            $sub_agent_data_num = count($sub_agent_data);
                            for($j=0;$j<$sub_agent_data_num;$j++){
                                $sub_info            = (array)$sub_agent_data[$j];
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
                        //佣金为负
                        //if ($sub_game_bkge <= 0) continue;
                        $bkge_list[$game_type] = bcadd($bkge_list[$game_type] ?? 0, $sub_game_bkge, 2);

                        $bkge = bcadd($bkge, $sub_game_bkge, 2);
                    }
                }else{
                    $memo = "deposit:{$deposit_num},register:{$register_num}";
                    var_dump($memo);
                }

                echo "{$userName}的返佣金额： {$bkge}";
                unset($data[$i]);
            }
        }
    }

}