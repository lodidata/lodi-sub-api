<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\User\Agent;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "代理新增10个推广码";
    const DESCRIPTION = "";
    const TAGS = "";
    const PARAMS = [
   ];
    const SCHEMAS = [
   ];

    
    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $userId = $this->auth->getUserId();
        //加锁 防止重复点击
        $lock_key = "make_agent_invite_code:$userId";
        $lock   = $this->redis->get($lock_key);
        if($lock){
            return $this->lang->set(886,['please wait a moment!']);
        }

        $this->redis->setex($lock_key,60,1);

        $res = \DB::table('agent_code')
            ->where('agent_id',$userId)
            ->count();

        if($res){
            $this->redis->del($lock_key);
            return $this->lang->set(886,[$this->lang->text('Add up to 10')]);
        }

        $agent  = new Agent($this->ci);

        for($i = 0;$i < 10; $i++){
            $code = $agent->getInviteCode();
            $data = [
                'agent_id' => $userId,
                'code'     => $code,
            ];
            \DB::table('agent_code')->insert($data);
        }
        $this->redis->del($lock_key);
        return $this->lang->set(0,[]);
    }
};