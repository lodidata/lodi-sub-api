<?php
use Logic\Admin\BaseController;
return new class() extends BaseController
{
    const TITLE = '渠道管理-下载页配置-删除配置';
    const DESCRIPTION = '删除配置';
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

        if (empty($id)) {
            return createRsponse($this->response, 200, -2, '编号不能为空');
        }
        $info = DB::table('channel_download')->where('id',$id)->first();
        if(empty($info)){
            return createRsponse($this->response, 200, -2, '编号有误');
        }
        if($info->channel_no == 'default'){
            return createRsponse($this->response, 200, -2, '默认下载地址不允许删除');
        }

        $res = DB::table('channel_download')->where('id',$id)->update(['is_delete'=>1]);
        if ($res) {
            return $this->lang->set(0, [], [], []);
        } else {
            return createRsponse($this->response, 200, -2, '删除失败');
        }

    }
};