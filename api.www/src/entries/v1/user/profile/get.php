<?php
use Utils\Www\Action;
return new class extends Action
{
    const TOKEN = true;
    const TITLE = "个人详细资料";
    const TAGS = "个人中心";
    const SCHEMAS = [
        'true_name'     => "string() #真实姓名",
        'nickname'      => "string() #昵称",
        'avatar'        => "int() #头像id",
        'region_id'     => "int() #城市ID",
        'address'       => "string() #详细地址",
        'gender'        => "int(,1) #性别 (1:男,2:女,3:保密)",
        "qq"            => "string() #qq",
        "weixin"        => "string() #wechat",
        "skype"         => "string() #skype",
        "mobile"        => "string() #mobile",
        "email"         => "string() #email",
        'birth'         => "string() #出生日期 2018-08-23",
        'idcard'        => "string() #身份证号码",
        'updated'       => "string() #变更时间 2018-08-23 12:12:12",
        'user_name'     => "string(required) #用户名",
        'wallet_id'     => "int(required) #钱包ID",
        "wallet"        => "int() #余额",
        'ranting'       => "int(required) #用户等级",
        'tags'          => "string() #标签",
        'last_login'    => "int() #最后登录时间 2018-08-23 12:12:12",
        "level_name"    => "string() #用户等级名称",
        "level_icon"    => "string() #用户等级图标"
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $user = new \Logic\User\User($this->ci);
        if($this->auth->getTrialStatus()){
            return $user->getTrialInfo($this->auth->getUserId());
        }else{
            $data = $user->getInfo($this->auth->getUserId());
            $data['updated'] = date('Y-m-d H:i:s',$data['last_login']);

            //返佣总金额
            $totalBkge = DB::table("agent_bkge_log")
                           ->where('agent_id', $this->auth->getUserId())
                           ->where('user_id', '!=', $this->auth->getUserId())
                           ->sum('bkge_money');
            $data['total_bkge'] = intval($totalBkge) ?? 0;

            //直属人数
            $data['direct_member'] =  \DB::table('user_agent')->where('uid_agent', $this->auth->getUserId())->count() ?? 0;

            $data['max_profit_loss'] = 0;
            $res = DB::table("user_agent")->where("user_id", $this->auth->getUserId())->first(['profit_loss_value']);
            if ($res) {
                $t = json_decode($res->profit_loss_value, TRUE);
                $data['max_profit_loss'] = intval(max(array_values($t)));
            }

            $tmp = $data;
            $user_login = new Logic\User\User($this->ci);
            $tmp['id'] = $this->auth->getUserId();
            $tmp['name'] = $data['user_name'];
            $user_login->upgradeLevelMsg((array)$tmp);
            //兼容一下负数
//            if(isset($data['share_wallet']) && $data['share_wallet'] < 0){
//                $data['share_wallet'] = 0;
//            }
            return $this->lang->set(0,[],$data,['upgrade'=>$user_login->upgradeLevelWindows($this->auth->getUserId())]);
        }

    }
};