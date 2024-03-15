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

/**
 * 返水模块
 * 1 => '回水厅',
 * 2 => '保本厅',
 * 3 => '高赔率厅',
 * 4 => 'PC房',
 * 5 => '传统',
 * 6 => '直播'
 * ---------------------------------
 * 101 => '视讯',
 * 102 => '电子',
 * 103 => '体育',
 */
class RebetThird extends \Logic\Logic
{
    public $rebot_way = [
        'loss',     //按当日亏损额回水
        'betting',  //按当日投注额回水
    ];

    public $rebot_way_type = [
        'percentage',   //返现百分比
        'fixed',        //返现固定金额
    ];

    public $rebet_condition_default = [
        'group_gt' => 0,    // 组合（大双、小单、大单、小双）投注额>=投注总额 N%
        'blend_lt' => 0,    // 混合（大双、小单）投注额<=投注总额 N%
        'guess_gt' => 0,    // 猜特码的期数>= N
        'group_lt' => 0,    // 混合（大、小、单、双）的投注额 <= N%
        'betting_gt' => 0,  // 当天投注期数 >= N期
        'betting_all_gt' => 0 //当天总投注额>=当天最大单投注的 N倍
    ];

    public $rebet_condition_index = [
        0 => 'group_gt',
        1 => 'blend_lt',
        2 => 'guess_gt',
        3 => 'group_lt',
        4 => 'betting_gt',
        5 => 'betting_all_gt'
    ];

    public $level_3th_levels = [
        101 => 'live',
        102 => 'game',
        103 => 'sport'
    ];

    public $parter_type_name = [
        'game' => '电子',
        'live' => '视讯',
        'sport' =>'体育',
    ];

    /**
     * @var \Logic\Lottery\Rebet
     */
    public $logic_rebet;

    protected function getCurrentBetUser($date)
    {
        $userList = \Model\OrderThird::where('created', '>=', $date)
            ->where('created', '<', date('Y-m-d 00:00:00', strtotime($date) + 86400))
            ->select([
                \DB::raw('DISTINCT user_id'),
            ])->get()
            ->toArray();
        return \DB::resultToArray($userList);
    }

    public function getTypeName($t, $isThird = false)
    {
        $types = ['pc28', 'ssc', 'k3', '11x5', 'sc', 'lhc', 'bjc'];
        $typesName = ['28类', '时时彩类', '快3类', '11选5类', '赛车类', '六合彩', '百家彩'];
        $type = array_combine($types, $typesName);

        if ($isThird) {
            return $this->parter_type_name[$t];
        }
        return $type[$t];
    }

    /**
     * 读取厅配置
     * @return [type] [description]
     */
    protected function getHallInfo($date, $levels, $types)
    {
        $rs = [];
        $data = \Model\Partner::whereIn('type', $levels)
            ->select([\DB::raw('type as level,3th_name')])
            ->get()->toArray();

        $types_map = [];
        foreach ($data as $type) {
            $types_map[$type['3th_name']] = $type['level'];
        }

        //获取大厅游戏类别信息
        $halls = Hall3th::getHallByType($types);
        $rs = [];
        foreach ($halls as $v) {
            $row = (array)$v;
            $row['hall_level'] = $types_map[$row['3th_name']];
            $row['hall_name'] = $row['game_name'];
            $row['type'] = $row['3th_name'];
            $row['id'] = $row['game_id']; //对应partner.id
            unset($row['3th_name']);
            $rs[$row['game_id']] = $row;
        }
        return $rs;
    }

    /**
     * 取某类hall配置
     *
     * @param  [type] $hallInfo [description]
     * @param  [type] $type     [description]
     * @param  [type] $level    [description]
     *
     * @return [type]           [description]
     */
    protected function getHallInfoByType($hallInfo, $type, $level)
    {
        $rs = [];
        foreach ($hallInfo as $id => $v) {
            if ($v['type'] == $type && $level == $v['hall_level']) {
                $rs[] = $v;
            }
        }
        return $rs;
    }

    /**
     * 取房间配置
     *
     * @param  [type] $hallInfoTypeData [description]
     *
     * @return [type]                   [description]
     */
    protected function getRoomByHall($hallInfoTypeData)
    {
        if (empty($hallInfoTypeData)) {
            return [];
        }
        $ids = array_values(array_column($hallInfoTypeData, 'id'));
        return \Model\Room::whereIn('hall_id', $ids)->get()->toArray();
    }

    /*
     * 获取某个厅的投注记录
     */
    protected function getUserHallBetList($game_types, $dateStart, $dateEnd, $userId)
    {
        $data = \DB::table('order_game_user_middle')->where('game_type', $game_types)
            ->where('date', $dateStart)
            ->where('user_id', $userId)
            ->select([\DB::raw('user_id, game_type, type_name, bet, profit')]);//bet存储的是分
        $result = $data->get()->toArray();
        return $result;
    }

    /**
     * 依据用户层级拿 整理数据以层级为下标，其它的返设置
     * @return array|bool
     */
    public function getRebetByUserLevel(){
        $arr = ['game_menu.id', 'game_menu.name', 'game_menu.type',
            'rebet.user_level_id', 'rebet.rebot_way', 'rebet.rebet_ceiling',
            'rebet.rebet_desc', 'rebet.rebet_condition'];
        $data = \DB::table('game_menu')->leftJoin('rebet_config AS rebet','game_menu.id','=','rebet.game3th_id')
            ->where('pid', '>', 0)->where('rebet.status_switch', 1)->get($arr)->toArray();
        $user_level = \DB::table('user_level')->get(['id','level'])->toArray();//查询会员层级

        if(!$user_level ||!$data) {
            return false;
        }
        $user_level = array_column($user_level, NULL, 'id');
        $res = [];
        $rebetConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('rebet_config');
        $rebetMultiple = $rebetConfig['day'] ?? [];
        $rebetZeroSwitch = $rebetConfig['day_gt_zero'] ?? [];
        foreach ($data as $val) {
            if($val->rebot_way && isset($user_level[$val->user_level_id])) {
                $l = $user_level[$val->user_level_id]->level;
                $tmpVal = (array)$val;
                $tmpVal['rebet_multiple'] = intval($rebetMultiple[$val->user_level_id] ?? 0);
                $tmpVal['rebet_gt_zero_switch'] = boolval($rebetZeroSwitch[$val->user_level_id] ?? false);
                $res[$l][$val->id] = $tmpVal;
            }
        }
        return $res;
    }

    //得到需要返回的用户ID和用户层级
    public function getRebetUserId($date, array $hall_info) {
        //获取类型
        $game_type = [];
        foreach($hall_info as $key=>$val){
            foreach($val as $d){
                if(!in_array($d['type'], $game_type)){
                    array_push($game_type, $d['type']);
                }
            }
        }
        $userList = RptOrderUser::where('count_date', $date)
            ->whereIn('game_type', $game_type)->select([\DB::raw('DISTINCT user_id')])->pluck('user_id');
        if(!$userList)
            return false;
        $user_level = array_keys($hall_info);
        $user = User::whereIn('id', $userList->toArray())->whereIn('ranting', $user_level)->get(['id AS user_id','ranting'])->toArray();
        foreach ($user AS &$v) {
            $v = (array)$v;
        }
        return $user;
    }

    /**
     * 执行第三方回水运算
     *
     * @param  string $date [description]
     * @param  string $runMode rebet 执行  test 测试不写入数据
     * @return [type]       [description]
     */
    public function runByUserLevelRebet($date = '', $runMode = 'rebet', $userId = 0)
    {
        try{
            $this->startRebet($date, $runMode, $userId);
        }catch (\Exception $e){
            $this->logger->error('【游戏返水错误】 ' . $e->getMessage());
            return false;
        }
        return 'game rebet end';
    }

    public function startRebet($date = '', $runMode = 'rebet', $userId = 0){
        $userId = $runMode == 'rebet' ? 0 : $userId;
        $date = empty($date) ? date("Y-m-d", strtotime("-1 day")) : $date;
        //类型（1：游戏，2：彩票）
        $rebet_exec_id = RebetExec::where('date',$date)->where('type',1)->value('id');

        if ($runMode == 'rebet' && $rebet_exec_id) {
            $this->logger->error('【游戏返水】已经计算过返水数据 ' . $date);
            return false;
        }
        $this->logic_rebet = new Rebet($this->ci);

        if(!$this->logic_rebet->middleOrder()){
            $this->logger->error('日返水投注数据不统一');
            return false;
        }

        if($runMode == 'rebet'){
            //新增一条执行记录 date type有唯一索引 保证只能新增一次
            RebetExec::insert(['date' => $date,'type' => 1]);
        }

        //$hallInfo  一级下标为层级，二级下标为game_id 值为回水设置
        $hallInfo = $this->getRebetByUserLevel();//获取层级列表
        if(!$hallInfo){
            $this->logger->error('【游戏返水】无回水设置 ' . $date);
            return false;
        }
        $wallet            = new Wallet($this->ci);

        $website           = $this->ci->get('settings')['website'];

        $order_query = \DB::table('order_game_user_day as o')->leftJoin('user as u','o.user_id','=','u.id')
            ->where('o.date', $date)
            ->whereRaw(" !find_in_set('refuse_rebate',u.auth_status)");

        if ($userId > 0) {
            $order_query->where('o.user_id', $userId);
        }

        //指定标签会员不返水
        if(!empty($website['notInTags'])){
            $notInTags = implode(',', $website['notInTags']);
            $order_query->whereRaw(" !find_in_set(u.tags,'{$notInTags}')");
        }

        $order_list = $order_query->groupBy('o.user_id','o.game_type')
            ->select([\DB::raw('o.user_id,u.ranting,u.name,u.wallet_id, o.game_type,o.play_id game_id, sum(o.bet) bet, sum(o.profit) profit')])
            ->get()->toArray();

        $batchNo=time();
        $menuData = $this->summaryRebetBetAmount($order_list, $hallInfo);
        if($order_list){
            $order_num = count($order_list);
            if($runMode == 'test'){
                $total_money=0;
            }
            //用for循环可以释放内存
            for($i=0; $i<$order_num; $i++){
                $v = (array)$order_list[$i];
                $v['type_name'] = $this->lang->text($v['game_type']);
                if(!isset($hallInfo[$v['ranting']])) {
                    $this->logger->error('【游戏返水】 第三方类型:LV' . $v['ranting'] . "等级未设置反水user_id=" . $v['user_id'] . $date);
                    continue;
                }
                //这个game不返水
                if(empty($hallInfo[$v['ranting']][$v['game_id']])) continue;
                $rebet_config = $hallInfo[$v['ranting']][$v['game_id']];

                $temp = $this->logic_rebet->threbetElse($date, $wallet, [$v['game_id']=>$rebet_config], $v, $rebet_config['type'], $runMode,true, $batchNo, $menuData);
                if (!empty($temp) && $runMode == 'rebet') {

//                    if($temp['money'] > 0) {
//                        $title = $website['name'];//标题
//                        //发送回水信息
//                        $content = $this->lang->text("Dear user, you bought %s color amount of %s yuan yesterday. The system will return the return amount of %s to you. Please check and check. If you have any questions about the amount of return water, please consult online customer service in time.", [$rebet_config['name'],$temp['allBetAmount'] / 100, $temp['money'] / 100, $title]);
//
//                        $exchange = $this->lang->text('user_message_send');
//                        \Utils\MQServer::send($exchange, [
//                            'user_id' => $v['user_id'],
//                            'title'   => $this->lang->text('Backwater news'),
//                            'content' => $content,
//                        ]);
//                    }
                    //插入日志数据
                    $rebetLog = [
                        'user_id'            => $v['user_id'],//用户id
                        'rebet_user_ranting' => $v['ranting'],//用户等级
                        'game_id'            => $v['game_id'],//第三方游戏id
                        'batch_no'           => $batchNo,//批次id
                        'desc'               => json_encode($temp),
                    ];
                    \DB::table('rebet_log')->insert($rebetLog);
                }
                if(!empty($temp) && $runMode == 'test'){
                    $total_money += $temp['money'];
                }
                unset($order_list[$i]);
            }

            $activeData=\DB::table('rebet')
                           ->selectRaw('count(distinct(user_id)) as cnt,sum(rebet) as back_amount')
                           ->where('batch_no',$batchNo)
                           ->first();
            $backData=array(
                'batch_no'  =>$batchNo,
                'type'      =>1,
                'active_type'=> 2,
                'batch_time'=>$date,
                'back_cnt'  => $activeData->cnt,
                'back_amount'=>bcmul($activeData->back_amount,100) ?? 0,
            );
            if($activeData->cnt ==0){
                $backData['status'] = 2;
                $backData['send_time']=date('Y-m-d H:i:s',time());
            }
            \DB::table('active_backwater')->insert($backData);

            unset($v);
            if($runMode == 'test') echo $total_money;
        }
        return 'rebet success';
    }

    /**
     * 汇总厂商到类型的打码量计算占比
     * @param $order_list
     * @param $hallInfo
     * @return array
     */
    public function summaryRebetBetAmount($order_list, $hallInfo)
    {
        $menus = DB::table('game_menu')
            ->select(['id', 'pid'])
            ->where('pid', '>', 0)
            ->get()
            ->toJson();
        $menus = json_decode($menus, true);
        $menus = array_column($menus, 'pid', 'id');
        $data = [];
        foreach ($order_list as $item) {
            $pid = $menus[$item->game_id] ?? 0;
            $v = (array)$item;
            if ($pid) {
                if (!isset($hallInfo[$v['ranting']])) {
                    continue;
                }
                //这个game不返水
                if (empty($hallInfo[$v['ranting']][$v['game_id']])) continue;
                $tmpItem = $data[$item->user_id][$pid] ?? [
                        'bet' => 0,
                        'game_ids' => [],
                    ];
                $tmpItem['bet'] += $item->bet;
                $tmpItem['game_ids'][$item->game_id] = $item->bet;
                $data[$item->user_id][$pid] = $tmpItem;
            }
        }
        return $data;
    }


    function arrayUniqueness($arr,$key){
        $res = array();
        foreach ($arr as $value) {
            //查看有没有重复项
            if(isset($res[$value[$key]])){
                //有：销毁
                unset($value[$key]);
            }
            else{
                $res[$value[$key]] = $value;
            }
        }
        return $res;
    }
}