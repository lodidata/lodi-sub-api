<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */

use Logic\Admin\Active as activeLogic;
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Wallet\Wallet;
use Model\Admin\Message;
use Model\FundsDealLog;

return new class() extends BaseController {

    const TITLE = '用户活动申请审批操作';
    const QUERY = [
        'id' => 'int #申请id'
    ];

    const PARAMS = [
        'status' => 'int #操作方式 0拒绝 1通过'
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id)
    {

        $this->checkID($id);

        $validate = new BaseValidate([
            'status' => 'in:0,1',
        ]);

        $validate->paramsCheck('', $this->request, $this->response);

        $params = $this->request->getParams();
        $active = (array)DB::table('active_apply')->find($id);
        if (!$active) {
            return $this->lang->set(10015);
        }
        $status = $params['status'];

        if (isset($GLOBALS['playLoad'])) {
            $admin_id = $GLOBALS['playLoad']['uid'];
            $admin_name = $GLOBALS['playLoad']['nick'];
        } else {
            $admin_id = 0;
            $admin_name = '';
        }
        $user = (new \Logic\User\User($this->ci))->getUserInfo($active['user_id']);

        //拒绝用户申请
        if (!$status) {
            if (empty($params['content'])) {
                return $this->lang->set(4203);
            }
            $res = DB::table('active_apply')
                ->where('id', $id)
                ->update(['memo' => $params['content'], 'status' => 'rejected', 'created_uid' => $admin_id, 'process_time' => date('Y-m-d H:i:s')]);
            //拒绝消息通知
            $title = 'Active apply rejected news';
            $content = ["Your apply active %s rejectd,reason %s", $active['active_name'], $params['content']];
        } else { //通过用户申请
            if (empty($params['money']) || empty($params['user_dml'])) {
                return $this->lang->set(4204);
            }

            $money = bcmul($params['money'], 100, 0);
            $userDml = bcmul($params['user_dml'], 100, 0);

            try {
                $this->db->getConnection()
                    ->beginTransaction();

                //添加打码量可提余额等信息
                $dml = new \Logic\Wallet\Dml($this->ci);
                $dmlData = $dml->getUserDmlData($active['user_id'], (int)$userDml, 2);

                // 锁定钱包
                $oldFunds = \Model\Funds::where('id', $user['wallet_id'])
                    ->lockForUpdate()
                    ->first();
                (new Wallet($this->ci))->crease($user['wallet_id'], $money);

                $balance = \DB::table('funds')
                    ->where('id', '=', $user['wallet_id'])
                    ->value('balance');

                //添加资金流水
                $dealData = ([
                    'user_id' => $active['user_id'],
                    'user_type' => 1,
                    'username' => $user['user_name'],
                    'order_number' => date('YmdHis') . rand(pow(10, 3), pow(10, 4) - 1),
                    'deal_type' => FundsDealLog::TYPE_ACTIVITY,
                    'deal_category' => FundsDealLog::CATEGORY_INCOME,
                    'deal_money' => $money,
                    'coupon_money' => 0,
                    'balance' => $balance,
                    'memo' => $this->lang->text("join user apply active[%s]", [$active['active_name']]),
                    'wallet_type' => 1,
                    'total_bet' => $dmlData->total_bet,
                    'withdraw_bet' => $userDml,
                    'total_require_bet' => $dmlData->total_require_bet,
                    'free_money' => $dmlData->free_money,
                    'admin_id' => $admin_id,
                    'admin_user' => $admin_name,
                ]);
                FundsDealLog::create($dealData);

                //更新活动申请状态
                $res = DB::table('active_apply')
                    ->where('id', $id)
                    ->update(['memo' => '赠送金额' . bcdiv($money,100,0), 'status' => 'pass', 'created_uid' => $admin_id, 'process_time' => date('Y-m-d H:i:s')]);
                //通过消息通知
                $title = 'Active apply pass news';
                $content = ["Your apply active %s pass,reward %s", $active['active_name'], bcdiv($money,100,0)];

                $this->db->getConnection()
                    ->commit();
            } catch (\Exception $e) {
                $this->db->getConnection()
                    ->rollback();
                $res = false;
            }
        }
        if ($res === false) {
            return $this->lang->set(-2);
        }

        //处理成功发送消息
        $content = json_encode($content);
        $insertId = Message::insertGetId([
            'send_type' => 3,
            'title' => $title,
            'admin_uid' => $admin_id,
            'admin_name' => $admin_name,
            'recipient' => $user['user_name'],
            'user_id' => $active['user_id'],
            'type' => 2,
            'status' => 1,
            'content' => $content,
            'created' => time(),
            'updated' => time(),
        ]);
        \Utils\MQServer::send('user_message', ['filed' => 'id', 'inArray' => [$active['user_id']], 'type' => 1, 'm_id' => $insertId]);

        return $this->lang->set(0);
    }
};