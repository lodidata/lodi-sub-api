<?php

use \Utils\Www\Action;
use Logic\Set\SystemConfig;

return new class extends Action {

    const TITLE = "能使用的域名";
    const DESCRIPTION = "";
    const TAGS = '';
    const QUERY = [

    ];

    const SCHEMAS = [

    ];

    public function run() {
        $list=[];
        $domain_list = SystemConfig::getModuleSystemConfig('domain')['api_url']??'';
        if($domain_list){
            $list_array = explode(',', $domain_list);
            if($list_array){
                foreach ($list_array as $k => $v){
                    $v && array_push($list,trim($v,'/'));
                }
            }

        }
        return $this->lang->set(0,[],$list);
    }

};