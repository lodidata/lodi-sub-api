<?php

use Logic\Admin\BaseController;
use Logic\User\Agent as agentLgoic;
use lib\validate\BaseValidate;
use Logic\Wallet\Wallet as walletLogic;
use Logic\User\User as userLogic;
return new class() extends BaseController
{
    const TITLE       = '用户详情';
    const DESCRIPTION = '';

    const QUERY       = [
        'id'        => 'int(required) #用户id',
        'type'      => 'enum[stat,base,balance,withdraw,bank](required) #获取细分项，可能值：统计 stat，基本信息 base，账户余额 balance，取款稽核 withdraw，银行信息 bank',
        'page'      => 'int()#当前页数',
        'page_size' => 'int() #一页多少条数'
    ];

    const STATEs      = [
//        \Las\Utils\ErrorCode::INVALID_VALUE => '无效用户id'
    ];
    const PARAMS      = [];
    const SCHEMAS     = [
        ['map # channel: registe=网站注册, partner=第三方,reserved =保留'],
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);

        (new BaseValidate([
            'type'=>'require|in:stat,base,balance,withdraw,bank'
        ]))->paramsCheck('',$this->request,$this->response);
        $params = $this->request->getParams();

        switch ($params['type']) {


            case 'stat':

                $res = DB::table('funds_deal_log')->where('user_id',$id)->whereIn('deal_type',[701,702,703,107,113])->sum('deal_money');
                $rs['rebet_money'] = (float) $res;

                $res = DB::table('funds_deal_log')->where('user_id',$id)->whereIn('deal_type',[105, 114])->sum('deal_money');
                $rs['coupon_money'] = (float) $res;

                $res = DB::table('funds_deposit')->where('user_id',$id)->where('status','paid')->where('money', '>', 0)->count();
                $rs['deposit_times'] = $res;

                $res = DB::table('funds_deposit')->where('user_id',$id)->where('status','paid')->sum('money');
                $rs['deposit_money'] = (float) $res;

                $res = DB::table('funds_deal_log')->where('user_id',$id)->whereIn('deal_type',[201, 204])->sum('deal_money');
                $rs['withdraw_money'] = (float) $res;

                $res = DB::table('funds_deal_log')->where('user_id',$id)->whereIn('deal_type',[201, 204])->count();
                $rs['withdraw_times'] = $res;

                $res = DB::table('lottery_order')->where('user_id',$id)->sum('pay_money');
                $rs['order_money'] = (float) $res;

                $res = DB::table('lottery_order')->where('user_id',$id)->count('*');

                $rs['order_times'] =  $res;

                $res = DB::table('funds_deal_log')->where('user_id',$id)->whereIn('deal_type',[103, 104, 502])->sum('deal_money');
                $rs['send_prize'] = (float) $res;

                $rs['lose_earn'] = (float) ($rs['send_prize'] + $rs['rebet_money'] + $rs['coupon_money'] - $rs['order_money']);

                $sql = "SELECT type,SUM(back_money) AS back_money FROM user_rake_log WHERE state = 1 AND user_id = $id GROUP BY type";
//                1:电子，2:视讯，3:体育，4：彩票
                $rack_types = [1=>'bkge_game',2=>'bkge_live',3=>'bkge_sport',4=>'bkge_lottery'];
                $rack = DB::select($sql);
                $rack = array_map('get_object_vars',$rack);
                $rack_data = ['bkge_game'=>0,'bkge_live'=>0,'bkge_sport'=>0,'bkge_lottery'=>0,'sum'=>0];
                foreach ($rack ?? [] as $value){
                    $rack_data[$rack_types[$value['type']]] = $value['back_money'];
                    $rack_data['sum'] += $value['back_money'];
                }
                $rs['rake_agent'] = $rack_data;
                return $rs;

            case 'base':

                $re = $this->baseInfo($id);
                if (!$re) {
                   return $this->lang->set(10015);
                }
                $re['language_name'] = 'zh-CN';
                $re['last_login']    = $re['last_login'] > 0 ? date("Y-m-d H:i:s", $re['last_login']) : '';
                $re['birth']         = $re['birth'] > 0 ? date("Y-m-d H:i:s", $re['birth']) : '';
                //返佣设制
//                $sql = "SELECT * FROM user_agent WHERE user_id = $id";
                $cur = DB::table('user_agent')->where('user_id',$id)->first();
                if(!$cur){
                    return $this->lang->set(10015);
                }
                $cur = (array)$cur;

                $rake_round = (new agentLgoic($this->ci))->allow([],'',$cur['uid_agent'],$id,true);

                if(!$rake_round->getState()){
                    return $rake_round;
                }

                list($status, $state, $msg, $data, $attributes) = $rake_round->get();
                $re['rake_agent'] = [
                    'bkge_game'=>$cur['bkge_game'] ?? 0,
                    'bkge_live'=>$cur['bkge_live'] ?? 0,
                    'bkge_sport'=>$cur['bkge_sport'] ?? 0,
                    'bkge_lottery'=>$cur['bkge_lottery'] ?? 0,
                    'max_bkge_game'=>$data['bkge_game'] ?? 0,
                    'max_bkge_live'=>$data['bkge_live'] ?? 0,
                    'max_bkge_sport'=>$data['bkge_sport'] ?? 0,
                    'max_bkge_lottery'=>$data['bkge_lottery'] ?? 0,
                    'min_bkge_game'=>$data['min_bkge_game'] ?? 0,
                    'min_bkge_live'=>$data['min_bkge_live'] ?? 0,
                    'min_bkge_sport'=>$data['min_bkge_sport'] ?? 0,
                    'min_bkge_lottery'=>$data['min_bkge_lottery'] ?? 0,
                    ];
                return $re;

//                return roleControlFilter($re, admin()->getPlayload()['rid']);

            case 'balance':

                $user = (new userLogic($this->ci))->getInfo($id);
                if (!$user ) {
                    return $this->lang->set(10015);
                }
                $re = (new walletLogic($this->ci))->getWallet($id);

                $parentWallet = array_shift($re['children']);
//                $total_money = $re['balance'];
                $re = array_merge([
                    'currency_name' => '',
                    'updated'       => '',
                    'children'      => []
                ], $parentWallet,$re ?? []);

                if (isset($re['currency'])) {
                    $currency = DB::table('currency as c')
                        ->select(DB::raw("
                        IF(FIND_IN_SET('enabled', status),1,0) AS status,c.id AS id,c.ctype AS ctype,c.cytype AS cytype,IF(FIND_IN_SET('changed', status),1,0) AS changed
                        "))
                        ->where('id',$re['currency'])
                        ->get()->toArray();

                    if ($currency) {
                        $re['currency_name'] = $currency[0]->ctype ?? '';
                    }
                }

                $re['updated'] = $user['last_login'] ? date("Y-m-d h:i:s", $user['last_login']) : '';
//                $arr           = [];
//                if (count($re['children'])) {
//                    foreach ($re['children'] as &$v) {
//                        //$v['last_updated'] = date("Y-m-d h:i:s", $v['last_updated']);
//                        $total_money += $v['balance'];
//                        array_push($arr, $v);
//                    }
//                    $re['children'] = $arr;
//                }
                $re['total_money'] = $parentWallet['balance'];

                return $re;

            case 'withdraw':

                $dml = new \Logic\Wallet\Dml($this->ci);
                $tmp = $dml->getUserDmlData($id);
                $dmlData = [
                    'factCode' => $tmp->total_bet,
                    'codes'    => $tmp->total_require_bet,
                    'canMoney' => $tmp->free_money,
                    'balance'  => \Model\User::getUserTotalMoney($id)['lottery'] ?? 0 ,
                ];
                return $dmlData;

            case 'bank':

                $rs = $this->cardList($id, 1);

                return $rs;
            default:
                return [];
        }

        return [];
    }

    /**
     * 用户基本信息
     *
     * @param number $id
     * @return boolean|mixed
     */
    public function baseInfo($id = 0) {

        $rs = DB::table('user as u')
            ->leftJoin('profile as p','p.user_id','=','u.id')
            ->leftJoin('user_agent as a','u.id','=','a.user_id')
            ->leftJoin('currency as c','u.currency','=','c.id')
            ->leftJoin('label as l','u.tags','=','l.id')
            ->leftJoin('level as le','u.ranting','=','le.id')
            ->leftJoin('funds as f','u.wallet_id','=','f.id')
            ->select(DB::raw("u.id, u.`name` AS username,if(u.mobile='',0,1) as mobile_binded, p.address,l.title, p.`name` AS truename, u.`password` , f.`password` AS wpassword, le.`name` AS level, l.title AS tags,
                  u.created, inet6_ntoa(u.ip) as ip, u.last_login , f.last_ip, u.channel, a.`uid_agent_name` AS agent, u.email, u.mobile ,p.idcard, p.qq, p.weixin, p.region_id, p.nationality, c.ctype,
                  u.`language`, p.birth, p.gender, p.comment,u.agent_id,u.wallet_id,u.auth_status,u.ranting,u.telphone_code"))
            ->where('u.id',$id)->get()->toArray();

        if(!$rs){
            return [];
        }

        $rs = (array) $rs[0];
        $rs = \Utils\Utils::RSAPatch($rs);
        $rs['user_type'] = $rs['agent_id'] > 0 ? '代理会员' : '直属会员';

//        dd(DB::getQueryLog());exit;
        return $rs;
    }


    /**
     * 读取用户名下银行列表
     *
     * @param int    $userId
     * @param int    $role
     * @param string $state
     * @return array
     */
    public function cardList(int $userId, int $role = null, string $state = null)
    {

        $query = DB::table('bank_user as a')
            ->leftJoin('bank as b','a.bank_id','=','b.id')
            ->select(DB::raw("
            a.id,b.code as code,b.h5_logo,logo,a.inserted as created_time,a.updated as updated_time,a.state,b.shortname,a.address,a.bank_id,b.code as bank_code,a.name as accountname,a.card 
            "))
            ->where('a.user_id',$userId);

        $query = !empty($role) ? $query->where('a.role',$role) : $query;

        $query = !empty($state) ? $query->whereRaw("AND FIND_IN_SET('{$state}',a.state)") : $query;

        $res = $query->orderBy('a.id','desc')->get()->toArray();
        $user_control = \DB::table('admin_user_role')->where('id',$this->playLoad['rid'])->value('member_control');
        $user_control = json_decode($user_control,true);
        $re = \Utils\Utils::RSAPatch($res);
        foreach ($re as &$v) {
            $v['bank_name'] = $this->lang->text($v['bank_code']);
            if (!$user_control['bank_card'] && $v['card']) {
                $l = intval(strlen($v['card']) / 3);
                $v['card'] = str_replace(substr($v['card'], $l, $l), '****', $v['card']);
            }
            if (!$user_control['true_name'] && $v['accountname']) {
                $v['accountname'] = substr($v['accountname'], 0, 3) . '**';
            }
        }
        unset($v);
        return $re;
    }

};
