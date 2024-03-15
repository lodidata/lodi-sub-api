<?php
use Utils\Www\Action;
/**
 * 返回包括可参加活动、支持的银行列表、最低、最高充值额度、提示等信息
 */
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "获取渠道信息";
    const TAGS = "获取渠道信息";
    const SCHEMAS = [];

    public function run()
    {
//        $verify = $this->auth->verfiyToken();
//        if (!$verify->allowNext()) {
//            return $verify;
//        }
//        $userId = $this->auth->getUserId();
        $channle_key = '';
        if(isset($this->ci->get('settings')['website']['channel_key'])){
            $channle_key = $this->ci->get('settings')['website']['channel_key'];
        }
        $number = $this->request->getParam('number', '');
        $apiKey = $this->request->getParam('apiKey', '');

        if(empty($channle_key) || $apiKey != $channle_key){
            return $this->lang->set(11);
        }

        //获取渠道信息
        $info = \DB::table('channel_management')
                        ->where('number', $number)
                        ->first();

        $data = [];
        if(!empty($info)){
            $data = [
                'number'  => $info->number,
                'name'    => $info->name,
                'remark'  => $info->remark,
                'updated' => $info->updated,
                'url'     => $info->url,
            ];
            //获取系统设置推广地址
            if(empty($data['url'])){
                $config = \DB::table('system_config')->where('key','h5_url')->first();
                $h5_url = '';
                if(!empty($config)){
                    $h5_url = $config->value;
                }
                $data['url'] = $h5_url;
            }
            $data['url'] .= '?channel_id='.$data['number'];
        }

        return $data;
    }
};
