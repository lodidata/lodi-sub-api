<?php
use Utils\Www\Action;

return new class extends Action
{
    const TITLE = "会员注册-保存邀请码";
    const TAGS = "登录注册";
    const QUERY = [
        'plat'           => 'string(required) #平台-ios或者android',
        'system_version' => 'string(required) #系统版本',
        'phone_model'    => 'string() #手机型号，没有为空',
        'code'           => 'string(required) #邀请码',
   ];
    const SCHEMAS = [

   ];
    public function run() {
        $version = strtolower($this->request->getParam('system_version'));
        //$language = strtolower($this->request->getParam('language'));
       // $language = $this->language_id;
        $model = strtolower($this->request->getParam('phone_model'));
        $code = $this->request->getParam('code');
        $plat = strtolower($this->request->getParam('plat'));
        $ip = \Utils\Client::getIp();
        $arr = [
            'invite_code'=> $code,
            'plat'=> $plat,
            'system_version'=>$version,
            'ip'=>$ip,
           // 'language'=>$language,
            'model'=>$model,
        ];
        $id = \DB::table('invate_msg')
            ->where('plat',$plat)
            ->where('system_version',$version)
            ->where('ip',$ip)
            ->value('id');
        if(!$id) {
            \DB::table('invate_msg')->insert($arr);
        }
        if($plat == 'ios') {
            $this->redis->setex($plat.'_'.$version,10*60,$code);
        }else {
            $this->redis->setex($plat.'_'.$model,10*60,$code);
        }
        return $this->lang->set(0);
    }

};