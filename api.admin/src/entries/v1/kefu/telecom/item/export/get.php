<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '电访后台详情';
    const DESCRIPTION = '电访后台导出';

    const QUERY = [
    ];

    const PARAMS = [];
    const SCHEMAS = [
    ];

    protected $title=[
        'mobile'=>'手机号','username'=>'账号','register_time'=>'注册时间','recharge_time'=>'充值时间',
        'recharge_amount'=>'充值金额','name'=>'客服名称','total'=>'名单总数',
        'recharge_num'=>'总充值人数','all_recharge_amount'=>'总充值金额',
        'recharge_mean'=>'平均充值金额',
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $stime = $this->request->getParam('start_time','');
        $etime = $this->request->getParam('end_time','');
        $telecom_id = $this->request->getParam('telecom_id',0);
        $kefu_id = $this->request->getParam('kefu_id',0);

        $telecom_arr = [];
        if($telecom_id <= 0){
            $telecom_list = DB::connection('slave')->table('kefu_telecom')->where('kefu_id', $kefu_id)->get()->toArray();
            foreach($telecom_list as $val){
                $telecom_arr[] = $val->id;
            }
        }else{
            $telecom_arr[] = $telecom_id;
        }

        $kefu_info = DB::connection('slave')->table('kefu_user')->where('id', $kefu_id)->first();
        if(empty($kefu_info)){
            return createRsponse($this->response, 200, -2, '参数有误');
        }
        $attr['name'] = $kefu_info->name;

        $subQuery = DB::table('funds_deposit as f')
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

        $attr['register_num'] = $attr['total'];

        $resData = $subQuery->leftJoin('user as u','f.user_id','=','u.id')
                            ->orderBy('u.created','desc')->orderBy('f.created','desc')
                            ->select('f.user_id','u.mobile','u.name as username','u.created as register_time','f.created as recharge_time','f.money as recharge_amount')
                            ->get()->toArray();

        foreach($resData as $key => &$val){
            $val = (array)$val;
            $val['recharge_amount'] = $val['recharge_amount'] > 0  ? bcdiv($val['recharge_amount'], 100, 2) : 0;
            if($key == 0){
                $val['name']                = $attr['name'];
                $val['total']               = $attr['total'];
                $val['recharge_num']        = $attr['recharge_num'];
                $val['all_recharge_amount'] = $attr['recharge_amount']>0 ? bcdiv($attr['recharge_amount'], 100, 2) : 0;
                $val['recharge_mean']       = $attr['recharge_mean']>0 ? bcdiv($attr['recharge_mean'], 100, 2) : 0;
            }else{
                $val['name']                = '';
                $val['total']               = '';
                $val['recharge_num']        = '';
                $val['all_recharge_amount'] = '';
                $val['recharge_mean']       = '';
            }
            $val['mobile'] = \Utils\Utils::RSADecrypt($val['mobile']);
        }

        Utils\Utils::exportExcel('kefuDetail',$this->title,$resData);

    }

};
