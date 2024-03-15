<?php

use Logic\Admin\BaseController;
use Logic\Funds\DealLog;

return new class() extends BaseController {
    const TITLE = '手动存提--交易类型 列表';
    const DESCRIPTION = '以静态文件的形式存在';
    const QUERY = [];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $types = DealLog::getManualDealTypes(1);

        return $types;
    }
};
