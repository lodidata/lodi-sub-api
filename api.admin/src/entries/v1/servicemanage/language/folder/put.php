<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 17:13
 */


use Logic\Admin\BaseController;

/*
 * 编辑常用语语录
 *
 * */
return new class extends BaseController
{

    const TITLE = 'PUT 编辑常用语语录';
    const DESCRIPTION = '编辑常用语语录';

    const QUERY = [

    ];

    const PARAMS = [
    ];
    const SCHEMAS = [];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run($id)
    {
        $params = $this->request->getParams();

        if (empty($params['name'])) return $this->lang->set(10010);

        //$params['uid'] = $this->playLoad['uid'];

        $folderModel = new \Model\Admin\ServiceFolder();
        $data = $folderModel::findOrFail($id);
        $data->name = $params['name'];
        $res = $data->save();
        if ($res) {
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-2);
        }

    }
};