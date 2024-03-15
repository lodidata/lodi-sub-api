<?php

use Utils\Www\Action;

return new class() extends Action {
    const HIDDEN = true;
    const TITLE = "试玩游戏列表";
    const HINT = "为推广而免费进入玩";
    const DESCRIPTION = "";
    const TAGS = '游戏';
    const QUERY = [
        'type' => "string(required) #第三方游戏类型 如：AGIN"
    ];
    const HEADERS = [
        'HTTP_UUID' => "string(required) #uuid 唯一标识"
    ];
    const SCHEMAS = [
        "status" => 'int(required) #游戏状态 0正常',
        'url'    => 'string(required) #进入游戏链接'
    ];

    public function run() {
        // 头 pl平台(pc,h5,ios,android) mm 手机型号 av app版本 sv 系统版本  uuid 唯一标识
        $headers = $this->request->getHeaders();
        $type = strtoupper($this->request->getParam('type'));
        if (is_array($headers['HTTP_UUID'])) {
            $device = array_shift($headers['HTTP_UUID']);
        }
        if(!isset($device)){
            return ['status' => 886, 'message' => $this->lang->text('please enter the game channel from the normal way')];
        }
        $lockKey = \Logic\Define\CacheKey::$perfix['freePlayer'].$device.'_lock';
        $lock = $this->redis->setnx($lockKey,1);
        if(!$lock) {
            return ['status' => 886, 'message' => $this->lang->text('frequent requests, please try again later')];
        }
        try {
            $gameClass = \Logic\GameApi\GameApi::getApi($type);
        }catch (\Exception $e){
            $this->redis->del($lockKey);
            return ['status' => 886, 'message' => $this->lang->text('please contact the technician to deal with it').' 1'];
        }
        if(is_object($gameClass) && method_exists($gameClass,'getFreeJumpUrl')){
            $count = $this->redis->get(\Logic\Define\CacheKey::$perfix['freePlayer'].'LimitSend_'.$device) ?? 1;
            //金额是分
            $money = $count <= 3 ? 30000 : 0;
            $res = $gameClass->getFreeJumpUrl($device,$money);
            if($res['status'] == 0 && $money){
                $this->redis->setex(\Logic\Define\CacheKey::$perfix['freePlayer'].'LimitSend_'.$device, 86400, $count + 1);
            }
            $this->redis->del($lockKey);
            return $res;
        }
        $this->redis->del($lockKey);
        return ['status' => 886, 'message' => $this->lang->text('please contact the technician to deal with it') . ' 2'];
    }



};