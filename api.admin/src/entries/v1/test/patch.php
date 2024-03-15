<?php

use Logic\Admin\BaseController;
use QL\QueryList;
use Logic\User\Bkge;
return new class() extends BaseController
{
    const TITLE       = 'test';
    const DESCRIPTION = '';
    
    const QUERY       = [
        'id' => 'int() #id'
    ];
    
    const PARAMS      = [
        'sort'           => 'int #排序',
        'status'         => 'int #是否开启，1 是，0 否'
    ];

    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id='')
    {


    }

};
