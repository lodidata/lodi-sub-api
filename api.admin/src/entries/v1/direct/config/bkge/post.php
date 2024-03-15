<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE = '添加推广返水配置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $register_count = $this->request->getParam('register_count', '');
        $recharge_count = $this->request->getParam('recharge_count', '');
        $bkge_increase  = (float)$this->request->getParam('bkge_increase', '');
        $bkge_increase_week  = (float)$this->request->getParam('bkge_increase_week', '');
        $bkge_increase_month  = (float)$this->request->getParam('bkge_increase_month', '');

        if (empty($register_count) || empty($recharge_count)) {
            return $this->lang->set(-2);
        }

        $max_serial_no = DB::table('direct_bkge')->max('serial_no');

        $max_no_data = DB::table('direct_bkge')->where('serial_no', $max_serial_no)->get()->toArray();

        if($max_no_data){
            $max_no_data = (array)$max_no_data[0];
            if ($register_count <= $max_no_data['register_count']) {
                return $this->lang->set(11060);
            }

            if ($recharge_count <= $max_no_data['recharge_count']) {
                return $this->lang->set(11061);
            }

//            if ($bkge_increase <= $max_no_data['bkge_increase']) {
//                return $this->lang->set(11062);
//            }
        }


        $res = DB::table('direct_bkge')
            ->insert(
                [
                    'register_count' => $register_count,
                    'recharge_count' => $recharge_count,
                    'bkge_increase'  => $bkge_increase,
                    'serial_no'      => $max_serial_no + 1,
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
