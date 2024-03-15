<?php

use Logic\Admin\BaseController;
use Utils\IpLocation;

/**
 * ip检查
 */
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $ip = $this->request->getParam('ip');
        $start_time = $this->request->getParam('start_time');
        $end_time = $this->request->getParam('end_time');
        $log_type = $this->request->getParam('log_type');
        $page = $this->request->getParam('page') ?? 1;
        $size = $this->request->getParam('page_size') ?? 1;

        $ipLoca=new IpLocation();
        $ipLoca->init();


        $query=DB::connection('slave')->table('user_logs as l')
            ->leftJoin('user as u', 'l.user_id', '=', 'u.id')
            ->leftJoin('user_level as le', 'u.ranting', '=', 'le.level')
            ->selectRaw('l.*, le.`name` AS `level`');


        $query=!empty($ip) ?$query->where('l.log_ip','=',$ip):$query;

        $query=!empty($start_time)  ? $query->where('l.created', '>=', $start_time) : $query;

        $query=!empty($end_time)  ? $query->where('l.created', '<=', $end_time) : $query;

        $query = !empty($log_type) ? $query->where('l.log_type','=',$log_type):$query;

        $attributes['total'] = $query->count();
        $data= $query->forPage($page,$size)
            ->orderBy('l.id', 'desc')
            ->get()
            ->toArray();

        foreach ($data as $key=>$item) {
            $item=(array)$item;
            $address=$ipLoca->getlocation($item['log_ip'])['country'];
            $data[$key]->address=$address;
        }

        $attributes['size'] = $size;
        $attributes['number'] = $page;

        return $this->lang->set(0, [], $data, $attributes);
    }
};
