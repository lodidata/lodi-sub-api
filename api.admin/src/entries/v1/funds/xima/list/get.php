<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {

    const TITLE = '洗码记录';

    //前置方法
    protected $beforeActionList = [
       'verifyToken', 'authorize',
    ];

    public function run() {

        $username = $this->request->getParam('user_name','');
        $order_number = $this->request->getParam('order_number');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 10);

        $query = DB::table('xima_order')->selectRaw('id,order_number,user_id,user_name,dml_total,amount_total,created');
        if($username){
            $user_id = DB::table('user')->where('name',$username)->value('id');
            if(!$user_id)
                return [];
            $query = $query->where('user_id',$user_id);
        }
        $query = !empty($order_number) ? $query->where('order_number',$order_number) : $query ;
        $query = !empty($stime) ? $query->where('created','>=',$stime) : $query ;
        $query = !empty($etime) ? $query->where('created','<=',$etime.' 59:59:59') : $query ;

        $data = $query->forPage($page,$size)->orderByDesc('id')->get()->toArray();
        $count = clone $query;
        $attributes['total'] = $count->count();

        $attributes['size'] = $size;
        $attributes['number'] = $page;


        return $this->lang->set(0, [], $data, $attributes);
    }
};
