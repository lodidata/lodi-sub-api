<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '登录IP排行榜';
    const DESCRIPTION = '登录IP排行榜';
    
    const QUERY = [
    ];

    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $date_start = $this->request->getParam('date_start',date('Y-m-d'));
        $date_end = $this->request->getParam('date_end',date('Y-m-d'));

        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 20);
        $field_id = $this->request->getParam('field_id', 'num');
        $sort_way = $this->request->getParam('sort_way', 'desc');
        if(!in_array($sort_way, ['asc', 'desc'])) $sort_way = 'desc';
        $sort_way = ($sort_way == 'asc') ? "ASC" : "DESC";

        $query = DB::connection('slave')->table('user');
        if(!empty($date_start)){
            $date_start = strtotime($date_start);
            $query = $query->where('last_login','>=',$date_start);
        }
        if(!empty($date_end)){
            $date_end = strtotime($date_end) + 86399;
            $query = $query->where('last_login','<=',$date_end);
        }

        $query = $query->selectRaw('inet6_ntoa(login_ip) as login_ip,count(login_ip) as num')->groupBy(DB::Raw('inet6_ntoa(login_ip)'));
        $query2 = clone $query;
        $list = $query2->get()->toArray();
        $total = count($list);
        $user_list = $query->orderBy($field_id,$sort_way)
                            ->forPage($page, $page_size)
                            ->get()->toArray();

        $attributes['total'] = $total;
        $attributes['number'] = $page;
        $attributes['size'] = $page_size;

        return $this->lang->set(0, [], $user_list, $attributes);
    }

};