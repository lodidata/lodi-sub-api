<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE = '更新推广返水配置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $id             = $this->request->getParam('id', '');
        $register_count = $this->request->getParam('register_count', '');
        $recharge_count = $this->request->getParam('recharge_count', '');
        $bkge_increase  = (float)$this->request->getParam('bkge_increase', '');
        $bkge_increase_week  = (float)$this->request->getParam('bkge_increase_week', '');
        $bkge_increase_month  = (float)$this->request->getParam('bkge_increase_month', '');

        if (empty($id) || empty($register_count) || empty($recharge_count)) {
            return $this->lang->set(-2);
        }

        // 编辑时判断上下级编号限制
        $current_no_data = DB::table('direct_bkge')->where('id', $id)->get()->toArray();
        $last_serial_no = $current_no_data[0]->serial_no - 1;
        $next_serial_no = $current_no_data[0]->serial_no + 1;

        // 判断不能小于上一级
        if($last_serial_no){
            $last_no_data = DB::table('direct_bkge')->where('serial_no', $last_serial_no)->get()->toArray();
            if($last_no_data){
                $last_no_data = (array)$last_no_data[0];
                if ($register_count <= $last_no_data['register_count']) {
                    return $this->lang->set(11060);
                }
    
                if ($recharge_count <= $last_no_data['recharge_count']) {
                    return $this->lang->set(11061);
                }
    
//                if ($bkge_increase <= $last_no_data['bkge_increase']) {
//                    return $this->lang->set(11062);
//                }
            }
        }

        // 判断不能大于下一级
        if($next_serial_no){
            $next_no_data = DB::table('direct_bkge')->where('serial_no', $next_serial_no)->get()->toArray();
            if($next_no_data){
                $next_no_data = (array)$next_no_data[0];
                if ($register_count >= $next_no_data['register_count']) {
                    return $this->lang->set(11060);
                }
    
                if ($recharge_count >= $next_no_data['recharge_count']) {
                    return $this->lang->set(11061);
                }
    
//                if ($bkge_increase >= $next_no_data['bkge_increase']) {
//                    return $this->lang->set(11069);
//                }
            }
        }
        

        $res = DB::table('direct_bkge')
            ->where('id', $id)
            ->update(
                [
                    'register_count' => $register_count,
                    'recharge_count' => $recharge_count,
                    'bkge_increase'  => $bkge_increase,
                    'bkge_increase_week'  => $bkge_increase_week,
                    'bkge_increase_month'  => $bkge_increase_month,
                ]);

        if ($res !== false) {
            $directBkge = new \Logic\Lottery\DirectBkge($this->ci);
            $directBkge->sendMQ();
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};
