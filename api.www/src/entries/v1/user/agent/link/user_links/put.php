<?php


use Logic\Set\SystemConfig;
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "启用/删除用户的某个推广链接";
    const TAGS = "";
    const SCHEMAS = [
    ];


    public function run()
    {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $uid = $this->auth->getUserId();
        
        $data = $this->request->getParams();

        if (!isset($data['link']) || empty($data['link'])) {
            return $this->lang->set(-1);
        }
        if (!isset($data['use_state'])) {
            return $this->lang->set(-1);
        }

        //先检测表中是否存在该条数据，有则更新状态，没有则添加
        $find = (array)DB::table('user_agent_link_state')->where('uid',$uid)->where('link',$data['link'])->first();
        if (empty($find)) {
            //获取链接?参数以前的部分
            $exp = explode('?', $data['link']);
            $tag = (array)DB::table('system_config')->where([
                ['module','=','market'],
                ['value', '=', $exp[0]]
            ])->first();
            if (empty($tag)) {
                return "系统配置中不存在该链接地址";
            }
            $insert_data = [
                'uid'=>$uid,
                'link'=>$data['link'],
                'link_module'=>'market',
                'link_id'=> $tag['id'],
                'use_state'=>$data['use_state'],
                'link_key'=> $tag['key']
            ];
            //特殊处理默认的h5_url连接欸，如果前端页面对h5_url链接点击添加，这里查询user_agent_link_state表没有对应数据，说明是第一次对该链接操作，需要对其is_proxy字段设置为允许代理
            if ($tag['key'] == "h5_url" && $data['use_state'] == 1) {
                $insert_data['is_proxy'] = 1;
            }
            $rs = DB::table('user_agent_link_state')->insertGetId($insert_data);
        } else {
            $rs = DB::table('user_agent_link_state')->where([
                ['uid', '=', $uid],
                ['link', '=', trim($data['link'])]
            ])->update(['use_state' => intval($data['use_state'])]);
        }

        if ($rs) {
            return $this->lang->set(0);
        }

        return "修改数据库失败";
    }
};