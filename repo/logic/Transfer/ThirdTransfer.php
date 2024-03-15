<?php

namespace Logic\Transfer;

/*
 * 第三方代付入口类 @author viva
 *
 */

use Logic\Logic;
use Model\Admin\BankUser;
use Model\FundsDealLog;
use Utils\Telegram;

class ThirdTransfer extends Logic {

    private $ThirdClass = 'Logic\Transfer\ThirdParty';
    public $return = [
        'code'    => 0,     //统一 0 为OK
        'balance' => 0,     //统一 0 为OK
        'msg'     => ['SUCCESS'],     //统一
    ];

    /**
     * @param $thirdId 第三方代付ID
     * @param $bankUserName 银行卡户名
     * @param $bankCard 银行卡号
     * @param $bankCode 银行代号
     * @param $bankName
     * @param $area 所属支行
     * @param $cityCode 城市代号
     * @param $money 转账金额，分
     * @param string $withdrawOrder
     * @param string $fee 0转账金额扣除，1余额扣除
     * @param string $type 0普通，1加急
     * @param string $memo 备注
     *
     * @return array
     */
    public function thirdTransfer($thirdId, $bankUserName, $bankCard, $bankCode, $bankName, $area, $cityCode, $money, $withdrawOrder = '', $fee = '1', $type = '0', $memo = '')
    {
        //第三方代付配置数据
        $thirdWay = (array)\DB::table('transfer_config')
                              ->find($thirdId);
        $config_params = !empty($thirdWay['params']) ? json_decode($thirdWay['params'],true) : [];

        if ($thirdWay && $this->verifyThirdWay($thirdWay)) {
            if (($money <= $thirdWay['max_money'] && $money >= $thirdWay['min_money']) ||
                ($thirdWay['max_money'] == 0 && $money >= $thirdWay['min_money'])) {
                if ($bankCode) {
                    $bank = json_decode($thirdWay['bank_list'], true)[$bankCode] ?? '';

                    $bankName = \DB::table('bank')
                                   ->where('code', '=', $bank)
                                   ->value('name');
                } else {
                    $tmpCode = \DB::table('bank')
                                  ->where('name', '=', $bankName)
                                  ->value('code');

                    $bankCode = array_search($tmpCode, json_decode($thirdWay['bank_list'], true), true);
                }

                //转账订单生成
                if ($bankName && $bankCode) {
                    try {
                        $transferOrder['third_id'] = $thirdId;
                        $transferOrder['trade_no'] = date('mdhis') .
                                                     str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
                        $transferOrder['withdraw_order'] = $withdrawOrder;
                        $transferOrder['money'] = $money;
                        $transferOrder['fee'] = $fee;
                        $transferOrder['fee_way'] = $fee;
                        $transferOrder['tran_type'] = $type;
                        $transferOrder['receive_bank'] = $bankName;
                        $transferOrder['receive_bank_code'] = $bankCode;
                        $transferOrder['receive_bank_card'] = $bankCard;
                        $transferOrder['receive_user_name'] = $bankUserName;
                        $transferOrder['receive_bank_area'] = $area;
                        $transferOrder['city_code'] = $cityCode;
                        $transferOrder['status'] = 'pending';
                        $transferOrder['memo'] = $memo;
                        $transferOrder['created_uid'] = $GLOBALS['playLoad']['uid'];
                        $transferOrder['created_name'] = $GLOBALS['playLoad']['nick'];

                        //根据卡号查用户 poppay
                        if(isset($config_params['checkCard']) && $config_params['checkCard'] == 1){
                            $user_card = \Utils\Utils::RSAEncrypt($bankCard);
                            $user_id = \Model\Admin\BankUser::where('card', $user_card)->value('user_id');
                            if(!$user_id){
                                throw new \Exception('银行卡未关联会员');
                            }
                            $userInfo = \Model\User::where('id', $user_id)->select(['name','mobile'])->get()->toArray();
                            $transferOrder['user_id'] = $user_id;
                            $transferOrder['user_name'] = $userInfo[0]['name'];
                            $transferOrder['user_mobile'] = \Utils\Utils::RSADecrypt($userInfo[0]['mobile']);
                        }

                        $className = "{$this->ThirdClass}\\" . $thirdWay['code'];
                        $obj = new $className;   //初始化类
                        $obj->init($thirdWay, $transferOrder, true); //初始化数据
                        $obj->runTransfer();      //开始申请代付转账
                        $re = $obj->returnResult();   // 返回相应数据

                        $this->return['balance'] = $re['balance'];
                        $this->return['code'] = $re['code'];
                        $this->return['msg'] = [$re['msg']];
                    } catch (\Exception $e) {   //第三方代付请求过程有错，请联系技术人员
                        $this->return['msg'] = [$e->getMessage()];
                        $this->return['code'] = 886;
                    }
                } else {  //该第三方代付不支持转到该行的
                    $this->return['code'] = 10502;
                }
            } else {//该第三方代付转账金额限制
                $this->return['code'] = 10504;
                $this->return['msg'] = [$thirdWay['min_money']/100, $thirdWay['max_money']/100];
            }
        } else {
            //未有该第三方代付或第三方代付配置数据不完整
            $this->return['code'] = 10501;
        }

        return $this->return;
    }

    //第三方配置验证所需字段
    public function verifyThirdWay($thirdConfig)
    {
        $verifyParam = [   //与数据库字段对应
                           'code',
                           'key',
                           'pub_key',
                           'partner_id',
                           'bank_list',
                           'request_url',
        ];
        foreach ($verifyParam as $v) {
            if (!$thirdConfig[$v]) {
                return false;
            }
        }
        return true;
    }

    /*
     * 第三方查询账户余额
     * @return ['code'=>0,'balance'=>88,'msg'=>'' ]  0 查询成功  失败886  msg 错误信息
     */
    public function getThirdBalance($thirdId)
    {
        //第三方代付配置数据
        $thirdWay = (array)\DB::table('transfer_config')
                              ->find($thirdId);

        if ($thirdWay) {
            $className = "{$this->ThirdClass}\\" . $thirdWay['code'];
            if(!class_exists($className)){
                return ['code' => 10511, 'transfer' => '', 'msg' => $className.'类不存在'];
            }
            $obj = new $className;   //初始化类
            $obj->init($thirdWay); //初始化数据
            $obj->getThirdBalance();
            return $obj->returnResult();
        }
        return ['code' => 10511, 'balance' => 0, 'msg' => ''];
    }

    /**
     * kpay
     */
    public function sendLodiPayUserAmount($trade_no,$transfer_no,$transfer_no_sub,$status,$user_id)
    {
        $order = (array)\DB::table('transfer_order')
                        ->where(['withdraw_order'=>$trade_no,'status'=>'pending'])
                        ->first();
        if (count($order)>0) {
            $thirdWay = (array)\DB::table('transfer_config')
                        ->where(['code'=>'KPAY'])
                        ->first();
            if (count($thirdWay)>0) {
                $className = "{$this->ThirdClass}\\" . 'KPAY';
                if(!class_exists($className)){
                    return ['code' => 10511, 'msg' => $className.'类不存在'];
                }
                $obj = new $className;   //初始化类
                $order['transfer_no']=$transfer_no;
                $order['trade_no']=$order['withdraw_order'];
                $obj->init($thirdWay,$order); //初始化数据
                return $obj->submitOrderStatus($transfer_no_sub,$status,$user_id);
            }
            return ['code' => 10511, 'transfer' => '', 'msg' => ''];
        }
        return ['code' => 10513, 'msg' => ''];
    }

    /*
     * 第三方查询代付结果
     * @return ['code'=>0,transfer=>'success','msg'=>'']
     * 0 查询成功  代付转账成功success 失败failed  886查询失败  msg 众失败原因
     */
    public function getTransferResult($id)
    {
        //第三方代付配置数据
        $order = (array)\DB::table('transfer_order')
                           ->find($id);
        if ($order) {
            $thirdWay = (array)\DB::table('transfer_config')
                                  ->find($order['third_id']);
            if ($thirdWay) {
                $className = "{$this->ThirdClass}\\" . $thirdWay['code'];
                if(!class_exists($className)){
                    return ['code' => 10511, 'transfer' => '', 'msg' => $className.'类不存在'];
                }
                $obj = new $className;   //初始化类
                $obj->init($thirdWay, $order); //初始化数据
                $obj->getTransferResult();
                return $obj->returnResult();
            }
            return ['code' => 10511, 'transfer' => '', 'msg' => ''];
        }
        return ['code' => 10513, 'transfer' => '', 'msg' => ''];
    }

    public static function updateWithdrawOrder($withdraw_order, $status)
    {
        global $app;
        $withdraw = \DB::table('funds_withdraw')
                       ->where('trade_no', '=', $withdraw_order)
                       ->first();
        if ($withdraw) {
            if (in_array($withdraw->status, ['prepare', 'pending', 'obligation'])) {   //prepare:准备支付, pending:待处理
                if ($status == 'paid') {
                    try{
                    //修改状态
                    $res = \DB::table('funds_withdraw')
                       ->where('trade_no', '=', $withdraw_order)
                        ->where('status',$withdraw->status)
                       ->update(
                           [
                               'status'       => $status,
                               'confirm_time' => date('Y-m-d H:i:s'),
                               //'process_uid'  => 0,
                               'memo'         => $app->getContainer()->lang->text("Successful payment"),
                           ]
                       );
                    //更新失败 就直接返回
                    if(!$res){
                        $app->getContainer()->logger->error('ThirdTransfer res.' . $res, (array)$withdraw);
                        return;
                    }


                    $user = (new \Logic\User\User($app->getContainer()))->getInfo($withdraw->user_id);
                    $funds =(array) \DB::table('funds')
                                ->where('id', '=', $user['wallet_id'])
                                ->first();
                    $bank = json_decode($withdraw->receive_bank_info);

                    //股东钱包区分
                    if($withdraw->type == 2){
                        $dealType=FundsDealLog::TYPE_SHARE_WITHDRAW;
                        $balance=$funds['share_balance'];
                        $title = "Successful withdrawal";
                        $content = "Successful withdrawal share";
                    }else{
                        //流水里面添加打码量可提余额等信息
                        $dml = new \Logic\Wallet\Dml($app->getContainer());
//                        $dmlData = $dml->getUserDmlData($withdraw->user_id, $withdraw->money, 3);
                        //线上代付回调成功时，$opt传3会再扣一次user_data表的free_money,因此参数$opt不传值
                        $dmlData = $dml->getUserDmlData($withdraw->user_id, $withdraw->money);
                        //添加存款总笔数，总金额
                        \Model\UserData::where('user_id', $withdraw->user_id)
                                       ->increment(
                                           'withdraw_amount', $withdraw->money,
                                           ['withdraw_num' => \DB::raw('withdraw_num + 1')]
                                       );
                        $dealType=FundsDealLog::TYPE_WITHDRAW;
                        $balance=$funds['balance'];
                        $title    = "Withdrawal to account";
                        $content  = ["Dear user, you have received %s yuan from %s on %s. Please check", $withdraw->money / 100, $bank->bank, $withdraw->created];
                    }



                    //拿到操作者
                    $opts = \DB::table('transfer_order')
                               ->where('withdraw_order', '=', $withdraw_order)
                               ->first(['created_uid', 'created_name']);
                    if ($opts) {
                        $admin_id = $opts->created_uid ?? 0;
                        $admin_name = $opts->created_name ?? '';
                    } else {
                        $admin_id = 0;
                        $admin_name = '';
                    }

                    //代付成功更新状态
                    $dealData = [
                        "user_id"           => $withdraw->user_id,
                        "username"          => $user['user_name'],
                        "order_number"      => $withdraw->trade_no,
                        "deal_type"         => $dealType,
                        "deal_category"     => 2,
                        "deal_money"        => $withdraw->money,
                        "balance"           => $balance,
                        "memo"              => $app->getContainer()->lang->text("Successful withdrawal"),
                        "wallet_type"       => 1,
                        'total_bet'         => $dmlData->total_bet ??0,
                        'withdraw_bet'      => 0,
                        'total_require_bet' => $dmlData->total_require_bet ??0,
                        'free_money'        => $dmlData->free_money ??$balance,
                        'admin_user'        => $admin_name,
                        'admin_id'          => $admin_id,
                    ];
                    FundsDealLog::create($dealData);


                    //发送消息给客户
                    $insertId = (new \Logic\Recharge\Recharge($app->getContainer()))->messageAddByMan(
                        $title, $user['user_name'], $content
                    );
                    (new \Logic\Admin\Message($app->getContainer()))->messagePublish($insertId);

                    } catch (\Throwable $e){
                        $app->getContainer()->logger->error('ThirdTransfer ' . $e->getMessage());
                    }
                } else {
                    //修改状态
                    \DB::table('funds_withdraw')
                       ->where('trade_no', '=', $withdraw_order)
                       ->update(
                           [
                               'confirm_time' => date('Y-m-d H:i:s'),
                               //'process_uid'  => 0,
                               'memo'         => $app->getContainer()->lang->text('Payment failed'),
                           ]
                       );
                }
            }
        }
    }

    /*
     * 第三方查询代付异步回调
     * @return ['code'=>0,'msg'=>'']
     * 0 成功   886失败  msg 众失败原因
     */
    public function callbackResult($orderNo, $params)
    {
        $website_name = $this->ci->get('settings')['website']['name'];
        //第三方代付配置数据
        $order = (array)\DB::table('transfer_order')
            ->where('trade_no', $orderNo)
            ->first();
        if ($order) {
            $thirdWay = (array)\DB::table('transfer_config')
                ->find($order['third_id']);
            if ($thirdWay) {
                $className = "{$this->ThirdClass}\\" . $thirdWay['code'];
                $obj = new $className;   //初始化类
                $obj->init($thirdWay, $order); //初始化数据
                $obj->callbackResult($params);
                return $obj->returnResult();
            }else{
                $content = "【".$website_name."】代付回调：".PHP_EOL;
                $content .= 'pay type error'  . PHP_EOL;
                $content .= '代付单号：' . $orderNo . PHP_EOL;
                $content .= '参数：' . json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                $content .= '警告时间：' . date('Y-m-d H:i:s');
                Telegram::sendMiddleOrdersMsg($content);
                return ['code' => '886', 'msg' => 'pay type error'];
            }
        }else{
            $content = "【".$website_name."】代付回调：".PHP_EOL;
            $content .= 'order error'  . PHP_EOL;
            $content .= '代付单号：' . $orderNo . PHP_EOL;
            $content .= '参数：' . json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            $content .= '警告时间：' . date('Y-m-d H:i:s');
            Telegram::sendMiddleOrdersMsg($content);
            return ['code' => '886', 'msg' => 'order error'];
        }
    }

    /*
     * 第三方查询代付异步回调
     */
    public function anotherCallbackResult($orderNo, $params)
    {
        $website_name = $this->ci->get('settings')['website']['name'];
        //第三方代付配置数据
        $order = (array)\DB::table('transfer_order')
            ->where('trade_no', $orderNo)
            ->first();
        if ($order) {
            $thirdWay = (array)\DB::table('transfer_config')
                ->find($order['third_id']);
            if ($thirdWay) {
                $className = "{$this->ThirdClass}\\" . $thirdWay['code'];
                $obj       = new $className;   //初始化类

                $obj->init($thirdWay, $order); //初始化数据
                $obj->callbackResult($params);
                return $obj->returnResult();
            }else{
                $content = "【".$website_name."】代付回调：".PHP_EOL;
                $content .= 'pay type error'  . PHP_EOL;
                $content .= '代付单号：' . $orderNo . PHP_EOL;
                $content .= '参数：' . json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                $content .= '警告时间：' . date('Y-m-d H:i:s');
                Telegram::sendMiddleOrdersMsg($content);
                throw new \Exception('pay type error');
            }
        }
        $content = "【".$website_name."】代付回调：".PHP_EOL;
        $content .= 'order error'  . PHP_EOL;
        $content .= '代付单号：' . $orderNo . PHP_EOL;
        $content .= '参数：' . json_encode($params, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $content .= '警告时间：' . date('Y-m-d H:i:s');
        Telegram::sendMiddleOrdersMsg($content);
        throw new \Exception('order error');
    }

}