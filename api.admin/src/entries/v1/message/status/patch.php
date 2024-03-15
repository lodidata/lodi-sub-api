<?php

use Logic\Admin\Message as messageLogic;
use Logic\Admin\BaseController;
return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE       = '发布公告';
    const DESCRIPTION = '';

    const QUERY       = [
        'id' => 'int(required) #id',
    ];
    
    const PARAMS      = [

    ];
    const SCHEMAS     = [];

   //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id)
    {

        $this->checkID($id);
        
        return (new messageLogic($this->ci))->messagePublish($id);
        
    }
};
