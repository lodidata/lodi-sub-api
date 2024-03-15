<?php

use Utils\Www\Action;
use Logic\Level\Level as levelLogic;

return new class extends Action {
    const TOKEN = true;
    const TITLE = '获取线上支付通道';
    const DESCRIPTION = '金额限制都按分来比较';
    const TAGS = "充值提现";
    const QUERY = [
        'type'  => 'int(required) #支付方式 1 bigpay',
        'money' => 'int(required) #支付金额 单位分',
    ];
    const SCHEMAS = [
            [
                'id'          => 'int(required) #支付通道ID',
                'name'        => 'string(required) #通道名称',
                'pay_id'      => 'int(required) #渠道ID',
                'pay_scene'   => 'string(required) #类型',
                'pay_way'     => 'string(required) #支付方式中文',
                'pay_way_str' => 'string(required) #支付方式str',
                'max_money'   => 'int(required) #最大值',  //分
                'min_money'   => 'int(required) #最小值',   //分
                'comment'     => 'string(required) #备注',   //分
                'bank_list'   => 'string(,1) #银行类型该参数才有效，为1代表必须跳转到相应银行列表选择相应银行',
                'bank_data'   => [
                    [
                        'code'     => '(required) #银行代码',
                        'logo'     => 'string(required) #LOGO',
                        'name'     => 'string(required) #银行名',
                        'pay_code' => 'string(required) #支付值（请求支付时传该参数）',
                    ],
                ],
            ],
    ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $req = $this->request->getParams();
        $pay_type = $req['pay_type'] ?? '';

        //获取用户等级所支持的充值渠道
        $user = (new \Logic\User\User($this->ci))->getUserInfo($this->auth->getUserId());
        $levelLogic = new levelLogic($this->ci);
        $use_pay_list = $levelLogic->getLevelOnline($user['ranting']);

        /*if (!in_array($type, array_values($types)) || empty($type)) {
            return $this->lang->set(13);
        }*/
        /*if (empty($type_name)) {
            return $this->lang->set(13);
        }*/

        //线上充值限额，第三方及平台综合
        $pay = new \Logic\Recharge\Pay($this->ci);
        $payConfig = $pay->getOnlinePassageway() ?? [];
        if (empty($payConfig)) {
            $this->lang->set(2090);
        }
        $result = [];
        $j = 0;
        foreach ($payConfig as $k => $v){
            if($v['status'] != 'enabled' && (empty($pay_type) || !in_array($pay_type,['711_direct','grabpay','qr','UBPB','BPIA']))){
                continue;
            }
            //排除AutoTopup
            if($v['type'] == 'autotopup'){
                continue;
            }
            if(!in_array($v['type'], $use_pay_list)){
                continue;
            }
            if(!empty($pay_type) && in_array($pay_type,['711_direct','grabpay','qr','UBPB','BPIA'])){
                if($v['type'] != 'luckypay'){
                    continue;
                }
                if($pay_type == '711_direct'){
                    $v['name'] = '711';
                }
                if($pay_type == 'grabpay'){
                    $v['name'] = 'GRAB';
                }
                if($pay_type == 'qr'){
                    $v['name'] = 'QR';
                }
                if($pay_type == 'UBPB'){
                    $v['name'] = 'UBPB';
                }
                if($pay_type == 'BPIA'){
                    $v['name'] = 'BPIA';
                }
            }

            $result[$j]['id']           = $v['id'];
            $result[$j]['sort']         = $v['sort'];
            $result[$j]['pay_id']       = $v['pay_id'];
            $result[$j]['min_money']    = $v['min_money'];
            $result[$j]['max_money']    = $v['max_money'];
            $result[$j]['pay_scene']    = $v['type'];
            $result[$j]['comment']      = $v['comment'];
            $result[$j]['name']         = $v['name'];

            if ($v['show_type'] == 'code') {
                $result[$j]['pay_way']      = $this->lang->text("Payment by scanning code");
                $result[$j]['pay_way_str']  = 'code';
            } else {
                $result[$j]['pay_way']      = $this->lang->text("H5 payment");
                $result[$j]['pay_way_str']  = 'h5';
            }
            if ($v['link_data']) {
                $result[$j]['bank_list'] = 1;
                $result[$j]['bank_data'] = $pay->decodeOnlineBank($v['link_data']);
            } else {
                $result[$j]['bank_list'] = 0;
                $result[$j]['bank_data'] = [];
            }
            $result[$j]['pay_type'] = [];
            if(!empty($v['pay_type'])){
                $pay_type = json_decode($v['pay_type'], true);
                if(!empty($pay_type)){
                    $result[$j]['pay_type'] = $pay_type;
                }
            }

            $j++;
        }

        if ($result) {
            //sort($result);
            return $result;
        }else {
            return $this->lang->set(883);
        }
    }
};
