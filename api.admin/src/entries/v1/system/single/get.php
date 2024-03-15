<?php

use Logic\Admin\BaseController;
use Illuminate\Database\Capsule\Manager as DB;

return new class() extends BaseController {
    const TITLE       = '设置';
    const QUERY       = [];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
//        'authorize',
    ];

    public function run() {

        (new \lib\validate\BaseValidate(
            [
                'module'=>'require',
                'key'=>'require',
            ],
            [],
            [
                'module'=>'模块',
                'key'=>'设置',
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

        $res = DB::table('system_config')->where('module',$params['module'])->where('key',$params['key'])->first();
        if(!$res)
            return $this->lang->lang->set(10015);

        return (array)$res;
    }
};
