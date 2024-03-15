<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/7 13:04
 */

use Logic\Admin\BaseController;
use lib\exception\BaseException;
use Logic\Admin\Log;
use Logic\User\Agent as agentLgoic;
use lib\validate\BaseValidate;

return new class() extends BaseController {
    const TITLE = '修改会员资料';
    const DESCRIPTION = '会员管理--只能修改一部分';
    
    const QUERY = [
        'id' => 'int(required) #会员id',
    ];
    
    const PARAMS = [
        'status' => 'string() #状态',

    ];
    const STATEs = [
//        \Las\Utils\ErrorCode::NO_PERMISSION => '无权限操作'
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = '') {


        $this->checkID($id);

        (new BaseValidate(
            [
                'state'=>'require|in:0,1'

            ]
        ))->paramsCheck('',$this->request,$this->response);

        $state = $this->request->getParsedBodyParam('state');

        $user = \Model\Admin\User::find($id);
        if(!$user)
            return $this->lang->set(10014);

        $user->setTarget($this->playLoad['uid'],$user->name);
        $user->state = $state;
        $res = $user->save();


        if(!$res)
            return $this->lang->set(-2);
        return $this->lang->set(0);
    }
};
