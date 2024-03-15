<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/29
 * Time: 11:42
 */

use Logic\Admin\BaseController;
use Model\Language;
use Logic\Admin\Log;
/**
 * 删除语言信息
 */
return new class extends BaseController
{
    const TITLE = "删除语言信息";

    const PARAMS = [
        'id' => "int(required) #ID",
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id)
    {
        $this->checkID($id);
        $langModel = new Language();
        $info = DB::table('language')->find($id);
        $result = $langModel->updateLang('', $id);
        if($result !== false){
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '语言列表', '语言列表', '删除', 1, "语言名称:{$info->name}");
            return $this->lang->set(0);
        }else{
            return $this->lang->set(-2);
        }
    }

};