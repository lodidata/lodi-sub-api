<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
return new class extends Action
{
    const TITLE = "会员注册-校验手机号是否已注册";
    const TAGS = "登录注册";
    const QUERY = [
       "telphone"       => "string(required) #手机号码",
        'telphone_code' => 'string() #手机区号 默认为+86'
   ];
    const SCHEMAS = [
   ];


    public function run() {

        $phoneCode = $this->request->getQueryParam('telphone_code', '+86');
        $mobile = $this->request->getQueryParam('telphone');
        //手机号
        if(!empty($mobile)){
            $len=strlen($phoneCode);
            $phoneCode=substr($phoneCode,1,$len);
            if($phoneCode=='86'){
                if(!preg_match("/^1[3456789]{1}\d{9}$/",$mobile)){
                    return $this->lang->set(140);
                }

                if(strlen($mobile)>11){
                    return $this->lang->set(141);
                }
                if(strlen($mobile)<11){
                    return $this->lang->set(141);
                }
            }

            if(!preg_match("/^\d*$/",$mobile)){
                return $this->lang->set(140);
            }

            if(strlen($mobile)>15){
                return $this->lang->set(143);
            }
        }else{
            return $this->lang->set(13);
        }
        $mobile = Utils\Utils::RSAEncrypt($mobile);
        if(\Model\User::where('mobile', $mobile)->count() > 0){
            return $this->lang->set(104);
        }

        return [];
    }
};