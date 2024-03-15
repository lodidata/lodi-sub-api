<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '第三方代付搜索';
    const DESCRIPTION = '第三方代付';

    const SCHEMAS     = [
        [
            'code'   => '代付代码',
            'name' => '代付名称',
        ],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        return \DB::table('transfer_config')
            ->orderBy('sort', 'DESC')
            ->orderBy('id', 'DESC')
            ->get(['code', 'name'])
            ->toArray();
    }

};
