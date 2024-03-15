<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取充值银行卡账户";
    const TAGS = "钱包";
    const QUERY = [
    ];
    const SCHEMAS = [
       "id"        => "int(required) #id",
       "name"      => "string() #名字",
       "card"      => "string() #银行卡号",
       "bank_name" => "string() #银行名称",
       "code"      => "string() #银行缩写",
       "bank_img"  => "string() #图片url",
    ];
    public function run() {

        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $pay = new \Logic\Recharge\Pay($this->ci);
        $res = $pay->getBankAccount();
        foreach ($res as &$v){
            $v['bank_name'] = $this->lang->text($v['code']);
            $v['qrcode'] = showImageUrl($v['qrcode']);
            $v['bank_img'] = showImageUrl($v['bank_img']);
        }
        unset($v);
        return $res;
    }
};