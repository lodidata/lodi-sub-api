<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '注册IP排行榜详情';
    const DESCRIPTION = '注册IP排行榜详情';

    const PARAMS = [
        'date_start' => 'date #开始时间 2022-08-20',
        'date_end' => 'date #结束时间 2022-08-21',
        'ip' => 'string #注册ip',
    ];
    const SCHEMAS = [
        [
            'username' => 'string #会员账号',
            'agent' => 'int #上级代理',
            'level' => 'string #会员等级',
            'balance' => 'float #会员余额',
            'state' => 'string #账号状态'
        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();
        if(empty($params['ip'])){
            return $this->lang->set(10010);
        }
        $params['date_start'] = isset($params['date_start']) ? $params['date_start'] : '';
        $params['date_end'] = isset($params['date_end']) ? $params['date_end'] : '';

        $query = DB::connection('slave')->table('user as ur')
            ->leftJoin('user_agent as ua', 'ur.id', '=', 'ua.user_id')
            ->leftJoin('user_level as le', 'ur.ranting', '=', 'le.id')
            ->leftJoin('funds as f', 'ur.wallet_id', '=', 'f.id')
            ->where('ur.ip', DB::raw("INET6_ATON('".$params['ip']."')"));
        if(!empty($params['date_start'])){
            $startTime=date('Y-m-d 00:00:00',strtotime($params['date_start']));
            $query = $query->where('ur.created','>=',$startTime);
        }
        if(!empty($params['date_end'])){
            $endTime=date('Y-m-d 23:59:59',strtotime($params['date_end']));
            $query = $query->where('ur.created','<=',$endTime);
        }

        $attributes['total'] = $query->count();

        $data = $query->selectRaw('
                ur.id as user_id,
                ur.name AS username,
                ua.uid_agent_name AS agent,
                le.name AS level,
                f.balance,
                ur.state
            ')
            ->forPage($params['page'], $params['page_size'])
            ->get()->toArray();

        $attributes['size'] = $params['page_size'];
        $attributes['number'] = $params['page'];

        return $this->lang->set(0, [], $data, $attributes);
    }

};