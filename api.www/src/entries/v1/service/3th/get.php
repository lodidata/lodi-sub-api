<?php
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "pc第三方客服代码";
    const DESCRIPTION = "pc第三方客服代码";
    const TAGS = '客服';
    const SCHEMAS = [
       "code" => "string() #第三方客服代码"
   ];

    public function run() {
        return (new \Logic\Set\SystemConfig($this->ci))->getService();
    }
};