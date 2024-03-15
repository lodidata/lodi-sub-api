<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '更改第三方支付';
    const DESCRIPTION = '目前只支持更改状态';

    const QUERY = [
        'id' => 'int #第三方支付id'
    ];

    const PARAMS = [
        'status' => 'enum[1,0](required,1) #状态, 1:启用， 0:停用',
    ];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        $status = $this->request->getParam('status');
        $pay = new Logic\Recharge\Pay($this->ci);
        $payList = $pay->allPayConfig();
        $payConfig = [];
        foreach ($payList as $val) {
            if ($id == $val['id']) {
                $payConfig = $val;
                break;
            }
        }
        if (empty($payConfig)) {
            return $this->lang->set(886, ['支付方式不存在']);
        }
        /*================================日志操作代码=================================*/
        if ($status == 0) {
            $new_sta = "停用";
            $old_sta = "启用";
            $str_status = 'disabled';
        } else {
            $old_sta = "停用";
            $new_sta = "启用";
            $str_status = 'enabled';
        }
        $rs = \DB::table('pay_config')->where('id', $id)->update(['status' => $str_status]);
        if ($rs) {
            $this->redis->del(\Logic\Define\Cache3thGameKey::$perfix['payConfig']);
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方支付', '第三方支付', $new_sta, 1, "支付名称:{$payConfig['name']}/渠道名称:{$payConfig['name']}/支付类型:{$payConfig['type']}//状态[$old_sta]更改为[$new_sta]");
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-2);
        }
    }
};
