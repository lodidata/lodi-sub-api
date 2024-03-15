<?php
use Logic\Admin\BaseController;
use lib\validate\BaseValidate;
use Logic\Admin\Log;
use Logic\Set\SystemConfig;
use Model\GameMenu;

return new class() extends BaseController
{
    const TITLE = '审核代理';
    const DESCRIPTION = '审核代理';

    const QUERY = [
    ];

    const SCHEMAS = [
    ];
//前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run($id)
    {
        $this->checkID($id);

        $params = $this->request->getParams();
        $remark = $params['remark'] ?? '';

        //检查数据是否存在
        $apply_info = \DB::table('agent_apply')->where('id',$id)->first();
        if(empty($apply_info)){
            return $this->lang->set(-2);
        }

        $update = [
            'remark'       => $remark,
        ];
        \DB::table('agent_apply')->where('id', $id)->update($update);

        return $this->lang->set(0);
    }
};
