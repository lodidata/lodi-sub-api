<?php
use Utils\Www\Action;
use Model\Admin\ActiveBkge as ActiveBkgeModel;

return new class extends Action
{
    const TOKEN = true;
    const TITLE = "代理返佣说明";
    const DESCRIPTION = "";
    const TAGS = "代理返佣";
    const QUERY = [
    ];

    const SCHEMAS = [
        [
        ]
    ];


    public function run(){
        $verify = $this->auth->verfiyToken();
        if (!$verify->allowNext()) {
            return $verify;
        }

        $desc = \Model\Admin\ActiveBkge::first(['desc']);
        if(empty($desc)){
            return $this->lang->set(0);
        }
        return $this->lang->set(0, [], $desc->desc);
    }
};