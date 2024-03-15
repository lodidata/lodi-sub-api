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

        (new \lib\validate\BaseValidate(
            [
                'type'=>'require|in:qq,wechat',
                'account'=>'require|max:20',
                'name'=>'require|max:20',
                'avatar'=>'require|url',
                'status'=>'require|in:1,2',
            ],
            [],
            [
                'type'=>'账号类型',
                'account'=>'账号',
                'name'=>'昵称',
                'avatar'=>'头像',
                'status'=>'账号状态',
            ]
        ))->paramsCheck('',$this->request,$this->response);


        $params = $this->request->getParams();


        $serviceUser =  ServiceAccount::find($id);
        if(!$serviceUser)
            return $this->lang->set(10014);
        $serviceUser->type = $params['type'];
        $serviceUser->account = $params['account'];
        $serviceUser->name = $params['name'];
        $serviceUser->avatar = $params['avatar'];
        $serviceUser->status = $params['status'];

        $res = $serviceUser->save();

        if(!$res)
            return $this->lang->set(-2);

        return $this->lang->set(0, [], $serviceUser->id);
    }
};