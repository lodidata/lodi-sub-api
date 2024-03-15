<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '删除 包地区黑名单';
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
        /*============================日志操作代码================================*/
        $info=\DB::table('app_black_area')->find($id);
        /*============================================================*/

        $res=\DB::table('app_black_area')->delete($id);
        if($res){
            /*============================日志操作代码================================*/
            $sta = $res !== false ? 1 : 0;
            (new Log($this->ci))->create(null , null, Log::MODULE_APP, '屏蔽设置', '屏蔽设置', "删除", $sta, "渠道类型:{$info->channel}");
            /*============================================================*/
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);
    }
};
