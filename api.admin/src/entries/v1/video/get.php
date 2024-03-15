<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE       = '获取视频推广列表';
    const QUERY       = [
        'page' => 'int(required) #当前页',
        'page_size' => 'int(required) #每页数量',
    ];
    const SCHEMAS     = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {
        $page = $this->request->getParam('page', 1);
        $page_size = $this->request->getParam('page_size', 10);

        $info = DB::connection('slave')->table('agent_video_conf')->paginate($page_size,['*'],'page',$page)->toJson();
        $deInfo = json_decode($info, true);

        $res = $deInfo['data'];
        $attr = [
            'total' => isset($deInfo['total']) ? $deInfo['total'] : 0,
            'size' => $page_size,
            'number' => isset($deInfo['last_page']) ? $deInfo['last_page'] : 0,
            'current_page' => isset($deInfo['current_page']) ? $deInfo['current_page'] : 0,    //当前页数
            'last_page' => isset($deInfo['last_page']) ? $deInfo['last_page'] : 0,   //最后一页数

        ];
        return $this->lang->set(0, [], $res, $attr);
    }
};