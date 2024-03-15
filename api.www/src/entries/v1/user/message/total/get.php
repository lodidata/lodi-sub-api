<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "未读消息数";
    const DESCRIPTION = "";
    const TAGS = "会员消息";
    const QUERY = [

    ];
    const SCHEMAS = [
            "total" => "int() #未读消息数",
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        if($this->auth->getTrialStatus()) {
            return $this->lang->set(0, [],['total' => 0]);
        }

        $total = DB::table('message_pub')
            ->where('status', 0)
            ->where('message_pub.uid', $this->auth->getUserId())
            ->count();
        return $this->lang->set(0, [], ['total' => $total]);
    }
};
