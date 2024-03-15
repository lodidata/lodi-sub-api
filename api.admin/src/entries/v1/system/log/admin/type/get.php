<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/16 17:18
 */

use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '后台日志--操作类型';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        return [];
        $types = \Las\Logs::MODULES;
        $_t    = [];
        foreach ($types as $id => $type) {
            $_t[] = ['id' => $id, 'name' => $type];
        }

        return $_t;
    }
};
