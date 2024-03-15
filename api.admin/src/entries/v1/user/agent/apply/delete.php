<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '删除 代理申请';
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
        $del = DB::table('agent_apply')->delete($id);
        if(!$del) {
            $this->lang->set(-1);
        }

        \DB::table('agent_apply_submit')->where('apply_id',$id)->delete();

        return $this->lang->set(0);
    }
};
