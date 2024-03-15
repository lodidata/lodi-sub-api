<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "消息列表详情";
    const DESCRIPTION = "";
    const TAGS = "会员消息";
    const QUERY = [];
    const SCHEMAS = [];
    public $rebot_way = [
//        'loss',     //按当日亏损额回水
        'betting',  //按当日投注额回水
    ];

    public $rebot_way_type = [
        'percentage',   //返现百分比
//        'fixed',        //返现固定金额
    ];

    public function run() {
        $resulterify = $this->auth->verfiyToken();
        if (!$resulterify->allowNext()) {
            return $resulterify;
        }
        if($this->auth->getTrialStatus()) {
            return $this->lang->set(0, [],['total' => 0]);
        }
        $user_id = $this->auth->getUserId();
        $id       = (int) $this->request->getQueryParam('id', 0);

        $data = DB::table('message_pub')
                    ->leftjoin('message', 'message.id', '=', 'message_pub.m_id')
                    ->where('message_pub.status', '!=', -1);

        if ($id > 0) {
            $data = $data->where('message_pub.id', $id);
        }

        $data = $data->select([
                    'message_pub.id', 
                    'message_pub.status', 
                    DB::raw('from_unixtime(message_pub.created) as start_time'), 
                    'message.title', 
                    'message.content', 
                    'message.send_type', 
                    'message.type',
                    'message.active_type',
                    'message.active_id',
                    DB::raw('FROM_UNIXTIME(message_pub.created) as start_time'),
                ])
                ->where('message_pub.uid', $user_id)
                ->orderBy('message_pub.id', 'DESC');
        $result = $data->get()->first();

        $result->title   = $this->translateMsg($result->title);
        $result->content = $this->translateMsg($result->content);
        if(in_array($result->active_type,['12','13','14','16','17'])){
            //如果是彩金消息通知，补充彩金领取信息
            if ($result->active_type == 12 && !empty($result->active_id)) {
                $handse_log = (array)DB::table("active_handsel_log")->where('active_handsel_id', $result->active_id)->first();
                if ($handse_log) {
                    $handse_receive = (array)DB::table("handsel_receive_log")->whereRaw('user_id=? and handsel_log_id=?', [$user_id,$handse_log['id']])->first();
                }
                $result->give_amount = isset($handse_receive['receive_amount']) ? bcdiv($handse_receive['receive_amount'],100,2) : 0;
            }
            if (isset($handse_receive['status'])) {
                if ($handse_receive['status'] == 0 && $handse_receive['valid_time'] < date("Y-m-d H:i:s")) {
                    $result->active_status = 2;
                } else {
                    $result->active_status = $handse_receive['status'];    //-1表示非彩金活动领取状态，0-未领取，1-已领取，2-彩金失效
                }
            } else {
                $result->active_status = -1;
            }
            //返水审核
            if($result->active_type == 13 && !empty($result->active_id)){
                $activeApply = DB::table('active_apply')->where('id',$result->active_id)->first(['status','batch_no','coupon_money']);
                //获取返水明细
                $msg_list = $this->msg_list(13,$result->active_id,$user_id);
                $result->content_list = $msg_list['game_list'];
                if ($activeApply){
                    $check = \DB::table('active_backwater')->where('batch_no',$activeApply->batch_no)->first(['id','type','batch_time']);
                    $result->give_amount = isset($activeApply->coupon_money) ? bcdiv($activeApply->coupon_money,100,2) : 0;
                    if ($check){
                        $result->batch_time         = $check->batch_time;
                        $result->backwater_type     = $check-> type;
                        $time = explode('~',$check->batch_time);
                        if ($check-> type ==2){
                            $result->title              = '【'.substr($time[1],5).'】'.$this->lang->text('Weekly rebet money');//周返
                        }else{
                            $result->title              = '【'.substr($time[1],5).'】'.$this->lang->text('Monthly rebet money');//月返
                        }
                    }
                }

                if(!empty($activeApply)){
                    if($activeApply->status == 'pass'){
                        $result->active_status=1;
                    }elseif($activeApply->status == 'pending'){

                        $result->active_status=0;
                    }
                }else{
                    $result->active_status = -1;
                }
            }
            if($result->active_type == 14 && !empty($result->active_id)){
                $back  = DB::table('rebet')->where('batch_no',$result->active_id)->where('user_id',$user_id)->first(['status','rebet']);
                //获取返水明细
                $msg_list = $this->msg_list(14,$result->active_id,$user_id);
                $result  -> content_list = $msg_list['game_list'];
                $result  -> give_amount  = $msg_list['all_money'];
                $check   = \DB::table('active_backwater')->where('batch_no',$result->active_id)->first(['id','type','batch_time']);
                if ($check){
                    $result->batch_time         = $check->batch_time;
                    $result->title              = '【'.substr($check->batch_time,5).'】'.$this->lang->text('Daily rebet money');//日返
                }
                $result->backwater_type     = 1;//日返
                if(!empty($back)){
                    if($back->status == 3){
                        $result->active_status=1;
                    }elseif($back->status == 2){
                        $result->active_status=0;
                    }
                }else{
                    $result->active_status = -1;
                }
            }
            if (in_array($result->active_type,['13','14'])){
                $result->desc = $this->translateMsg('We will now open an activity to increase the return water ratio. Stay tuned!');
            }
            //月俸禄
            if($result->active_type == 16 && !empty($result->active_id)){
                $back = DB::table('user_monthly_award')->where('id',$result->active_id)->where('user_id',$user_id)->first(['status','award_money']);
                $result->give_amount = sprintf('%.2f', $back->award_money / 100) ;
                if(!empty($back)){
                    if($back->status == 3){
                        $result->active_status=1;
                    }elseif($back->status == 2){
                        $result->active_status=0;
                    }
                }else{
                    $result->active_status = -1;
                }
            }
            //周俸禄
//            if($result->active_type == 17 && !empty($result->active_id)){
//                $back = DB::table('user_week_award')->where('id',$result->active_id)->where('user_id',$user_id)->first(['status','award_money']);
//                $result->give_amount = sprintf('%.2f', $back->award_money / 100) ;
//                if(!empty($back)){
//                    if($back->status == 3){
//                        $result->active_status=1;
//                    }elseif($back->status == 2){
//                        $result->active_status=0;
//                    }
//                }else{
//                    $result->active_status = -1;
//                }
//            }
        }
        return $this->lang->set(0, [], $result);
    }


    /**
     * 获取返水明细
     * $type 13周返月返  14 日返
     * $batch_no 日返-批次号   周返月返-active_apply_id
     * $user_id 用户id
     * return array
     */
    public function msg_list($type,$batch_no,$user_id){
        $id         = (int) $this->request->getQueryParam('id', 0);
        $key        = \Logic\Define\CacheKey::$perfix['backwater'].$id;
        $redisData  =  $this->redis->get($key);

        if(!empty($redisData)){
            return json_decode($redisData,true);
        }
        $game_list = [];
        $all_money = 0;

        if ($type ==14){
            //日返
            $data = DB::table('rebet')->where('batch_no',$batch_no)->where('user_id',$user_id)->get(['bet_amount','proportion_value', 'rebet', 'plat_id as game_id'])->toJson();
            $data = $this->getRebetList($data);
            if (is_array($data)){
                foreach ($data as $k=>$item){
                    $item  = (array) $item;
                    $game_list[$k]['game_id']       = $item['game_type'];
                    $game_list[$k]['value']         = $item['proportion_value'];
                    $game_list[$k]['require_bet']   = $item['bet_amount'];
                    $game_list[$k]['money']         = $item['rebet'];
                }

                $all_money       = array_sum(array_column($game_list,'money'));
                $all[]           = ['game_id'=>'Total','value' => '/','require_bet'=>'/','money'=>sprintf('%.2f', $all_money)];
                $game_list = array_merge($game_list,$all);
            }
        }else{
            //13周返 月返
            $list = DB::table('rebet_log')->where('active_apply_id',$batch_no)->where('user_id',$user_id)->first();
            if (!empty($list)){
                $data = json_decode($list->desc,true);
                $all_money = 0;
                foreach ($data as $k=>$item){
                    if ($item['money'] == 0 ) continue;
                    $item['allBetAmount'] = bcdiv($item['allBetAmount'],100,2);

                    $value = $this->getRebetActConfig($item['rebetConfigs'],$item['allBetAmount']);
                    if ($list->direct_rate && $list->direct_rate > 0){
                        $value         = bcadd($value,$value * ($list->direct_rate / 100),2);
                        $item['money'] = bcadd($item['money'],$item['money'] * ($list->direct_rate / 100), 2);
                    }
                    $game_list[$k]['game_id']       =  DB::connection('slave')->table("game_menu")->where('id',$item['game_id'])->first('name')->name;
                    $game_list[$k]['value']         =  $value;
                    $game_list[$k]['require_bet']   = $item['allBetAmount'];
                    $tmp_money = $item['money'] ? bcdiv($item['money'], 100, 4) : 0;
                    $all_money += $tmp_money;
                    $game_list[$k]['money']         = sprintf('%.2f', $tmp_money);
                }
//                $all_money       = array_sum(array_column($game_list,'money'));
                $all_require_bet = array_sum(array_column($game_list,'require_bet'));
                $direct_rate     = '/';
                if ($all_require_bet){
                    $direct_rate     = bcdiv($all_money,$all_require_bet,4) * 100;
                }
                $all[]           = ['game_id'=>'Total','value' =>sprintf('%.2f', $direct_rate),'require_bet'=>sprintf('%.2f', $all_require_bet),'money'=>sprintf('%.2f', $all_money)];
                $game_list = array_merge($game_list,$all);
            }

        }
        //返水明细
        $info['game_list'] =  $game_list;
        //返水总额
        $info['all_money'] =  sprintf('%.2f', $all_money) ?? 0;
        $this->redis->setex($key,60*60*24,json_encode($info));
        return $info;
    }

    /**
     * 根据游戏分类投注额获取当前阶级返水比例
     * @return int 当前返水比例
     */
    public function getRebetActConfig($data, $all_amount) {
        if(empty($data)) {
            return false;
        }
        if(!in_array($data['type'][0], $this->rebot_way)) {
            return '/';
        }
        if(!in_array($data['type'][1], $this->rebot_way_type)) {
            return '/';
        }
        $value = '/';
        //如果投注额超过最大比例 按最高的算
        foreach ($data['data'] as $key => $item){
            if ($all_amount >= $item['range'][0]){
                $value   = $item['value'];
            }
        }

        return $value;
    }


    /**
     * 合并厂商日返，返回游戏类型分类数组
     * @return string
     */
    public function getRebetList($data)
    {
        $data = json_decode($data, true);

        $platIdArr = array_column($data, 'game_id', 'game_id');
        if ($platIdArr) {
            $gameMenus = DB::table('game_menu')
                ->addSelect(['id', 'pid', 'name'])
                ->get()
                ->toJson();
            $gameMenus = json_decode($gameMenus, true);
            $gameMenus = array_column($gameMenus, null, 'id');
            $tmpData   = [];

            foreach ($data as $item) {
                if (isset($gameMenus[$item['game_id']])) {
                       $id  = $gameMenus[$item['game_id']]['id'];
                    if ($gameMenus[$item['game_id']]['pid'] > 0) {
                        $id = $gameMenus[$item['game_id']]['pid'];
                    }
                    $item['game_type'] = $gameMenus[$id]['name'];
                    $tmpData[$id][]    = $item;
                }
            }
            $data = [];

            foreach ($tmpData as $values) {
                $tmpRate    = (($values[0]['direct_rate'] ?? 0) / 100);
                $rate       = bcadd($values[0]['proportion_value'], bcmul($values[0]['proportion_value'], $tmpRate, 2), 2);
                $gameId     = $values[0]['game_id'];
                $gameType   = $values[0]['game_type'];
                $betAmount  = 0;
                $rebet      = 0;
                $gameIds    = [];
                foreach ($values as $item) {
                    $item['rebet'] = bcadd($item['rebet'], bcmul($item['rebet'], $tmpRate, 2), 2);
                    $rebet         = bcadd($rebet, $item['rebet'], 2);
                    $betAmount     = bcadd($betAmount, $item['bet_amount'], 2);
                    $gameIds[]     = $item['game_id'];
                }
                $data[] = [
                    'proportion_value'  => $rate,
                    'game_id'           => $gameId,
                    'game_type'         => $gameType,
                    'bet_amount'        => $betAmount,
                    'rebet'             => $rebet,
                    'game_ids'          => $gameIds,
                ];
            }
        }
        return $data;
    }

};
