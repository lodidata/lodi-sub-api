<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE       = '删除指定公告';
    const DESCRIPTION = '';

    const QUERY       = [
        'id' => 'int(required) #id',
    ];
    
    const PARAMS      = [];
    const SCHEMAS     = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];
    public $page = 10;

    public function run($id=null)
    {
        $this->checkID($id);
        /*============================日志操作代码================================*/
        $info = DB::table('room_notice')->find($id);
        /*============================================================*/

        $res = DB::table('room_notice')->where('id',$id)->delete();

        if (!$res) {
            return $this->lang->set(-2);
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '公告管理', '房间公告', "删除", $sta, "公告标题：{$info->title}");
        /*============================================================*/

        return $this->lang->set(0);

    }
};
