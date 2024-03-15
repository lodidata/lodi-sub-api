<?php

use \Utils\Www\Action;
use Logic\Set\SystemConfig;

return new class extends Action {

    const TITLE = "h5版本号";
    const DESCRIPTION = "";
    const TAGS = '';
    const QUERY = [

    ];

    const SCHEMAS = [

    ];

    public function run() {

        $h5_version = SystemConfig::getModuleSystemConfig('market')['h5_version']??'';

        return $this->lang->set(0,[],$h5_version);
    }

};