<?php

use Utils\Www\Action;

return new class() extends Action {
    const HIDDEN = true;
    const TOKEN = true;
    const TAGS = '游戏';
    const TITLE = "免费进入游戏玩耍";
    const DESCRIPTION = "为推广而免费进入玩";
    const HEADERS = [
        'HTTP_UUID' => "string(required) #uuid 唯一标识"
    ];
    const PARAMS = [
        'action' => "string(required) #操作动作 share分享或download下载"
    ];
    const SCHEMAS = [];

    public function run() {
        // 头 pl平台(pc,h5,ios,android) mm 手机型号 av app版本 sv 系统版本  uuid 唯一标识
        $headers = $this->request->getHeaders();
        $action = strtoupper($this->request->getParam('action'));
        if (is_array($headers['HTTP_UUID'])) {
            $device = array_shift($headers['HTTP_UUID']);
        }
        if(!isset($device) || !in_array($action,['share','download'])){
            return ['status' => 886, 'message' => $this->lang->text('please enter the game channel from the normal way')];
        }
        $lockKey = \Logic\Define\CacheKey::$perfix['freePlayer'].$device.'_incr_lock';
        $lock = $this->redis->setnx($lockKey,1);
        if(!$lock) {
            return ['status' => 886, 'message' => $this->lang->text('frequent requests, please try again later')];
        }
        $d = date('Ymd');
        $count = $this->redis->get(\Logic\Define\CacheKey::$perfix['freePlayer'].'LimitSend_'.$device) ?? 1;
        $this->redis->setex(\Logic\Define\CacheKey::$perfix['freePlayer'].'LimitSend_'.$device, 86400, $count - 1);
        //记录操作次数
        $val = $this->redis->hGet(\Logic\Define\CacheKey::$perfix['freePlayer'].$d.'_'.$device, $action );
        $this->redis->hSet(\Logic\Define\CacheKey::$perfix['freePlayer'].$d.'_'.$device, $action ,$val+1);

        $this->redis->del($lockKey);
        return ['status' => 886, 'message' => $this->lang->text('please contact the technician to deal with it') . ' 2'];
    }



};