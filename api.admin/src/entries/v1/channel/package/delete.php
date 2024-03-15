<?php

use Logic\Admin\Log;
use Logic\Admin\BaseController;

/**
 * 删除渠道代理包
 */
return new class() extends BaseController
{
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);

        $check=DB::table('channel_package')->where('id',$id)->first();
        if(!$check){
            return $this->lang->set(10015);
        }
        $data=array(
            'status'=>3
        );
        $res=DB::table('channel_package')->where('id',$id)->update($data);
        if(!$res){
            return $this->lang->set(-2);
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_CHANNEL, '渠道代理包', '渠道代理包', "删除", 0, "母包名称：{$check->name}");
        return $this->lang->set(0);
    }

};
