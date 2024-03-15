<?php

use Logic\Admin\Active as activeLogic;
//use Model\Admin\Active;
use Logic\Admin\BaseController;

return new class() extends BaseController
{
//    const STATE       = \API::DEPRECATED;
    const TITLE = '编辑活动类型';

    const QUERY = [
    ];
    const PARAMS = [
        "id" => "活动类型id",
        "name" => "活动类型名称",
        "status" => "活动类型状态",
    ];
    const SCHEMAS = [
    ];
    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run($id = null)
    {
        if ($id) {
            $params = $this->request->getParams();
            $validate = new \lib\validate\BaseValidate([
                "status"=>"require|in:enabled,disabled"
            ]);
            $validate->paramsCheck('', $this->request, $this->response);

            $res = \Model\ActiveType::updateByid($params['status'], $id);
        } else {
            $params = $this->request->getParsedBodyParam('data');
            $update = [];
            $insert = [];
            foreach ($params as $val) {
                $val['image'] = replaceImageUrl($val['image']);
                if(isset($val['id'])){
                    $update[] = $val;
                }elseif($val['name']){
                    $insert[] = $val;
                }
            }
            $update && \Model\ActiveType::updateByAll($update);
            $insert && \Model\ActiveType::insert($insert);
            $res = true;
        }

        if ($res !== false) {
            return $this->lang->set(0);
        }
        return $this->lang->set(-2);
    }
};
