<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '删除 客服电访名单';
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

        $info = \DB::table('kefu_telecom')->where('id',$id)->first();
        if(empty($info)){
            return $this->lang->set(-2);
        }

        //开始删除数据
        \DB::beginTransaction();
        $result = \DB::table('kefu_telecom')->delete($id);
        if(!$result){
            \DB::rollback();
            return $this->lang->set(-2);
        }
        \DB::table('kefu_telecom_item')->where('pid',$id)->delete();

        \DB::commit();

        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_KEFU, '客服电访', '名单列表', "删除", 1, "名单名称：{$info->name}");
        /*============================================================*/

        return $this->lang->set(0);

    }
};
