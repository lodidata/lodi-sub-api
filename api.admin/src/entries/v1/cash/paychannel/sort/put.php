<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use Utils\Utils;

return new class() extends BaseController
{

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {

        $params = $this->request->getParsedBodyParam('data');
        $type   = $this->request->getParam('type');

        if ($params) {

            if($type == 'channel'){
                $table='pay_channel';
                $fun_name = "支付渠道";
            }elseif($type == 'payment'){
                $table='payment_channel';
                $fun_name = "支付通道";
            }else{
                return $this->lang->set(-2);
            }
            $res=Utils::updateBatch($params,$table);

            if ($res!==false) {
                $str = $fun_name."上移/下移操作";
                (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '第三方支付', $fun_name, '编辑', 1, $str);
                return $this->lang->set(0);
            }
            return $this->lang->set(-2);
        } else {
            return $this->lang->set(10010);
        }
    }

};
