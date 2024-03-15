<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '注册IP排行榜';
    const DESCRIPTION = '注册IP排行榜';

    const PARAMS = [
        'date_start' => 'date #开始时间 2022-08-20',
        'date_end' => 'date #结束时间 2022-08-21',
        'page' => 'int(required) #当前页 1',
        'page_size' => 'int(required) #每页数量 默认20',
        'field_id' => 'string #排序参数',
        'sort_way' => 'enum[asc, desc] #排序方式 默认asc'
    ];
    const SCHEMAS = [
        [
            'ip' => 'string #注册ip',
            'num' => 'int #注册人数'
        ]
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
            $startTime=date('Y-m-d 00:00:00',strtotime($date_start));
            $query = $query->where('created','>=',$startTime);
        }
        if(!empty($date_end)){
            $endTime=date('Y-m-d 23:59:59',strtotime($date_end));
            $query = $query->where('created','<=',$endTime);
        }
        $query=$query->selectRaw('inet6_ntoa(ip) as ip,count(ip) as num')
                     ->groupBy(DB::Raw('INET6_NTOA(ip)'));
        $query2=clone $query;
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