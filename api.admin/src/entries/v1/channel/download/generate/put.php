<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const TITLE = '单独渠道代理包生成';
    const DESCRIPTION = '单独渠道代理包生成';
    const PARAMS       = [];
    const SCHEMAS = [

    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id=null)
    {
        $id = $this->request->getParam('id');
        $this->checkID($id);
        $channelDownload = DB::table('channel_download')->where(['id' => $id, 'is_delete' => 0])->first();

        if (!$channelDownload) {
            return $this->lang->set(10015);
        }

        // 获取安卓包跟ios包地址
        $androidUrl = \DB::table('app_package')->where(['status' => 0, 'type' => 1, 'update_type' => 2])->orderBy('created','DESC')->value('bag_url');
        $iosUrl = \DB::table('app_package')->where(['status' => 0, 'type' => 2, 'update_type' => 2])->orderBy('created','DESC')->value('bag_url');

        if (empty($androidUrl) || empty($iosUrl)) {
            return $this->lang->set(11065);
        }

        $package = new \Logic\Lottery\Package($this->ci);
        $android = $package->packgeUpload(showImageUrl($androidUrl), $channelDownload->channel_no);
        $ios = $package->packgeUploadIOS(showImageUrl($iosUrl), $channelDownload->channel_no);
        if (!$android || !$ios) {
            return $this->lang->set(-2);
        }
        $update = array(
            'android' => replaceImageUrl($android),
            'ios' => replaceImageUrl($ios),
            'update_time' => date("Y-m-d H:i:s")
        );
        try {
            DB::table('channel_download')->where('id', $id)->update($update);
        } catch (\Exception $e) {
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);
    }

};
