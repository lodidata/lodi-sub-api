<?php

use Logic\Admin\Log;
use Logic\Admin\Notice as noticeLogic;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE = '删除指定公告';
    const DESCRIPTION = '';
    
    const QUERY = [
        'id' => 'int(required) #id',
    ];
    
    const PARAMS = [];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
//        'authorize'
    ];
    public $page = 10;

    public function run($id = null)
    {
        $this->checkID($id);

        $info = DB::table('notice')->find($id);

        $res = (new noticeLogic($this->ci))->delNoticeById($id);
        /*============================日志操作代码================================*/
        $popup_type = $info->popup_type == 1 ? "登录弹出" : ($info->popup_type == 2 ? "首页弹出" : "滚动公告");
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '公告管理', '公告管理', "删除", $sta, "弹出类型：{$popup_type}/标题：{$info->title}");
        /*============================================================*/
        return $res;

    }
};
