<?php
use Utils\Www\Action;
use Respect\Validation\Validator as V;
use Logic\User\Agent;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取代理申请列表";
    const DESCRIPTION = "";
    const TAGS = "";
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];


    public function run($id) {
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }
        $userId = $this->auth->getUserId();
        $params = $this->request->getParams();

        $rs = \DB::table('agent_apply')->delete($id);

        return $this->lang->set(0,[],["delete_res"=>$rs]);

    }
};