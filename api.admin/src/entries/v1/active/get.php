<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;
return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '活动列表';
    const DESCRIPTION = '';
    

    const QUERY       = [
        'title'=>'string() #活动名称',
        'template_id' => 'integer() #模板ID',
        'status' => 'string() #enabled（启用），disabled（停用）',
    ];
    const PARAMS      = [

    ];
    const SCHEMAS     = [
        [
            "id"=> 56,
            "title"=> "每日首充",
            "vender_type"=> 3,
            "updated"=> "2018-06-11 11:07:27",
            "content_type"=> 1,
            "content"=> null,
            "description"=> "dgfdsfhfshsdhsd",
            "cover"=> "/upload/3cacafec995e0c2f5af32949b18a16e0.png",
            "begin_time"=> "2018-05-06 11:06:46",
            "end_time"=> "2018-07-12 11:06:50",
            "sort"=> 1,
            "status"=> "enabled",
            "created_user"=> "lebo10",
            "updated_user"=> "lebo10",
            "created"=> "2018-06-11 11:07:27",
            "rule"=> null,
            "template_id"=> 3,
            "withdraw_require_val"=> null,
            "issue_mode"=> null,
            "send_type"=> null,
            "send_max"=> null,
            "bind_info"=> null
        ]
    ];
    //前置方法
   protected $beforeActionList = [
       'verifyToken',
       'authorize'
   ];
    
    public function run()
    {

        $params = $this->request->getParams();
        
        return (new activeLogic($this->ci))->getNewActiveList($params,$params['page'],$params['page_size']);
        
        
    }
};
