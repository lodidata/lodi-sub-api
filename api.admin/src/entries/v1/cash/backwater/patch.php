<?php
use Logic\Admin\BaseController;
use Model\FundsDealLog;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '返水审核发放返水';
    const DESCRIPTION = '返水审核发放返水';

    const QUERY       = [
        'id' => 'int(required) #需要修改的payment id',
    ];

    const PARAMS      = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id)
    {
        $this->checkID($id);
        $check=DB::table('active_backwater')->where('id',$id)->first(['back_cnt','batch_no','status','active_type']);
        if(!$check || $check->back_cnt <=0){
            return $this->lang->set(11170);
        }
        if($check->status != 0){
            return $this->lang->set(11171);
        }

        $res=DB::table('active_backwater')->where('id',$id)->update(['status'=>1]);
        if(!$res){
           return $this->lang->set(-2);
        }
        $return=[
            'batch_no'=>$check->batch_no,
            'active_type'=>$check->active_type
        ];
        \Utils\MQServer::send('back_water', $return);
        return $this->lang->set(0);
    }
};
