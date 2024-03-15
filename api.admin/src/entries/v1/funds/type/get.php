<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE       = '交易流水(记录)/资金流水--类别与类型';
    const DESCRIPTION = '';

    const QUERY       = [];

    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        return array_values(\Logic\Funds\DealLog::getDealLogTypes());
    }
};
