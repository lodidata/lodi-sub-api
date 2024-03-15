<?php

use Logic\Admin\BaseController;
use lib\validate\admin\TopConfigValidate;

return new class() extends BaseController {

    const TITLE       = '修改';
    const DESCRIPTION = '第三方logo';
    const QUERY       = [];

    const PARAMS      = [
        'name'      => 'string #名称',
        'logo'      => 'string #Icon',
    ];

    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id) {


        $params      = $this->request->getParams();
        $name        = $params['name'];
        $logo        = replaceImageUrl($params['logo']);

        $result = \DB::table('third_logo')
            ->where('id',$id)
            ->update(['name' => $name,'logo'=>$logo]);
        if (!$result) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);

    }
};