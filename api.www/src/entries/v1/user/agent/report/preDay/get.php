<?php
use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "昨日数据-流水金额，占成，净盈利";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [

    ];
    const SCHEMAS = [
        [
            "bet_amount"             => "int(required) #流水金额",
            "proportion"              => "int(required) #占成",
            "bkge"             => "int(required) #净盈利",   //等于代理结算报表中的结算金额
        ]
    ];

    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();

        $res = ['profit'=>0, 'bet_amount'=>0, 'proportion'=>0];
        //查询用户昨日的 流水金额，占成
        $yesterday = date("Y-m-d", strtotime("-1 days"));
        $info = (array)DB::table("unlimited_agent_bkge")->whereRaw('user_id = :uid and date = :dt', ['uid'=>$uid, 'dt'=>$yesterday])->first(['bet_amount','proportion','bkge']);
        if (!empty($info)) {
            $res['bkge'] = $info['bkge'];
            $res['bet_amount'] = $info['bet_amount'];
            $res['proportion'] = $info['proportion'];
        }

        return $this->lang->set(0, [], $res);
    }
};