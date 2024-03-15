<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 17:11
 */


use Logic\Admin\BaseController;

/*
 * 删除常用语语录
 *
 * */
return new class extends BaseController
{

    const TITLE = 'PUT 删除常用语语录';
    const DESCRIPTION = '删除常用语语录';
    
    const QUERY = [

    ];
    
    const PARAMS = [
    ];
    const SCHEMAS = [
    ];

    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];


    public function run($id)
    {

        //$params['uid'] = $this->playLoad['uid'];
        $anaModel = new \Model\Admin\ServiceAna();
        $res = $anaModel::findOrFail($id)->delete();
        if ($res) {
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-2);
        }

    }
};