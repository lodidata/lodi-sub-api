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
        $kefu_id = $this->request->getParam('kefu_id', 0);
        $telecom_id = $this->request->getParam('telecom_id',0);
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        if(!is_numeric($kefu_id) || $kefu_id <= 0){
            return createRsponse($this->response, 200, -2, '参数有误');
        }

        //获取客服
        $subQuery = DB::connection('slave')->table('kefu_telecom')->where('kefu_id', $kefu_id);
        if(is_numeric($telecom_id) && $telecom_id > 0){
            $subQuery = $subQuery->where('id', $telecom_id);
        }
        if(!empty($stime)){
            $subQuery = $subQuery->where('created', '>=', $stime);
        }
        if(!empty($etime)){
            $subQuery = $subQuery->where('created', '<=', $etime);
        }
        $attr['total'] = $subQuery->count();
        $telecom_list = $subQuery->orderBy('id','desc')->forPage($page, $page_size)->get()->toArray();

        $data = [];
        foreach($telecom_list as $key => $val){
            $fundQuery = DB::table('funds_deposit as f')
                          ->where('f.money','>',0)
                          ->where('f.status','paid')
                          ->whereIn('f.user_id',DB::table('kefu_telecom_item')->where('pid',$val->id)->pluck('user_id')->toArray());
            if(!empty($stime)){
                $fundQuery = $fundQuery->where('f.created', '>=', $stime);

            }
            if(!empty($etime)){
                $fundQuery = $fundQuery->where('f.created', '<=', date('Y-m-d 23:59:59',strtotime($etime)));
            }
            $query= clone  $fundQuery;
            $query3= $fundQuery;
            $recharge_num=$query->distinct()->count('f.user_id');
            $recharge_amount=$query3->sum('f.money');

            $data[] = [
                'kefu_id'         => $val->kefu_id,
                'telecom_id'      => $val->id,
                'name'            => $val->name,
                'roll_num'        => $val->roll_num,
                'register_num'    => $val->register_num,
                'recharge_num'    => $recharge_num,
                'recharge_amount' => $recharge_amount,
                'recharge_mean'   => $recharge_num <=0 ? 0 : round($recharge_amount/$recharge_num),
                'created'         => $val->created,
            ];
        }
        $attr['num'] = $page;
        $attr['size'] = $page_size;

        return $this->lang->set(0, '', $data, $attr);
    }
};