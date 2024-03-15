<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE = '删除推广返水配置';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $id = $this->request->getParam('id', '');

        if (empty($id)) {
            return $this->lang->set(-2);
        }

        $max_serial_no = DB::table('direct_bkge')->max('serial_no');

        $data = DB::table('direct_bkge')->where('id', $id)->get()->toArray();
        $data = (array)$data[0];

        if ($data['serial_no'] != $max_serial_no) {
            return $this->lang->set(11063);
        }

        $res = DB::table('direct_bkge')->where('id', $id)->delete();

        if ($res !== false) {
            $directBkge = new \Logic\Lottery\DirectBkge($this->ci);
            $directBkge->sendMQ();
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};
