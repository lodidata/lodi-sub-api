<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '新增视频推广';
    const QUERY       = [
        'title' => 'string(required) #标题',
        'link' => 'string(required) #视频连接',
        'location' => 'string(required) #视频位置',
        'status' => 'int(required) #启用状态',
    ];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        $data = $this->request->getParams();

        $insertId = DB::table('agent_video_conf')->insertGetId([
            'title' => $data['title'],
            'link' => $data['link'],
            'location' => $data['location'],
            'status' => intval($data['status']),
            'create_time' => date("Y-m-d H:i:s")
        ]);
        if (!$insertId) {
            return $this->lang->set(-1);
        }
        return $this->lang->set(0);
    }
};