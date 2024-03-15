<?php
use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "更新直推活动未读消息";
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

        $recordCount = DB::table('direct_record')
                         ->where(['sup_uid' => $userId, 'is_read' => 0])
                         ->count();
        if ($recordCount > 0) {
            DB::table('direct_record')
                ->where('sup_uid', $userId)
                ->where('is_read', 0)
                ->update(['is_read'=>1]);
        }

        return $this->lang->set(0);
    }
};