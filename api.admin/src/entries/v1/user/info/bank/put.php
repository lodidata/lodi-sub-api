<?php
/**
 * vegas2.0
 *
 * @auth *^-^*<dm>
 * @copyright XLZ CO.
 * @package
 * @date 2017/4/6 18:43
 */


use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use lib\exception\BaseException;
use Logic\Admin\Log;

return new class() extends BaseController {
//    const STATE       = \API::DRAFT;
    const TITLE = '添加/修改会员银行卡';
    const DESCRIPTION = '会员管理--检查是否重复添加，是否超过最大限制';
    
    const QUERY = [
        'id' => 'int() #银行卡id，修改银行卡资料时填写',
    ];
    
    const PARAMS = [
        'card'        => 'string(required) #card no',
        'bank_id'     => 'int(required) #bank id',
        'address'     => 'string(required) #开户行',
        'accountname' => 'string(required) #开户名',
        'role'        => 'int #1 用户，2 代理',
        'uid'         => 'int() #用户id，为用户/代理添加新银行卡时填写',
    ];
    const STATEs = [
//        \Las\Utils\ErrorCode::BEYOND_LIMIT  => '超过银行卡数限额',
//        \Las\Utils\ErrorCode::DATA_EXIST    => '银行卡已存在',
//        \Las\Utils\ErrorCode::NO_PERMISSION => '无权限操作'
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id='') {
        //判断是否有权限修改用户银行卡
        $rid = $this->playLoad['rid'];
        $memberControls = (new Logic\Admin\AdminAuth($this->ci))->getMemberControls($rid);
        if ($memberControls) {
            $privileges = $memberControls['bank_card'];
            if (!$privileges) {
                return $this->lang->set(10401);
            }
        }
        $params = $this->request->getParams();
        // 修改
        (new BaseValidate([
            //  'id'=>'require|isPositiveInteger',
            'bank_id'     => 'require|isPositiveInteger',
            'card'        => 'require|length:5,30',
            'accountname' => 'require|length:2,30',
            'address'     => 'require|length:2,100',
        ],
            [], ['bank_id' => '开户银行', 'card' => '银行卡号', 'accountname' => '开户名', 'address' => '支行信息']
        ))->paramsCheck('', $this->request, $this->response);
        $id = isset($params['id']) ? $params['id'] : $id;
        $bank = \Model\Admin\BankUser::find($id);
        if (!$bank) {
            return $this->lang->set(10015);
        }
        $card = \Utils\Utils::RSADecrypt($bank->card);
        $user = \Model\User::find($bank->user_id);
        $upData = [];
        $upData['bank_id'] = $params['bank_id'];
        $upData['address'] = $params['address'];
        $upData['card'] = \Utils\Utils::matchChinese($params['card']);
        $upData['name'] = $params['accountname'];

        $log_desc = '';
        if($bank->bank_id != $params['bank_id']){
            $old_name = \DB::table('bank')->where('id', $bank->id)->value('code');
            $new_name = \DB::table('bank')->where('id', $params['bank_id'])->value('code');
            $log_desc .= '更换银行：' . $old_name . ' 更换成 ' . $new_name;
        }
        if($bank->address != $params['address']){
            $log_desc .= ' 更换支行：' . $bank->address . ' 更换成 ' . $params['address'];
        }
        if($card != $params['card']){
            $log_desc .= ' 更换卡号：' . $card . ' 更换成 ' . $params['card'];
        }
        if($bank->name != $params['accountname']){
            $log_desc .= ' 更换卡号：' . $bank->name . ' 更换成 ' . $params['accountname'];
        }

        $changes = \Utils\Utils::RSAPatch($upData, \Utils\Encrypt::ENCRYPT);
        $bank->setTarget($user->id,$user->name);
        $res = $bank->save($changes);

        $logs = new \Model\Admin\LogicModel();
        $logs->setTarget($user->id,$user->name);
        $logs->logs_type = '修改';
        $logs->opt_desc = $log_desc;
        $logs->log();

        if ($res === false) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);
    }
};
