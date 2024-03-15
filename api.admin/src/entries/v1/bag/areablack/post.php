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
        'area' => 'string(require)',#APP Name,
    ];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    
    public function run($id = null)
    {
        $data = $this->request->getParams();
        $channel = $this->request->getParam('channel');
        $v = [
            'channel' => 'require',
            'area' => 'require',
        ];
        $validate = new \lib\validate\BaseValidate($v);
        $validate->paramsCheck('',$this->request,$this->response);
        if($id){
            $res=\DB::table('app_black_area')->where('id',$id)->update($data);
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null, null, Log::MODULE_APP, '屏蔽设置', '屏蔽设置', "修改", $sta, "渠道类型:$channel");
            /*============================================================*/
        }else{
            $flag = \DB::table('app_black_area')->where('channel','=',$channel)->value('id');
            if($flag)
                return $this->lang->set(10406,['channel']);
            try {
                $res=\DB::table('app_black_area')->insert($data);
                /*============================日志操作代码================================*/
                $sta = $res !== false ? 1 : 0;
                (new Log($this->ci))->create(null , null, Log::MODULE_APP, '屏蔽设置', '屏蔽设置', "新增区域", $sta, "渠道类型:$channel/已选城市:{$data['area']}");
                /*============================================================*/
                return $this->lang->set(0);
            }catch (\Exception $e) {
                return $this->lang->set(-2);
            }
        }
    }
};
