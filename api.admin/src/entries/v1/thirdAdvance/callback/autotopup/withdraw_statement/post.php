<?php
/**
 * AutoTopup 代付 回调
 */
use Logic\Admin\BaseController;

return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $return = [];
        $params = $this->ci->request->getParams();
        $log       = json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        \Logic\Recharge\Recharge::addLogByTxt(['method' => 'withdraw_statement','third' => 'autotopup', 'date' => date('Y-m-d H:i:s'), 'json' => $log, 'response' => ''], 'transfer_log');
        if(!(isset($params['username']) || isset($params['bank_number']) || isset($params['before_credit']))){
            $return = [
                'status' => 'ERROR',
                'message' => 'Missing parameter',
            ];

        }else{
            $params['username'] = trim($params['username']);
            $user_id = \Model\User::where('name', $params['username'])->value('id');
            if(!$user_id){
                $return = [
                    'status' => 'ERROR',
                    'message' => 'user not found',
                ];
            }else{
                $trade_no = (array)\DB::table('transfer_order')
                    ->where('third_id', 9)
                    ->where('status', 'pending')
                    ->where('transfer_no', $params['id'])
                   // ->where('money', $params['amount']*100)
                    ->value('trade_no');
                if(!$trade_no){
                    $return = [
                        'status' => 'OK',
                        'message' => 'SUCCESS',
                    ];
                }else{
                    $params['trade_no'] = $trade_no;
                    $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
                    $return = $transfer->callbackResult($trade_no, $params);
                    if($return['code'] == 0){
                        $return = [
                            'status' => 'OK',
                            'message' => 'SUCCESS',
                        ];
                    }else{
                        $return = [
                            'status' => 'ERROR',
                            'message' => $return['msg'],
                        ];
                    }
                }
            }
        }

        return $this->response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($return);
    }
};
