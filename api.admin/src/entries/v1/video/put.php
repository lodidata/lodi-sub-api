<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '修改视频推广信息';
    const QUERY       = [
        'id' => 'int(required) #编号',
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
//        (new noticeValidate())->paramsCheck('post', $this->request, $this->response);//参数校验

        $data = $this->request->getParams();

        $findInfo = (array)DB::table('agent_video_conf')->where('id',intval($data['id']))->first();
        if (empty($findInfo)) {
            return $this->lang->set(-2);
        }
        $up = DB::table('agent_video_conf')->where("id", intval($data['id']))->update([
            'title' => $data['title'],
            'link' => $data['link'],
            'location' => $data['location'],
            'status' => intval($data['status'])
        ]);
        if ($up) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};