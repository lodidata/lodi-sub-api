<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '包IP黑名单';
    const DESCRIPTION = '';
    
    const QUERY       = [
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'channel' => 'string',#渠道,
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        $query= \DB::table('app_bag')->where('channel','!=','');
        $data = $query->groupBy('channel')->get([
                'channel',
                \DB::raw('count(1) as count')
            ])->toArray();
        return $this->lang->set(0,[],$data);
    }
};
