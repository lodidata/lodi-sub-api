<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '删除 包IP黑名单';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id)
    {
        $this->checkID($id);

        $info=DB::table('app_black_ip')
            ->find($id);

        $res=\DB::table('app_black_ip')->delete($id);
        if($res){
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null , null, Log::MODULE_APP, '屏蔽设置', 'IP屏蔽', "删除", $sta, "渠道类型:{$info->channel}");
            /*============================================================*/
            return $this->lang->set(0);
        }
        /*============================日志操作代码================================*/
        $sta = $res !== false ? 1 : 0;
        (new Log($this->ci))->create(null , null, Log::MODULE_APP, '屏蔽设置', 'IP屏蔽', "删除", $sta, "渠道类型:{$info->channel}");
        /*============================================================*/

        return $this->lang->set(-2);
    }
};
