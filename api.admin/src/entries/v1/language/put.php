<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/29
 * Time: 11:42
 */
use Logic\Admin\BaseController;
use Model\Language;
use Respect\Validation\Validator as v;
use Logic\Admin\Log;

/**
 * 修改语言信息
 */
return new class extends BaseController
{

    const TITLE = "修改语言信息";

    const PARAMS = [
        'id'        => "int(required) #ID",
        'name'      => "string(required) #语言名称",
        'code'      => "string(required) #语言代码",
        'cur'       => "string(required) #货币代码",
        'sort'      => "int(required) #排序 从大到小",
        "status"    => "int(required, 1) #状态 1开启， 0停用",
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id)
    {
        $this->checkID($id);
        $data = $this->request->getParams();
        $validation = $this->validator->validate($this->request, [
            'name' => v::noWhitespace()
                ->notEmpty()
                ->setName('语言名称'),
            'code' => v::noWhitespace()
                ->notEmpty()
                ->setName('语言代码'),
            'cur' => v::noWhitespace()
                ->notEmpty()
                ->setName('货币代码'),
        ]);

        if (!$validation->isValid()) {
            return $validation;
        }
        $langModel = new Language();
        $info = [];
        $languageList = $langModel->getList();
        foreach ($languageList as $code => $lang){
            if($id == $lang['id']){
                $info = $lang;
                break;
            }
        }
        if(!$info){
            return $this->lang->set(10015);
        }
        if($info['code'] != $data['code'] ){
            if(isset($languageList[$data['code']])){
                return $this->lang->set(10406, ['语言代码']);
            }

            //语言目录不存在
            if (!in_array($data['code'], $this->lang->getLangDir())) {
                return $this->lang->set(886, ['请先建立语言目录']);
            }
        }
        $result = $langModel->updateLang($data, $id);
        /*============================日志操作代码================================*/
        $str="";
        if($info['code']!=$data['code']){
            $str.="[{$info->code}]更改为[{$data['code']}]";
        }
        if($info['name']!=$data['name']){
            $str.="[{$info['name']}]更改为[{$data['name']}]";
        }
        if($info['cur']!=$data['cur']){
            $str.="[{$info['cur']}]更改为[{$data['cur']}]";
        }
        $sta = $result !== false ? 1 : 0;
        (new Log($this->ci))->create(null, null, Log::MODULE_USER, '语言列表', '语言列表', '编辑', $sta, $str);
        /*============================================================*/
        if($result !== false){
            return $this->lang->set(0);
        }else{
            return $this->lang->set(-2);
        }
    }

};