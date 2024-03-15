<?php

use Logic\Admin\BaseController;
use Logic\Lottery\Package;
use Model\ChannelDownload;

return new class() extends BaseController {
    const TITLE = '渠道管理-下载页配置-新建配置';
    const DESCRIPTION = '新建配置';
    const QUERY = [

    ];
    const PARAMS = [];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run()
    {
        $channelNo = $this->request->getParam('channel_no', '');
        $channelName = $this->request->getParam('channel_name', '');
        $H5_url = $this->request->getParam('H5_url', '');

        if (empty($channelNo) || empty($channelName)) {
            return createRsponse($this->response, 200, -2, '渠道号或渠道名称不能为空');
        }
        $isExist = ChannelDownload::query()
            ->where('channel_no', $channelNo)
            ->where('is_delete', ChannelDownload::DELETE_IS_NOT)
            ->exists();
        if ($isExist) {
            return createRsponse($this->response, 200, -2, '渠道号已存在');
        }

        $package = new Package($this->ci);
        $packageData = DB::table('app_package')
            ->where(['status' => 0, 'update_type' => 2])
            ->orderBy('created', 'DESC')
            ->pluck('bag_url', 'type')
            ->toArray();

        // 获取安卓包跟ios包地址
        $androidUrl = $packageData[1] ?? '';
        $iosUrl = $packageData[2] ?? '';

        if (empty($androidUrl) || empty($iosUrl)) {
            return $this->lang->set(-2);
        }

        $android = $package->packgeUpload(showImageUrl($androidUrl), $channelNo);
        $ios = $package->packgeUploadIOS(showImageUrl($iosUrl), $channelNo);
        if (!$android || !$ios) {
            return $this->lang->set(-2);
        }

        $input = [
            'channel_no' => $channelNo,
            'channel_name' => $channelName,
            'H5_url' => $H5_url,
            'android' => replaceImageUrl($android),
            'ios' => replaceImageUrl($ios),
            'bottom_text' => '',
            'is_delete' => 0,
            'update_time' => date("Y-m-d H:i:s"),
            'create_time' => date("Y-m-d H:i:s"),
        ];
        $res = ChannelDownload::query()->insertGetId($input);
        (new \Logic\Admin\Log($this->ci))->create(
            null,
            null,
            \Logic\Admin\Log::MODULE_USER,
            '渠道管理',
            '渠道包下载',
            '添加',
            $res ? 1 : 0,
            "渠道号：{$channelNo} 渠道名称：$channelName"
        );
        if ($res) {
            return $this->lang->set(0, [], [], []);
        } else {
            return createRsponse($this->response, 200, -2, '新增失败');
        }

    }
};