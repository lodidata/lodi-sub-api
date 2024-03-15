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
//    const STATE       = \API::DRAFT;
    const TITLE       = '修改用户/代理银行状态';
    const DESCRIPTION = '会员管理--开启/关闭';
    
    const QUERY       = [
        'id'   => 'int(required) #用户或代理的银行条目id',
        'uid'  => 'int #用户或代理的id',
        'role' => 'int #1 用户，2 代理'
    ];
    
    const PARAMS      = [
        'status' => 'int(required) #true 1 or false 0'
    ];
    const STATEs      = [
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = '')
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

        $this->checkID($id);

        (new BaseValidate([
                'status'=>'require|in:0,1',
            ]
        ))->paramsCheck('',$this->request,$this->response);

        $params = $this->request->getParams();

        $state = ['disabled','enabled'];
        $status = $state[$params['status']];
        $bank = \Model\Admin\BankUser::find($id);
        if (!$bank) {
            return $this->lang->set(10015);
        }
        $user = \Model\User::find($bank->user_id);
        $card = \Utils\Utils::RSADecrypt($bank->card);
        $res = $bank->save([ 'state'=>$status]);

        $logs = new \Model\Admin\LogicModel();
        $logs->setTarget($user->id,$user->name);
        $logs->logs_type = $status == 'enabled' ? '开启' : '停用';
        $logs->opt_desc = '银行卡('.$card.')';
        $logs->log();

        if($res === false){
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }
};
