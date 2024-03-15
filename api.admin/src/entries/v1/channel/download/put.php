<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '渠道管理-下载页配置-修改配置';
    const DESCRIPTION = '修改配置';
    const QUERY = [

    ];
    const PARAMS = [];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        $id = $this->request->getParam('id','');
//        $channel_no = $this->request->getParam('channel_no','');
//        $channel_name = $this->request->getParam('channel_name','');
//        $product_name = $this->request->getParam('product_name','');
        $download_url = $this->request->getParam('download_url','');
        $H5_url = $this->request->getParam('H5_url','');
//        $android = $this->request->getParam('android','');
        $super_label = $this->request->getParam('super_label','');
        $super_label_state = $this->request->getParam('super_label_state',1);
        $enterprise_label = $this->request->getParam('enterprise_label','');
        $enterprise_label_state = $this->request->getParam('enterprise_label_state',1);
        $TF_label = $this->request->getParam('TF_label','');
        $TF_label_state = $this->request->getParam('TF_label_state',1);
        $icon_url = $this->request->getParam('icon_url','');
        $bottom_text = $this->request->getParam('bottom_text','');

        if (empty($id)) {
            return createRsponse($this->response, 200, -2, '编号不能为空');
        }
//        if (empty($channel_no)) {
//            return createRsponse($this->response, 200, -2, '渠道号不能为空');
//        }
//        if (empty($channel_name)) {
//            return createRsponse($this->response, 200, -2, '渠道名不能为空');
//        }

        $input = [
//            'channel_no' => $channel_no,
//            'channel_name' => $channel_name,
//            'product_name' => $product_name,
            'download_url' => trim($download_url),
            'H5_url' => trim($H5_url),
//            'android' => trim($android),
            'super_label' => trim($super_label),
            'super_label_state' => $super_label_state,
            'enterprise_label' => trim($enterprise_label),
            'enterprise_label_state' => $enterprise_label_state,
            'TF_label' => trim($TF_label),
            'TF_label_state' => $TF_label_state,
            'icon_url' => replaceImageUrl($icon_url),
            'bottom_text' => $bottom_text,
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $res = DB::table('channel_download')->where('id',$id)->update($input);
        (new \Logic\Admin\Log($this->ci))->create(
            null,
            null,
            \Logic\Admin\Log::MODULE_USER,
            '渠道管理',
            '渠道包下载',
            '编辑',
            $res ? 1 : 0,
            "渠道编号：{$id}"
        );
        if ($res) {
            return $this->lang->set(0, [], [], []);
        } else {
            return createRsponse($this->response, 200, -2, '编辑失败');
        }

    }
};