<?php

use lib\validate\BaseValidate;
use Logic\Admin\BaseController;
use lib\validate\admin\RoomValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
//    const STATE       = \API::DEPRECATED;
    const TITLE       = '停用启用房间公告';
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
        $params = $this->request->getParams();

        if(!isset($params['id']) && empty($params['id'])){
            return $this->lang->set(10010);
        }
        $info = DB::table('room_notice')->find($params['id']);

        if(isset($params['status'])){
            $validate = new BaseValidate([
                'status'  => 'in:enable,disable',
            ]);

            $validate->paramsCheck('',$this->request,$this->response);

            $res = DB::table('room_notice')->where('id',$params['id'])->update(['status'=>$params['status']]);
            $sta_name=[
                'enable'=>'启用',
                'disable'=>'停用'
            ];
            $type=$sta_name[$params['status']];
        }else{
            $res = DB::table('room_notice')->where('id',$params['id'])->delete();
            $type="删除";
        }

        if($res === false){
            return $this->lang->set(-2);
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_WEBSITE, '公告管理', '房间公告', $type, $sta, "公告标题：{$info->title}");
        /*============================================================*/

        return $this->lang->set(0);
    }

};
