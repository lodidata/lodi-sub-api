<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取直推活动未读消息";
    const TAGS = "直推活动";
    const QUERY = [
    ];
    const SCHEMAS = [
        [

        ]
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();

        $count = DB::table('direct_record')
                    ->where('sup_uid', $userId)
                    ->where('is_read', 0)
                    ->count();

        $return = ['num' => $count];

        return $this->lang->set(0, [], $return, []);
    }
};