<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '经纬度黑名单';
    const DESCRIPTION = '';

    const QUERY       = [
        'channel' => 'string(, 30)',
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id' => 'int() #',
            'channel' => 'string(, 30)',
            'longitude' => 'int() #经度',
            'latitude' => 'int() #纬度',
            'kilometre' => 'int() #屏蔽范围',
            'status' => 'enum[0,1]() #状态',
            'page'         => 'int()   #页码',
            'page_size'    => 'int()    #每页大小',
        ]
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $channel = $this->request->getParam('channel');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);

        $query= \DB::table('app_black_kilometre');
        $channel && $query->where('channel','=',$channel);
        $t = clone $query;
        $query->forPage($page,$page_size);
        $total = $t->count();
        $data = $query->get([
                'id',
                'channel',
                'longitude',
                'latitude',
                'kilometre',
                'status',
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
