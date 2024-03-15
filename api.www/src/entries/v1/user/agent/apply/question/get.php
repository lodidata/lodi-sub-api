<?php
use Utils\Www\Action;
use Logic\Set\SystemConfig;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取代理申请问题";
    const DESCRIPTION = "";
    const TAGS = "";
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    public function run() {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $global = SystemConfig::getModuleSystemConfig('agent');
        $agentApplyDesc = $global['agent_apply_desc'] ?? "";

        $data = DB::table('agent_apply_question')->orderBy('sort','ASC')->get()->toArray();
        $j = 1;
        foreach ($data as $value) {
            $option = !empty($value->option) ? json_decode($value->option) : [];
            if(!empty($option)) {
                $k = 1;
                foreach($option as $v){
                    $arr[$k] = $v;
                    $arr['id'] = $j;
                    $optionArr[] = (object)$arr;
                    $arr = [];
                    $k++;
                    $j++;
                }
                $value->option = $optionArr ?? null;
                $optionArr = [];
            } else {
                $value->option = null;
            }
            $j++;
        }

        $return = [
            'desc' => $agentApplyDesc,
            'question' => $data
        ];

        return $return;
    }
};