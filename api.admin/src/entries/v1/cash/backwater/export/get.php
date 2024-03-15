<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '返水审核列表';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [];

    protected $title = array(
        'name'=>'活动名称',
        'type'=>'类型',
        'batch_time'=>'返水批次',
        'back_cnt'=>'返水人数',
        'receive_cnt'=>'已领取人数',
        'back_amount'=>'返水金额',
        'receive_amount'=>'已领取金额',
        'status'=>'状态',
        'send_time'=>'赠送时间',
    );

    protected $type=[
        '1'=>'日返水',
        '2'=>'周返水',
        '3'=>'月返水',
        '4'=>'月俸禄'
    ];

    protected $status=[
        '0'=>'待返水',
        '1'=>'返水中',
        '2'=>'已返水',
    ];

    public function run()
    {
        $type = $this->request->getParam('type');
        $status = $this->request->getParam('status');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');

        $query=DB::table('active_backwater as ab')->leftJoin('active as a','ab.active_id','=','a.id');
        $type && $query->where('type',$type);
        if(isset($status)){
            $query->where('ab.status',$status);
        }
        $stime && $query->where('create_time','>=',$stime);
        $etime && $query->where('create_time','<=',$etime);

        $data=$query->orderBy('ab.id','desc')->get(['ab.*','a.name'])->toArray();
        if(!empty($data)){
            foreach($data as $val){
                $val->type=$this->type[$val->type];
                $val->back_amount=bcdiv($val->back_amount,100,2);
                $val->receive_amount=bcdiv($val->receive_amount,100,2);
                $val->status=$this->status[$val->status];
            }
        }
        \Utils\Utils::exportExcel('backwater',$this->title,$data);
    }
};
