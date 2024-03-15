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
 * 删除银行信息
 */
return new class extends BaseController
{
   protected $beforeActionList = [
       'verifyToken', 'authorize',
   ];

    public function run($id = null)
    {
        $id = $this->request->getParam('id');
        $result = DB::table('bank')->delete($id);
        return true;
    }

};