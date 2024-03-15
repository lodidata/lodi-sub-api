<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 16:16
 */


use Logic\Admin\BaseController;

/*
 * 新增常用语语录
 *
 * */
return new class extends BaseController
{

    const TITLE = 'POST 新增常用语语录';
    const DESCRIPTION = '新增常用语语录';
    
    const QUERY = [

    ];
    
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {
        $params = $this->request->getParams();

        if (empty($params['content']) && empty($params['service_folder_id'])) return $this->lang->set(10010);

        //$params['uid'] = $this->playLoad['uid'];

        $anaModel = new \Model\Admin\ServiceAna();
        $res = $anaModel::create($params);
        if ($res) {
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-2);
        }

    }
};