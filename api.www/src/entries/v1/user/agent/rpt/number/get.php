<?php

use Utils\Www\Action;
use Utils\Utils;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "会员后台";
    const TAGS = "会员后台";
    const SCHEMAS = [];

    public function run() {
        $verify = $this->auth->verfiyToken();

        if(!$verify->allowNext()) {
            return $verify;
        }

        $uid        = $this->auth->getUserId();
        $params = $this->request->getParams();
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);

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

        $total = $subQuery->count();

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
            ->forPage($page, $page_size)->get()->toArray();

        $attributes['total'] = $total;
        $attributes['number'] = $page;
        $attributes['size'] = $page_size;

        foreach ($res as $key => &$v) {
            $v->mobile = \Utils\Utils::RSADecrypt($v->mobile);
            $v->last_login = !empty($v->last_login) ? date('Y-m-d H:i:s', $v->last_login) : '';
            $v->profit_status = !empty($v->profit_status) ? 1 : 2;
        }

        return $this->lang->set(0,[],$res,$attributes);
    }
};