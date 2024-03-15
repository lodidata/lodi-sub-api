<?php

use Logic\Admin\BaseController;
use Utils\Utils;

return new class() extends BaseController {
    const TITLE = '导出会员信息';
    const DESCRIPTION = '导出会员信息';
    protected $title = [
        'username'       => '会员账号',
        'truename'       => '真实姓名',
        'agent'          => '上级代理',
        'proportion_value'=> '占成',
        'balance'        => '账户余额',
        'level'          => '会员等级',
        'state'          => '账号状态',
        'mobile'         => '手机号码',
        'last_login'     => '最近登录时间',
        'last_ip'        => 'ip',
        'created'        => '注册时间',
        'last_ip'        => '最近登录ip',
        'origin'         => '注册渠道',
        'channel_id'     => '渠道号',
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run() {
        $params = $this->request->getParams();
        $rs = $this->getUserList($params);
        return $rs;
    }

    protected function getUserList($params) {
        $subQuery = DB::table('user as u1')
            ->leftJoin('profile as p', 'p.user_id', '=', 'u1.id')
            ->leftJoin('user_agent as ua', 'u1.id', '=', 'ua.user_id')
            ->where('u1.tags', '<>', 7);

        $subQuery = isset($params['id']) && !empty($params['id']) ? $subQuery->where('u1.id', $params['id']) : $subQuery;
        if (isset($params['name']) && !empty($params['name'])) {
            $names = explode(';',$params['name']);
            $user_ids = [];
            foreach ($names as $name){
                $user_id = DB::table('user')->where('name',$name)->value('id');
                array_push($user_ids,$user_id);
            }
            array_filter($user_ids);
            if(count($user_ids)){
                $subQuery->whereIn('u1.id',$user_ids);
            }

        }
        $subQuery = isset($params['ip']) && !empty($params['ip']) ? $subQuery->whereRaw("u1.login_ip = inet6_aton('" . $params['ip'] . "')") : $subQuery;
        $subQuery = isset($params['online']) && !empty($params['online']) ? $subQuery->where('u1.online', $params['online']) : $subQuery;
        $subQuery = isset($params['state']) && is_numeric($params['state']) ? $subQuery->where('u1.state', $params['state']) : $subQuery;
        if (isset($params['inferiors']) && is_numeric($params['inferiors'])) {
            if ($params['inferiors'] == 0)
                $subQuery = $subQuery->where('ua.inferisors_all', '=', 0);
            if ($params['inferiors'] == 1)
                $subQuery = $subQuery->where('ua.inferisors_all', '>', 0);
        }
        if (isset($params['agent']) && !empty($params['agent'])) {
            $params['agent_id'] = \Model\User::getAgentIdByName($params['agent'], $params['similar'] ?? false);

            if (!$params['agent_id']) {
                return [];
            }

            $subQuery = $subQuery->whereIN('ua.uid_agent', $params['agent_id']);
        }
        if(isset($params['card']) && !empty($params['card'])){
            $subQuery= $subQuery->leftJoin('bank_user as ub', 'u1.id', '=', 'ub.user_id')->groupBy('ub.card');
            $subQuery= $subQuery->where('ub.card',Utils::RSAEncrypt($params['card']));
        }
        if (isset($params['level']) && !empty($params['level'])){
            $subQuery->whereIn('u1.ranting', $params['level']);
        }
        $subQuery = isset($params['register_from']) && !empty($params['register_from']) ? $subQuery->where('u1.created', '>=', $params['register_from']) : $subQuery;
        $subQuery = isset($params['register_to']) && !empty($params['register_to']) ? $subQuery->where('u1.created', '<=', $params['register_to'] . ' 23:59:59') : $subQuery;
        $subQuery = isset($params['channel']) && !empty($params['channel']) ? $subQuery->where('u1.channel', $params['channel']) : $subQuery;
        $subQuery = isset($params['tags']) && !empty($params['tags']) ? $subQuery->where('u1.tags', $params['tags']) : $subQuery;
        $subQuery = isset($params['pname']) && !empty($params['pname']) ? $subQuery->where('p.name', $params['pname']) : $subQuery;
        $subQuery = isset($params['mobile']) && !empty($params['mobile']) ? $subQuery->where('u1.mobile', Utils::RSAEncrypt($params['mobile'])) : $subQuery;
        $subQuery = isset($params['qq']) && !empty($params['qq']) ? $subQuery->where('p.qq', Utils::RSAEncrypt($params['qq'])) : $subQuery;
        $subQuery = isset($params['email']) && !empty($params['email']) ? $subQuery->where('p.email', Utils::RSAEncrypt($params['email'])) : $subQuery;
        $subQuery = isset($params['weixin']) && !empty($params['weixin']) ? $subQuery->where('p.weixin', Utils::RSAEncrypt($params['weixin'])) : $subQuery;
        $subQuery = isset($params['truename']) && !empty($params['truename']) ? $subQuery->where('p.name', $params['truename']) : $subQuery;
        $subQuery = isset($params['uid']) && !empty($params['uid']) ? $subQuery->whereIn('u1.id', explode(',',$params['uid'])) : $subQuery;
        $subQuery = isset($params['channel_id']) && !empty($params['channel_id']) ? $subQuery->where('u1.channel_id', $params['channel_id']) : $subQuery;
        $subQuery = isset($params['origin']) && !empty($params['origin']) ? $subQuery->where('u1.origin', $params['origin']) : $subQuery;
        if (!empty($params['last_login'])) {
            $params['last_login']      =  strtotime($params['last_login']);
            $params['last_login_end']  =  strtotime($params['last_login_end'] . ' 23:59:59');
            $subQuery->whereBetween('last_login', [$params['last_login'], $params['last_login_end']]);
        }
        $subTable = DB::raw("({$subQuery->selectRaw('u1.channel_id,u1.id, u1.agent_switch, u1.ranting, u1.tags, u1.wallet_id, u1.name, u1.created, u1.ip, u1.last_login, u1.login_ip, u1.channel, u1.online, u1.state, p.`name` AS truename, p.mobile, ua.uid_agent_name, u1.origin,ua.proportion_value')
	        ->orderByDesc('id')
            ->toSql()}) as u");

        $query = DB::table($subTable)
            ->mergeBindings($subQuery)
            ->leftJoin('user_level as le', 'u.ranting', '=', 'le.id')
            ->leftJoin('funds as f', 'u.wallet_id', '=', 'f.id')
            ->leftJoin('label as l', 'u.tags', '=', 'l.id')
            ->select(DB::raw("u.id,u.agent_switch, u.`name` AS username, u.truename, u.mobile,le.`name` AS `level`, u.created, l.title AS tags, inet6_ntoa(u.ip) AS ip, u.last_login, inet6_ntoa(u.login_ip) AS last_ip, f.balance,
            u.`uid_agent_name` AS agent, u.state,u.channel_id, u.origin,u.proportion_value"));

        //排序规则
        if (isset($params['order_by']) && in_array($params['order_by'], ['balance', 'withdraw', 'deposit'])
            &&
            isset($params['order_rule']) && in_array($params['order_rule'], ['desc', 'asc'])
        ) {
            $query->orderBy($params['order_by'], $params['order_rule']);
        } else {
            $query->orderBy('u.id', 'desc');
        }

        $query->groupBy('u.id');

        $res = $query
            ->get()
            ->toArray();
//        if (!$res) {
//            return [];
//        }

        $user_control = \DB::table('admin_user_role')->where('id',$this->playLoad['rid'])->value('member_control');
        $user_control = json_decode($user_control,true);

        foreach ($res as  &$v) {
            $v->mobile = \Utils\Utils::RSADecrypt($v->mobile);
            if (!$user_control['address_book'] && !empty($v->mobile)) {
                $v->mobile = '******';
            }
            if (!$user_control['true_name'] && !empty($v->truename)) {
                $v->truename = '******';
            }
            $v->mobile = "=\"{$v->mobile}\"";
            $v->last_login = !empty($v->last_login) ? date('Y-m-d H:i:s', $v->last_login) : '未登录过';
            $v->balance = $v->balance /100;
            $v->username = "=\"$v->username\"";
            switch ($v->origin) {
                case '1':
                    $v->origin ='pc';
                    break;
                case '2':
                    $v->origin ='h5';
                    break;
                case '3':
                    $v->origin ='ios';
                    break;
                default:
                    $v->origin ='android';
                    break;
            }

        }
        return $this->exportExcel('会员管理列表',$this->title,json_decode( json_encode($res),true));
    }

    public function exportExcel($file, $title, $data) {
        header('Content-type:application/vnd.ms-excel');
        header('Content-Disposition:attachment;filename=' . $file . '.xls');
        $content = '';
        foreach ($title as $tval) {
            $content .= $tval . "\t";
        }
        $content .= "\n";
        $keys = array_keys($title);
        if ($data) {
            foreach ($data as $ke=> $val) {
                if ($ke > 49999) {
                    break;
                }
                $val = (array)$val;
                foreach ($keys as $k) {
                    $content .= $val[$k] . "\t";
                }
                $content .= "\n";
                echo mb_convert_encoding($content, "UTF-8", "UTF-8");
                $content = '';
            }
        }
        exit;
    }
};