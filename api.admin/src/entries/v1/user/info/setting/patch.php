<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/16 14:11
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Auth\Auth as authLogic;
return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '修改会员设置--踢除在线/解除自我限制';
    const DESCRIPTION = '会员管理';

    const QUERY       = [
        'id' => 'int #用户id'
    ];

    const PARAMS      = [
        "online"       => "int(required) #是否在线 0 否，1 是；踢出在线，传0",
        "limit_status" => "int() # 是否自我限制，1 是，0 否",
    ];
    const SCHEMAS = [];


    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id)
    {
        (new BaseValidate([
            'online'=>'require|in:0',
        ]))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

        $res = DB::table('user')->where('id',$id)->update(['online'=>$params['online']]);

        if($res === false){
            return $this->lang->set(-2);
        }

        if ($params['online'] == 0) {

            $authLogic = (new authLogic($this->ci));
            $authLogic->logout($id);
            $authLogic->setOffLine($id, 'user', 7200);

        }

        return $this->lang->set(0);
//        $this->_api->context()->plus = ['uid' => $this->id, 'type' => 1];

    }


};
