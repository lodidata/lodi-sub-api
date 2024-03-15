<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改屏蔽经纬度信息';
    const DESCRIPTION = '';

    const QUERY       = [];

    const PARAMS      = [
        'channel' => 'string(require, 30) #渠道',
        'longitude' => 'int() #经度',
        'latitude' => 'int() #纬度',
        'kilometre' => 'int() #屏蔽范围',
        'status' => 'string() #enabled 开启，disabled关闭',
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    
    public function run($id)
    {
        $this->checkID($id);
        $channel = $this->request->getParam('channel');
        $longitude = $this->request->getParam('longitude');
        $latitude = $this->request->getParam('latitude');
        $kilometre = $this->request->getParam('kilometre');
        $status = $this->request->getParam('status');
        $v = [
            'channel' => 'require',
            'longitude' => 'require',
            'latitude' => 'require',
            'kilometre' => 'require',
            'status' => 'require',
        ];
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('',$this->request,$this->response);
        try {
            $data = [
                'channel' => $channel,
                'longitude' => $longitude,
                'latitude' => $latitude,
                'kilometre' => $kilometre,
                'status' => $status,
            ];
            $res=\DB::table('app_black_kilometre')->where('id',$id)->update($data);
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null , null, Log::MODULE_APP, '屏蔽设置', '屏蔽设置', "更新经纬区域", $sta, "{$channel}经纬度:{$longitude}:{$latitude},周边{$kilometre}公里");
            /*============================================================*/
            return $this->lang->set(0);
        }catch (\Exception $e) {
            return $this->lang->set(-2);
        }
    }
};
