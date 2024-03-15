<?php
/**
 * nn88pay 代付 回调
 */
use Logic\Admin\BaseController;
use Logic\Transfer\ThirdParty\BASES;
use Logic\Recharge\Recharge;
return new class() extends BaseController {
    //前置方法
    protected $beforeActionList = [
//        'verifyToken', 'authorize',
    ];

    public function run() {
        $params    = $this->ci->request->getParams();
        BASES::addLogByTxt($params);

        $config    = Recharge::getThirdConfig('nn88pay');
        $key       = $config['key'];
        $body      = $params['body'];
        $data  = $this->des3Decrypt($body,$key);
        $param = json_decode($data, true);

        if($params['mcode'] != $config['partner_id']){
            throw new \Exception("Incorrect mcode: {$param['mcode']}");
        }

        $transfer = new  Logic\Transfer\ThirdTransfer($this->ci);
        try{
            $transfer->anotherCallbackResult($param['mer_order_no'], $param);
        }catch (\Throwable $e){
            $data = [
                'pay_type'  => 'nn88pay',
                'method'    => $this->ci->request->getMethod(),
                'content'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                'error'     => $e->getMessage()
            ];
            \DB::table('transfer_callback_failed')->insert($data);
            echo $e->getMessage();
            die;
        }
        echo '{"code":0}';
        die;
    }
    function des3Decrypt($str, $des_key="", $des_iv= '')
    {
        $des_iv = substr($des_key,0,8);
        $str = base64_decode($str);
        $res = openssl_decrypt(base64_decode($str), 'des-ede3-cbc', $des_key, OPENSSL_RAW_DATA, $des_iv);
        return $res;
    }
};
