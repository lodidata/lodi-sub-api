<?php

use Logic\Admin\BaseController;
use Logic\Admin\Log;
use function Setting\Las\RSADecrypt;

return new class() extends BaseController {
    const TITLE = "修改收款银行帐号";
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
        "id" => "int() #修改银行账户时传递id",
    ];
    const SCHEMAS = [
        [
            "type" => "enum[rowset,map, row, dataset]",
            "data" => "rows[id:int,usage:string,bank_id:int,address:string,name:string,card:string,qrcode:string,limit_day_max:int,\n                limit_max:int,limit_once_min:int,limit_once_max:int,sort:int,state:set[online,default,enabled],comment:string] \n                #usage:使用层级; bank_id:银行/支付ID; address:开户行; name:户名; card:帐号; qrcode:二维码; limit_day_max:每日最大存款; \n                limit_max:总存款限制; limit_once_min:单笔最低; limit_once_max:单笔最高; sort:排序; state:集合(online:线上, default:默认, enabled:启用); \n                comment:备注",
        ],
    ];

    //前置方法
    protected $beforeActionList = [
        'verifyToken',
        'authorize',
    ];

    public function run($id = null) {
        global $playLoad;

        $param = $this->request->getParams();
        unset($param['s']);

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

        /*================================日志操作代码=================================*/

        $card = \Utils\Utils::RSADecrypt($param['card']);

        /*---操作日志代码---*/
        $data = DB::table('bank_account')->where('id', '=', $id)->get()->first();
        $data = (array)$data;

        $name_arr = [
            'card'           => '账号',
            'name'           => '户名',
            'limit_day_max'  => '日停用金额',
            'limit_max'      => '总停用金额',
            'limit_once_max' => '单笔最高金额',
            'limit_once_min' => '单笔最低金额',
            'sort'           => '排序',
            'state'          => '状态',
            'comment'        => '渠道备注',
            'qrcode'         => '收款二维码',
            'type'           => '类型',
        ];

        $status   = [
            'enabled' => '启用',
            'default' => '停用',
        ];
        $bank_arr = [
            '1' => '银行转账',
            '2' => '支付宝',
            '3' => '微信',
            '4' => 'QQ钱包',
            '5' => '京东支付',

        ];
        if($param['type'] == 1) {
            $name_arr['address'] = '开户行';
            $name_arr['bank_id'] = '银行/支付名称';
        }

        $str = "";
        foreach($data as $key => $item) {
            foreach($name_arr as $key2 => $item2) {
                if($key == $key2) {
                    if($item != $param[$key2]) {
                        if($key2 == 'limit_day_max' || $key2 == 'limit_max' || $key2 == 'limit_once_max' || $key2 == 'limit_once_min') {
                            $str .= "/" . $name_arr[$key2] . ":[" . ($item / 100) . "]更改为[" . ($param[$key2] / 100) . "]";
                        } else if($key2 == 'card') {
                            $str .= "/" . $name_arr[$key2] . ":[" . \Utils\Utils::RSADecrypt($item) . "]更改为[" . \Utils\Utils::RSADecrypt($param[$key2]) . "]";
                        } else if($key2 == 'state') {
                            $str .= "/" . $name_arr[$key2] . ":[" . $status[$item] . "]更改为[" . $status[$param[$key2]] . "]";
                        } else if($key2 == 'type') {
                            $str .= "/" . $name_arr[$key2] . ":[" . $bank_arr[$item] . "]更改为[" . $bank_arr[$param[$key2]] . "]";
                        } else if($key2 == 'bank_id') {
                            $bank_old_name = \DB::table('bank')->where('id', '=', $item)->value('name');
                            $bank_new_name = \DB::table('bank')->where('id', '=', $param['bank_id'])->value('name');
                            $str .= "/" . $name_arr[$key2] . ":[" . $bank_old_name . "]更改为[" . $bank_new_name . "]";
                        } else {
                            $str .= "/" . $name_arr[$key2] . ":[{$item}]更改为[{$param[$key2]}]";
                        }
                    }
                }
            }
        }

        $old_level=DB::table('level_bank_account')->where('bank_account_id',$id)->pluck('level_id')->toArray();
        if(isset($param['level']) && !empty($param['level'])){
            $old_level_str=implode(',',$old_level);
            $new_level_str=implode(',',$param['level']);
            if($old_level_str != $new_level_str){
                $str .="等级配置:[{$old_level_str}]更改为[{$new_level_str}]/";
            }
        }

        /*==================================================================================*/
        DB::beginTransaction();
        try {

            if(isset($param['level']) && !empty($param['level'])){
                if (is_array($param['level']) && count($param['level'])) {
                    $levelData = [];
                    foreach ($param['level'] ?? [] as $k => $v) {
                        $levelData[] = ['bank_account_id' => $id, 'level_id' => $v];
                    }
                    DB::table('level_bank_account')->where('bank_account_id', $id)->delete();
                    DB::table('level_bank_account')->insert($levelData);
                } else {
                    //必须先删除之前线上支付的数据
                    DB::table('level_bank_account')->where('bank_account_id', $id)->delete();
                }
            }
            unset($param['level']);
            \Model\BankAccount::where('id', '=', $id)->update($param);
            (new Log($this->ci))->create(null, null, Log::MODULE_CASH, '收款账号', '收款账号', '编辑', 1, "类型:{$bank_arr[$param['type']]}/户名：{$param['name']}/账号：$card/$str");
            DB::commit();
            return $this->lang->set(0);
        } catch(Exception $exception) {
            DB::rollback();
            $this->logger->error('修改收款账号失败' . $exception->getMessage());
            return $this->lang->set(-2);
        }
    }
};
