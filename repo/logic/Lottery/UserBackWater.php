<?php 

namespace Logic\Lottery;


use Logic\Logic;
use \Logic\Wallet\Wallet;
use \Logic\Lottery\Rebet;
use DB;
use Model\RebetExec;
use \Model\Room;
use \Model\OrderThird;
use \Model\Hall3th;
use Model\RptOrderUser;
use Model\User;
use Slim\Container;
use Logic\Set\SystemConfig;



class UserBackWater extends \Logic\Logic
{
    public $rebot_way = [
        'loss',     //按当日亏损额回水
        'betting',  //按当日投注额回水
    ];

    public $rebot_way_type = [
        'percentage',   //返现百分比
        'fixed',        //返现固定金额
    ];

    // 返水统计数据列
    protected $back_water_field = [
    	'day' => [
    		'dml' 		=> 0,
	    	'rate'		=> 0,
            'base'      => 0,
	    	'amount'	=> 0,
    	],
    	'week' => [
    		'dml' 		=> 0,
	    	'rate'		=> 0,
            'base'      => 0,
	    	'amount'	=> 0,
    	],
    	'month' => [
    		'dml' 		=> 0,
	    	'rate'		=> 0,
            'base'      => 0,
	    	'amount'	=> 0,
    	],
    ];


    /**
     * 依据用户层级拿 整理数据以层级为下标，其它的返设置
     * @return array|bool
     */
    protected function getRebetConfByUserLevel($gid = 0){

        $field = [
			'game_menu.id', 
			'game_menu.name', 
			'game_menu.type',
    		'rebet.user_level_id', 
    		'rebet.rebot_way', 
    		'rebet.rebet_ceiling',
    		'rebet.rebet_desc', 
    		'rebet.rebet_condition'
		];

		// 游戏配置
        $data = \DB::table('game_menu')
               ->leftJoin('rebet_config AS rebet','game_menu.id','=','rebet.game3th_id')
               ->where('pid', '=', $gid)
               ->where('game_menu.status', 'enabled')
               ->where('rebet.status_switch', 1)
               ->get($field)->toArray();

        // 查询会员层级       
        $user_level = \DB::table('user_level')->get(['id','level'])->toArray();

        if(!$user_level ||!$data) {
            return false;
        }
        $user_level = array_column($user_level, NULL, 'id');
        $res = [];
        foreach ($data as $val) {
            if($val->rebot_way && isset($user_level[$val->user_level_id])) {
                $l = $user_level[$val->user_level_id]->level;
                $res[$l][$val->id] = (array)$val;
            }
        }
        
        return $res;
    }


    // 获取游戏日反比例
    protected function getRebetRate($rebet_config)
    {
        $rate = 0;
    	
    	// 配置为空
    	if(empty($rebet_config['rebot_way'])) {
            return $rate;
        }

        $conf = json_decode($rebet_config['rebot_way'], TRUE);
        if(!in_array($conf['type'], $this->rebot_way)) {
            return $rate;
        }

        foreach($conf['data'] as $key => $value) {
            if('status' == $key) {
                if(!in_array($value, $this->rebot_way_type)) {
                    continue;
                }
            } else {
                // 基础返水比例默认取第一条
                if(is_array($value)){
                    $conf = current($value);
                    $v = explode(';', $conf);
                    $rate = $v[1];
                }
            }
        }
        
        return $rate;
    }


    // 执行日反查询
    public function getUserRebetDetailByDaily($userId = 0, $gid = 0, $date = '')
    {
        // 日期
        $date = empty($date) ? date("Y-m-d") : $date;
        
        $rebet = new Rebet($this->ci);

        // 获取等级返水配置列表  一级下标为层级，二级下标为game_id 值为回水设置
        $hallInfo = $this->getRebetConfByUserLevel($gid);

        $res = $this->back_water_field['day'];
		$res['date'] = date("m-d", strtotime("+1 day"));
        if(!$hallInfo){
            $this->logger->info('【游戏返水】无回水设置 ' . $date);
            return $res;
        }

        // 查询用户等级对应的返水比例
        $user_ranting = \DB::table('user')->where('id', $userId)->value('ranting');
        $rate_info = $hallInfo[$user_ranting] ?? [];
        if(empty($rate_info)) {
            return $res;
        }
        $res['rate'] = $res['base'] = $this->getRebetRate(current($rate_info));

        $wallet  = new Wallet($this->ci);
        $website = $this->ci->get('settings')['website'];

        $order_query = \DB::table('order_game_user_middle as o')->leftJoin('user as u','o.user_id','=','u.id')
            ->where('o.date', $date)
            ->where('o.user_id', $userId)
            ->whereRaw(" !find_in_set('refuse_rebate',u.auth_status)");

        //指定标签会员不返水
        if(!empty($website['notInTags'])){
            $notInTags = implode(',', $website['notInTags']);
            $order_query->whereRaw(" !find_in_set(u.tags,'{$notInTags}')");
        }

        $order_list = $order_query->groupBy('o.user_id','o.game_type')
            ->select([\DB::raw('o.user_id,u.ranting,u.name,u.wallet_id, o.game_type,o.play_id game_id, sum(o.bet) bet, sum(o.profit) profit')])
            ->get()->toArray();

        $batchNo = time();
        if($order_list){
        	// 总数
            $order_num = count($order_list);
            //用for循环可以释放内存
            for( $i = 0; $i < $order_num;  $i++ ){
                $order = (array)$order_list[$i];
                $order['type_name'] = $this->lang->text($order['game_type']);
                // 游戏等级未设置返水
                if(!isset($hallInfo[$order['ranting']])) {
                    continue;
                }
                // 这个game不返水
                if(empty($hallInfo[$order['ranting']][$order['game_id']])){
                	continue;
                } 
                
                $rebet_config = $hallInfo[$order['ranting']][$order['game_id']];
                
                // 计算返水详细信息
                $data = $rebet->threbetElse(
                	$date, 		// 日期
                	$wallet, 
                	[$order['game_id']=>$rebet_config], 
                	$order,
                	$rebet_config['type'], 
                	'show',	
                	true,
                	$batchNo
                );

                if(!empty($data)){
                	$res['amount'] += $data['money'];
                	$res['dml'] += bcdiv($data['allBetAmount'], 100, 2);
                    $res['rate'] = $data['value'] > $res['rate'] ? $data['value'] : $res['rate'];
                }
                
                unset($order_list[$i]);
            }
            
            $res['dml']    = number_format($res['dml'], 2);
            $res['rate']   = bcdiv($res['rate'], 1, 2);          // 返水比例保留两位 不进行四舍五入
            $res['amount'] = bcdiv($res['amount'], 100, 2);

        }

        return $res;
    }

    // 周&月返水
    public function getUserRebetDetailByWeekOrMonth($uid, $gid, $date_type, $startTime=null, $endTime=null) 
    {
        switch($date_type) {
            case "month":
                //上个月28号
                !$startTime && $startTime = date("Y-m-28", strtotime(date('Y-m-01')) - 86400);
                !$endTime && $endTime   = date('Y-m-27');
                $activity_type = 9;
                $rebet_time = time() >= strtotime(date('Y-m-28 12:00')) ? date('m-28', strtotime("+1 month")) : date('m-28');
                break;
            case "week":
            default :
                //传入开始时间  为了手动补发返水
                if(!$startTime){
                    //获取周一的时间
                    if(date('w') == 1){
                        $startTime = date("Y-m-d");
                        $endTime = date("Y-m-d", strtotime('next sunday'));
                    }else{
                        $startTime = date("Y-m-d", strtotime('last monday'));
                        $endTime = date("Y-m-d", strtotime('next sunday'));
                    }
                }

                $activity_type = 8;
                $rebet_time = date('w') >= 1 ? date("m-d", strtotime('+1 week last monday')) : date('m-d');

                break;
        }
        
        // 返回结构
        $res = $this->back_water_field[$date_type];
		$res['date'] = $rebet_time;

        $date     = date('Y-m-d H:i:s', time());
        $activity = \DB::table("active")
				->where('type_id', '=', $activity_type)
                ->where('status', '=', "enabled")
                ->where('begin_time', '<', $date)
                ->where('end_time', '>', $date)
                ->first(['id', 'name', 'type_id']);

        if(empty($activity)) {
            return $res;
        }

        // 活动规则
        $rule = \DB::table("active_rule")
        		->where("template_id", '=', $activity->type_id)
        		->where("active_id", '=', $activity->id)
        		->first(['id', 'issue_time', 'issue_cycle', 'issue_mode', 'rule']);

        // 未配置规则
        if(empty($rule) || empty($rule->rule)) {
            return $res;
        }
        
        $ruleData = json_decode($rule->rule, true);
        if(empty($ruleData)) {
            return $res;
        }

        // 过滤游戏&返水类型
        foreach($ruleData as $key => &$val) {
        	// 过滤游戏
        	if($val['game_menu_id'] != $gid) {
        		unset($ruleData[$key]);
        		continue;
        	}
        	// 只计算betting
        	if($val['type'] != $this->rebot_way[1]){
        		unset($ruleData[$key]);
        		continue;
        	}
        	// 只计算按流水百分比
        	if($val['data']['status'] != $this->rebot_way_type[0]){
        		unset($ruleData[$key]);
        		continue;
        	}
        }

        // 如果没有符合规则的配置直接返回
        if(empty($ruleData)) {
            return $res;
        }

        $rebet = new \Logic\Lottery\Rebet($this->ci);
        $userList = $rebet->getActivityOrderUserIds($startTime, $endTime, $date_type, $uid);
        
        // 直推
        $direct_role_list = '';
        if (SystemConfig::getModuleSystemConfig('direct')['direct_switch']){
            $direct_role_list = \DB::table('direct_bkge')->get()->toArray();
        }

        // 默认返水比例
        $r = current($ruleData)['data']['value'][0];
        $v = explode(';', $r);
        $res['rate'] = $res['base'] = $v[1] ?? 0;

        foreach($userList as $u){
            $userRebet = $rebet->getUserActivityBkgeMoney($u->user_id, $startTime, $endTime, $ruleData, $date_type, 'show');
            
            // 用户实际返水比例
            if(isset($userRebet['rebetLog'][0]['rate'])){
                $res['rate'] = $res['base']= $userRebet['rebetLog'][0]['rate'];
            }   

            // 判断是否返水
            if($userRebet['money'] > 0) {
            	// 回水金额
                $money             = bcmul($userRebet['money'], 1, 2); 
                // 应有打码量
                $total_require_bet = bcmul($userRebet['total_require_bet'], 1, 2); 
                // 直推返水
                if (!empty($direct_role_list)){
                    $direct_list            = $rebet->userDirectMoney($money, $total_require_bet, $u->user_id, $date_type);
                    $money                  = $direct_list['money'];
                    $res['rate']			= $res['base'] + ($res['base'] * $direct_list['rate'] / 100);
                }

                $res['dml']    += sprintf('%.2f', ($userRebet['allBetAmount'] / 100));
                $res['amount'] = $money;

            }
        }

        $res['amount'] = bcmul($res['amount'], 1, 2);
        $res['rate']   = bcdiv($res['rate'], 1, 2);

        return $res;
    }

    // 查询用户直推提升后比例
    public function getUserDirectProgress($uid)
    {
        $data = \DB::table('direct_bkge')
            ->select([
                'serial_no', 'register_count', 'recharge_count',
                'bkge_increase', 'bkge_increase_week', 'bkge_increase_month',
            ])
            ->orderBy('serial_no')
            ->get()
            ->toArray();
        // 已经完成人数
        $progress = DB::table('user_data')
            ->where('user_id', $uid)
            ->first([
                'direct_register', 'direct_deposit',
                'direct_bkge_increase', 'direct_bkge_increase_week', 'direct_bkge_increase_month',
            ]);
        $res = [
            'curr' => [],
            'next' => [],
        ];
        $maxConf = [];
        if (!empty($res)) {// 注册人数，充值人数都超过了配置最大的值，取最大的配置
            $maxConf = $data[count($data) - 1];
        }

        if (!empty($progress)) {
            foreach ($data as $key => $val) {
                // 如果当前任务没完成直接返回该任务
                if ($progress->direct_deposit >= $val->recharge_count && $progress->direct_register >= $val->register_count) {
                    $res['curr'] = (array)$val;
                }
                if ($progress->direct_deposit < $val->recharge_count || $progress->direct_register < $val->register_count) {
                    if (empty($res['next'])) {
                        $res['next'] = (array)$val;
                    }
                }
            }
            $res['next']['is_top_direct'] = !empty($maxConf) && $progress->direct_bkge_increase >= $maxConf->bkge_increase
                && $progress->direct_bkge_increase_week >= $maxConf->bkge_increase_week
                && $progress->direct_bkge_increase_month >= $maxConf->bkge_increase_month;
            if (!$res['next']['is_top_direct']) {
                $res['next']['direct_bkge_increase'] = $progress->direct_bkge_increase;
                $res['next']['direct_bkge_increase_week'] = $progress->direct_bkge_increase_week;
                $res['next']['direct_bkge_increase_month'] = $progress->direct_bkge_increase_month;
            }
        }

        return $res;
    }

    // 周&月返水
    public function getUserRebetDetailByWeekOrMonthGid($uid, $gid, $date_type, $startTime=null, $endTime=null)
    {
        switch($date_type) {
            case "month":
                //上个月28号
                !$startTime && $startTime = date("Y-m-28", strtotime(date('Y-m-01')) - 86400);
                !$endTime && $endTime   = date('Y-m-27');
                $activity_type = 9;
                $rebet_time = time() >= strtotime(date('Y-m-28 12:00')) ? date('m-28', strtotime("+1 month")) : date('m-28');
                break;
            case "week":
            default :
                //传入开始时间  为了手动补发返水
                if(!$startTime){
                    //获取周一的时间
                    if(date('w') == 1){
                        $startTime = date("Y-m-d");
                        $endTime = date("Y-m-d", strtotime('next sunday'));
                    }else{
                        $startTime = date("Y-m-d", strtotime('last monday'));
                        $endTime = date("Y-m-d", strtotime('next sunday'));
                    }
                }

                $activity_type = 8;
                $rebet_time = date('w') >= 1 ? date("m-d", strtotime('+1 week last monday')) : date('m-d');

                break;
        }

        // 返回结构
        $res = $this->back_water_field[$date_type];
        $res['date'] = $rebet_time;

        $date     = date('Y-m-d H:i:s', time());
        $activity = \DB::table("active")
            ->where('type_id', '=', $activity_type)
            ->where('status', '=', "enabled")
            ->where('begin_time', '<', $date)
            ->where('end_time', '>', $date)
            ->first(['id', 'name', 'type_id']);

        if(empty($activity)) {
            return $res;
        }

        // 活动规则
        $rule = \DB::table("active_rule")
            ->where("template_id", '=', $activity->type_id)
            ->where("active_id", '=', $activity->id)
            ->first(['id', 'issue_time', 'issue_cycle', 'issue_mode', 'rule']);

        // 未配置规则
        if(empty($rule) || empty($rule->rule)) {
            return $res;
        }

        $ruleData = json_decode($rule->rule, true);
        if(empty($ruleData)) {
            return $res;
        }
        // 过滤游戏&返水类型
        foreach($ruleData as $key => &$val) {
            // 过滤游戏
            if($val['game_menu_id'] != $gid) {
                unset($ruleData[$key]);
                continue;
            }
            // 只计算betting
            if($val['type'] != $this->rebot_way[1]){
                unset($ruleData[$key]);
                continue;
            }
            // 只计算按流水百分比
            if($val['data']['status'] != $this->rebot_way_type[0]){
                unset($ruleData[$key]);
                continue;
            }
        }

        // 如果没有符合规则的配置直接返回
        if(empty($ruleData)) {
            return $res;
        }

        if($date_type=='month'){
            $table = 'order_game_user_day';
        }else{
            $table = 'order_game_user_week';
        }
        $menuList = \Model\GameMenu::getParentMenuIdType();
        $userMenus = [];

        foreach($ruleData as $rule){
            if(!isset($menuList[$rule['game_menu_id']])){
                continue;
            }
            $userMenus[$rule['game_menu_id']] = $menuList[$rule['game_menu_id']];
        }
        $list = (array)\DB::connection('slave')->table($table)
            ->selectRaw('game,sum(bet) as bet,sum(profit) as profit')
            ->where('date','>=',$startTime)
            ->where('date','<=',$endTime)
            ->where('user_id','=',$uid)
            ->where('game', $userMenus[$gid])
            ->groupBy('game')->get()->first();
        // 默认返水比例
        $r = current($ruleData)['data']['value'][0];
        $v = explode(';', $r);
        $res['rate'] = $res['base'] = $v[1];
        foreach($ruleData as $key => &$val) {
            //今日投注金额
            if (!isset($list['bet'])) continue;
//            if (!isset($userData[$userMenus[$val['game_menu_id']]])) continue;
            $allBetAmount = $list['bet'] / 100;

            foreach ($val['data']['value'] as $item){

                $value = explode(';', $item);
                $bet   = explode(',',$value[0]);

                if ($allBetAmount >= $bet[0]){
                    $res['rate']      = bcdiv($value[1], 1, 2);;
                }
            }
        }

        return $res;
    }
    // 执行日反查询
    public function getUserRebetDetailByDailyAll($userId = 0, $gid = 0, $date = '')
    {
        // 日期
        $date = empty($date) ? date("Y-m-d") : $date;

        $rebet = new Rebet($this->ci);

        // 获取等级返水配置列表  一级下标为层级，二级下标为game_id 值为回水设置
        $hallInfo = $this->getRebetConfByUserLevel($gid);

        $res = $this->back_water_field['day'];
        $res['date'] = date("m-d", strtotime("+1 day"));
        if(!$hallInfo){
            $this->logger->info('【游戏返水】无回水设置 ' . $date);
            return $res;
        }

        // 查询用户等级对应的返水比例
        $user_ranting = \DB::table('user')->where('id', $userId)->value('ranting');
        $rate_info = $hallInfo[$user_ranting] ?? [];
        if(empty($rate_info)) {
            return $res;
        }
        $res['rate'] = $res['base'] = $this->getRebetRate(current($rate_info));
        $website = $this->ci->get('settings')['website'];

        $order_query = \DB::table('order_game_user_middle as o')->leftJoin('user as u','o.user_id','=','u.id')
            ->where('o.date', $date)
            ->where('o.user_id', $userId)
            ->whereRaw(" !find_in_set('refuse_rebate',u.auth_status)");

        //指定标签会员不返水
        if(!empty($website['notInTags'])){
            $notInTags = implode(',', $website['notInTags']);
            $order_query->whereRaw(" !find_in_set(u.tags,'{$notInTags}')");
        }

        $order_list = $order_query->groupBy('o.user_id','o.game_type')
            ->select([\DB::raw('o.user_id,u.ranting,u.name,u.wallet_id, o.game_type,o.play_id game_id, sum(o.bet) bet, sum(o.profit) profit')])
            ->get()->toArray();

        if($order_list){
            // 总数
            $order_num = count($order_list);
            //用for循环可以释放内存
            for( $i = 0; $i < $order_num;  $i++ ){
                $order = (array)$order_list[$i];
                $order['type_name'] = $this->lang->text($order['game_type']);
                // 游戏等级未设置返水
                if(!isset($hallInfo[$order['ranting']])) {
                    continue;
                }
                // 这个game不返水
                if(empty($hallInfo[$order['ranting']][$order['game_id']])){
                    continue;
                }

                $rebet_config = $hallInfo[$order['ranting']][$order['game_id']];

                $rebetConfigs = $rebet->getRebetConfig([$order['game_id']=>$rebet_config], $order['game_id']);
                if(!empty($rebetConfigs)){
                    $value = $order['bet'];
                    foreach($rebetConfigs['data'] as $data) {
                        if($value >= $data['range'][0] && $value < $data['range'][1]) {
                            $res['rate'] = $data['value'];
                        }
                    }
                }

                unset($order_list[$i]);
            }
            $res['rate']   = bcdiv($res['rate'], 1, 2);          // 返水比例保留两位 不进行四舍五入
        }

        return $res;
    }
}