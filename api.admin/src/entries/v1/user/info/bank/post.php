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
    const TITLE = '添加会员银行卡';
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

    public function run() {
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
        //添加
        (new BaseValidate([
            'uid'         => 'require|isPositiveInteger',
            'bank_id'     => 'require|isPositiveInteger',
            'card'        => 'require|length:5,50',
            'accountname' => 'require|length:2,100',
            'address'     => 'require|length:2,100',
        ], [],
            ['uid' => '用户id', 'bank_id' => '开户银行', 'card' => '银行卡号', 'accountname' => '开户名', 'address' => '支行信息']
        ))->paramsCheck('', $this->request, $this->response);

        $user = DB::table('user')
                  ->find($params['uid']);
        if (!$user) {
            return $this->lang->set(10014);
        }
        $params['card'] = \Utils\Utils::matchChinese($params['card']);
        (new \Logic\User\Bank($this->ci))->addCard($params['uid'], $params['bank_id'], $params['accountname'], $params['card'], $params['address'], 0, 1);

        $logs = new \Model\Admin\LogicModel();
        $logs->setTarget($params['uid'],$user->name);
        $logs->logs_type = '新增/添加';
        $logs->opt_desc = '银行卡('.$params['card'].')';
        $logs->log();
        return $this->lang->set(0);
    }

};
