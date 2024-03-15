<?php

use Model\ChannelDownload;
use Utils\Www\Action;

return new  class() extends Action {
    const TITLE = '下载地址';
    const DESCRIPTION = '获取下载地址信息';
    const QUERY = [
        'channel_no' => 'int(required) #渠道号',
    ];

    const PARAMS = [];
    const SCHEMAS = [];

    public function run()
    {
        $channelNo = $this->request->getParam('channel_no', '');

        $data = ChannelDownload::query()
            ->where('is_delete', ChannelDownload::DELETE_IS_NOT)
            ->when(isset($channelNo), function ($query) use ($channelNo) {
                if (!empty($channelNo)) {
                    $query->where('channel_no', $channelNo);
                } else {
                    $query->where('channel_no', ChannelDownload::CHANNEL_DOWNLOAD_DEFAULT);
                }
            })
            ->latest('id')
            ->first();

        if ($data) {
            $data->icon_url = showImageUrl($data->icon_url);
            $data->ios = showImageUrl($data->ios);
            $data->android = showImageUrl($data->android);
        }

        return $this->lang->set(0, [], $data, []);
    }
};