<?php
use Logic\Admin\BaseController;
use Model\Admin\ServiceAccount;
return new class extends BaseController {

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = '') {

        $this->checkID($id);

        $serviceUser =  ServiceAccount::find($id);
        if(!$serviceUser)
            return $this->lang->set(10014);

        $res = $serviceUser->delete();

        if(!$res)
            return $this->lang->set(-2);

        return $this->lang->set(0);
    }
};