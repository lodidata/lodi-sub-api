<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;

return new class() extends BaseController
{
    const STATE = '';
    const TITLE = '线下入款处理，通过或拒绝';
    const DESCRIPTION = '如果拒绝，不能发放优惠';

    const QUERY = [
        'id' => 'int #id'
    ];
    
    const PARAMS = [
        'state' => 'string(required) # paid 通过，failed 拒绝',
        'coupon' => 'int(required) #是否发放优惠，1 是，0 否',
        'comment' => 'string() #备注'
    ];
    const SCHEMAS = [];
    //前置方法
    protected $beforeActionList = [
        'verifyToken', 'authorize'
    ];

    public function run($id = null)
    {
        $this->checkID($id);
        $params = $this->request->getParams();
        $validate = new \lib\validate\BaseValidate([
            'coupon' => 'require|in:0,1',
            'state' => 'require|in:paid,failed',
        ]);
        $validate->paramsCheck('', $this->request, $this->response);

        $paid = $params['state'] == 'paid' ? 1 : 2;
        $coupon = $params['coupon'];
        $argc = [
            'memo'          => $params['comment'] ?? null,
            'send_memo'     => !empty($params['comment']),
            'send_coupon'   => $coupon, //1
            'process_uid'   => $this->playLoad['uid'],
            'status'        => $paid    //1
        ];

        try{
            $re = (new \Logic\Recharge\Recharge($this->ci))->updateOffline(intval($id), $argc);
        }catch (\Exception $e){
            var_dump($e->getMessage());die;
        }

        /*================================日志操作代码=================================*/
        $data = DB::table('funds_deposit')
            ->select('trade_no','user_id')
            ->where('id', '=', $id)
            ->get()
            ->first();
        $data = (array)$data;

        $user_info=DB::table('user')
            ->find($data['user_id']);

        $sta = $re !== false ? 1 : 0;
        try{
            if ($params['coupon'] == 0&& $params['state']=='paid') {
                (new Log($this->ci))->create($data['user_id'],  $user_info->name, Log::MODULE_CASH, '线下转账', '线下转账', '通过（拒绝优惠）', $sta, "订单号：{$data['trade_no']}");
            }else  {
                $sta_name = [
                    'paid' => '通过',
                    'failed' => '拒绝',
                ];
                (new Log($this->ci))->create($data['user_id'],  $user_info->name, Log::MODULE_CASH, '线下转账', '线下转账', $sta_name[$params['state']], $sta, "订单号：{$data['trade_no']}");
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());die;
        }


        /*==================================================================================*/
        if ($re) {
            return $this->lang->set(0);
        } else {
            return $this->lang->set(-2, [], [], ['res' => $re]);
        }
    }
};
