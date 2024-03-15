<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '手动发放优惠';
    const DESCRIPTION = '';

    const QUERY = [
    ];

    const PARAMS = [
        'uid'       => 'int(required) #用户id',
        'memo'      => 'string() #备注',
        'play_code' => 'int() #打码量',
        'discount'  => 'int() #优惠金额',
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {
        $param = $this->request->getParams();

        $validate = new \lib\validate\BaseValidate([
            'uid'       => 'require|isPositiveInteger',
            'discount'  => 'require|egt:0',
            'play_code' => 'require|egt:0',
        ], [
            'discount'  => '优惠金额必须大于等于0',
            'play_code' => '打码量必须大于等于0',
        ]);
        //超过50W  不允许 加钱  以防出错
        if($param['discount'] > 50000000){
            return $this->lang->set(11021);
        }
        $validate->paramsCheck('', $this->request, $this->response);
        $memo = $param['memo'] ?? 'hand send coupon';
        $re = (new \Logic\Recharge\Recharge($this->ci))->handSendCoupon($param['uid'], $param['play_code'] ?? 0, $param['discount'], $memo, \Utils\Client::getIp(), $this->playLoad['uid']);

        if(!$re){
            return $this->lang->set(-2);
        }

        $user = \Model\Admin\User::find($param['uid']);
        $user->setTarget($user->id,$user->name);
        $user->logs_type = '手动发放优惠';
        $user->opt_desc = "优惠({$param['discount']} / 100)打码量({$param['play_code']} / 100)";
        $user->log();

        return $this->lang->set(0);
    }
};
