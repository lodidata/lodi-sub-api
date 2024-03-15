<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\User\Agent;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取代理申请列表";
    const DESCRIPTION = "";
    const TAGS = "";
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];


    public function run() {
        $info = \DB::table('system_config')->where('module','system')->where('key','kefu_code')->first();
        $code = '';
        if(!empty($info)){
            $code = $info->value;
        }
        $url_info = \DB::table('system_config')->where('module','market')->where('key','service')->first();
        $url = '';
        if(!empty($url_info)){
            $url = $url_info->value;
        }
        if(empty($code) && empty($url)){
            return $this->lang->set(4115);
        }
        $data = [
            'kefu_code' => $code,
            'kefu_url'  => $url,
        ];

        return $this->lang->set(0,[],$data,[]);
    }
};