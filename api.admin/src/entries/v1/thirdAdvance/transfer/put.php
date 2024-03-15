<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '第三方代付转账申请';
    const DESCRIPTION = '第三方代付';

    const QUERY = [

    ];
    
    const PARAMS = [
        'third'          => 'string() #第三主代付ID',
        'bank_code'      => 'string() #银行代号',
        'bank_name'      => 'string() #银行代号',
        'user_name'      => 'string() #户名',
        'bank_card'      => 'string() #卡号',
        'city_code'      => 'string() #城市代号',
        'area'           => 'string() #支行',
        'money'          => 'string() #转账金额',
        'withdraw_order' => 'string() #提现审核中的订单号',
    ];
    const SCHEMAS = [
        "balance" => '申请转账金额',
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run(){
        $third          = $this->request->getParam('third');
        $withdrawOrder  = $this->request->getParam('withdraw_order');
        $order_arr = explode(',',$withdrawOrder);
        if(empty($order_arr)){
            return $this->lang->set(10514);
        }
        if(count($order_arr) == 1){

            $withdraw_info = \DB::table('funds_withdraw')
                ->where('trade_no', '=', $order_arr[0])
                ->where('status', '=', 'obligation')
                ->first();
            if (empty($withdraw_info)) {
                return $this->lang->set(10514);
            }
            return $this->item_run($withdraw_info,$third);
        }else{
            $return = '';
            foreach($order_arr as $val){
                $withdraw_info = \DB::table('funds_withdraw')
                    ->where('trade_no', '=', $val)
                    ->where('status', '=', 'obligation')
                    ->first();
                if (empty($withdraw_info)) {
                    continue;
                }
                $return = $this->item_run($withdraw_info,$third);
            }

            if(empty($return)){
                return $this->lang->set(10514);
            }else{
                return $return;
            }
        }

    }

    public function item_run($withdraw_info,$third) {
        $withdrawOrder = $withdraw_info->trade_no;
        $money = $withdraw_info->money;
        if ($withdraw_info->fee) $money =  bcsub($money,bcmul($money, $withdraw_info->fee / 100,2),2);

        $bank_info = json_decode($withdraw_info->receive_bank_info,true);

        $bankCode       = $bank_info['bank_code'];
        $bankName       = $bank_info['bank'];
        $userName       = $bank_info['name'];
        $bankCard       = \Utils\Utils::RSADecrypt($bank_info['card']);
        $cityCode       = 0;
        $area           = $bank_info['address'];


        /*============================日志操作代码================================*/
        $third_info = \DB::table('transfer_config')
                         ->where('status', '=', 'enabled')
                         ->where('id', '=', $third)
                         ->get()
                         ->first();

        $third_info = (array)$third_info;
        /*=======================================================================*/

        //防止 重复 提交
        if ($this->redis->incr($withdrawOrder) > 1) {
            return $this->lang->set(10516);
        }

        $this->redis->expire($withdrawOrder, 10);

        $third_id = \DB::table('transfer_order')
                       ->where('withdraw_order', '=', $withdrawOrder)
                       ->where('status', '=', 'pending')
                       ->value('id');

        if ($third_id) {
            return $this->lang->set(10514);
        } else {
            $third_id = \DB::table('transfer_order')
                           ->where('withdraw_order', '=', $withdrawOrder)
                           ->where('status', '=', 'paid')
                           ->value('id');

            if ($third_id) {
                /*============================日志操作代码==============================*/
                (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '提现审核', '提现审核', '代付', 1, "订单号：{$withdrawOrder}/第三方支付：{$third_info['name']}/");
                /*=====================================================================*/

                return $this->lang->set(10515);
            }
        }

        $bankName = urldecode($bankName);

        $validate = new \lib\validate\BaseValidate([
            'third'     => 'require|isPositiveInteger',
//            'user_name' => 'require',
//            'bank_card' => 'require',
//            'money'     => 'require',
        ]);

        $validate->paramsCheck('', $this->request, $this->response);

        $result = (new \Logic\Transfer\ThirdTransfer($this->ci))->thirdTransfer(
            $third,
            $userName,
            $bankCard,
            $bankCode,
            $bankName,
            $area?: '',
            $cityCode,
            $money,
            $withdrawOrder
        );

        //更新操作人员ID
        if($withdrawOrder && $result['code'] == 10500){
            \DB::table('funds_withdraw')
                ->where('trade_no', '=', $withdrawOrder)
                ->update(['process_uid' => $this->playLoad['uid']]);
        }
        /*============================日志操作代码================================*/
        (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方代付', '发起转账', '提交转账', 1, "第三方支付：{$third_info['name']}/收款人姓名：{$userName}/收款银行:{$bankName}/收款账号：{$bankCard}/提现单号：{$withdrawOrder}/转账金额[" . ($money / 100) . "]");
        /*=======================================================================*/

        return $this->lang->set($result['code'], $result['msg'], ['balance' => $result['balance']]);
    }
};
