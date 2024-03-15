<?php

use Logic\Admin\BaseController;
use lib\validate\admin\TopConfigValidate;

return new class() extends BaseController {


    const TITLE       = '删除';
    const DESCRIPTION = '第三方logo';
    const QUERY       = [];

    const PARAMS      = [
        'name'        => 'string #名称',
        'logo'        => 'string #Icon',
    ];


    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id) {


        $this->checkID($id);

        $result = DB::table('third_logo')->where('id', $id)
            ->delete();
        if (!$result) {
            return $this->lang->set(-2);
        }
        return $this->lang->set(0);

    }
};