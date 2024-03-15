<?php
use Logic\Admin\BaseController;
use Logic\Set\SystemConfig;

return new class() extends BaseController
{
    const TITLE = '新增代理申请模板';
    const DESCRIPTION = '新增代理申请模板';

    const QUERY = [
    ];

    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run()
    {
        $params = $this->request->getParams();

        //申请页描述
        $agentApplyDesc = SystemConfig::getModuleSystemConfig('agent');
        $tmp = ['agent' => $agentApplyDesc];

        if (isset($params['desc'])) {
            $data['agent']['agent_apply_desc'] = $params['desc'];
        }

        $confg = new SystemConfig($this->ci);
        $confg->updateSystemConfig($data, $tmp);

        //判断排序值不能存在相同数值
        $sortArr = array_column($params['question'], 'sort');
        $sort = array_count_values($sortArr);
        foreach($sort as $value) {
            if($value != 1) {
                return $this->lang->set(11064);
            }
        }

        //更新申请页问题
        DB::table('agent_apply_question')->delete();
        $apply = [];
        foreach ($params['question'] as $k=>$v) {
            $apply[$k]['option'] = !empty($v['option']) ? json_encode($v['option']) : "";
            $apply[$k]['required'] = $v['required'] ?? 1;
            $apply[$k]['title'] = $v['title'] ?? "";
            $apply[$k]['type'] = $v['type'] ?? 1;
            $apply[$k]['sort'] = $v['sort'] ?? 1;
            $apply[$k]['created'] = $v['created'] ?? date("Y-m-d H:i:s", time());
        }

        DB::table('agent_apply_question')->insert($apply);

        return $this->lang->set(0);
    }
};
