<?php

use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;

return new class() extends BaseController
{
//    const STATE       = \API::DRAFT;
    const TITLE       = '新增/修改会员标签';
    const DESCRIPTION = '会员标签--若不存在新增，存在则更新';
    
    const QUERY       = [];
    
    const STATEs      = [
//        \Las\Utils\ErrorCode::DATA_EXIST => '重复标签'
    ];
    const PARAMS      = [
        "account"            => "string #用户账号",
        "password"           => "string #密码",
        "pin_password"       => "pin密码 #密码",
        'bank_name'          => 'string #银行名称',
        "bank_account_name"  => "string #开户名",
        "bank_card"          => "string #银行卡号",
        "account_bank"       => "string #开户行",
        "status"             => "tinyint #审核状态,0 审核中，1 通过，2 拒绝",
        "created"            => "datetime #eg:2017-08-31 02:56:26",
        "updated"            => "datetime #eg:2017-08-31 03:03:32",
    ];
    const SCHEMAS     = [
    ];
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    /**
     * @param null $id 标签id，新增时为空
     * @return bool|mixed
     */
    public function run($id = null)
    {
        $params = $this->request->getParams();

        if($params['id'] == -1){
            //新增时
            $res = DB::table('user_data_review')->insert($params);
        }else{
            //更新时
            $data = DB::table('user_data_review')->find($id);

            if(!$data){
                return $this->lang->set(10015);
            }
            $params['password'] = \Model\User::getPasword($params['password'],$data['salt']);
            /*============================日志操作代码================================*/
            $info = DB::table('user_data_review')->where('id',$id)->find($id);
            /*============================================================*/
            $update_data = [
                'password'          => $params['password'] ?? '',
                'pin_password'      => $params['pin_password'] ?? '',
                'bank_name'         => $params['bank_name'] ?? '',
                'bank_account_name' => $params['bank_account_name'] ?? '',
                'bank_card'         => $params['bank_card'] ?? '',
                'account_bank'      => $params['account_bank'] ?? '',
                'status'            => $params['status'] ?? 0,
                'updated'           => date('Y-m-d H:i:s',time()),
            ];
            $res = DB::table('user_data_review')->where('id',$id)->update($update_data);
        }

        if($res !== false){
            return $this->lang->set(0);
        }

        return $this->lang->set(-2);

    }
};
