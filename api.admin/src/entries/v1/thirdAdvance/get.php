<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '第三方代付信息列表';
    const DESCRIPTION = '第三方代付';
    
    const QUERY       = [

    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'id'   => '第三方代付ID',
            'name' => '第三方代付名',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        return \DB::table('transfer_config')
                  ->where('status', '=', 'enabled')
                  ->orderBy('sort', 'DESC')
                  ->orderBy('id', 'DESC')
                  ->get(['id', 'name'])
                  ->toArray();
    }

};
