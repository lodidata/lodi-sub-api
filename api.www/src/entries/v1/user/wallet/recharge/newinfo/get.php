<?php
use Utils\Www\Action;
use Logic\User\Safety;
/**
 *
 */
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "向appsflyer发送请求";
    const TAGS = "appsflyer事件统计";
    const SCHEMAS = [];

    public function run()
    {
        //接口废弃
        return $this->lang->set(0,[]);
        /*//前端传这3个参数过来 app_id , eventName ,devKey
        //eventName 事件有3个 首充 first_deposit 充值 deposit 注册 register  前端只传 deposit 和 register
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        $params = [
            'app_id'        => $this->request->getParam('app_id'),
            'eventName'     => $this->request->getParam('eventName'),
            'devKey'        => $this->request->getParam('devKey'),
        ];
        foreach ($params as $k=>$v){
            if(empty($v)) return $this->lang->set(886,["{$k} 不能为空"]);
        }
        $params['appsflyer_id'] = $userId;
        try{
            $res = (new Safety($this->ci))->sendAppsflyer($userId, $params);
            (new Safety($this->ci))->statisticsRegDep($params);
        }catch (\Throwable $e){
            return $this->lang->set(886,[$e->getMessage()]);
        }

        return $this->lang->set(0,[$res]);*/
    }
};
