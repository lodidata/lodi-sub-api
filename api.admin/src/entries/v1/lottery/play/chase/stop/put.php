<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 2018/7/27
 * Time: 16:45
 */

use Logic\Admin\BaseController;
use lib\validate\admin\LotteryOrderValidate;
use Logic\Wallet\Wallet;
use Logic\Admin\Log;

return new class() extends BaseController {
    const TITLE = '停止追号';
    const DESCRIPTION = '';
    
    
    const SCHEMAS = [];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run() {

        (new LotteryOrderValidate())->paramsCheck('put', $this->request, $this->response);

        $chase_number = $this->request->getParam('chase_number');
        $chaseOrder = new \Logic\Lottery\ChaseOrder($this->ci);
        $user_id = \Model\LotteryChaseOrder::where('chase_number',$chase_number)->value('user_id');
        if($chaseOrder->cancel($chase_number,$user_id)){
            $this->lang->set(0);
        }
        $this->lang->set(-2);
    }
};

