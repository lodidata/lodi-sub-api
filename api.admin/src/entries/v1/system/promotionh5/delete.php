<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '删除H5推广链接';
    const QUERY = [];
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run()
    {
        $data = $this->request->getParams();

        if (!isset($data['uid']) || empty($data['uid'])) {
            return $this->lang->set(-1);
        }
        if (!isset($data['link']) || empty($data['link'])) {
            return $this->lang->set(-1);
        }
        if (!isset($data['key']) || empty($data['key'])) {
            return $this->lang->set(-1);
        }

        $rs = (array)DB::table('user_agent_link_state')->where('uid',intval($data['uid']))->where('link', trim($data['link']))->first();
        if (empty($rs)) {
            $rs = DB::table('user_agent_link_state')->insertGetId([
                'uid' => intval($data['uid']),
                'link' => trim($data['link']),
                'link_module' => 'market',
                'link_key' => $data['key'],
                'is_del' => 1
            ]);
        } else {
            $rs = DB::table('user_agent_link_state')->where([
                ['uid', '=', intval($data['uid'])],
                ['link', '=', trim($data['link'])]
            ])->update(['is_del' => 1]);
        }

        if ($rs) {
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-1);
        }
    }
};