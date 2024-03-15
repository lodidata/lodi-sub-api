<?php
use Utils\Www\Action;

return new class extends Action {
    const TITLE = "会员注册-获取邀请码";
    const TAGS = "登录注册";
    const QUERY = [
        'system_version' => 'string()#系统版本',
        'phone_model'    => 'string()#手机型号，没有为空',
   ];
    const SCHEMAS = [
           "code" => "string() #邀请码",
   ];

    public function run() {
        $version = strtolower($this->request->getParam('system_version'));
        //$language = strtolower($this->request->getParam('language'));
       // $language = $this->language_id;
        $model = strtolower($this->request->getParam('phone_model'));
        $ip = \Utils\Client::getIp();
        $code = $this->must($version,$ip);
        if($code)
            return $this->lang->set(0,[],['code'=>$code]);
        $code = $this->blur($model,$version);
        return $this->lang->set(0,[],['code'=>$code]);;
    }

    public function must(string $version,string $ip) {
        $plat = $this->auth->getCurrentPlatform();
        $code = \DB::table('invate_msg')
                ->where('plat',$plat)
                ->where('system_version',$version)
                ->where('ip',$ip)
                ->value('invite_code');
        return $code ? $code : null;
    }

    public function blur(string $model,string $version){
        $plat = $this->auth->getCurrentPlatform();
        //ios模糊匹配ip为主
        if($plat == 'ios') {
            $code = $this->redis->get($plat.'_'.$version);
            $this->redis->del($plat.'_'.$version);
        }else {
            $code = $this->redis->get($plat.'_'.$model);
            $this->redis->del($plat.'_'.$model);
        }
        return $code ? $code : null;
    }

};