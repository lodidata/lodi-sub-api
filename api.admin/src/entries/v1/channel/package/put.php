<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '单独渠道代理包生成';
    const DESCRIPTION = '单独渠道代理包生成';
    const PARAMS       = [];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id=null)
    {
        $this->checkID($id);
        $check=DB::table('channel_package')->where('id',$id)->first();

        if(!$check){
            return $this->lang->set(10015);
        }
        if($check->status != 0){
            return $this->lang->set(11160);
        }
        $package=new \Logic\Lottery\Package($this->ci);
        $result=$package->packgeUpload($check->url,$check->channel_id);
        if(!$result){
            return $this->lang->set(-2);
        }
        $update=array(
            'download_url'=>replaceImageUrl($result),
            'status'=>1
        );
        $res=DB::table('channel_package')->where('id',$id)->update($update);
        if(!$res){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }




};
