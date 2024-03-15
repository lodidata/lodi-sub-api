<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '彩种排序';
    const DESCRIPTION = '接口';
    
    const QUERY = [

    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $params = $this->request->getParams();
        $data = $params['data'];
        foreach ($data as $value) {
            $sort = intval($value['sort']);
            $id = intval($value['id']);
            $sql = "update lottery set sort = '{$sort}' where id = '{$id}'";
            DB::update($sql);
        }

        (new Log($this->ci))->create(null, null, Log::MODULE_LOTTERY, '彩种设定', '购彩大厅排序', '编辑', 1, "购彩大厅");

        return [];
    }
};
