<?php

use Logic\Admin\BaseController;
use lib\validate\admin\RoomValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE = '新增房間公告';
    const DESCRIPTION = '接口';
    

    const QUERY = [

    ];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

        $params = $this->request->getParams();

        (new RoomValidate())->paramsCheck('post', $this->request, $this->response);//参数校验

        $data['lottery_id'] = $params['lottery_id'];
        $data['hall_id'] = $params['hall_id'];
        $data['title'] = $params['title'];
        $data['content'] = $params['content'];
        $data['sleep_time'] = $params['sleep_time'];
        $data['creator'] = $this->playLoad['uid'];
        $data['status'] = 'enable';
        $data['created'] = date('Y-m-d H:i:s');

        $res = DB::table('room_notice')->insert($data);//新增

        if (!$res) {
            return $this->lang->set(-2);
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '公告管理', '房间公告', "新增房间公告", $sta, "公告标题：{$params['title']}");
        /*============================================================*/

        return $this->lang->set(0);
    }

};
