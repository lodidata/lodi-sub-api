<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '包地区黑名单';
    const DESCRIPTION = '';

    const QUERY       = [
        'channel' => 'string(, 30)',
        'area' => 'string(, 30)',
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'channel' => 'string',#渠道,
            'area' => 'string',#IP黑名单数量,
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $channel = $this->request->getParam('channel');
        $area = $this->request->getParam('area');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);

        $query= \DB::table('app_black_area');
        $channel && $query->where('channel','=',$channel);
        $area && $query->where('area','like','%'.$area.'%');
        $t = clone $query;
        $query->forPage($page,$page_size);
        $total = $t->count();
        $data = $query->get([
                'id',
                'channel',
                'area',
                'created',
            ])->toArray();
        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
