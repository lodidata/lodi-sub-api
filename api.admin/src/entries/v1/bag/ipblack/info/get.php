<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '包IP黑名单';
    const DESCRIPTION = '';

    const QUERY       = [
        'channel_id' => 'string(, 30)',
        'ip' => 'string(, 30)',
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'channel_id' => 'string',#渠道,
            'count' => 'string',#IP黑名单数量,
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $channel = $this->request->getParam('channel');
        $channel_id = $this->request->getParam('channel_id');
        $ip = $this->request->getParam('ip');
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);

        $query= \DB::table('app_black_ip');
        $channel && $query->where('channel','=',$channel);
        $channel_id && $query->where('channel_id','=',$channel_id);
        $ip && $query->where('ip','=',$ip);
        $t = clone $query;
        $query->forPage($page,$page_size);
        $total = $t->count();
        $data = $query->get([
                'id',
                'channel_id',
                'channel',
                'ip'
            ])->toArray();
        $i=1;
        foreach ($data as $val){
            $val->number = ($page-1) * $page_size + $i++;
        }
        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
