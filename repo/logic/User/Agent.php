<?php

namespace Logic\User;

use DB;
use Logic\Define\CacheKey;
use Logic\Set\SystemConfig;
use Model\UserAgent;
use Model\UserData;
use Model\GameMenu;

/**
 * 人人代理模块
 */
class Agent extends \Logic\Logic {

    /**
     * 代理推广链接
     * @param $uid
     * @return array
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function generalizeList($uid) {
        $env = $this->request->getHeaders()['HTTP_ENV'][0] ?? '';
        $domains = SystemConfig::getModuleSystemConfig('market');
        $allConfig = SystemConfig::globalSystemData();
        $allMarket = [];
        foreach ($allConfig as $item) {
            if ($item['module'] == 'market') {
                array_push($allMarket, $item);
            }
        }
        if($env == 'qp') {
            $t = SystemConfig::getModuleSystemConfig('market2');
            $domains['spread_url'] = $t['app_spead_url'];
        }

        $agentInfo = \Model\UserAgent::where('user_id', $uid)
                                     ->first();
        if (!$agentInfo) {
            $this->addAgent(['user_id' => $uid]);

            $agentInfo = \Model\UserAgent::where('user_id', $uid)
                                         ->first();
        }

        $wxConfig = isset($this->ci->get('settings')['weixin_credentials']) ? $this->ci->get('settings')['weixin_credentials'] : [];
        $user = \Model\User::where('id', $uid)
                           ->first(['name','channel_id']);

        $list = [];
        $list['code'] = '';
        $list['h5_url'] = [];
        $list['h5_url_list'] = [];    //用来存放所有h5_url推广链接
        $list['pc_url'] = [];
        $list['spread_url'] = [];
        $list['spread_desc'] = $domains['spread_desc'];
        $list['weChat_url'] = '';
        $list['all_agent'] = $agentInfo['inferisors_all'];//所有下级代理人数
        $list['direct_agent'] = $agentInfo['inferisors_num'];//直系下级代理人数
        $list['next_agent'] = $agentInfo['inferisors_all'] - $agentInfo['inferisors_num'];//下下级代理数
        $list['proportion_value'] = 0;//占成

        if($list['next_agent'] < 0){
            $list['next_agent'] = 0;
        }

        $list['channel_id']=$user->channel_id;

        //统一推广域名
        if (!empty($domains) && !empty($agentInfo)) {
            $list['code'] = $agentInfo['code'];
            //h5_url和h5_url_2 是之前开发写死的两个，这里不对它做删除，只做兼容处理
            if (isset($domains['h5_url']) && !empty($domains['h5_url'])) {
                $h5_url=trim(rtrim($domains['h5_url'], '/')) . (stripos($domains['h5_url'], '?')>0 || stripos($domains['h5_url'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                if(!empty($user->channel_id)){
                    $h5_url = $h5_url.'&channel_id='.$user->channel_id;
                }
                $list['h5_url'][] = $h5_url;
            } else {
                $list['h5_url_2'][] = "";
            }
            if (isset($domains['h5_url_2']) && !empty($domains['h5_url_2'])) {
                $h5_url_2=trim(rtrim($domains['h5_url_2'], '/')). (stripos($domains['h5_url_2'], '?')>0 || stripos($domains['h5_url_2'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                if(!empty($user->channel_id)){
                    $h5_url_2 = $h5_url_2.'&channel_id='.$user->channel_id;
                }
                $list['h5_url_2'][] = $h5_url_2;
            } else {
                $list['h5_url_2'][] = "";
            }

            $pc_url=trim(rtrim($domains['pc_url'], '/')) . (stripos($domains['pc_url'], '?')>0 || stripos($domains['pc_url'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
            $spread_url=trim(rtrim($domains['spread_url'], '/')) . (stripos($domains['spread_url'], '?')>0 || stripos($domains['spread_url'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
            if(!empty($user->channel_id)){
                $pc_url = $pc_url.'&channel_id='.$user->channel_id;
                $spread_url = $spread_url.'&channel_id='.$user->channel_id;
            }
            $list['pc_url'][] =$pc_url;
            $list['spread_url'][] = $spread_url;

            if (!empty($wxConfig)) {
                foreach ($wxConfig as $k => $v) {
                    $redirectUrl = $v['share_domain'] . '?' . http_build_query(['app_id' => $v['app_id'], 'code' => $agentInfo['code']]);
                    $redirectUrl = urlencode($redirectUrl);
                    $v['share_url'] = str_replace('{redirect_url}', $redirectUrl, $v['share_url']);
                    $list['weChat_url'] = $v['share_url'];
                }
            }
        }

        if (!empty($allMarket) && !empty($agentInfo)) {
            $h5List = [];
            //重新定义一个数组用来存放所有H5_url推广链接
            $link_list = [];
            foreach ($allMarket as $k => $v) {
                if (strpos($v['key'], 'h5_url') === 0) {
                    //产品需求：第一条h5_url初始状态默认为允许代理，其他h5_url默认不允许代理
                    $isProxy = 0;
                    if ($v['key'] == "h5_url") {
                        $isProxy = 1;
                    }
                    $link=trim(rtrim($v['value'], '/')) . (stripos($v['value'], '?')>0 || stripos($v['value'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                    if(!empty($user->channel_id)){
                        $link = $link.'&channel_id='.$user->channel_id;
                    }
                    $list['h5_url_list'][] = [
                        'name' => $v['name'],
                        'key' => $v['key'],
                        'link' => $link,
                        'is_proxy' => $isProxy,
//                        'state' => $v['state'] == "enabled" ? 1 : -1
                    ];
                    array_push($link_list, trim(rtrim($v['value'], '/')) . (stripos($v['value'], '?')>0 || stripos($v['value'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code']);
                }
            }
            //查询用户的链接状态表, 获取链接是否允许代理的状态
            $userLinks = DB::table('user_agent_link_state')->where('uid', $uid)->whereIn('link', $link_list)->get()->toArray();
            if ($userLinks) {
                $fmtUserLinks = [];
                foreach ($userLinks as $v) {
                    $fmtUserLinks[$v->link] = [
                        'use_state' => $v->use_state,
                        'is_del' => $v->is_del,
                        'is_proxy' => $v->is_proxy
                    ];
                }
                foreach ($list['h5_url_list'] as $k=>$v) {
                    if (isset($fmtUserLinks[$v['link']])) {
                        if ($fmtUserLinks[$v['link']]['is_del'] == 1) {
                            unset($list['h5_url_list'][$k]);
                            continue;
                        }
                        $list['h5_url_list'][$k]['is_proxy'] = $fmtUserLinks[$v['link']]['is_proxy'];
                    }
                }
            }
            //防止数组对象变成对象格式，PHP数字数组中删除了某个元素后，该数组会变成key=>value形式数组，即对象格式。
            $list['h5_url_list'] = array_values($list['h5_url_list']);
        }

        //单独用户推广域名


        $market = \DB::table('user_agent_market')
                     ->where('user_name', '=', $user->name)
                     ->get()
                     ->toArray();
        if ($agentInfo && $user->name && $market) {
            foreach ($market as $v) {
                if (!in_array($v->h5_url, $list['h5_url'])) {
                    $list['h5_url'][] = trim(rtrim($v->h5_url, '/')) . (stripos($domains['h5_url'], '?')>0 || stripos($domains['h5_url'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                }
                if (!in_array($v->h5_url_2, $list['h5_url_2'])) {
                    $list['h5_url_2'][] = trim(rtrim($v->h5_url_2, '/')) . (stripos($domains['h5_url_2'], '?')>0 || stripos($domains['h5_url_2'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                }
                if (!in_array($v->pc_url, $list['pc_url'])) {
                    $list['pc_url'][] = trim(rtrim($v->pc_url, '/')) . (stripos($domains['pc_url'], '?')>0 || stripos($domains['pc_url'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                }

                if (!in_array($v->spread_url, $list['spread_url'])) {
                    $list['spread_url'][] = trim(rtrim($v->spread_url, '/')) . (stripos($domains['spread_url'], '?')>0 || stripos($domains['spread_url'], '/share')>0 ? "&" : "?").'code=' . $agentInfo['code'];
                }
            }
        }
        return $list;
    }

    /**
     * 获取代理下级资料
     * @param array $condtion
     * @return array
     */
    public function getAgentInfoById($condtion = []) {

        $res = $this->isHigherLevel($condtion['uid'], $condtion['uid_agent']);//验证是否有权限查看
        if ($res->getState()) {
            return $res;
        }

        $rs = \Model\UserAgent::getAgentInfo($condtion['uid_agent']);
        if (!empty($rs)) {
            $rs = (array)$rs[0];
            $rs = \Utils\Utils::RSAPatch($rs);
        }

        if ($rs) {
            $rs['sum_bet'] = UserData::where('user_id', '=', $condtion['uid'])
                                ->value('order_amount');
            $rake_round = $this->allow('', '', $condtion['uid'], $condtion['uid_agent'], true);
            $rake_round = $rake_round->getData();
            $rs['rake_region'] = $rake_round;
        }

        return $rs;
    }

    /**
     * 佣金率范围
     * @param string $agentArr 判断传过来的数据是否在佣金率范围内的数据 json格式
     * @param string $invitCode 邀请码
     * @param mixed $invitUserId 邀请用户ID  上级ID
     * @param mixed $userId 用户ID（取下级佣金范围）
     * @param boolean  true返回佣金范围  false 返回默认佣金率值
     *
     * @return array|int
     */
    public function allow(string $agentArr = '', string $invitCode = '', int $invitUserId = 0, int $userId = 0, $around = false) {
        $agentArr = json_decode($agentArr,true);
        if ($userId && !$invitUserId) {
            $info = \Model\UserAgent::where('user_id', $userId)
                                    ->first();

            $invitUserId = $info['uid_agent'] ?? 0;
        }

        //是否有邀请码
        if ($invitCode || $invitUserId) {
            if ($invitCode) {
                $agent = \Model\UserAgent::where('code', $invitCode)
                                         ->first();
            } else {
                $agent = \Model\UserAgent::where('user_id', $invitUserId)
                                         ->first();
            }
        }
        $res = [];
        //最大返佣值   有上级取上级，没上级取系统
        $conf = SystemConfig::getModuleSystemConfig('rakeBack');
        unset($conf['agent_switch']);
        if(isset($agent) && $agent) {
            $max = json_decode($agent['bkge_json'],true);
        }

        foreach ($conf as $key => $val) {
            $res['MAX_'.$key] = $max[$key] ?? $val;
            $res['MIN_'.$key] = 0;  //最小返佣值  初始化
        }

        //最小返佣值
        if ($userId) {
            //依据下级获取返佣值最大
            $bkges = \Model\UserAgent::where('uid_agent', $userId)->pluck('bkge_json')->toArray();

            foreach ($bkges as $val) {
                $bkge = json_decode($val,true);
                if(!$bkge) continue;

                foreach ($bkge as $k => $b) {
                    $key = 'MIN_'.$k;
                    if (@isset($res[$key])) {
                        $res[$key] = $res[$key] < $b ? $b : $res[$key];
                    } else {
                        @$res[$key] = $b;
                    }
                }
            }
        }

        //退佣比例
        if (is_array($agentArr) && $agentArr) {

            foreach ($agentArr as $key=>$val) {

                if($val > $res['MAX_'.$key]) {
                    return $this->lang->set(131, [], $res);
                }

                if(isset($res['MIN_'.$key]) && $val < $res['MIN_'.$key]) {
                    return $this->lang->set(132, [], $res);
                }

                if(!isset($agentArr[$key])) {
                    $agentArr[$key] = 0;
                }
            }
            return $this->lang->set(0, [], $agentArr);   //  在返佣率范围内  返回传过来的数值
        }

        if ($around) {
            return $this->lang->set(0, [], $res);
        }

        if(isset($agent) && $agent) {
            $default = json_decode($agent['junior_bkge'],true);
        }else {
            $default = SystemConfig::getModuleSystemConfig('rakeBack');
        }

        return $this->lang->set(0, [], array_merge($conf,$default));
    }

    /**
     * 添加代理
     * @param array $agentArr 代理数据( user_id:注册用户ID,code：而来的推广码可选 )
     * @return array|int
     */
    public function addAgent($agentArr, $invitCode = '', $invitUserId = '') {

        if (!isset($agentArr['user_id'])) {
            return $this->lang->set(133);
        }
        try {
            $agent = [];
            if ($invitCode || $invitUserId) {
                if ($invitCode) {
                    $agent = \Model\UserAgent::where('code', $invitCode)
                                             ->first();
                    if(!$agent){
                        $agent_id = \DB::table('agent_code')->where('code',$invitCode)->value('agent_id');
                        $agent    = \Model\UserAgent::where('user_id', $agent_id)
                            ->first();
                    }

                } else {
                    $agent = \Model\UserAgent::where('user_id', $invitUserId)
                                             ->first();
                }

                if (!empty($agent)) {
                    $agent = $agent->toArray();
                    $agentInfo = \Model\User::where('id', $agent['user_id'])
                                            ->first()
                                            ->toArray();
                    $agentArr['bkge'] = $agent['junior_bkge'];
                }
            }

            $sysConfig = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');

            $agentId                    = $agent['user_id'] ?? 0;
            $agentArr['uid_agent']      = $agentId;                                        //渠道，3后台
            $agentArr['uid_agent_name'] = $agentInfo['name'] ?? '';                                        //渠道，3后台
            $agentArr['uid_agents']     = isset($agent['uid_agents']) && $agent['uid_agents'] ? $agent['uid_agents'] . ',' . $agentArr['user_id'] : $agentArr['user_id'];                                        //渠道，3后台
            $agentArr['channel']        = $agentArr['channel'] ?? '1';                                        //渠道，3后台
            $agentArr['code']           = $this->getInviteCode();
            $agentArr['bkge_json']      = $agentArr['bkge'] ?? json_encode(SystemConfig::getModuleSystemConfig('rakeBack'));
            $agentArr['junior_bkge']    = json_encode($this->rakeBackJuniorDefault());
            $agentArr['level']          = isset($agent['level']) ? $agent['level'] + 1 : 1;
            $agentArr['direct_reg']     = $sysConfig['direct_switch'] === true ? 1 : 0;        //直推开启后注册的用户才有充值赠送(1有0没有)
            $agentArr['updated']        = time();
            $agentArr['created']        = time();
            $agentArr                   = array_filter($agentArr);

            if (!\Model\UserAgent::create($agentArr)) {
                return $this->lang->set(134, [], $agentArr);
            }
            $agents = explode(',', $agentArr['uid_agents']);
            if ($agentId > 0) {  //说明有上级

                \Model\UserAgent::where('user_id', $agentId)
                                ->update(['inferisors_num' => DB::raw('inferisors_num + 1')]);
                $child_agents = [];
                foreach ($agents as &$pid){
                    if($pid != $agentArr['user_id']){
                        $child_agents[] = [
                            'pid' => $pid,
                            'cid' => $agentArr['user_id'],
                        ];
                    }
                }
                DB::table('child_agent')->insert($child_agents);
            }
            //注册时包含自己为代理
            \Model\UserAgent::whereIN('user_id', $agents)
                ->update(['inferisors_all' => DB::raw('inferisors_all + 1')]);
            return $this->lang->set(135);
        } catch (\Exception $e) {
            return $this->lang->set(134, [], [], ['err' => $e->getMessage()]);
        }
    }

    //添加新大类，之前注册的用户并没有该类的返佣值
    public function synchroUserBkge(int $user_id , array $bkge_info) {
        $config = SystemConfig::getModuleSystemConfig('rakeBack');
        unset($config['agent_switch']);  //排除人人返佣开关
        $bkge = json_decode($bkge_info['bkge_json'],true) ?? [];
        $junior = json_decode($bkge_info['junior_bkge'],true) ?? [];

        $bkge_diff = array_diff_assoc($config,$bkge);
        $junior_diff = array_diff_assoc($config,$junior);
        if(!$bkge_diff && !$junior_diff) {
            return ['bkge'=>$bkge,'junior'=>$junior];
        }
        //差异初始化
        foreach ($bkge_diff as &$val) {
            $val = 0;
        }
        foreach ($junior_diff as &$val) {
            $val = 0;
        }

        if($bkge_info['uid_agent'] > 0) {  //代表有上级代码
            $agent = UserAgent::where('uid_agent',$bkge_info['uid_agent'])->value('junior_bkge');
            $bkge_diff = $agent ? array_merge($bkge_diff,json_decode($agent,true)) : $bkge_diff;
        }
        $bkge = array_merge($bkge_diff,$bkge);
        $junior = array_merge($junior_diff,$junior);
        UserAgent::where('user_id',$user_id)->update([
                                                        'bkge_json'=>json_encode($bkge),
                                                        'junior_bkge'=>json_encode($junior)
                                                    ]);
        return ['bkge'=>$bkge,'junior'=>$junior];
    }

    //默认下级返佣比例
    public function rakeBackJuniorDefault(int $user_id = 0) {
        $config = SystemConfig::getModuleSystemConfig('rakeBack');
        $default = [];
        foreach ($config as $key => $val){
            $default[$key] = 0;
        }
        if($user_id) {
            $junior = json_decode(UserAgent::where('user_id')->value('junior_bkge'),true) ?? [];
            $default = array_merge($default,$junior);
        }
        unset($default['agent_switch']);
        return $default;
    }

    //生成推广码
    public function getInviteCode($length = 10) {
        $chars_list = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $chars = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            $chars .= $chars_list[ mt_rand(0, strlen($chars_list) - 1) ];
        }
        return $chars;
        //return dechex(time() . mt_rand(0, 999999));
    }

    /**
     * 验证是否是上级代理
     *
     * @param $uid
     * @param $uidAgent
     *
     * @return array
     * @throws \NotFoundException
     */
    public function isHigherLevel($uid, $uidAgent) {
        $res = \Model\UserAgent::where('user_id', $uidAgent)
                               ->select(['uid_agents'])
                               ->first();
        if (empty($res)) {
            return $this->lang->set(115);
        }

        if (!in_array($uid, explode(',', $res['uid_agents']))) {
            return $this->lang->set(115);
        }
        return $this->lang->set(0);
    }

    /**
     * 总流水统计
     *
     * @param $uid
     * @param $type 1、天 2、月
     * @param $time 时间 格式2022-6-21
     *
     */
    public function betStatic($uid, $type=1, $time=''){
        if(!in_array($type,[1,2])){
            $type = 1;
        }
        if(empty($time)){
            $time = date('Y-m-d', time());
        }else{
            //兼容一下时间格式
            $time = date('Y-m-d', strtotime($time));
        }
        $bet_list = [];
        if($type == 1){
            //一次返回7天数据
            $end_time = $time;
            for($i=0;$i<7;$i++){
                $start_time = strtotime($end_time) - 24*60*60;
                $start_time = date('Y-m-d', $start_time);
                try{
                    $bkge_info = \DB::table('unlimited_agent_bkge')
                        ->where('user_id', '=', $uid)
                        ->Where('date', '=', $start_time)
                        ->selectRaw('sum(bet_amount) as bet_amount,sum(settle_amount) as settle_amount')
                        ->get()->toArray();
                    if(!empty($bkge_info[0]->bet_amount)){
                        $bet_amount = $bkge_info[0]->bet_amount;
                    }else{
                        $bet_amount = 0;
                    }
                    if(!empty($bkge_info[0]->settle_amount)){
                        $bkge = $bkge_info[0]->settle_amount;
                    }else{
                        $bkge = 0;
                    }
                }catch (\Exception $e) {
                    $bet_amount = 0;
                    $bkge = 0;
                }
                $bet_list[] = [
                    'time'       => $start_time,
                    'bet_amount' => $bet_amount,
                    'bkge'       => $bkge,
                    'fee'        => 0,
                ];
                $end_time = $start_time;
            }
        }else{
            //一次返回1年
            $year = date('Y',strtotime($time));
            for($i=12;$i>0;$i--){
                $month = mb_strlen($i) > 1 ? $i : '0'.$i;
                $next = $i+1;
                $next_month = mb_strlen($next) > 1 ? $next : '0'.$next;
                $start_time = $year.'-'.$month.'-01';
                $end_time = $year.'-'.$next_month.'-01';
                if(strtotime($start_time) > time()){
                    continue;
                }
                try{
                    $bkge_info = \DB::table('unlimited_agent_bkge')
                        ->where('user_id', '=', $uid)
                        ->Where('date', '>=', $start_time)
                        ->Where('date', '<', $end_time)
                        ->selectRaw('sum(bet_amount) as bet_amount,sum(bkge) as bkge,sum(fee_amount) as fee_amount')
                        ->get()->toArray();
                    if(!empty($bkge_info[0]->bet_amount)){
                        $bet_amount = $bkge_info[0]->bet_amount;
                    }else{
                        $bet_amount = 0;
                    }
                    if(!empty($bkge_info[0]->bkge)){
                        $bkge = $bkge_info[0]->bkge;
                    }else{
                        $bkge = 0;
                    }
                    if(!empty($bkge_info[0]->fee_amount)){
                        $fee = $bkge_info[0]->fee_amount;
                    }else{
                        $fee = 0;
                    }
                }catch (\Exception $e) {
                    $bet_amount = 0;
                    $bkge = 0;
                    $fee = 0;
                }
                $bet_list[] = [
                    'time'       => $start_time,
                    'bet_amount' => $bet_amount,
                    'bkge'       => $bkge,
                    'fee'        => $fee,
                ];
            }
        }
        $sort = array_column($bet_list, 'time');
        array_multisort($sort, SORT_ASC, $bet_list);

        return $bet_list;
    }

    /**
     * 统计时间段的数据
     *
     * @param $uid
     * @param $start_time 开始时间
     * @param $end_time 截止时间
     *
     */
    public function timeInfo($uid, $start_time, $end_time){
        $time_info = [];
        //统计时间段的数据
        try {
            $agent_bkge_query = \DB::table('unlimited_agent_bkge')
                ->where('user_id', '=', $uid)
                ->Where('date', '>=', $start_time)
                ->Where('date', '<=', $end_time);
            $query2 = clone $agent_bkge_query;
            //利润
            $time_info['profits'] = $query2->sum('bkge');
            //占成
            $time_info['proportion'] = 0;
            //公司成本
            $query3 = clone $agent_bkge_query;
            $time_info['fee_amount'] = $query3->sum('fee_amount');

            $rpt_agent_query = \DB::table('rpt_agent')
                ->where('agent_id', '=', $uid)
                ->Where('count_date', '>=', $start_time)
                ->Where('count_date', '<=', $end_time);
            //有效投注
            $time_info['valid_amount'] = $rpt_agent_query->sum('bet_agent_amount');
            //新注册用户
            $rpt_query = clone $rpt_agent_query;
            $time_info['new_register'] = $rpt_query->sum('agent_inc_cnt');
            //有效用户
            $rpt_query2 = clone $rpt_agent_query;
            $time_info['valid_user'] = $rpt_query2->sum('deposit_user_num');
            //首充人数
            $rpt_query3 = clone $rpt_agent_query;
            $time_info['first_recharge_user'] = $rpt_query3->sum('new_register_deposit_num');
            //总充值金额
            $rpt_query4 = clone $rpt_agent_query;
            $time_info['all_recharge_amount'] = $rpt_query4->sum('deposit_agent_amount');
            //首充金额
            $rpt_query5 = clone $rpt_agent_query;
            $time_info['first_recharge_amount'] = $rpt_query5->sum('new_register_deposit_amount');
            //总流水
            $time_info['total_bet_amount'] = $time_info['valid_amount'];
            //投注人数
            //获取下级代理uids
            $child_agent = \DB::table('child_agent')->where('pid', '=', $uid)->get()->toArray();
            $agent_uids = [];
            if (!empty($child_agent)) {
                $agent_uids = array_column($child_agent, 'cid');
            }
            $time_info['bet_user'] = \DB::table('rpt_user')
                ->Where('count_date', '>=', $start_time)
                ->Where('count_date', '<=', $end_time)
                ->WhereIn('user_id', $agent_uids)
                ->Where('bet_user_amount', '>', 0)
                ->distinct('user_id')
                ->count();

            //获取自身流水
            $time_info['bet_amount'] = DB::table("rpt_user")
                ->where('user_id', $uid)
                ->where('count_date', '>=', $start_time)
                ->where('count_date', '<=', $end_time)
                ->sum('bet_user_amount');
            $time_info['next_bet_amount'] = bcsub($time_info['total_bet_amount'], $time_info['bet_amount'], 2);
        }catch (\Exception $e) {
            return false;
        }

        return $time_info;
    }

    /**
     * 开启代理
     */
    public function getProfit($userId){
        $userAgent=DB::table('user_agent')->where('user_id',$userId)->first();
        $proportion_list = [];
        $game_list = GameMenu::getGameTypeList();
        $lose_sql='';
        foreach($game_list as $v){
            if(in_array($v,['CP','HOT'])){
                continue;
            }
            $lose_sql .="IFNULL(sum(cast(lose_earn_list->'$.{$v}' as DECIMAL(18,2))),0) as {$v}_lose,";
            $proportion_list[$v] = 0;
        }
        $sub_config = SystemConfig::getModuleSystemConfig('profit_loss');
        if($userAgent->uid_agent == 0){
            //1级默认占比开关
            $default_proportion_switch=$sub_config['default_proportion_switch'];
            //1级固定占比开关
            $fixed_proportion_switch=$sub_config['fixed_proportion_switch'];
            if(isset($fixed_proportion_switch) && $fixed_proportion_switch){
                $fixed_proportion=json_decode($sub_config['fixed_proportion'],true);
                foreach($fixed_proportion as $key=>$value){
                    $proportion_list[$key]=$value;
                }
            }elseif(isset($default_proportion_switch) && $default_proportion_switch){
                $default_proportion=json_decode($sub_config['default_proportion'],true);
                foreach($default_proportion as $key=>$value){
                    $proportion_list[$key]=$value;
                }
            }else{
                $lose_sql=rtrim($lose_sql,',');
                $query=DB::table('orders_report')->selectRaw($lose_sql);
                //查询是否有下级
                $userAgentId=DB::table('user_agent')->where('uid_agent',$userId)->pluck('user_id')->toArray();
                if(!empty($userAgentId)){
                    $query=$query->whereIn('user_id',$userAgentId);
                }else{
                    $query=$query->where('user_id',$userId);
                }
                $bkgeData=(array)$query->first();
                global $app;
                $bkge=new \Logic\User\Bkge($app->getContainer());
                $proportion_config=$bkge->handleAgentProportionConfig();
                foreach($proportion_config as $key=>$value){
                    $loseearn_amount = bcmul($bkgeData[$key.'_lose'],1,2) ?? 0;
                    foreach($value as $val){
                        if($loseearn_amount > $val[0] && $loseearn_amount <= $val[1]){
                            $proportion_list[$key] = $val[2];
                            break;
                        }elseif($loseearn_amount > $val[1]){
                            //主要是为了超过最大值的时候 按最后一个区间算
                            $proportion_list[$key] = $val[2];
                        }
                    }

                }
            }
        }else{
            if(!empty($userAgent->profit_loss_value) && $userAgent->profit_loss_value != '{}'){
                $this->logger->error('用户id:'.$userId.'已经设置过盈亏占比');
                return false;
            }
            //下级固定占比打开用占比
            $sub_proportion_switch=$sub_config['sub_proportion_switch'];
            $parentAgent=DB::table('user_agent')->where('user_id',$userAgent->uid_agent)->first();
            $profit_loss_value=json_decode($parentAgent->profit_loss_value,true);
            if(empty($parentAgent->profit_loss_value)){
                $this->logger->error('用户id:'.$userAgent->uid_agent.'未设置盈亏占成');
                return false;
            }

            if(isset($sub_proportion_switch) && $sub_proportion_switch){
                if(!isset($sub_config['sub_fixed_proportion'])){
                    $this->logger->error('下级固定占比未设置');
                    return false;
                }
                $sub_fixed_proportion=json_decode($sub_config['sub_fixed_proportion'],true);
                foreach($sub_fixed_proportion as $key=>$value){
                    $proportion_list[$key]=$value;
                }
            }else{
                //下级代理取上级代理的设置值
                if(!isset($sub_config['sub_default_proportion'])){
                    $this->logger->error('下级代理默认百分比未设置');
                    return false;
                }
                $sub_proportion=json_decode($sub_config['sub_default_proportion'],true);
                foreach($sub_proportion as $k=>$val){
                    if(strpos($val,',')){
                        $val=explode(',',$val)[0];
                    }
                    if(isset($profit_loss_value[$k]) && $profit_loss_value[$k] > 0){
                        //上级占成*百分比
                        $value=bcdiv($val,100,2);
                        $proportion_list[$k] = bcmul($profit_loss_value[$k],$value,2);
                    }
                }
            }
            //占比对比上级是否倒挂
            foreach($proportion_list as $key1=>&$value1){
                if($value1 !=0 && ( bcsub($profit_loss_value[$key1],$value1,2) < 1)){
                    $loss=bcsub($profit_loss_value[$key1],1,2);
                    $loss = $loss > 0 ? $loss : 0;
                    $value1=$loss;
                }
            }

        }
        return json_encode($proportion_list);
    }

    public function rangProfit($userId){
        $userProfitLoss=DB::table('user_agent')->where('user_id',$userId)->value('profit_loss_value');
        $agentValue=json_decode($userProfitLoss,true);
        $sub_config = SystemConfig::getModuleSystemConfig('profit_loss');
        $sub_default_proportion=json_decode($sub_config['sub_default_proportion'],true);
        $profitValue=[];
        foreach($agentValue as $key=>$value){
            if(isset($sub_default_proportion[$key])){
                if( strpos($sub_default_proportion[$key],',')){
                    $sub_range=explode(',',$sub_default_proportion[$key]);
                    $min=$sub_range[0];
                    $max=$sub_range[1];
                }else{
                    $min =0;
                    $max =$sub_default_proportion[$key];
                }
                $paramMinValue=bcmul($value,bcdiv($min,100,4),2);
                $paramMaxValue=bcmul($value,bcdiv($max,100,4),2);
                $profitValue[$key]=[
                    'min'=>$paramMinValue,
                    'max'=>$paramMaxValue
                ];
            }

        }
        return $profitValue;
    }

    public function compareProfit($agentValue,$profitValue){
        //获取下级默认占比区间
        $sub_config = SystemConfig::getModuleSystemConfig('profit_loss');
        $sub_default_proportion=json_decode($sub_config['sub_default_proportion'],true);
        foreach($agentValue as $key=>$value){
            if(isset($profitValue[$key])){
                if(strpos($sub_default_proportion[$key],',')){
                    $sub_range=explode(',',$sub_default_proportion[$key]);
                    $min=$sub_range[0];
                    $max=$sub_range[1];
                }else{
                    $min =0;
                    $max =$sub_default_proportion[$key];
                }
                $paramMinValue=bcmul($value,bcdiv($min,100,4),2);
                $paramMaxValue=bcmul($value,bcdiv($max,100,4),2);
                if($profitValue[$key] < $paramMinValue ){
                    return $this->lang->set(10035,[$this->lang->text($key),$min.'%']);
                }
                if($profitValue[$key] > $paramMaxValue ){
                    return $this->lang->set(10036,[$this->lang->text($key),$max.'%']);
                }
            }
        }
        return true;
    }

    public function profitLossSwitch($param){
        if(empty($param)){
            return false;
        }
        $redisKey=CacheKey::$perfix['profit_loss_switch'];
        $lock_key=$this->redis->setnx($redisKey,1);
        $this->redis->expire($redisKey,7200);
        if(!$lock_key) {
            $this->logger->error('重复开启'.$param['type']);
            return false;
        }

        $sub_config = SystemConfig::getModuleSystemConfig('profit_loss');

        switch($param['type']){
            //1级默认占比
            case 'default_proportion_switch':
                $default_proportion_switch=$sub_config['default_proportion_switch'];
                if(isset($default_proportion_switch) && $default_proportion_switch){
                    $default_proportion=$sub_config['default_proportion'];
                    if(isset($default_proportion) && !empty($default_proportion)){
                        $this->defaultAgent(0,$default_proportion);
                    }
                }
                break;
                //1级固定占比
            case 'fixed_proportion_switch':
                $fixed_proportion_switch=$sub_config['fixed_proportion_switch'];
                if(isset($fixed_proportion_switch) && $fixed_proportion_switch){
                    $fixed_proportion=$sub_config['fixed_proportion'];
                    if(isset($fixed_proportion) && !empty($fixed_proportion)){
                        $this->fixedAgent(0,$fixed_proportion);
                    }
                }
                break;
                //下级固定比
            case 'sub_proportion_switch':
                $sub_proportion_switch=$sub_config['sub_proportion_switch'];
                if(isset($sub_proportion_switch) && $sub_proportion_switch){
                    $sub_fixed_proportion=$sub_config['sub_fixed_proportion'];
                    if(isset($sub_fixed_proportion) && !empty($sub_fixed_proportion)){
                        $this->subFixedAgent(0,$sub_fixed_proportion);
                    }

                }
                break;
            default:
                return false;
        }
        $this->redis->del($redisKey);
    }

    //一级默认开关
    public function defaultAgent($user_id,$default_proportion){
        $agent=DB::table('user_agent')
                 ->where('uid_agent',$user_id)
                 ->get(['user_id','uid_agent','profit_loss_value'])->toArray();
        if(!empty($agent)){
            foreach($agent as $item){
                //一级直接更新占比
                if($item->uid_agent == 0 && !empty($default_proportion)){
                    $this->logger->info('用户id'.$item->user_id.'一级代理修改默认占比:'.$default_proportion);
                    DB::table('user_agent')->where('user_id',$item->user_id)->update(['profit_loss_value'=>$default_proportion]);
                }
                //下级比对一级占成,超过上级则降低下级-1
                $this->supAgent($item->user_id,$default_proportion);
            }
        }
    }

    public function fixedAgent($user_id,$fixedProportion){
        $agent=DB::table('user_agent')
                 ->where('uid_agent',$user_id)
                 ->get(['user_id','uid_agent','profit_loss_value'])->toArray();
        if(!empty($agent)){
            foreach($agent as $item){
                //一级直接更新占比
                if($item->uid_agent == 0 && !empty($fixedProportion)){
                    $this->logger->info('用户id'.$item->user_id.'一级代理修改固定占比:'.$fixedProportion);
                    DB::table('user_agent')->where('user_id',$item->user_id)->update(['profit_loss_value'=>$fixedProportion]);
                }
                //下级比对一级占成,超过上级则降低下级-1
                $this->supAgent($item->user_id,$fixedProportion);
            }
        }
    }

    //下级固定开关
    public function subFixedAgent($user_id,$proportion){
        $agent=DB::table('user_agent')
                 ->where('uid_agent',$user_id)
                 ->get(['user_id','uid_agent','profit_loss_value'])->toArray();
        if(!empty($agent)){
            foreach($agent as $item){
                DB::table('user_agent')->where('uid_agent',$item->user_id)->update(['profit_loss_value'=>$proportion]);
                $this->supAgent($item->user_id,$item->profit_loss_value);
            }
        }
    }


    public function supAgent($user_id,$proportion){
        $proportion_list = [];
        $game_list = GameMenu::getGameTypeList();
        foreach($game_list as $v){
            if(in_array($v,['CP','HOT'])){
                continue;
            }
            $proportion_list[$v] = 0;
        }
        $update=false;
        $downAgent=DB::table('user_agent')
                     ->where('uid_agent',$user_id)
                     ->get(['user_id','uid_agent','profit_loss_value'])
                     ->toArray();
        if(!empty($downAgent)){
            $upAgentProfit=json_decode($proportion,true);
            foreach($downAgent as $val){
                $downAgentProfit=json_decode($val->profit_loss_value,true);
                foreach($downAgentProfit as $key=>$value){
                    if($value != 0 && ( bcsub($upAgentProfit[$key],$value,2) < 1)){
                        $loss=bcsub($upAgentProfit[$key],1,2);
                        $loss = $loss > 0 ? $loss : 0;
                        $proportion_list[$key] = $loss;
                        $update=true;
                    }else{
                        $proportion_list[$key]=$value;
                    }
                }
                $this->logger->info('上级用户id'.$val->uid_agent.",占比:".$proportion);
                $this->logger->info('下级用户id'.$val->user_id.'修改状态'.var_export($update,true).",占比:".json_encode($proportion_list));
                if(!empty($proportion_list) && $update){
                    $this->logger->info('用户id'.$val->user_id.",修改占比:".json_encode($proportion_list));
                    DB::table('user_agent')->where('user_id',$val->user_id)->update(['profit_loss_value'=>json_encode($proportion_list)]);
                    $update =false;
                }
                unset($proportion_list);
                $proportion=DB::table('user_agent')->where('user_id',$val->user_id)->value('profit_loss_value');
                $this->supAgent($val->user_id,$proportion);
            }
        }
    }

    public function batchAgent($agent_list){
        if(empty($agent_list)){
            return false;
        }
        try {
            //合并渠道
            foreach($agent_list as $key=>$value){
                \DB::beginTransaction();

                $chanel=DB::table('channel_management')->where('number',$key)->first();

                //合并后代理账号的直属下级转成1级代理并根据历史盈亏获得盈亏百分比
                foreach($value as $item){
                    $user=(array)DB::table('user')->where('name',$item)->first();
                    $userAgent=DB::table('user_agent')->where('uid_agent',$user['id'])->get(['user_id'])->toArray();
                    if($chanel){
                        DB::table('user')->where('id',$user['id'])->update(['channel_id'=>$key]);
                    }

                    if(!empty($user) && !empty($userAgent)){
                        $userAgentId=array_column($userAgent,'user_id');
                        if(!empty($userAgentId)){
                            DB::table('user')->whereIn('id',$userAgentId)->update(['channel_id'=>$key]);
                        }
                        foreach($userAgent as $agent){
                            $agent=(array)$agent;
                            $this->changeAgent($agent['user_id']);

                            $profit=$this->getProfit($agent['user_id']);
                            if($profit){
                                $this->logger->error('用户id:'.$agent['user_id'].'迁移1级代理，计算占比:'.$profit);
                                DB::table('user_agent')->where('user_id',$agent['user_id'])->update(['profit_loss_value'=>$profit]);
                            }
                            $subAgent=DB::table('user_agent')->where('uid_agent',$agent['user_id'])->pluck('user_id')->toArray();

                            if(empty($subAgent)){
                                $this->logger->error('用户id:'.$agent['user_id'].'无子属下级');
                                //无下级用户关闭代理
                                $userModel = \Model\Admin\User::find($agent['user_id']);
                                $userModel->agent_switch = 0;
                                $userModel->save();
                            }else{
                                //有下级自动获得上级自身百分比占比
                                DB::table('user')->whereIn('id',$subAgent)->update(['channel_id'=>$key]);

                                foreach($subAgent as $v){
                                    $profit=$this->getProfit($v);
                                    $this->logger->error('用户id:'.$agent['user_id'].'下级用户id:'.$v.'计算占比:'.$profit);
                                    if($profit){
                                        DB::table('user_agent')->where('user_id',$v)->update(['profit_loss_value'=>$profit]);
                                    }
                                }
                            }
                        }

                    }
                }
                DB::commit();
            }
        }catch(\Exception $exception){
            $this->logger->error('error:'.$exception->getMessage());
        }

    }

    //转移代理
    public function changeAgent($user_id){
        $user = (array)\DB::table('user_agent')->where('user_id',$user_id)->first(['id','user_id','uid_agent','uid_agent_name','uid_agents','level','inferisors_all']);
        if(empty($user)){
            return false;
        }

        $oldBeforeIds = explode(',',$user['uid_agents']);
        $new_level_count = 0;
        $old_level_count = count($oldBeforeIds);
        $levelDiff = $old_level_count>=$new_level_count ?  ($new_level_count - $old_level_count) : ($new_level_count);
        $currSubAgentIds = $this->getAllSubAgentIds($user_id);


        //删除自己与上级的关系
        if($oldBeforeIds){
            \DB::table('child_agent')->where('cid', $user_id)->delete();
            if ($currSubAgentIds) {   //所有下级有需要删掉与当前移到用户的上级的关系
                DB::table('child_agent')->whereIn('pid', $oldBeforeIds)->whereIn('cid', $currSubAgentIds)->delete();
            }
        }

        //更新旧上级的下级数
        if($oldBeforeIds) {
            DB::table('user_agent')->whereIn('user_id', $oldBeforeIds)->update([
                'inferisors_all' => \DB::raw("inferisors_all - {$user['inferisors_all']}")
            ]);
        }


        //更新下级等级及代理关系
        DB::table('user_agent')->whereIn('user_id', $currSubAgentIds)->update([
            'level' => \DB::raw("abs(level + {$levelDiff})"),
            'uid_agents' => \DB::raw("replace(uid_agents,'{$user['uid_agents']}','{$user_id}')"),
        ]);

        //修改当前等级
        DB::table('user_agent')->where('user_id',$user_id)->update([
            'uid_agent' => 0,
            'uid_agent_name' => null,
            'uid_agents' => $user_id,
            'level' => \DB::raw("abs(level + {$levelDiff})"),
        ]);
        $this->logger->error('用户id:'.$user_id.'迁移代理成功');
    }

    /**
     * 获取所有子孙级用户ID
     * @param $user_id
     * @return array
     */
    public function getAllSubAgentIds($user_id){
        $userIds = [];
        if(is_numeric($user_id)){
            $user_id = (array)$user_id;
        }
        $userIds = (array)\DB::table('user_agent')->whereIn('uid_agent', $user_id)->pluck('user_id')->toArray();
        if($userIds){
            $result2 = $this->getAllSubAgentIds($userIds);
            $userIds = array_merge($userIds, $result2);
        }
        return $userIds;
    }
}