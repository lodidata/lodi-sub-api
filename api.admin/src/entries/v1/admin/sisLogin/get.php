<?php

use Logic\Admin\BaseController;

return new class extends BaseController {
    const TITLE = "以token获取权限";
    const HINT = "";
    const DESCRIPTION = "";

    const PARAMS = [
        "token"  => "string(required) #name+jurisdiction base64位编码再md5加密",
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run() {
        return $this->superAuthList();
    }
};
