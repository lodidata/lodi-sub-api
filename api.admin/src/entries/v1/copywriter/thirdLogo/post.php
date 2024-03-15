<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Logic\Admin\Advert as advertLogic;
use Logic\Admin\BaseController;
use lib\validate\admin\TopConfigValidate;
use Logic\Admin\Log;

return new class() extends BaseController {

    const TITLE       = '添加';
    const DESCRIPTION = '第三方logo';
    const QUERY       = [];

    const PARAMS      = [
        'name'        => 'string #名称',
        'logo'        => 'string #Icon',
    ];


    const SCHEMAS     = [
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {


        $params      = $this->request->getParams();
        $name        = $params['name'];
        $logo        = replaceImageUrl($params['logo']);
        $date = date('Y-m-d H:i:s');

        $result = \DB::table('third_logo')
            ->insert(['name' => $name,'logo'=>$logo, 'created' => $date]);
        if (!$result) {
            return $this->lang->set(-2);
        }

        return $this->lang->set(0);

    }
};