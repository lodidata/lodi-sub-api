<?php

use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE       = '活动列表';
    const DESCRIPTION = '';

    const QUERY       = [
        'title'=>'string() #活动名称',
    ];
    const PARAMS      = [

    ];
    const SCHEMAS     = [
        [

        ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();

        $query = DB::connection('slave')
                    ->table('active')
                    ->select(['id', 'title'])
                    ->where('status', 'enabled');

        if (isset($params['title'])) {
            $query = $query->where('title', "{$params['title']}");
        }

        $res = $query->orderBy('sort')
                     ->orderBy('id', 'DESC')
                     ->get()
                     ->toArray();

        return $this->lang->set(0, [], $res);
    }
};