<?php
use Logic\Admin\BaseController;
use Model\FundsDealLog;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改付款状态';
    const DESCRIPTION = '强制修改付款状态';

    const QUERY       = [
        'id' => 'int(required) #需要修改的payment id',
    ];

    const PARAMS      = [
        'state' => 'enum[paid,failed,refused](required) # paid:支付成功, failed 支付失败，refused 拒绝支付'
    ];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id)
    {
        $this->checkID($id);
        $status = $this->request->getParam('state');
        $validate = new \lib\validate\BaseValidate([
            'state' => 'in:paid',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);

        $info = \DB::table('funds_withdraw')->find($id);
        if($info) {
            try {
                $user = (new \Logic\User\User($this->ci))->getInfo($info->user_id);
                $this->db->getConnection()->beginTransaction();
                //修改状态
                \DB::table('funds_withdraw')->where('id', '=', $id)
                    ->update(['status' => $status, 'confirm_time' => date('Y-m-d H:i:s'), 'user_type' => 1,
                        'process_uid' => $this->playLoad['uid']]);
                //插入流水
                // 增加资金流水
                $money = \DB::table('funds')->where('id','=',$user['wallet_id'])->value('balance');
                $dealData = array(
                    "user_id" => $info->user_id,
                    "username" => $user['user_name'],
                    "order_number" => $info->trade_no,
                    "deal_type" =>201,
                    "deal_category" => 2,
                    "deal_money" => $info->money,
                    "balance" => $money,
                    "memo" => $this->lang->text('Successful withdrawal'),
                    "wallet_type" => 1
                );
                 FundsDealLog::create($dealData);
                $bank = json_decode($info->receive_bank_info);
                $title = 'Withdrawal to account';
                $content  = ["Dear user, you have received %s yuan from %s on %s. Please check", $info->money / 100,$bank->bank, $info->created];
                //发送消息给客户
                $insertId = (new \Logic\Recharge\Recharge($this->ci))->messageAddByMan($title, $user['name'], $content);
                (new \Logic\Admin\Message($this->ci))->messagePublish($insertId);
                $this->db->getConnection()->commit();
                return $this->lang->set(0);
            } catch (\Exception $e) {
                $this->db->getConnection()->rollback();
            }
        }
        return $this->lang->set(-2);
    }
};
