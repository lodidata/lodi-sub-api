<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\User\Agent;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "申请代理";
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
        $params = $this->request->getParams();

        //检查是否有在审核中
        $count = \DB::table('agent_apply')->where('user_id', $userId)->where('status', 0)->count();
        if($count > 0){
            return $this->lang->set(4004);
        }

        //当天最多只能申请3次
        $time = date('Y-m-d 00:00:00', time());
        $day_count = \DB::table('agent_apply')->where('user_id', $userId)->where('created', '>=', $time)->count();
        if($day_count >= 3){
            return $this->lang->set(4005);
        }

        //检查是否是代理
        $agent_switch = (array)\DB::table('user')->where('id', $userId)->first();
        if(empty($agent_switch) || $agent_switch['agent_switch'] == 1){
            return $this->lang->set(4008);
        }

        $question = $params['question'] ?? [];
        if(!empty($question)) {
            foreach ($question as $value) {
                if($value['required'] == 1 && empty($value['selected'])) {
                    return $this->lang->set(4015);
                }
            }
        }

        //检测申请用户是否有上级，上级是否为代理用户
        $isAgent = [];
        $agent_user_id = 0;
        $agent_user_name = '';
        $agentData = (array)\DB::table('user_agent')->where('user_id',$userId)->first();
        if ($agentData && !empty($agentData["uid_agent"])) {
            $isAgent = (array)\DB::table('user')->whereRaw('id=? and agent_switch=?', [$agentData["uid_agent"],1])->first();
            if(!empty($isAgent)){
                $agent_user_id = $isAgent['id'];
                $agent_user_name = $isAgent['name'];
            }
        }

        $create = [
            'user_id'        => $userId,
            'uid_agent'      => $agent_user_id,
            'uid_agent_name' => $agent_user_name,
            'status'         => 0,
        ];
        $applyId = \DB::table('agent_apply')->insertGetId($create);

        //记录用户当前提交的申请问题及回复
        $answer = [];
        foreach ($question as $key=>$value) {
            $answer[$key] = [
                'apply_id' => $applyId,
                'title' => $value['title'],
                'type' => $value['type'],
                'sort' => $value['sort'],
                'required' => $value['required'],
                'option' => in_array($value['type'], [1,2]) ? json_encode($value['option']) : ($value['option'] ?? ""),
                'selected' => in_array($value['type'], [1,2]) ? json_encode($value['selected']) : ($value['selected'] ?? "")
            ];
        }
        if(!empty($answer)) {
            \DB::table('agent_apply_submit')->insert($answer);
        }

        if (!empty($isAgent)) {
            //给上级发通知
//            $content = "你的伙伴".$agent_switch["name"]."向你发起了代理申请<br>请前往代理后台进行审核";
            $content = $this->lang->text(4114,[$agent_switch["name"]]);
            $exchange = 'user_message_send';
            \Utils\MQServer::send($exchange,[
                'user_id'   => $isAgent['id'],
                'user_name' => $isAgent['name'],
                'title'     => $this->lang->text(11024),
                'content'   => $content,
            ]);
        }

        return $this->lang->set(0,[]);
    }
};