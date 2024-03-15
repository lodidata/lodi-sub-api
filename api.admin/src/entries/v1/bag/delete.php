<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '删除 包相关信息';
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
        $info = \DB::table('app_package')->find($id);
        /*============================================================*/
        $res=   \DB::table('app_package')->delete($id);
        if($res){
            /*============================日志操作代码================================*/
            $info = (array)$info;
            if($info['type']==1){
                $type="安卓安装包";
            }else if($info['type']==2){
                $type="苹果安装包";
            }else{
                $type="未知";
            }
            $str="包内容:".json_encode($info);
            $sta = 1;
            (new Log($this->ci))->create(null, null, Log::MODULE_APP, $type, $type, "删除", $sta,$str );
            /*============================================================*/
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};
