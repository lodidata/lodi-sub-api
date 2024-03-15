<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/3/27
 * Time: 15:14
 */
use Logic\Admin\BaseController;

return new class() extends BaseController {

    const STATE       = 1;
    const TITLE       = '跳转模块列表';
    const DESCRIPTION = '跳转模块列表';
    
    const QUERY       = [];
    
    const PARAMS      = [];
    const SCHEMAS     = [
        [
            'name'        => "string(required) #跳转名称",
            "path"      => "string(required) #跳转路由",
        ]
    ];
    
    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];

    public function run() {
        $copywriter = $this->ci->get('settings')['website']['copywriter_jump'];
        $jump_model = [
            [
                'name' => '充值页面',
                'path' => $copywriter['recharge'],
            ],
            [
                'name' => '兑换页面',
                'path' => $copywriter['withdraw'],
            ],
        ];

        $attributes = [];
        return $this->lang->set(0,[],$jump_model,$attributes);
    }
};