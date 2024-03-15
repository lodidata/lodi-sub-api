<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use function Setting\Las\RSADecrypt;

return new class() extends BaseController {
    const TITLE = "新增收款银行帐号";
    const DESCRIPTION = "修改银行帐户";

    const PARAMS = [
        "usage"         => "string(required)    #使用层级",
        "name"          => "string #账户名",
        "card"          => "string(required) #帐号",
        "qrcode"        => "string()   #二维码，如果没有二维码传空(null)",
        "limit_day_max" => "int(required) #每日最大存款",
        "limit_max"     => "int(required) #总存款限额",
        "sort"          => "int # 排序",
        "is_enabled"    => "int #启用 1，停用 0",
        "comment"       => "string(required) #存款说明",
    ];
    const QUERY = [
    ];
    const SCHEMAS = [
        [
            "type" => "enum[rowset,map, row, dataset]",
            "data" => "rows[id:int,usage:string,bank_id:int,address:string,name:string,card:string,qrcode:string,limit_day_max:int,\n                limit_max:int,limit_once_min:int,limit_once_max:int,sort:int,state:set[online,default,enabled],comment:string] \n                #usage:使用层级; bank_id:银行/支付ID; address:开户行; name:户名; card:帐号; qrcode:二维码; limit_day_max:每日最大存款; \n                limit_max:总存款限制; limit_once_min:单笔最低; limit_once_max:单笔最高; sort:排序; state:集合(online:线上, default:默认, enabled:启用); \n                comment:备注",
        ],
    ];

    //前置方法
    protected $beforeActionList = [
       'verifyToken', 'authorize',
    ];

    public function run() {
        global $playLoad;

        $param = $this->request->getParams();

        $validate = new \lib\validate\BaseValidate([
            'state'         => 'require|in:enabled,default',
            //            'card' => 'alphaNum',
            'qrcode'        => 'url',
            'limit_day_max' => 'number',
            'limit_max'     => 'number',
        ]);

        $validate->paramsCheck('', $this->request, $this->response);
        $param['comment'] = isset($param['comment']) ? strip_tags(trim($param['comment'])) : '';
        $param['bank_id'] = $param['bank_id'] == null ? '' : $param['bank_id'];
        $param['qrcode'] = replaceImageUrl($param['qrcode']);
        if(isset($param['ifsccode']) && empty($param['ifsccode'])){
            unset($param['ifsccode']);
        }


        /*================================日志操作代码=================================*/
        $types = [
            '1' => '银行转账',
            '2' => '支付宝',
            '3' => '微信',
            '4' => 'QQ钱包',
            '5' => '京东支付',
        ];

        $card = \Utils\Utils::RSADecrypt($param['card']);
        /*==================================================================================*/


            $param['creater'] = $playLoad['uid'];
            $level = $param['level']??[];
            unset($param['level']);
        DB::beginTransaction();
        try {
            $bank_account_id=\Model\BankAccount::insertGetId($param);
            if(is_array($level) && $level){
                $levelData = [];
                foreach ($level ?? [] as $k => $v) {
                    $levelData[] = ['bank_account_id' => $bank_account_id, 'level_id' => $v];
                }

                DB::table('level_bank_account')->insert($levelData);
            }

            /*================================日志操作代码=================================*/
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '收款账号', '收款账号', '新增收款账号', 1, "类型:{$types[$param['type']]}/户名：{$param['name']}/账号： $card  ");
            /*==================================================================================*/
            DB::commit();
            return $this->lang->set(0);
        }catch(Exception $exception){
            DB::rollback();
            $this->logger->error('添加收款账号失败' . $exception->getMessage());
            return $this->lang->set(-2);
        }
    }
};
