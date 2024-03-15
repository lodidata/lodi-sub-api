<?php

use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '编辑H5推广链接是否允许代理';
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

        //是否允许代理：1-允许代理，0-不允许代理
        if (!isset($data['is_proxy'])) {
            return $this->lang->set(-1);
        }
        if (!isset($data['uid']) || empty($data['uid'])) {
            return $this->lang->set(-1);
        }
        if (!isset($data['link']) || empty($data['link'])) {
            return $this->lang->set(-1);
        }
        if (!isset($data['key']) || empty($data['key'])) {
            return $this->lang->set(-1);
        }

        $rs = (array)DB::table('user_agent_link_state')->where('uid',intval($data['uid']))->where('link_key', trim($data['key']))->first();
        if (empty($rs)) {
            $rs_state = DB::table('user_agent_link_state')->insertGetId([
                'uid' => intval($data['uid']),
                'link' => trim($data['link']),
                'link_module' => 'market',
                'link_key' => $data['key'],
                'is_proxy' => intval($data['is_proxy'])
            ]);
        } else {
            $rs_state = DB::table('user_agent_link_state')->where([
                ['uid', '=', intval($data['uid'])],
                ['link', '=', trim($data['link'])]
            ])->update(['is_proxy' => intval($data['is_proxy'])]);
        }

        return ['修改结果'=>$rs_state];
    }
};