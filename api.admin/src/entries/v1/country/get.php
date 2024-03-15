<?php

use Logic\Admin\BaseController;

return new class() extends BaseController
{
    const STATE = '';

    const TITLE = '城市信息';

    //前置方法
    protected $beforeActionList = [
        'verifyToken','authorize'
    ];
    public function run()
    {
        return \Model\Area::getArea();
    }
};
