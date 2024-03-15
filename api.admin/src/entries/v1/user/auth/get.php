<?php
use Logic\Admin\BaseController;
return new class extends BaseController {

    protected $beforeActionList = [
        'verifyToken',
        'authorize'
    ];

    public function run() {
        //return $this->auth->verfiyToken();
    }
};