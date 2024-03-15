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

    public function run()
    {
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 20);
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
                $total=count($query->get()->toArray());
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




        $count = clone $query;
        $attributes['total'] = $total ?? $count->count();
        $attributes['number'] = $page;
        $attributes['size'] = $size;
        $data=$query->forPage($page,$size)->orderBy('ap.process_time','desc')->orderBy('ap.id','desc')->get()->toArray();


        return $this->lang->set(0,[],$data,$attributes);
    }
};
