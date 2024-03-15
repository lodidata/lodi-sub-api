<?php

namespace Las\Pay;

namespace Logic\Recharge\Pay;

use Logic\Recharge\Bases;
use Logic\Recharge\Recharge;

/**
 *
 * 线上银行卡支付
 * payment.socialgame.xyz
 * @author
 */
class AUTOTOPUP extends BASES
{
    public $http_code;
    public $action;

    static function instantiation()
    {
        return new AUTOTOPUP();
    }

    public function config()
    {
        $config = Recharge::getThirdConfig('autotopup');
        $this->key = $config['key'];
        $this->payUrl = $config['payurl'];
    }

    //与第三方交互
    public function start()
    {
        $this->return['code'] = '886';
        //第一步获取用户信息
        $userInfo = \DB::table('bank_user')
            ->leftjoin('bank', 'bank_user.bank_id', '=', 'bank.id')
            ->leftjoin('user', 'bank_user.user_id', '=', 'user.id')
            ->where('bank_user.role', 1)
            ->where('bank_user.user_id', $this->userId)
            ->selectRaw('bank_user.card, user.name as username, bank.code AS bank_code, user.mobile')
            ->get()
            ->toArray();
        if (empty($userInfo)) {
            $this->return['msg'] = 'user error';
            return false;
        }
        if (!empty($userInfo)) {
            $userInfo = \Utils\Utils::RSAPatch($userInfo);
        }

        $userInfo = $userInfo[0];
        //$userInfo['username'] = 'TEST';
        //var_dump($userInfo);
        //检验用户是否注册第三方
        //注册第三方
        $reg_res = $this->register($userInfo);
        if (!$reg_res) {
            $this->return['msg'] = $this->re['message']['message'];
            $this->return['str'] = $this->re;
            return false;
        }

        //发起支付
        $userInfo['amount'] = bcdiv($this->money, 100, 0);
        $resdep = $this->depositDecimal($userInfo);
        if (!$resdep) {
            $this->return['msg'] = $this->re['message']['message'];
            $this->return['str'] = $this->re;
            return false;
        }

        //查银行图片
        $resdep['logo'] = \Model\Bank::where('code', $resdep['bank_code'])->value('h5_logo');

        //发起支付成功
        $this->return['code'] = 0;
        $this->return['msg'] = 'success';
        $this->return['way'] = 'json';
        $this->return['str'] = $resdep;
        $this->return['pay_no'] = $resdep['tx_id'] ?? '';
        $this->return['fee'] = $resdep['amount'] * 100 - $this->money;

        //存返回金额为支付金额
        $this->return['money'] = $resdep['amount'] * 100;
    }

    /**
     * 注册会员 101 已存在用户
     * @param array $info
     * @return bool
     */
    public function register($info)
    {
        $this->config();
        // return true;
        $fields = [
            'username' => $info['username'],
            'phone_number' => $info['mobile'],
            'password' => 'Aa123456',
            'bank_number' => $info['card'],
            'bank_code' => $info['bank_code'],
        ];

        $this->parameter = $fields;
        $this->action = '/auto/register';
        $this->formPost();
        $res = $this->re;
        if ($res['status']['code'] == 200 && ($res['message']['code'] == 0) || $res['message']['code'] == 101) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加会员银行卡
     * @param $info
     * @return bool
     */
    public function addBank($info)
    {
        $fields = [
            'username' => $info['username'],
            'bank_number' => $info['card'],
            'bank_code' => $info['bank_code'],
        ];

        $this->parameter = $fields;
        $this->action = '/auto/add_bank';
        $res = $this->formPost();
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改会员银行卡
     * @param $info
     * @return bool
     */
    public function editBank($info)
    {
        $this->config();
        $fields = [
            'username' => $info['username'],
            'bank_number_old' => $info['old_bank_number'],
            'bank_number' => $info['card'],
            'bank_code' => $info['bank_code'],
        ];

        $this->parameter = $fields;
        $this->action = '/auto/edit_bank';
        $this->formPost();
        $res = $this->re;
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 返回自动存款账号
     * @param $info
     * @return bool
     */
    public function bankAuto($info)
    {
        $this->config();
        $fields = [
            'username' => $info['username'],
        ];

        $this->parameter = $fields;
        $this->action = '/auto/bank/auto';
        $this->formPost();
        $res = $this->re;
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            return $res['data']['auto'];
        } else {
            return false;
        }
    }

    /**
     * 查看真实钱包存款帐号
     * @param $info
     * @return mixed
     */
    public function truewallet($info)
    {
        $fields = [
            'username' => $info['username'],
        ];

        $this->parameter = $fields;
        $this->action = '/auto/bank/truewallet';
        $this->formPost();
        $res = $this->re;
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 支付申请
     * "data": {
     * "tx_id": "621366dd72996cdbb1a554bf",
     * "amount": 11.48,
     * "bank_name": "TEST",
     * "number": "0684015613",
     * "bank_code": "SCB",
     * "start_date": "2022-02-21T17:18:05.735724857+07:00",
     * "end_date": "2022-02-21T17:19:05.735724921+07:00"
     * },
     * @param $info
     * @return bool
     */
    public function depositDecimal($info)
    {
        $fields = [
            'username' => $info['username'],
            'amount' => $info['amount']
        ];

        $this->parameter = $fields;
        $this->action = '/auto/deposit_decimal';
        $this->formPost();
        $res = $this->re;
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            return $res['data'];
        } else {
            return false;
        }
    }

    /**
     * 提现申请
     * @param $info
     * @return bool
     */
    public function withdraw($info)
    {
        $this->config();
        $fields = [
            'username' => $info['username'],
            'current_credit' => $info['current_credit'],
            'amount' => $info['amount'],
            'bank_number' => $info['card']
        ];

        $this->parameter = $fields;
        $this->action = '/auto/withdraw';
        $this->formPost();
        $res = $this->re;
        if ($res['status']['code'] == 200 && $res['message']['code'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function formPost()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_URL, $this->payUrl . $this->action);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->parameter));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'apikey:' . $this->key
        ]);
        $response = curl_exec($ch);
        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->re = json_decode($response, true);

        $date = [
            'order_number' => $this->orderID,
            'pay_url' => $this->payUrl . $this->action,
            'json' => json_encode($this->parameter, JSON_UNESCAPED_UNICODE),
            'response' => $response,
            'date' => date('Y-m-d H:i:s')
        ];
        Recharge::addLog($date, 'pay_request_third_log');
    }


    //回调数据校验
    /*
     * $resArray 第三方通知数组
     * $deposit  系统流水信息
     * $vender   授权信息
     * */
    public function returnVerify($param = [])
    {
        $res = [
            'status' => 0,
            'order_number' => $param['order_number'],
            'third_order' => $param['tx_id'],
            'third_money' => bcmul($param['amount'] ,100, 0),
            'third_fee' => 0,
            'memo' => json_encode($param, JSON_UNESCAPED_UNICODE),
            'error' => '',
        ];

        if ($param['status'] == 1) {
            $res['status'] = 1;
        } else {
            throw new \Exception('unpaid');
        }

        return $res;
    }

    /**
     * 补单
     * 订单状态 0未支付,1已支付,2超时,4撤销,5未认领
     * @param $order_number
     * @return mixed
     */
    public function supplyOrder($order_number, $payNo = '')
    {
        throw new \Exception('无此功能');
    }
}
