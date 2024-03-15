<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '电访后台';
    const DESCRIPTION = '电访后台';
    const PARAMS       = [
    ];
    const SCHEMAS     = [

    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run() {
        $stime = $this->request->getParam('start_time','');
        $etime = $this->request->getParam('end_time','');
        $telecom_id = $this->request->getParam('telecom_id',0);
        $kefu_id = $this->request->getParam('kefu_id',0);
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        $telecom_arr = [];
        $roll_num = 0;
        if($telecom_id <= 0){
            $telecom_list = DB::table('kefu_telecom')->where('kefu_id', $kefu_id)->get(['id','roll_num'])->toArray();
            foreach($telecom_list as $val){
                $telecom_arr[] = $val->id;
                $roll_num += $val->roll_num;
            }
        }else{
            $roll_num = DB::table('kefu_telecom')->where('id', $telecom_id)->value('roll_num');
            $telecom_arr[] = $telecom_id;
        }
        if(empty($telecom_arr)){
            return $this->lang->set(0, '', [], []);
        }
        $attr['kefu_name'] = '';
        if(is_numeric($kefu_id) && $kefu_id > 0){
            $kefu_info = DB::table('kefu_user')->where('id', $kefu_id)->first();
            if(!empty($kefu_info)){
                $attr['kefu_name'] = $kefu_info->name;
            }
        }

        $telecom_str = implode($telecom_arr, ',');
        
        $subQuery = DB::table(DB::raw('(select user_id from kefu_telecom_item where `pid` in ('.$telecom_str.') group by user_id) as ke'))
                      ->leftJoin('funds_deposit as f','ke.user_id','=','f.user_id')
//                      ->whereIn('ke.pid',$telecom_arr)
                      ->where('f.money','>', 0)
                      ->where('f.status', 'paid')
                      ->whereIn('f.user_id',DB::table('kefu_telecom_item')->wherein('pid',$telecom_arr)->pluck('user_id')->toArray());

        if(!empty($stime)){
            $subQuery = $subQuery->where('f.created', '>=', $stime);

        }
        if(!empty($etime)){
            $subQuery = $subQuery->where('f.created', '<=', date('Y-m-d 23:59:59',strtotime($etime)));

        }

        $query = $query1 = clone $subQuery;
        $query3= $subQuery;
        $attr['total'] = $query->count();
        $attr['recharge_num']=$query1->distinct()->count('f.user_id');
        $attr['recharge_amount']=$query3->sum('f.money');

        $attr['recharge_mean'] = $attr['recharge_num'] <=0 ? 0 : round($attr['recharge_amount']/$attr['recharge_num']);
        $attr['roll_num'] = $roll_num;
        $attr['register_num'] = $attr['total'];

        $resData = $subQuery->leftJoin('user as u','f.user_id','=','u.id')
                            ->orderBy('u.created','desc')->orderBy('f.created','desc')
                            ->forPage($page, $page_size)
                            ->select('f.user_id','u.mobile','u.name as username','u.created as register_time','f.created as recharge_time','f.money as recharge_amount')
                            ->get()->toArray();
        $user_control = \DB::table('admin_user_role')->where('id',$this->playLoad['rid'])->value('member_control');
        $user_control = json_decode($user_control,true);
        foreach($resData as &$v){
            if($v->recharge_amount == 0){
                $v->recharge_amount = '';
            }
            if (!isset($user_control['kefu_phone']) || !$user_control['kefu_phone']) {
                $v->mobile = '******';
            }else{
                $v->mobile = \Utils\Utils::RSADecrypt($v->mobile);
            }

        }
        $attr['num'] = $page;
        $attr['size'] = $page_size;

        //查询彩金和兑换金额
        $newQuery = DB::table(DB::raw('(select user_id from kefu_telecom_item where `pid` in ('.$telecom_str.') group by user_id) as ke'))
            ->leftJoin('rpt_user as r','ke.user_id','=','r.user_id')
            ->where(function($query_item){
                $query_item->where('r.coupon_user_amount','>', 0)->orWhere('r.withdrawal_user_amount','>', 0);
            });

        if(!empty($stime)){
            $newQuery = $newQuery->where('r.count_date', '>=', date('Y-m-d',strtotime($stime)));

        }
        if(!empty($etime)){
            $newQuery = $newQuery->where('r.count_date', '<=', date('Y-m-d',strtotime($etime)));
        }
        $newQuery1 = clone $newQuery;
        $attr['withdraw_amount'] = $newQuery->sum('r.withdrawal_user_amount');
        $attr['coupon_amount'] = $newQuery1->sum('r.coupon_user_amount');


        return $this->lang->set(0, '', $resData, $attr);
    }
};