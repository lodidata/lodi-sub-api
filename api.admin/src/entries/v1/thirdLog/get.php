<?php

use Logic\Admin\BaseController;
use lib\validate\admin\RoomValidate;

return new class() extends BaseController {
    const TITLE = '回调日志查询';
    const DESCRIPTION = '接口';


    const QUERY = [
        'order_number'  => 'string()   #模糊搜索订单号',
        'start_time'    => 'string(required)   #开始时间',
        'end_time'      => 'string(required)   #结束时间',
        'page'          => 'int(required) ',
        'page_size'     => 'int(required) ',
    ];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $page        = $this->request->getParam('page',1);
        $size        = $this->request->getParam('page_size',20);
        $orderNumber = $this->request->getParam('order_number');
        $start_time  = $this->request->getParam('start_time', date('Y-m-d 00:00:00'));
        $end_time    = $this->request->getParam('end_time', date('Y-m-d 23:59:59'));

        $rs = [];
        $query = \DB::connection('slave')->table('pay_callback');
        $orderNumber && $query->where('order_number','=', $orderNumber);
        $query->where('created','>=', $start_time);
        $query->where('created','<=', $end_time);
        $rs   =    $query->forPage($page, $size)->orderBy('created','desc')->get()->toArray();

        if($orderNumber && !$rs){
            $new_query = \DB::connection('slave')->table('pay_callback');
            $orderNumber && $new_query->where('content','like', "%".$orderNumber."%");
            $new_query->where('created','>=', $start_time);
            $new_query->where('created','<=', $end_time);
            $rs   =    $new_query->forPage($page, $size)->orderBy('created','desc')->get()->toArray();
        }

        if ($rs) {
            foreach ($rs as &$val) {
                $val = (array)$val;
                $val['content']           = json_decode($val['content'], true);
                $val['content']['params'] = json_decode($val['content']['params']??'', true);
            }
            unset($val);
        }

        $attributes['total']  = $rs ? 20:0;//只看一页的
        $attributes['number'] = $page;
        $attributes['size']   = $size;
        return $this->lang->set(0, [], $rs, $attributes);
    }
};
