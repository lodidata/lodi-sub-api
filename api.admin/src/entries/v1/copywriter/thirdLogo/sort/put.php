<?php

use Logic\Admin\BaseController;
use lib\validate\admin\TopConfigValidate;
use Utils\Utils;

return new class() extends BaseController {


    const TITLE       = '排序';
    const DESCRIPTION = '第三方logo';
    const QUERY       = [];

    const PARAMS      = [
    ];


    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run() {

        $params = $this->request->getParsedBodyParam('data');

        $table = 'third_logo';
        $res=Utils::updateBatch($params,$table);
        if ($res!==false) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);

    }
};