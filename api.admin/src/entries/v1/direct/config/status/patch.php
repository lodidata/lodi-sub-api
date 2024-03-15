<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;

return new class() extends BaseController {

    const TITLE       = '修改直推相关图片状态';
    const DESCRIPTION = '停用、启用';
    const HINT        = '状态：停用、启用';
    const QUERY       = [];

    const PARAMS      = [
        'status'        => 'enum[disabled,enabled](required) #停用 disabled，启用 enabled',
    ];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = null) {

        $this->checkID($id);

        $validate = new BaseValidate([
            'status'  => 'require|in:enabled,disabled',
        ]);

        $validate->paramsCheck('',$this->request,$this->response);

        $param = $this->request->getParams();

        $res = DB::table('direct_imgs')->where('id',$id)->update(['status'=>$param['status']]);

        if($res === false){
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }
};