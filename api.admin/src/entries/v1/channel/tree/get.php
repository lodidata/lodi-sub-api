<?php

use Logic\Admin\BaseController;
use Model\ChannelDownload;

return new class() extends BaseController {
    const TITLE = '获取渠道号列表';
    const DESCRIPTION = '获取渠道号列表';


    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run()
    {
        $data = ChannelDownload::query()
            ->where('is_delete', ChannelDownload::DELETE_IS_NOT)
            ->select([
                'id', 'channel_no', 'channel_name'
            ])
            ->latest('id')
            ->get();
        return $this->lang->set(0, [], $data, []);
    }


};
