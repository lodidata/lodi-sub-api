<?php

use Logic\Admin\Spread as SpreadLogic;
use Logic\Admin\BaseController;

return new class() extends BaseController {

    const STATE = 1;

    const TITLE = '推广引导图片列表';

    const DESCRIPTION = '用户推广图片列表（后台）';

    

    const QUERY = [
        'id'        => 'int() #id',
        'page'      => 'int #第几页',
        'page_size' => 'int #每页多少条',
    ];



    const PARAMS = [];

    const SCHEMAS = [
            [
                "id"      => 'id',
                "name"    => '标题',
                "sort"    => '排序',
                "status"  => '状态',
                "picture" => '图片地址',
                "created" => '创建时间',
                "updated" => '更新时间'
            ]
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize',
    ];

    public function run($id = null) {
        if (is_numeric($id)) {
            return $this->detail($id);
        }

        $params = $this->request->getParams();

        $data = [
            'page'      => $params['page'],
            'page_size' => $params['page_size'],
        ];

        $loginSpread = new SpreadLogic($this->ci);

        return $loginSpread->getList($data);
    }

    protected function detail($id) {
        $loginSpread = new SpreadLogic($this->ci);

        return $loginSpread->getOne($id);
    }
};