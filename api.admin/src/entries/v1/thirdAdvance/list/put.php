<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const TITLE       = '修改第三方代付';
    const DESCRIPTION = '';
    const QUERY       = [
        'id' => 'int() #修改第三方代付（通过uri传参）'
    ];

    const PARAMS      = [
        'request_url'       => 'string #接口地址',
        'pay_callback_domain' => 'string #代付回调域名 默认为https://api-admin.XXX.com',
        'sort'              => 'int #排序',
        'status'            => 'int #是否开启，1 是，0 否',
        'name'              => 'string #代付（显示）名称',
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

        $param = $this->request->getParams();

        $updata = [];

        if (isset($param['status'])) {
            $updata['status'] = $param['status'] ? 'enabled' : 'default';
        }

        if (isset($param['sort'])) {
            $updata['sort'] = intval($param['sort']);
        }

        if (isset($param['name'])) {
            $updata['name'] = trim($param['name']);
        }

        if (isset($param['request_url'])) {
            $updata['request_url'] = trim($param['request_url']);
        }

        $domains = explode('.', $_SERVER['HTTP_HOST']);
        $adminDomin = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://api-admin.' . $domains[1].'.'.$domains[2];

        if(isset($param['pay_callback_domain']) && !empty($param['pay_callback_domain']) && $param['pay_callback_domain'] != $adminDomin){
            if(is_bool(strpos($param['pay_callback_domain'], 'http'))){
                return $this->lang->set(886, ['代付回调域名必须带http或者https']);
            }
            $updata['pay_callback_domain'] = trim(rtrim($param['pay_callback_domain'], '/'));
        }

        if(empty($updata)){
            return $this->lang->set(886, ['无更新数据']);
        }

        /*--------操作日志代码-------*/
        $transfer = \DB::table('transfer_config')->where('id', $id)->first();
        if (empty($transfer)) {
            return $this->lang->set(886, ['支付方式不存在']);
        }
        $rs = \DB::table('transfer_config')->where('id', $id)->update($updata);

        /*================================日志操作代码=================================*/
        $sta = $rs !== false ? 1 : 0;
        $sts = '';
        if (isset($updata['status']) && $updata['status'] != $transfer->status) {
            if ($updata['status'] == 0) {
                $new_sta = "停用";
                $old_sta = "启用";
            } else {
                $old_sta = "停用";
                $new_sta = "启用";
            }
            $sts .= "状态[{$old_sta}]更改为[$new_sta]/";
        }

        if (isset($param['sort']) && $param['sort'] != $transfer->sort) {
            $sts .= "排序：[" . ($transfer->sort) . "]更改为[" . ($param['sort']) . "]";
        }
        if (isset($param['name']) && $param['name'] != $transfer->name) {
            $sts .= "商户名称：[" . ($transfer->name) . "]更改为[" . ($param['name']) . "]";
        }

        if (isset($param['request_url']) && $param['request_url'] != $transfer->request_url) {
            $sts .= "API地址：[" . ($transfer->request_url) . "]更改为[" . ($param['request_url']) . "]";
        }

        if (isset($param['pay_callback_domain']) && $param['pay_callback_domain'] != $transfer->pay_callback_domain) {
            $sts .= "回调域名：[" . ($transfer->pay_callback_domain) . "]更改为[" . ($param['pay_callback_domain']) . "]";
        }
        (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方代付', '第三方代付', '编辑', $sta, $sts);
        /*==================================================================================*/
        return $this->lang->set(0);
    }

};
