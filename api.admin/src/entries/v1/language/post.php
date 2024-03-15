<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/29
 * Time: 11:42
 */

use Logic\Admin\BaseController;
use Respect\Validation\Validator as v;
use Logic\Admin\Log;

/**
 * 添加语言信息
 */
return new class extends BaseController
{
    const TITLE = "添加语言信息";

    const PARAMS = [
        'name'      => "string(required) #语言名称",
        'code'      => "string(required) #语言代码",
        'cur'       => "string(required) #货币代码",
        'sort'      => "int(required) #排序 从大到小",
        "status"    => "int(required, 1) #状态 1开启， 0停用",
    ];


    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run()
    {
        $data=$this->request->getParams();
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
        $langModel = new \Model\Language;
        $languageList = $langModel->getList();
        if(isset($languageList[$data['code']])){
            return $this->lang->set(10406, ['语言代码']);
        }

        //语言目录不存在
        if (!in_array($data['code'], $this->lang->getLangDir())) {
            return $this->lang->set(886, ['请先建立语言目录']);
        }
        $result = $langModel->updateLang($data);
        if($result !== false){
            $str = '名称：'.$data['name'].' 代码：'. $data['code']. ' 符号：' . $data['cur'];
            (new Log($this->ci))->create(null, null, Log::MODULE_USER, '语言列表', '语言列表', '新增语言', 1, "新增语言：{$str}");
            return $this->lang->set(0);
        }else{
            return $this->lang->set(-2);
        }
    }

};