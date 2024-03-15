<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/5 14:53
 */

use Logic\Admin\BaseController;
use Utils\Utils;
use lib\validate\BaseValidate;

return new class() extends BaseController {
//  const STATE = \API::DRAFT;
    const TITLE = '会员列表';
    const DESCRIPTION = '所有会员';

    const QUERY = [
        'similar'       => 'enum[0,1]() #是否模糊匹配,1 是，0 否',
        'name'          => 'string() #用户名',
        'ip'            => 'string() #最后登录ip',
        'online'        => 'int() #在线状态，1 在线，0 下线',
        'status'        => 'int() #账号状态，1 正常，0 被关闭',
        'agent'         => 'string() #代理',
        'level'         => 'int() #会员等级',
        'balance_from'  => 'int() #余额，起点',
        'balance_to'    => 'int() #余额，终点',
        'register_from' => 'string() #注册时间，起点',
        'register_to'   => 'string() #注册时间，终点',
        'channel'       => 'string() #注册来源,register=网站注册, partner=第三方,reserved =保留',
        'tags'          => 'string() # 标签',
        "page"          => 'int()',
        "page_size"     => "int()",
        "pname"         => "string() #真实姓名",
        "moblie"        => "string() #手机号码",
        "card"          => "string() #银行卡号",
        "weixin"        => "string() #微信号",
        "qq"            => "string() #qq号",
        "email"         => "string() #邮箱",
        'field_id'    => "int() #排序字段 默认id, 1=余额 2=等级 3=登陆时间 4=注册时间",
        'sort_way'    => "string() #排序规则 desc=降序 asc=升序",
    ];

    const PARAMS = [];
    const SCHEMAS = [
        [
            "id"                => "1",
            "name"              => "string #名称，eg:未分层",
            "memo"              => "string #描述，eg:初始默认层级",
            "num"               => "int #会员人数，eg:1",
            'agent_switch'      => 'int #是否开启,1 是，0 否',
            "deposit_min"       => "int #存款区间开始",
            "deposit_max"       => "int #存款区间结束",
            "deposit_times"     => "int #存款次数",
            "deposit_money"     => "int #存款金额",
            "max_deposit_money" => "int #最大存款额",
            "withdraw_times"    => "int #取款次数",
            "withdraw_count"    => "int #提款总额",
            "deposit_stime"     => "datetime #存款时间，开始，eg:0000-00-00 00:00:00",
            "deposit_etime"     => "datetime #存款时间，结束，eg:0000-00-00 00:00:00",
            "register_stime"    => "datetime #加入时间，开始，eg:0000-00-00 00:00:00",
            "register_etime"    => "datetime #加入时间，结束，eg:0000-00-00 00:00:00",
            "comment"           => 'string #备注',
            "created"           => "datetime #eg:2017-08-31 02:56:26",
            "updated"           => "datetime #eg:2017-08-31 03:03:32",
            "t_default"         => "int #是否默认层级",
            'game_account'      => 'string #游戏账号'
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    // 获取指定用户最新的登录记录 user_logs
    protected function getUserLoginLogs($uids,$ctime,$etime)
    {
        $dbHandle = DB::table('user_logs');
        if($ctime) {
            $dbHandle->where('created' , '>', $ctime. ' 00:00:00');
        }
        if($etime) {
            $dbHandle->where('created' , '<', $etime . ' 23:59:59');
        }

        $res = $dbHandle->where('log_type', 1)
                     ->whereIn('user_id', $uids)
                     ->groupBy('user_id')
                     ->selectRaw("max(id) as id, max(created) as last_login, user_id")
                     ->get()->map(function ($value){
                            return (array)$value;
                     })->toArray();

        return array_column($res, NULL, "user_id");
    } 

    public function run() {

        (new BaseValidate([
            ['online', 'in:0,1'],
            ['state', 'in:0,1'],
            ['similar', 'in:0,1'],
            ['inferiors', 'in:0,1'],
            ['register_from', 'dateFormat:Y-m-d'],
            ['register_to', 'dateFormat:Y-m-d'],
            ['email', 'email'],
            ['mobile', 'checkValueByRegex:mobile'],
            ['ip', 'ip'],
            ['balance_from', '<:10000000000'],
            ['balance_to', '<:10000000000'],
            ['last_bet_time', 'dateFormat:Y-m-d'],
        ],
            [],
            ['mobile' => '手机号'],
            ['balance_from' => '金额'],
            ['balance_to' => '金额']
        ))->paramsCheck('', $this->request, $this->response);

        $params            = $this->request->getParams();
        $params['similar'] = 1;

        $rs = $this->getUserList($params, $params['page'], $params['page_size']);

        return $rs;
    }

    protected function getUserList($params, $page = 1, $page_size = 15) {
        $subQuery = DB::connection('slave')->table('user as u1');
        $subQuery->where('u1.tags', '<>', 7);

        $need_user_agent = 0; //是否需要user_agent表 (1：需要，0：不需要)
        $need_funds      = 1;
        $need_profile    = 0;

        !empty($params['id']) && $subQuery->where('u1.id', $params['id']);

        //游戏账号
        if(!empty($params['game_account'])){
            $user_id = \DB::connection('slave')->table('game_user_account')->where('user_account', $params['game_account'])->value('user_id');
            $subQuery->where('u1.id',$user_id);
        }

        if (!empty($params['name'])) {
            $names    = explode(';',$params['name']);
            $user_ids = [];

            foreach ($names as $name){
                $user_id = DB::connection('slave')->table('user')->where('name',$name)->value('id');
                array_push($user_ids,$user_id);
            }
            array_filter($user_ids);
            if(count($user_ids)){
                $subQuery->whereIn('u1.id',$user_ids);
            }

        }
        isset($params['agent_switch']) && $subQuery->where('u1.agent_switch', $params['agent_switch']);
        //根据注册IP查询
        !empty($params['reg_ip']) && $subQuery->whereRaw("u1.ip = inet6_aton(?)",[$params['reg_ip']]);
        !empty($params['ip']) && $subQuery->whereRaw("u1.login_ip = inet6_aton(?)",[$params['ip']]);
        !empty($params['online']) && $subQuery->where('u1.online', $params['online']);

        if(isset($params['state']) && is_numeric($params['state'])){
            $subQuery->where('u1.state', $params['state']);
        }

        if (!empty($params['agent'])) {
            $params['agent_id'] = \Model\User::getAgentIdByName($params['agent'], $params['similar'] ?? false);

            if (!$params['agent_id']) {
                return [];
            }
            $need_user_agent = 1;
            $subQuery->whereIN('ua.uid_agent', $params['agent_id']);
        }

        if(!empty($params['proportion_status'])){
            $need_user_agent = 1;
            if($params['proportion_status'] ==1){
                $subQuery->whereRaw('proportion_value is not null');
            }
            if($params['proportion_status'] == 2){
                $subQuery->whereRaw('proportion_value is null');
            }
        }

        if(!empty($params['profit_loss_status'])){
            $need_user_agent = 1;
            if($params['profit_loss_status'] ==1){
                $subQuery->whereRaw('profit_loss_value is not null');
            }
            if($params['profit_loss_status'] == 2){
                $subQuery->whereRaw('profit_loss_value is null');
            }
        }

        if(!empty($params['card'])){
            $subQuery->leftJoin('bank_user as ub', 'u1.id', '=', 'ub.user_id')->groupBy('ub.card','u1.id');
            $subQuery->where('ub.card',Utils::RSAEncrypt($params['card']));
        }

//        !empty($params['level']) && $subQuery->where('u1.ranting', $params['level']);
        if (isset($params['level']) && !empty($params['level'])){
            $level = explode(',',$params['level']);
            $subQuery->whereIn('u1.ranting', $level);
        }
        !empty($params['register_from']) && $subQuery->where('u1.created', '>=', $params['register_from']);

        !empty($params['register_to']) && $subQuery->where('u1.created', '<=', $params['register_to'] . ' 23:59:59');
        if (!empty($params['last_login'])) {
            $params['last_login']      =  strtotime($params['last_login']);
            $params['last_login_end']  =  strtotime($params['last_login_end'] . ' 23:59:59');
            $subQuery->whereBetween('last_login', [$params['last_login'], $params['last_login_end']]);
        }
        !empty($params['channel']) && $subQuery->where('u1.channel', $params['channel']);

        !empty($params['tags']) && $subQuery->where('u1.tags', $params['tags']);

        if(!empty($params['pname'])){
            $need_profile = 1;
            $subQuery->where('p.name', $params['pname']);
        }

        !empty($params['mobile']) && $subQuery->where('u1.mobile', Utils::RSAEncrypt($params['mobile']));

        if(!empty($params['email'])){
            $need_profile = 1;
            $subQuery->where('p.email', Utils::RSAEncrypt($params['email']));
        }

        if(!empty($params['truename'])){
            $need_profile = 1;
            $subQuery->where('p.name', $params['truename']);
        }
        !empty($params['channel_id']) && $subQuery->where('u1.channel_id', $params['channel_id']);
        !empty($params['origin']) && $subQuery->where('u1.origin', $params['origin']);

        $need_user_agent && $subQuery->leftJoin('user_agent as ua', 'u1.id', '=', 'ua.user_id');
        $need_profile && $subQuery->leftJoin('profile as p', 'p.user_id', '=', 'u1.id');

        $sub_query_id  = clone $subQuery;
        $total         = $subQuery->count();

        if (!$total) {
            return [];
        }

        //1余额 2等级 3登陆时间 4注册时间
        $field_id = $this->request->getParam('field_id', '');
        $sort_way = $this->request->getParam('sort_way', 'desc');
        if($field_id == 1) $need_funds = 1;
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'desc';
        $str = '';

        switch ($field_id)
        {
            case 1:
                $field_id = 'f.balance';
                break;

            case 2:
                $field_id = 'u1.ranting';
                break;

            case 3:
                $field_id = 'u1.last_login';
                break;

            case 4:
                $field_id = 'u1.created';
                break;

            default:
                $field_id = 'u1.id';
                break;
        }

        $sub_query_id->select('u1.id')->orderBy($field_id, $sort_way)
            ->forPage($page, $page_size);
        $need_funds && $sub_query_id->leftJoin('funds as f', 'u1.wallet_id', '=', 'f.id');

        $query_table = DB::connection('slave')->table('user as u1');
        $query_table->joinSub($sub_query_id,'u2','u2.id','=','u1.id','inner')
            ->leftJoin('profile as p', 'p.user_id', '=', 'u1.id')
            ->leftJoin('user_level as le', 'u1.ranting', '=', 'le.id')
            ->leftJoin('funds as f', 'u1.wallet_id', '=', 'f.id')
            ->leftJoin('label as l', 'u1.tags', '=', 'l.id')
            ->leftJoin('user_agent as ua', 'u1.id', '=', 'ua.user_id');

        $res = $query_table->selectRaw('
            u1.id, 
            u1.agent_switch, 
            u1.ranting, 
            u1.tags, 
            u1.wallet_id, 
            u1.name AS username, 
            u1.forbidden_des as forbidden_des,
            u1.created, 
            inet6_ntoa(u1.ip) AS ip, 
            u1.last_login, 
            inet6_ntoa(u1.login_ip) AS last_ip,  
            u1.channel, 
            u1.online, 
            u1.state, 
            u1.channel_id,
            p.name AS truename, 
            p.mobile, 
            ua.uid_agent_name AS agent, 
            u1.origin,
            le.name AS level,
            l.title AS tags,
            f.balance,
            ua.proportion_value,
            ua.proportion_type,
            ua.profit_loss_value
            ')
            ->orderBy($field_id, $sort_way)->get()->toArray();

        $attributes['total']  = $total;
        $attributes['number'] = $page;
        $attributes['size']   = $page_size;

        $user_control         = \DB::connection('slave')->table('admin_user_role')->where('id',$this->playLoad['rid'])->value('member_control');
        $user_control         = json_decode($user_control,true);

        $now                  = time();

        // $logs = $this->getUserLoginLogs(array_column($res, 'id'), $params['register_from'] ?? '', $params['register_to'] ?? '');

        foreach ($res as $key => &$v) {
            $v->mobile = \Utils\Utils::RSADecrypt($v->mobile);
            if (!$user_control['address_book'] && !empty($v->mobile)) {
                $v->mobile = '******';
            }
            if (!$user_control['true_name'] && !empty($v->truename)) {
                $v->truename = '******';
            }
            $v->online             = $now - 300 < $v->last_login ? 1 : 0;
            $v->last_login = !empty($v->last_login) ? date('Y-m-d H:i:s', $v->last_login) : '';
        }

        return $this->lang->set(0, [], $res, $attributes);
    }


};
