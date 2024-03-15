<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/6/29
 * Time: 11:42
 */

use Lib\Validate\Admin\BankValidate;
use Lib\Validate\Admin\CustomerValidate;
use Logic\Admin\BaseController;

/**
 * 添加银行信息
 */
return new class extends BaseController
{
   protected $beforeActionList = [
       'verifyToken', 'authorize',
   ];

    public function run()
    {
        $data=$this->request->getParam('data');
        $result = DB::table('bank')->insertGetId($data);
        return true;
    }

};