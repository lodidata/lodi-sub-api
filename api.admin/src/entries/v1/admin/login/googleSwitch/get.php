<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Utils\Www\Action;

return new class extends Action {

    const TITLE = "登录界面查询谷歌验证器开关";

    public function run() {
        $state = \DB::table('admin_user_google_manage')->where('admin_id',10000)->value('authorize_state') ?? 0;
        return $this->lang->set(0, [], ['authorize_state'=>$state]);
    }
};