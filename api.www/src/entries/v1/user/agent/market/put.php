<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "更新代理退佣率(人人代理)";
    const TAGS = "代理返佣";
    const PARAMS = [
       "rake_back" => "string(required) #代理退佣率 JSON格式"
   ];
    const SCHEMAS = [
   ];

    
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $junior = $this->request->getParam('rake_back');
        $bkge_json = \Model\UserAgent::where('user_id', $this->auth->getUserId())->value('bkge_json');
        $bkge = json_decode($bkge_json,true);
        foreach ($bkge as $key => $val) {
            if(!isset($junior[$key])) {
                return $this->lang->set(550);
            }
            if($junior[$key] > $val) {
                return $this->lang->set(551);
            }
        }
        \Model\UserAgent::where('user_id', $this->auth->getUserId())->update(['junior_bkge' => json_encode($junior)]);
        return $this->lang->set(0);
    }
};