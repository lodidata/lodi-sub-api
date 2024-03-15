<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改/新增APP Android包相关信息';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [
        'channel' => 'string(require, 30)',
        'ip' => 'string(require)',#APP Name,
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    
    public function run()
    {
        $ip = $this->request->getParam('ip');
        $channel = $this->request->getParam('channel');
        $v = [
            'channel' => 'require',
            'ip' => 'require',
        ];
        $data = [];
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('',$this->request,$this->response);
//        foreach (explode(PHP_EOL, $ip) as $v){
        foreach (explode('/', $ip) as $v){
            $area = (new \Model\AppBag())->getAreaByIp($v);
            $country = $area['country'] ?? '';
            $data[] = ['channel' => $channel, 'ip' => $v,'area'=>$country];
        }
        try {
            $res=\DB::table('app_black_ip')->insert($data);
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_APP, '屏蔽设置', 'IP屏蔽', "新增IP", $sta, "渠道类型:$channel/$ip");
            /*============================================================*/
            return $this->lang->set(0);
        }catch (\Exception $e) {
            return $this->lang->set(-2);
        }
    }
};
