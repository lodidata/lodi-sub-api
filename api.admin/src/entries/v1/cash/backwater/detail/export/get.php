<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '返水审核详情列表';
    const DESCRIPTION = '';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [];


    protected $title = array(
        'user_name'=>'会员账号',
        'coupon_money'=>'返水金额',
        'status'=>'状态',
        'process_time'=>'领取时间'
    );

    protected  $status=array(
        'pending'=>'待领取',
        'pass'=>'已领取'
    );

    public function run()
    {
        $id = $this->request->getParam('id');
        $userName = $this->request->getParam('user_name');
        $status = $this->request->getParam('status');

        $check=DB::table('active_backwater')->where('id',$id)->first(['id','batch_no','active_type','type']);
        if(empty($check)){
            return [];
        }
        if($check->active_type == 1){
            $query=DB::table('active_apply as ap')
                     ->selectRaw('ap.id,ap.user_id,ap.user_name,ap.coupon_money,ap.`status`,ap.process_time')
                     ->where('ap.batch_no',$check->batch_no);

            $userName && $query->where('ap.user_name',$userName);
            if(!empty($status)){
                if($status == 'pending'){
                    $query->whereIn('ap.status',['pending','undetermined']);
                }else{
                    $query->where('ap.status',$status);
                }
            }
        }else{
            if($check->active_type == 2 && $check->type ==1){
                $query=DB::table('rebet as ap')
                         ->selectRaw("ap.id,ap.user_id,ap.user_name,sum(ap.rebet) * 100 as coupon_money,case when ap.status=1 or ap.status=2 then 'pending' when ap.status=3 then 'pass' end as status,ap.process_time")
                         ->where('ap.batch_no',$check->batch_no)
                         ->groupBy('ap.user_id');
                $userName && $query->where('ap.user_name',$userName);
                if(!empty($status)){
                    if($status == 'pending'){
                        $query->whereIn('ap.status',[1,2]);
                    }elseif($status =='pass'){
                        $query->where('ap.status',3);
                    }
                }
            }elseif($check->active_type == 2 && $check->type ==4){
                $query=DB::table('user_monthly_award as ap')
                         ->selectRaw("ap.id,ap.user_id,ap.user_name,ap.award_money as coupon_money,case when ap.status=1 or ap.status=2 then 'pending' when ap.status=3 then 'pass' end as status,ap.process_time")
                         ->where('ap.batch_no',$check->batch_no);
                $userName && $query->where('ap.user_name',$userName);
                if(!empty($status)){
                    if($status == 'pending'){
                        $query->whereIn('ap.status',[1,2]);
                    }elseif($status =='pass'){
                        $query->where('ap.status',3);
                    }
                }
            }else{
                return [];
            }
        }


        $data=$query->orderBy('ap.process_time','desc')->orderBy('ap.id','desc')->get()->toArray();

        foreach($data as $val){
            $val->status=$this->status[$val->status];
            $val->coupon_money=bcdiv($val->coupon_money,100,2);
        }

        \Utils\Utils::exportExcel('backwaterDetail',$this->title,$data);


    }
};
