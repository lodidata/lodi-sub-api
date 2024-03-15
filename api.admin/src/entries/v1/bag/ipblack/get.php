<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '包IP黑名单';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'channel' => 'string(, 30)',
        'page'         => 'int()   #页码',
        'page_size'    => 'int()    #每页大小',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'channel' => 'string',#渠道,
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
        $page = $this->request->getParam('page',1);
        $page_size = $this->request->getParam('page_size',20);

        $query= \DB::table('app_black_ip');
        $channel && $query->where('channel','=',$channel);
        $query->groupBy('channel')->forPage($page,$page_size);
        $total = \DB::select('SELECT COUNT(1) AS channel FROM (SELECT 1 FROM app_black_ip GROUP BY channel) AS black');
        $data = $query->get([
                'id',
                'channel',
                \DB::raw('count(ip) as count')
            ])->toArray();
        $attr = [
            'number' => $page,
            'size' => $page_size,
            'total' => $total[0]->channel ?? 0,
        ];
        return $this->lang->set(0,[],$data,$attr);
    }
};
