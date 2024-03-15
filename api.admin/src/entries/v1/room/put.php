<?php

use lib\validate\admin\RoomValidate;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '修改公告';
    const DESCRIPTION = '接口';
    

    const QUERY       = [

    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {

//        $this->checkID($id);
        $params = $this->request->getParams();
        (new RoomValidate())->paramsCheck('patch', $this->request, $this->response);//参数校验

        $res = DB::table('room_notice')->find($params['id']);//修改

        if(!$res){
            return $this->lang->set(10015);
        }
        $data['lottery_id'] = $params['lottery_id'];
        $data['hall_id'] = $params['hall_id'];
        $data['title'] = $params['title'];
        $data['content'] = $params['content'];
        $data['sleep_time'] = $params['sleep_time'];
        $data['created'] = date('Y-m-d H:i:s');

        /*============================日志操作代码================================*/
        $data_info=DB::table('lottery')->find($params['lottery_id']);
        /*============================================================*/

        $res = DB::table('room_notice')->where('id',$params['id'])->update($data);//修改

        if (!$res) {
            return $this->lang->set(-2);
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '公告管理', '房间公告', "编辑", $sta, "彩种：{$data_info->name}/公告标题：{$params['title']}");
        /*============================================================*/

        return $this->lang->set(0);
        
    }

};
