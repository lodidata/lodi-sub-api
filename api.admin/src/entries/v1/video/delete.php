<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '删除视频推广';
    const QUERY       = [
        'id' => 'int(required) #编号',
    ];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        $data = $this->request->getParams();

        $dl = DB::table('agent_video_conf')->where('id', intval($data['id']))->delete();
        if ($dl) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};