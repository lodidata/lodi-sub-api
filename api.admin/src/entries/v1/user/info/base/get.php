<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '用户基本资料';
    const DESCRIPTION = 'map # channel: registe=网站注册, partner=第三方,reserved =保留';
    
    const QUERY = [
        'id'        => 'int(required) #用户id',
        'type'      => 'enum[stat,base,balance,withdraw,bank](required) #获取细分项，可能值：统计 stat，基本信息 base，账户余额 balance，取款稽核 withdraw，银行信息 bank',
        'page'      => 'int()#当前页数',
        'page_size' => 'int() #一页多少条数',
    ];
    
    const STATEs = [
//        \Las\Utils\ErrorCode::INVALID_VALUE => '无效用户id'
    ];
    const PARAMS = [];
    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = null) {

        $this->checkID($id);
        $user_info = DB::table('user as u')
            ->leftJoin('profile as p','u.id','=','p.user_id')
            ->selectRaw("id,wallet_id,u.name as username,p.nickname,p.name as truename,state,ranting,tags as tag_id,last_login as last_login_time,inet6_ntoa(login_ip) as last_login_ip,u.mobile,u.email,u.wechat,address,idcard,p.qq,birth,ifnull(FIND_IN_SET('refuse_withdraw',auth_status) > 0,0) AS refuse_withdraw,ifnull(FIND_IN_SET('refuse_sale',auth_status) > 0,0) AS refuse_sale,ifnull(FIND_IN_SET('refuse_rebate',auth_status) > 0,0) AS refuse_rebate,ifnull(FIND_IN_SET('refuse_bkge',auth_status) > 0,0) AS refuse_bkge,ifnull(FIND_IN_SET('maintaining_login',auth_status) > 0,0) AS maintaining_login")
            ->where('u.id',$id)
            ->first();
        if (!$user_info) {
            return $this->lang->set(10015);
        }

        $tags = DB::table('label')->pluck('title','id')->toArray();
        $user_info->tag_name = $tags[$user_info->tag_id] ?? '';
        $mainFundBalnace = DB::table('funds')->where('id',$user_info->wallet_id)->value('balance');
        $childFundsBalance = DB::table('funds_child')->where('pid',$user_info->wallet_id)->sum('balance');
        $user_info->balance = $mainFundBalnace + $childFundsBalance;
        $user_info->agent = DB::table('user_agent')->where('user_id',$id)->value('uid_agent_name');
        $user_info->user_data = DB::table('user_data')->selectRaw('deposit_num,deposit_amount,withdraw_num,withdraw_amount,withdraw_cj_amount,withdraw_cj_num,active_num,active_amount')->where('user_id',$id)->first();
        $user_info->user_data->withdraw_amount = $user_info->user_data->withdraw_amount - $user_info->user_data->withdraw_cj_amount;
        $user_info->user_data->withdraw_num = $user_info->user_data->withdraw_num - $user_info->user_data->withdraw_cj_num;
        $user_info->active_num = DB::table('active_apply')->where('user_id', $id)->where('status', 'pass')->sum('coupon_money');
        $user_info->active_amount = DB::table('active_apply')->where('user_id', $id)->where('status', 'pass')->count();
        $last_login_time = $this->redis->hget('user_online_last_time', $id);
        $user_info->last_login_time = !empty($last_login_time) ? date('Y-m-d H:i:s', $last_login_time) : '未登录过';
        $address_info = \Utils\Client::gerIpRegion($user_info->last_login_ip);
        if($address_info){
            $user_info->last_login = $address_info['regionName'] . ' ' . $address_info['city'];
        }

        $user_info = (array)$user_info;
        $user_info = \Utils\Utils::RSAPatch($user_info);

        $user_control = \DB::table('admin_user_role')->where('id',$this->playLoad['rid'])->value('member_control');
        $user_control = json_decode($user_control,true);
        if (!$user_control['address_book'] && !empty($user_info['mobile'])) {
            $user_info['mobile'] = '******';
        }
        if (!$user_control['true_name'] && !empty($user_info['truename'])) {
            $user_info['truename'] = '******';
        }
        $rptUser=DB::table('rpt_user')->where('user_id',$id)
                                      ->where('count_date',date('Y-m-d',time()))
                                      ->get(['deposit_user_amount','withdrawal_user_amount','deposit_user_cnt','withdrawal_user_cnt'])
                                      ->first();
        
        // 展示会员等级名称
        $user_info['ranting'] = \DB::table('user_level')->where('id', $user_info['ranting'])->value('name');
        
        $user_info['deposit_user_amount']   =!empty($rptUser->deposit_user_amount) ? bcmul($rptUser->deposit_user_amount,100):0;
        $user_info['withdrawal_user_amount']=!empty($rptUser->withdrawal_user_amount) ? bcmul($rptUser->withdrawal_user_amount,100):0;
        $user_info['deposit_user_cnt']      =$rptUser->deposit_user_cnt ??0;
        $user_info['withdrawal_user_cnt']   =$rptUser->withdrawal_user_cnt ??0;
        return $user_info;


    }


};
