<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 15:08
 */

use Logic\Admin\BaseController;

/*
 * 新增文件夹
 *
 * */
return new class extends BaseController
{

    const TITLE = 'POST 新增文件夹';
    const DESCRIPTION = '新增文件夹';
    
    const QUERY = [

    ];
    
    const PARAMS = [
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run()
    {
        $params = $this->request->getParams();

        if (empty($params['name'])) return $this->lang->set(10010);

        $params['uid'] = $this->playLoad['uid'];

        $folderModel = new \Model\Admin\ServiceFolder();
        $res = $folderModel::create($params);
        if ($res) {
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-2);
        }

    }
};