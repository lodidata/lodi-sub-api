<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/6/21 16:35
 */
use Logic\Admin\BaseController;
return new class() extends BaseController{
//    const STATE = \API::DRAFT;
    const TITLE = '获取追号列表';
    const DESCRIPTION = '追号管理 追号依整体考虑';
    const QUERY = [
    ];
    
    const PARAMS = [];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $state = [
            ['id' => 'complete' ,'name' => '已结束'],
            ['id' => 'underway' ,'name' => '追号中'],
            ['id' => 'cancel' ,'name' => '已撤单'],
        ];
        return $state;
    }
};
