<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/29
 * Time: 11:42
 */

use Logic\Admin\BaseController;
use Model\Language;

/**
 * 修改语言状态
 */
return new class extends BaseController
{
    const TITLE = "修改语言状态";

    const PARAMS = [
        'id'        => "int(required) #ID",
        "status"    => "int(required, 1) #状态 1开启， 0停用"
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id)
    {
        $this->checkID($id);
        $status=$this->request->getParam('status');

        $langModel = new Language();
        $result = $langModel->updateLang(['status'=>$status], $id);
        if($result !== false){
            return $this->lang->set(0);
        }else{
            return $this->lang->set(-2);
        }
    }

};