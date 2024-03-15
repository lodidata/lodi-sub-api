<?php
use Logic\Admin\BaseController;
use Logic\Admin\Log;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '删除 客服电访';
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

        $info = \DB::table('kefu_user')->where('id',$id)->first();
        if(empty($info)){
            return $this->lang->set(-2);
        }

        //查找名单ID
        $telecom_list = \DB::table('kefu_telecom')->select('id')->where('kefu_id',$id)->get()->toArray();
        $telecom_ids = [];
        foreach($telecom_list as $key => $val){
            $telecom_ids[] = $val->id;
        }

        //开始删除数据
        \DB::beginTransaction();
        \DB::table('kefu_user')->delete($id);
        \DB::table('kefu_telecom')->where('kefu_id',$id)->delete();
        if(!empty($telecom_ids)){
            \DB::table('kefu_telecom_item')->whereIn('pid',$telecom_ids)->delete();
        }

        \DB::commit();

        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_KEFU, '客服电访', '客服名称', "删除", 1, "客服名称：{$info->name}");
        /*============================================================*/

        return $this->lang->set(0);

    }
};
