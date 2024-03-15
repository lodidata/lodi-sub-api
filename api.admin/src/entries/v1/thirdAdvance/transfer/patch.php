<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Model\FundsDealLog;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '转账记录';
    const DESCRIPTION = '转账记录';

    const QUERY = [
        'id' => 'int(required) #转账记录id',
    ];

    const PARAMS = [
        'status'  => 'enum[rejected,paid](required)  #支付状态，rejected:拒绝, paid:支付',
        'comment' => 'string #拒绝理由',
    ];
    const SCHEMAS = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run(){
        $id = $this->request->getParam('id');
        $status = $this->request->getParam('status');
        $comment = $this->request->getParam('comment');

        $validate = new \lib\validate\BaseValidate([
            'status' => 'in:daifu_fail',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);

        return $this->item_run($id, $status, $comment);
    }

    public function item_run($id, $status, $comment) {
        $admin_id = $this->playLoad['uid'];
        $admin_name = $this->playLoad['nick'];
        try {
            $this->db->getConnection()
                ->beginTransaction();

            $daifu = \DB::table('transfer_order')
                ->where('status', '=', 'pending')
                ->where('id', '=', $id)
                ->first();

            if (empty($daifu)) {
                $this->db->getConnection()
                    ->rollback();
                return $this->lang->set(10517);
            }

            //修改状态
            $update_status = 'failed';
            \DB::table('transfer_order')
                ->where('id', '=', $daifu->id)
                ->where('status', '=', 'pending')
                ->update([
                    'status' => $update_status,
                    'remark' => $comment,
                ]);

            $this->db->getConnection()
                ->commit();

            /*============================日志操作代码================================*/

            $str="代付失败";
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '转账记录', '转账记录', $str, 1, "订单号：{$daifu->trade_no}");
            /*============================================================*/

            return $this->lang->set(0);
        } catch (\Exception $e) {
            $str="代付失败";
            $this->db->getConnection()
                ->rollback();
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '转账记录', '转账记录', $str, 0, "订单号：{$daifu->trade_no}");
            return $this->lang->set(-2);
        }
    }
};
