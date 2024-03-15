<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const STATE = '';
    const TITLE = '渠道统计访问次数查看';
    const DESCRIPTION = '渠道统计访问次数查看';

    const QUERY = [
        'channel_id' => 'int(required)   #渠道号',
        'type'       => 'int(required)    #查询类型 1总访问次数 2独立访问次数',
    ];

    const PARAMS = [];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $channel_id = $this->request->getParam('channel_id');
        if (empty($channel_id)) {
            return $this->lang->set(886, ['渠道号不能为空']);
        }

        $type = $this->request->getParam('type', '');
        if (!in_array($type, [1, 2])) {
            return $this->lang->set(886, ['查询类型错误']);
        }

        $num = 0;
        if ($type == 1) {
            // 总访问次数
            $click = \DB::connection('slave')
                ->table('user_channel_logs')
                ->selectRaw('count(log_ip) as click_num')
                ->where('channel_id', $channel_id)
                ->get()
                ->toArray();
            if (is_array($click)) {
                $num = $click[0]->click_num;
            }
        } else {
            // 独立访问次数
            $click = \DB::connection('slave')
                ->table('user_channel_logs')
                ->selectRaw('count(distinct log_ip) as distinct_click')
                ->where('channel_id', $channel_id)
                ->get()
                ->toArray();
            if (is_array($click)) {
                $num = $click[0]->distinct_click;
            }
        }

        return $this->lang->set(0, [], $num);
    }
};
