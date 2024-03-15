<?php

use Logic\Admin\Message as messageLogic;
use Model\Admin\Message as messageModel;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '本厅消息列表';
    const DESCRIPTION = '';

    const QUERY       = [
        'id'        => 'int #指定消息id',
        'title'     => 'string()',
        'type'      => 'int # 1 重要消息,2 一般消息 ',
        'admin_name'=> 'string # 发布人',
        'start_time'   => 'date()  #开始时间',
        'end_time'     => 'date()  #结束时间',
        'page'      => 'int() #default 1',
        'page_size' => 'int() #default 20'
    ];

    const PARAMS      = [];
    const SCHEMAS     = [
        ['map # type 1重要消息（import）,2,一般消息'],
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'       
    ];
    
    public function run()
    {

        $params = $this->request->getqueryParams();
        return (new messageLogic($this->ci))->getMesList($params, $params['page'],$params['page_size']);

    }

};
