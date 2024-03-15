<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/6 18:53
 */

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE       = '删除用户银行卡';
    const DESCRIPTION = '删除用户银行卡';
    
    const QUERY       = [
        'id'   => 'int(required) #银行条目id',
    ];
    
    const PARAMS      = [
        'id'   => 'int(required) #银行条目id',
    ];
    const STATEs      = [
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id)
    {
        //判断是否有权限修改用户银行卡
        $rid            = $this->playLoad['rid'];
        $memberControls = (new Logic\Admin\AdminAuth($this->ci))->getMemberControls($rid);
        if ($memberControls) {
            $privileges = $memberControls['bank_card'];
            if (!$privileges) {
                return $this->lang->set(10401);
            }
        }

        $bank = \Model\Admin\BankUser::find($id);
        $user = \Model\User::find($bank->user_id);
        $card = \Utils\Utils::RSADecrypt($bank->card);
        $res = $bank->save([ 'state'=>'delete']);

        $logs = new \Model\Admin\LogicModel();
        $logs->setTarget($user->id,$user->name);
        $logs->logs_type = '删除';
        $logs->opt_desc = '银行卡('.$card.')';
        $logs->log();
        if($res === false){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }
};
