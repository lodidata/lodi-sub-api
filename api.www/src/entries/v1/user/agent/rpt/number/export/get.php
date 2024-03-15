<?php

use Utils\Www\Action;

return new class() extends Action
{
    const TOKEN = true;
    const TITLE = "会员后台导出";
    const TAGS = "会员后台导出";
    const SCHEMAS = [];

    protected $title=[
        'username'=>'会员账号','id'=>'用户ID','truename'=>'真实姓名','agent'=>'上级代理',
        'profit_status'=>'盈亏占成','balance'=>'账户余额','level'=>'会员等级','state'=>'账号状态',
        'mobile'=>'手机号码','last_login'=>'最近登录时间','last_ip'=>'最近登录IP','created'=>'注册时间',
        'ip'=>'注册IP','origin'=>'注册渠道','forbidden_des'=>'备注'
    ];

    public function run()
    {
        $verify = $this->auth->verfiyToken();

        if(!$verify->allowNext()) {
            return $verify;
        }

        $uid        = $this->auth->getUserId();
        $params = $this->request->getParams();

        $subQuery = DB::table('user_agent as ua')
            ->leftJoin('user as ur', 'ua.user_id', '=', 'ur.id')
            ->leftJoin('profile as p', 'p.user_id', '=', 'ua.user_id')
            ->leftJoin('user_level as le', 'ur.ranting', '=', 'le.id')
            ->leftJoin('funds as f', 'ur.wallet_id', '=', 'f.id')
            ->where('ua.uid_agent', $uid);

        if(isset($params['profit_status']) && !empty($params['profit_status'])){
            if($params['profit_status'] ==1){
                $subQuery = $subQuery->whereRaw('ua.profit_loss_value is not null');
            }
            if($params['profit_status'] == 2){
                $subQuery = $subQuery->whereRaw('ua.profit_loss_value is null');
            }
        }

        $subQuery = isset($params['name']) && !empty($params['name']) ? $subQuery->where('ur.name', 'like', '%'.$params['name'].'%') : $subQuery;
        $subQuery = isset($params['mobile']) && !empty($params['mobile']) ? $subQuery->where('ur.mobile', Utils::RSAEncrypt($params['mobile'])) : $subQuery;
        $subQuery = isset($params['state']) && is_numeric($params['state']) ? $subQuery->where('ur.state', $params['state']) : $subQuery;
        $subQuery = isset($params['register_from']) && !empty($params['register_from']) ? $subQuery->where('ur.created', '>=', $params['register_from']) : $subQuery;
        $subQuery = isset($params['register_to']) && !empty($params['register_to']) ? $subQuery->where('ur.created', '<=', $params['register_to'] . ' 23:59:59') : $subQuery;

        $res = $subQuery->selectRaw('
            ur.id, 
            ur.name AS username, 
            p.name AS truename, 
            ua.uid_agent_name AS agent, 
            ua.profit_loss_value AS profit_status,
            f.balance,
            le.name AS level,
            ur.state, 
            p.mobile, 
            ur.last_login, 
            inet6_ntoa(ur.login_ip) AS last_ip,
            ur.created, 
            inet6_ntoa(ur.ip) AS ip,
            ur.origin, 
            ur.forbidden_des as forbidden_des
            ')
            ->orderBy('ur.id', 'asc')
            ->get()->toArray();


        foreach ($res as $key => &$v) {
            $v->mobile = \Utils\Utils::RSADecrypt($v->mobile);
            $v->mobile = "=\"{$v->mobile}\"";
            $v->last_login = !empty($v->last_login) ? date('Y-m-d H:i:s', $v->last_login) : $v->created;
            $v->profit_status = !empty($v->profit_loss_value) ? $this->lang->text('profit_status_yes') : $this->lang->text('profit_status_no');
            $v->state = $v->state == 1 ? $this->lang->text('profit_state_yes') : $this->lang->text('profit_state_no');
            $v->balance = bcdiv($v->balance, 100, 2);
            if($v->origin == 1){
                $v->origin = 'PC';
            }elseif($v->origin == 2){
                $v->origin = 'H5';
            }elseif($v->origin == 3){
                $v->origin = 'IOS';
            }elseif($v->origin == 4){
                $v->origin = 'Android';
            }else{
                $v->origin = '';
            }
        }

        \Utils\Utils::exportExcel('payment',$this->title,$res);
    }
};
