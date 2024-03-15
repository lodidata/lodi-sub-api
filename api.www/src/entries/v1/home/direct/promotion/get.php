<?php
/**
 * 获取用户推广使用的图片
 */

use Utils\Www\Action;
use Logic\Define\CacheKey;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取用户推广使用的图片";
    const DESCRIPTION = "";
    const TAGS = "";
    const QUERY = [
    ];
    const SCHEMAS = [
        []
    ];

    public $module = ['direct' => ''];

    public function run()
    {
        $param = $this->request->getQueryParam('login');
        $finish = $userId = 0;

        // 用户已完成注册弹窗 需判断登录状态
        if($param == "true") {
            $verify = $this->auth->verfiyToken();
            if (!$verify->allowNext()) {
                return $verify;
            }
            $userId = $this->auth->getUserId();
            $finish = $this->redis->get('inviteRigisterFinishWindow_' . $userId);
        }

        $data = [];
        $directImg = DB::table('direct_imgs')
                       ->whereIn('type', ['user_promotion','be_pushed','register_finish'])
                       ->where('status', 'enabled')
                       ->select(['name', 'desc', 'img', 'type'])
                       ->get()
                       ->toArray();
        foreach($directImg as &$v) {
            $data[$v->type] = $v;
            $data[$v->type]->img    = showImageUrl($data[$v->type]->img);
            if($v->type == 'register_finish') {
                $data[$v->type]->popup = intval($finish)==1 ? true : false;
            }
            // 用户已完成注册弹窗已经弹出，删除缓存
            if($param == "true" && intval($finish)==1) {
                $this->redis->del('inviteRigisterFinishWindow_' . $userId);
            }
        }

        return $data ?? [];
    }

};
