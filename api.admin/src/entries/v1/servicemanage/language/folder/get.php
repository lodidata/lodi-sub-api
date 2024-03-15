<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/9/7
 * Time: 16:04
 */

use Logic\Admin\BaseController;

/*
 * 获取文件夹
 *
 * */
return new class extends BaseController
{

    const TITLE = 'GET 获取文件夹';
    const DESCRIPTION = '获取文件夹';
    
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


        $uid = $this->playLoad['uid'];

        $folderModel = new \Model\Admin\ServiceFolder();
        $data = $folderModel::where(['uid' => $uid])->with('ana')->get();
        $total = count($data);
        $attr = [
            'number' => 1,
            'size' => $total,
            'total' => $total,
        ];
        return $this->lang->set(0, [], $data, $attr);

    }
};