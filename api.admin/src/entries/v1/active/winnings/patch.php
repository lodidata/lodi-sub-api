<?php
use Logic\Admin\BaseController;

return new class() extends BaseController {
    const TITLE = '批量赠送彩金活动';
    const DESCRIPTION = '启用和禁用活动';
    const QUERY = [
        'title' => 'string() #活动名称',
        'template_id' => 'integer() #模板ID',
        'status' => 'string() #enabled（启用），disabled（停用）',
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];
    public function run()
    {
        $params = $this->request->getParams();
        $active_id = $params['active_id'] ?? 0;
        $status = $params['status'] ?? '';

        if (empty($active_id) || empty($status)) {
            return createRsponse($this->response, 200, -2, '缺少必要参数');
        }

        $up = ['status' => $status];
        $res = DB::table("active")->where('id', $active_id)->update($up);
        if (!$res) {
            return createRsponse($this->response, 200, -2, '修改活动信息失败');
        }
        return $this->lang->set(0,[],$res,[]);
    }
};