<?php

use Utils\Www\Action;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "查看代理推广资料(人人代理)";
    const TAGS = "代理返佣";
    const SCHEMAS = [
        "marker_link" => [
            "code"          => "string(required) #推广码(ua_id)",
            'h5_url'        => "string() #H5推广地址",
            'pc_url'        => "string() #PC推广地址",
            'spread_url'    => "string() #推广页下载APP地址",
            "spread_desc"   => "string() #推广页描述",
            "weChat_url"    => "string() #wechat推广地址"
        ],
        "rake_back" => [
            [
                "key_desc"  => "string(required) #游戏名称",
                "key"       => "string(required) #游戏类型",
                "value"     => "int(required) #下级返佣比例 如10%返回10",
                "desc"      => "int(required) #返佣比例 如10%返回10",
            ]
        ],
    ];


    public function run() {
        $verify = $this->auth->verfiyToken();

        if (!$verify->allowNext()) {
            return $verify;
        }

        $uid = $this->auth->getUserId();
        $agent = new \Logic\User\Agent($this->ci);
        $res['marker_link'] = $agent->generalizeList($this->auth->getUserId());

        //获取代理后台地址
        $res['agent_admin_url'] = '';
        $agent_admin_url = \Model\Admin\SystemConfig::where('state','enabled')->where('key','agent_url')->first();
        if(!empty($agent_admin_url)){
            $res['agent_admin_url'] = $agent_admin_url->value;
        }

        $info = (array)\DB::table('user_agent')
                          ->where('user_id', $uid)
                          ->first();
        // 默认配置
        $default = $agent->rakeBackJuniorDefault();
        // 游戏
        $game = \Model\Admin\GameMenu::where('pid',0)->get()->toArray();
        $game = array_column($game,NULL,'type');
        $bkge = json_decode($info['bkge_json'],true);
        $junior = json_decode($info['junior_bkge'],true);
        foreach ($default as $key=>$val) {
            if(!isset($bkge[$key]) || !isset($junior[$key])) {   //说明该大类是后面添加的，
                $update = $agent->synchroUserBkge($uid,$info);
                $bkge = $update['bkge'];
                $junior = $update['junior'];
            }
            if(!isset($game[$key])) continue;
            $tmp['key_desc'] = $game[$key]['name'];
            $tmp['key'] = $key;
            $tmp['value'] = $junior[$key]??0;
            $desc         = $bkge[$key]??0;
            $tmp['desc'] = "% (0%-{$desc}%)";
            $res['rake_back'][] = $tmp;
        }
        return $res;
    }
};