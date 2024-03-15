<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE = '获取直推推广记录';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $username = $this->request->getParam('username');
        $stime = $this->request->getParam('start_time');
        $etime = $this->request->getParam('end_time');
        $month = $this->request->getParam('month','');
        $type = $this->request->getParam('type');
        $page = $this->request->getParam('page', 1);
        $size = $this->request->getParam('page_size', 10);

        $query = DB::table('direct_record');
        if(!empty($username)){
            $query = $query->where('sup_name', $username);
        }
        if(!empty($month)){
            $stime = $month . '-01 00:00:00';
            $etime = strtotime('+1 month', strtotime($stime));
            $etime = date('Y-m-d H:i:s', $etime);
            if(!empty($stime)){
                $query = $query->where('created', '>=', $stime);
            }
            if(!empty($etime)){
                $query = $query->where('created', '<', $etime);
            }
        }else{
            if(!empty($stime)){
                $query = $query->where('created', '>=', $stime);
            }
            if(!empty($etime)){
                $query = $query->where('created', '<=', $etime);
            }
        }
        if(!empty($type)){
            $query = $query->where('type', $type);
        }
        $count = clone $query;
        $attributes['total'] = $count->count();
        $attributes['size'] = $size;
        $attributes['number'] = $page;

        $data = $query->orderBy('created','desc')
                    ->orderBy('id','desc')
                    ->forPage($page, $size)
                    ->get()
                    ->toArray();
        foreach($data as &$val) {
            if($val->user_id == $val->sup_uid) {
                $val->sup_name = "-";
            }
        }

        return $this->lang->set(0, [], $data, $attributes);
    }
};
