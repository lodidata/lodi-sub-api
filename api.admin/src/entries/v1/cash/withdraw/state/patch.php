<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const STATE       = '';
    const TITLE       = '修改会员出款状态';
    const DESCRIPTION = '修改会员出款信息';
    
    const QUERY       = [
        'id' => 'int(required) #提款申请id'
    ];
    
    const PARAMS      = [
        'status' => 'enum[rejected,refused,paid,prepare,pending](required)  #支付状态，rejected:拒绝,refused:取消, paid:支付, prepare:准备支付, pending:待处理',
        //'uid'=>,
        'role'   => 'int(required) #角色，1 会员，2 代理',
        'comment'   => 'string #拒绝理由'
    ];
    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run($id)
    {
        $this->checkID($id);
        $status = $this->request->getParam('status');
        $role = $this->request->getParam('role');
        $comment = $this->request->getParam('comment');
        $validate = new \lib\validate\BaseValidate([
            'status' => 'in:rejected,refused,prepare',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);

        $info = \DB::table('funds_withdraw')->find($id);
        $user = (new \Logic\User\User($this->ci))->getInfo($info->user_id);
        $user['id'] = $info->user_id;
        try {
            $this->db->getConnection()->beginTransaction();
            //修改状态
            \DB::table('funds_withdraw')->where('id', '=', $id)
                ->update(['status' => $status, 'confirm_time' => date('Y-m-d H:i:s'), 'user_type' => $role,
                    'process_uid' => $this->playLoad['uid'], 'memo' => $comment]);
            $wallet = new \Logic\Wallet\Wallet($this->ci);
            //逻辑
            switch ($status) {
                //逻辑相同，rejected 审核失败   refused提现被拒
                case 'rejected' :
                case 'refused'  :
                    $wallet->addMoney($user, $info->trade_no, $info->money, 118, '提现失败');
                    $title   = 'Withdrawal failed';
                    $content = ["Dear user, your withdrawal of %s yuan on %s was rejected",$info->created, $info->money / 100];
                    if ($comment) {
                        $content = ["Dear user, your withdrawal of %s yuan on %s was rejected, reason %s",$info->created, $info->money / 100, $comment];
                    }
                    \Model\UserData::where('user_id', '=', $info->user_id)
                        ->update(['free_money' => \DB::raw('free_money + ' . $info->money)]);
                    //发送消息给客户
                    $insertId = (new \Logic\Recharge\Recharge($this->ci))->messageAddByMan($title, $user['name'], $content);
                    (new \Logic\Admin\Message($this->ci))->messagePublish($insertId);
                    break;
                    //提现审核通过进入到提现状态
                case 'prepare' :
                    break;
                default:
                    return $this->lang->set(-2);
            }
            $this->db->getConnection()->commit();
            return $this->lang->set(0);
        }catch (\Exception $e){
            $this->db->getConnection()->rollback();
            return $this->lang->set(-2);
        }
    }
};
