<?php


/**
 * 获取推广奖励弹窗
 */

use Utils\Www\Action;

return new class extends Action {
    const TOKEN = true;
    const TITLE = "获取推广奖励弹窗";
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
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = (int)$this->auth->getUserId();
        $pub    = $this->redis->get('inviteRigisterWindow_' . $userId);
        if (isset($pub) && $pub) {
            $direct_config['direct'] = \Logic\Set\SystemConfig::getModuleSystemConfig('direct');
            $amount         = $direct_config['direct']['cash_promotion_register']['send_amount'];
            $direct_img     = DB::table('direct_imgs')
                                ->where('type', 'index_promotion')
                                ->where('status','enabled')
                                ->select(['name', 'desc', 'img'])
                                ->get()
                                ->toArray();
            $data           = (array)$direct_img[0];
            $data['amount'] = $amount;
            $data['img']    = showImageUrl($data['img']);
            $this->redis->del('inviteRigisterWindow_' . $userId);
        }
        return $data ?? [];
    }

};
