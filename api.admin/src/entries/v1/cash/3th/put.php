<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE       = '(厅主)添加第三方支付';
    const DESCRIPTION = '';
    const QUERY       = [
        'id' => 'int() #修改厅主设置的第三方支付（通过uri传参）'
    ];

    const PARAMS      = [
        //  'pay_id'         => 'int(required) #支付接口ID/支付渠道，见另一接口',
        //   'name'           => 'string(required) #商户名称',
        // 'app_id'         => 'string(required) #商户编号/应用ID，第三方支付商提供的id',
        // 'app_secret'     => 'string() #应用密钥',
        // 'pay_scene'      => 'enum[wx,alipay,unionpay,qq,tz](required) #使用场景，wx 微信, alipay 支付宝, unionpay 银联, qq qq扫描支付, tz 出款下发',
        'levels'         => 'string() #会员等级，多个level id通过逗号分隔',
        //  'terminal'       => 'string() #终端号，由第三方支付商提供',
        'comment'     => 'string #备注',
        'url_notify'     => 'string #累计停用金额',
        'url_return'     => 'string #累计停用金额',
        'money_stop'     => 'int #累计停用金额',
        'money_day_stop' => 'int #日停用金额',
        'sort'           => 'int #排序',
        'status'         => 'int #是否开启，1 是，0 否'
    ];
    const SCHEMAS     = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id = '')
    {
        $this->checkID($id);
//        $pay_channel =  Logic\Recharge\Recharge::requestPaySit('postPayMsg',['id'=>$id]);
//        if(!$pay_channel)
//            return $this->lang->set(10046);

        $param = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'min_money' => 'number',
            'max_money' => 'number',
            'money_day_stop' => 'number',
            'money_stop' => 'number',
//            'levels' => 'string',
            'status' => 'require',
            'ip' => 'require',
            'payurl' => 'require',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);
        if ($param['status']) {
            $status = 'enabled';
        } else {
            $status = 'disabled';
        }

        $domains = explode('.', $_SERVER['HTTP_HOST']);
        $wwwdomin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://api-www.' . $domains[1].'.'.$domains[2];

        $pay_callback_domain = '';
        if(!empty($param['pay_callback_domain']) && $param['pay_callback_domain'] != $wwwdomin){
            if(is_bool(strpos($param['pay_callback_domain'], 'http'))){
                return $this->lang->set(886, ['支付回调域名必须带http或者https']);
            }
            $pay_callback_domain = trim(rtrim($param['pay_callback_domain'], '/'));
        }

        $updata = [
            'min_money' => $param['min_money'],
            'max_money' => $param['max_money'],
            'money_day_stop' => $param['money_day_stop'],
            'money_stop' => $param['money_stop'],
            'comment'   => $param['comment'],
            'sort'   => $param['sort'],
            'status' => $status,
            'name' => $param['name'],
            'ip' => $param['ip'],
            'payurl' => $param['payurl'],
            'pay_callback_domain' => $pay_callback_domain,
        ];

        //'name' => $param['name'],
        //$param['id'] = $id;
        // $param['active_rule'] = is_array($param['active_rule']) ? json_encode($param['active_rule']) : '';
        /*--------操作日志代码-------*/
        $pay = new Logic\Recharge\Pay($this->ci);
        $payList =  $pay->payConfig();
        $payConfig = [];
        foreach($payList as $val){

            if($id == $val['id']){
                $payConfig = $val;
                break;
            }
        }
        if (empty($payConfig)) {
            return $this->lang->set(886, ['支付方式不存在']);
        }
        $rs = \DB::table('pay_config')->where('id', $id)->update($updata);
        if ($rs) {
            $this->redis->del(\Logic\Define\Cache3thGameKey::$perfix['payConfig']);
        }
        /*================================日志操作代码=================================*/
        $sta = $rs !== false ? 1 : 0;
        $sts = '';
        if ($status != $payConfig['status']) {
            if ($status == 0) {
                $new_sta = "停用";
                $old_sta = "启用";
            } else {
                $old_sta = "停用";
                $new_sta = "启用";
            }
            $sts .= "状态[{$old_sta}]更改为[$new_sta]/";
        }
        if ($param['max_money'] != $payConfig['max_money']) {
            $sts .= "单笔最大金额[" . ($payConfig['max_money'] / 100) . "]更改为[{" . ($param['max_money'] / 100) . "}]/";
        }
        if ($param['min_money'] != $payConfig['min_money']) {
            $sts .= "单笔最小金额[" . ($payConfig['min_money'] / 100) . "]更改为[" . ($param['min_money'] / 100) . "]/";
        }
        if ($param['money_day_stop'] != $payConfig['money_day_stop']) {
            $sts .= "日停用金额[" . ($payConfig['money_day_stop'] / 100) . "]更改为[" . ($param['money_day_stop'] / 100) . "]/";
        }
        if ($param['money_stop'] != $payConfig['money_stop']) {
            $sts .= "累计停用金额[" . ($payConfig['money_stop'] / 100) . "]更改为[" . ($param['money_stop'] / 100) . "]/";
        }
        if ($param['comment'] != $payConfig['comment']) {
            $sts .= "备注信息[" . ($payConfig['comment']) . "]更改为[" . ($param['comment']) . "]";
        }
        if ($param['sort'] != $payConfig['sort']) {
            $sts .= "排序：[" . ($payConfig['sort']) . "]更改为[" . ($param['sort']) . "]";
        }
        if ($param['name'] != $payConfig['name']) {
            $sts .= "商户名称：[" . ($payConfig['name']) . "]更改为[" . ($param['name']) . "]";
        }
        if ($param['ip'] != $payConfig['ip']) {
            $sts .= "IP：[" . ($payConfig['ip']) . "]更改为[" . ($param['ip']) . "]";
        }
        if ($param['payurl'] != $payConfig['payurl']) {
            $sts .= "API地址：[" . ($payConfig['payurl']) . "]更改为[" . ($param['payurl']) . "]";
        }

        if ($param['pay_callback_domain'] != $payConfig['pay_callback_domain']) {
            $sts .= "回调域名：[" . ($payConfig['pay_callback_domain']) . "]更改为[" . ($param['pay_callback_domain']) . "]";
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方支付', '第三方支付', '编辑', $sta, "商户名称:{$payConfig['name']}/商户编号：{$payConfig['partner_id']}/渠道名称:{$payConfig['name']}/支付类型:{$payConfig['type']}/$sts");
        /*==================================================================================*/
        return $this->lang->set(0);
    }

};
